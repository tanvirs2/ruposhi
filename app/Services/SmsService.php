<?php

namespace App\Services;

use App\Models\SmsLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

class SmsService
{
    private string $apiKey;
    private string $senderId;
    private string $apiUrl = 'https://bulksmsbd.net/api/smsapi';

    public function __construct()
    {
        // Read from StoreConfig DB first, fallback to .env
        $this->apiKey   = \App\Models\StoreConfig::get('sms_api_key',   config('sms.api_key',   ''));
        $this->senderId = \App\Models\StoreConfig::get('sms_sender_id', config('sms.sender_id', ''));
    }

    /**
     * Send SMS to a single number.
     * Returns ['success' => bool, 'response' => string]
     */
    public function send(string $number, string $message, ?string $recipientName = null): array
    {
        $number = $this->normalizeNumber($number);

        // Log as pending first
        $log = SmsLog::create([
            'recipient'      => $number,
            'recipient_name' => $recipientName,
            'message'        => $message,
            'status'         => 'pending',
            'sent_by'        => Auth::id(),
        ]);

        if (empty($this->apiKey) || empty($this->senderId)) {
            $log->update(['status' => 'failed', 'gateway_response' => 'API key বা Sender ID সেট করা নেই']);
            return ['success' => false, 'response' => 'API key বা Sender ID সেট করা নেই'];
        }

        try {
            $response = Http::timeout(15)->get($this->apiUrl, [
                'api_key'  => $this->apiKey,
                'type'     => 'TEXT',
                'number'   => $number,
                'senderid' => $this->senderId,
                'message'  => $message,
            ]);

            $body = $response->body();
            $data = $response->json();

            // BulkSMSBD returns response_code 202 for success
            $success = isset($data['response_code']) && $data['response_code'] == 202;

            $log->update([
                'status'           => $success ? 'sent' : 'failed',
                'gateway_response' => $body,
            ]);

            return [
                'success'  => $success,
                'response' => $success ? 'সফলভাবে পাঠানো হয়েছে' : ($data['error_message'] ?? $body),
            ];
        } catch (\Exception $e) {
            $log->update(['status' => 'failed', 'gateway_response' => $e->getMessage()]);
            return ['success' => false, 'response' => $e->getMessage()];
        }
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
