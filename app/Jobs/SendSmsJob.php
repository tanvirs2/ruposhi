<?php

namespace App\Jobs;

use App\Models\SmsLog;
use App\Services\SmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * একটা SMS ব্যাকগ্রাউন্ডে পাঠায় (SmsService::sendLater থেকে আসে)।
 *
 * - শুধু আইডি বহন করে; লগ (বার্তা, নম্বর) রিকোয়েস্টেই তৈরি হয়ে আছে
 * - shopId সাথে আসে, কারণ worker-এ লগইন নেই — ShopScope ছাড়া ভুল শপের
 *   API key পড়ত (SmsService::__construct দেখুন)
 * - নেটওয়ার্ক ব্যর্থ হলে ৩০ সেকেন্ড, তারপর ২ মিনিট পরে আবার চেষ্টা
 * - লগ আর pending না থাকলে (sweeper/defer আগে পাঠিয়ে দিয়েছে) কিছুই করে না
 */
class SendSmsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public array $backoff = [30, 120];
    public int $timeout = 30;

    public function __construct(public int $logId, public ?int $shopId) {}

    public function handle(): void
    {
        $log = SmsLog::withoutGlobalScopes()->find($this->logId);
        if (!$log || $log->status !== 'pending') return;

        (new SmsService($this->shopId ?? $log->shop_id))->deliver($log, throwOnNetworkError: true);
    }

    /** সব চেষ্টা শেষ — লগে ব্যর্থ লেখা (অন্য পথ ইতিমধ্যে পাঠিয়ে থাকলে কিছু নয়) */
    public function failed(?\Throwable $e): void
    {
        SmsLog::withoutGlobalScopes()
            ->whereKey($this->logId)
            ->whereIn('status', ['pending', 'sending'])
            ->update([
                'status'           => 'failed',
                'gateway_response' => mb_substr('বারবার চেষ্টার পরও যায়নি: ' . ($e?->getMessage() ?? ''), 0, 250),
                'updated_at'       => now(),
            ]);
    }
}
