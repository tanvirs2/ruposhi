<?php

namespace App\Services;

use App\Jobs\SendSmsJob;
use App\Models\SmsLog;
use App\Models\StoreConfig;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * SMS পাঠানো (BulkSMSBD)।
 *
 * দুই পথ, একটা ব্যর্থ হলে অন্যটা (sendLater দেখুন):
 *  ১. Queue Job (SendSmsJob) — worker চালু থাকলে; নেটওয়ার্ক ব্যর্থ হলে আবার চেষ্টা করে
 *  ২. defer() — worker বন্ধ/dispatch ব্যর্থ হলে; রেসপন্সের পরে একই প্রসেসে
 *  + সেফটি নেট: app:sms-sweep (প্রতি মিনিটে) — Job queue-তে আটকে থাকলে সরাসরি পাঠায়
 *
 * ⚠️ একই SMS দুবার না যাওয়ার গ্যারান্টি: deliver() আগে sms_logs-এ
 * pending → sending "দাবি" করে (একটা atomic UPDATE); যে পথ দাবি পায় না সে কিছুই পাঠায় না।
 */
class SmsService
{
    /** worker প্রতি ~৩০ সেকেন্ডে এই কী-তে সময় লেখে (AppServiceProvider-এ Queue::looping) */
    public const WORKER_HEARTBEAT_KEY  = 'sms_queue_worker_heartbeat';
    /** এর চেয়ে পুরনো heartbeat = worker বন্ধ ধরে নেওয়া হয় */
    public const WORKER_FRESH_SECONDS  = 90;

    private string $apiKey;
    private string $senderId;
    private string $apiUrl = 'https://bulksmsbd.net/api/smsapi';

    /**
     * @param int|null $shopId  শপ জানা থাকলে সেই শপের API key/sender ID পড়া হয়।
     *
     * ⚠️ Queue worker আর scheduler-এ কেউ লগইন থাকে না, তাই ShopScope কিছু
     * ফিল্টার করে না — StoreConfig::get() তখন **সব শপের প্রথম সারি** দিত, মানে
     * অন্য দোকানের key/sender দিয়ে SMS যেত। তাই worker/sweeper সবসময় shopId দেয়।
     */
    public function __construct(?int $shopId = null)
    {
        $get = $shopId
            ? fn (string $k, $d) => StoreConfig::withoutGlobalScopes()->where('shop_id', $shopId)->where('key', $k)->value('value') ?? $d
            : fn (string $k, $d) => StoreConfig::get($k, $d);

        // Read from StoreConfig DB first, fallback to .env
        $this->apiKey   = (string) $get('sms_api_key',   config('sms.api_key',   ''));
        $this->senderId = (string) $get('sms_sender_id', config('sms.sender_id', ''));
    }

    /**
     * Send SMS to a single number — এখনই, এই রিকোয়েস্টের ভেতরে।
     * Returns ['success' => bool, 'response' => string]
     * যেখানে ফলাফল সাথে সাথে ব্যবহারকারীকে দেখাতে হয় সেখানে এটা; নইলে sendLater()।
     */
    public function send(string $number, string $message, ?string $recipientName = null): array
    {
        return $this->deliver($this->createLog($number, $message, $recipientName));
    }

    /**
     * ব্যবহারকারীকে উত্তর আটকে না রেখে SMS পাঠায়।
     *
     * লগ এখনই (রিকোয়েস্টের ভেতরে) তৈরি হয় — শপ ও ইউজার সঠিক থাকে। তারপর:
     *  - worker চালু (heartbeat তাজা) → Queue Job; dispatch ব্যর্থ হলে ↓
     *  - নইলে → defer(): রেসপন্স আগে যায় (PHP-FPM), তারপর একই প্রসেসে SMS
     * Job queue-তে পড়ে থাকলে (worker মাঝপথে থেমেছে) app:sms-sweep পাঠিয়ে দেয়।
     *
     * আগে SMS রিকোয়েস্টের ভেতরেই যেত (টাইমআউট ১৫ সেকেন্ড) — গেটওয়ে ধীর হলে
     * "বিক্রয় সম্পন্ন" পেজ ততক্ষণ আটকে থাকত।
     */
    public function sendLater(string $number, string $message, ?string $recipientName = null): void
    {
        $log = $this->createLog($number, $message, $recipientName);

        if (static::queueWorkerAlive()) {
            try {
                SendSmsJob::dispatch($log->id, $log->shop_id);
                return;
            } catch (\Throwable $e) {
                report($e);   // queue টেবিলে লেখা গেল না — নিচের defer পথে যাক
            }
        }

        defer(fn () => $this->deliver($log));
    }

    /** Queue worker চালু আছে কি না — heartbeat দেখে; কিছু গোলমাল হলে "না" */
    public static function queueWorkerAlive(): bool
    {
        if (config('queue.default') === 'sync') return false;
        try {
            $beat = (int) Cache::get(self::WORKER_HEARTBEAT_KEY, 0);
        } catch (\Throwable) {
            return false;
        }
        return $beat > 0 && (time() - $beat) <= self::WORKER_FRESH_SECONDS;
    }

    private function createLog(string $number, string $message, ?string $recipientName): SmsLog
    {
        return SmsLog::create([
            'recipient'      => $this->normalizeNumber($number),
            'recipient_name' => $recipientName,
            'message'        => $message,
            'status'         => 'pending',
            'sent_by'        => Auth::id(),
        ]);
    }

    /**
     * একটা pending লগের SMS গেটওয়েতে পাঠায়।
     *
     * @param bool $throwOnNetworkError  true (Queue Job) হলে নেটওয়ার্ক ব্যর্থতায় লগ আবার
     *        pending-এ ফেরে আর exception ছোড়ে, যাতে queue পরে আবার চেষ্টা করে।
     *        গেটওয়ে উত্তর দিয়ে "ব্যর্থ" বললে (যেমন ভুল নম্বর, ব্যালেন্স নেই) আর চেষ্টা নয়।
     */
    public function deliver(SmsLog $log, bool $throwOnNetworkError = false): array
    {
        // দাবি: pending → sending। অন্য পথ (Job/defer/sweeper) আগে দাবি করে থাকলে কিছু নয়।
        $claimed = SmsLog::withoutGlobalScopes()
            ->whereKey($log->id)
            ->where('status', 'pending')
            ->update(['status' => 'sending', 'updated_at' => now()]);
        if ($claimed !== 1) {
            return ['success' => false, 'response' => 'ইতিমধ্যে অন্য প্রক্রিয়া পাঠিয়েছে/পাঠাচ্ছে', 'skipped' => true];
        }

        if ($this->apiKey === '' || $this->senderId === '') {
            $this->finish($log, 'failed', 'API key বা Sender ID সেট করা নেই');
            return ['success' => false, 'response' => 'API key বা Sender ID সেট করা নেই'];
        }

        try {
            $response = Http::timeout(15)->get($this->apiUrl, [
                'api_key'  => $this->apiKey,
                'type'     => 'TEXT',
                'number'   => $log->recipient,
                'senderid' => $this->senderId,
                'message'  => $log->message,
            ]);

            $body = $response->body();
            $data = $response->json();

            // BulkSMSBD returns response_code 202 for success
            $success = isset($data['response_code']) && $data['response_code'] == 202;

            $this->finish($log, $success ? 'sent' : 'failed', $body);

            return [
                'success'  => $success,
                'response' => $success ? 'সফলভাবে পাঠানো হয়েছে' : ($data['error_message'] ?? $body),
            ];
        } catch (\Exception $e) {
            if ($throwOnNetworkError) {
                // Job আবার চেষ্টা করবে — অন্য পথও (sweeper) চাইলে দাবি করতে পারে
                $this->finish($log, 'pending', 'আবার চেষ্টা হবে: ' . $e->getMessage());
                throw $e;
            }
            $this->finish($log, 'failed', $e->getMessage());
            return ['success' => false, 'response' => $e->getMessage()];
        }
    }

    /** লগের স্ট্যাটাস লেখা — withoutGlobalScopes কারণ worker-এ লগইন নেই; কলাম varchar(255) */
    private function finish(SmsLog $log, string $status, string $response): void
    {
        SmsLog::withoutGlobalScopes()->whereKey($log->id)->update([
            'status'           => $status,
            'gateway_response' => mb_substr($response, 0, 250),
            'updated_at'       => now(),
        ]);
    }

    /**
     * Send SMS to multiple numbers.
     * Returns ['sent' => int, 'failed' => int, 'results' => array]
     */
    public function sendBulk(array $recipients, string $message): array
    {
        $sent   = 0;
        $failed = 0;
        $results = [];

        foreach ($recipients as $r) {
            $number = is_array($r) ? ($r['phone'] ?? '') : $r;
            $name   = is_array($r) ? ($r['name']  ?? null) : null;

            if (empty($number)) { $failed++; continue; }

            $result = $this->send($number, $message, $name);
            $result['success'] ? $sent++ : $failed++;
            $results[] = array_merge($result, ['number' => $number, 'name' => $name]);
        }

        return compact('sent', 'failed', 'results');
    }

    /**
     * Normalize BD phone number → 01XXXXXXXXX
     */
    private function normalizeNumber(string $number): string
    {
        $number = preg_replace('/\D/', '', $number);
        // +8801... → 01...
        if (str_starts_with($number, '880') && strlen($number) === 13) {
            $number = '0' . substr($number, 3);
        }
        return $number;
    }
}
