<?php

namespace App\Console\Commands;

use App\Models\SmsLog;
use App\Services\SmsService;
use Illuminate\Console\Command;

/**
 * SMS-এর সেফটি নেট — প্রতি মিনিটে scheduler থেকে চলে।
 *
 * Queue worker মাঝপথে থেমে গেলে Job queue-তে পড়ে থাকে আর SMS কখনো যায় না।
 * তাই ৩ মিনিটের বেশি pending থাকা SMS এখানে সরাসরি পাঠানো হয়। পরে worker
 * ফিরে এসে Job চালালেও লগ আর pending নেই, তাই দ্বিতীয়বার যায় না।
 *
 * ⚠️ শুধু গত ২ ঘণ্টার — অনেক পুরনো আটকে থাকা SMS হঠাৎ কাস্টমারের কাছে
 * যাওয়া ঠিক নয় (আর পুরনো রেকর্ড যেমন আছে তেমনই থাকে)।
 */
class SmsSweep extends Command
{
    protected $signature   = 'app:sms-sweep';
    protected $description = 'আটকে থাকা (pending) SMS সরাসরি পাঠায় — queue worker না চললে সেফটি নেট';

    public function handle(): int
    {
        $stale = SmsLog::withoutGlobalScopes()
            ->where('status', 'pending')
            ->where('created_at', '<=', now()->subMinutes(3))
            ->where('created_at', '>=', now()->subHours(2))
            ->orderBy('id')
            ->limit(50)
            ->get();

        $sent = 0;
        foreach ($stale as $log) {
            $r = (new SmsService($log->shop_id))->deliver($log);
            if (!empty($r['success'])) $sent++;
        }

        // পাঠানোর মাঝপথে প্রসেস থেমে গিয়েছিল (sending-এ আটকে) — আবার পাঠালে
        // দুবার যেতে পারে, তাই শুধু "ব্যর্থ/অজানা" চিহ্ন দেওয়া হয়
        $stuck = SmsLog::withoutGlobalScopes()
            ->where('status', 'sending')
            ->where('updated_at', '<=', now()->subMinutes(30))
            ->where('created_at', '>=', now()->subDay())
            ->update([
                'status'           => 'failed',
                'gateway_response' => 'পাঠানোর মাঝপথে প্রক্রিয়া থেমে গিয়েছিল — গেছে কি না অজানা',
                'updated_at'       => now(),
            ]);

        if ($stale->count() || $stuck) {
            $this->info("pending পাঠানো: {$sent}/{$stale->count()}, আটকে থাকা চিহ্নিত: {$stuck}");
        }
        return self::SUCCESS;
    }
}
