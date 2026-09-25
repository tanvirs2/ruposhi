<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * লোকালে প্রোডাকশন DB তোলার পর সব ইউজারের পাসওয়ার্ড "password" করে দেয়।
 *
 * লগইন পেজের ডেমো প্যানেল (শুধু non-production-এ দেখায়) ক্লিক করলে
 * 'password' বসায়। প্রোডাকশন ডাম্পে আসল পাসওয়ার্ড থাকে, তাই ইমপোর্টের পর
 * প্যানেল কাজ করত না। আসল পাসওয়ার্ড কোডে লেখার বদলে এটা চালান।
 *
 * ⚠️ শুধু APP_ENV=local-এ চলে — প্রোডাকশনে চললে সবার লগইন ভেঙে যেত।
 */
class DevResetPasswords extends Command
{
    protected $signature   = 'app:dev-reset-passwords {--force : নিশ্চিতকরণ প্রশ্ন ছাড়া চালান}';
    protected $description = 'লোকাল ডেভ: সব ইউজারের পাসওয়ার্ড "password" করে (ডেমো প্যানেলের জন্য) — শুধু APP_ENV=local';

    public const DEV_PASSWORD = 'password';

    public function handle(): int
    {
        if (!app()->environment('local')) {
            $this->error('❌  এই কমান্ড শুধু APP_ENV=local-এ চলে। এখন: ' . app()->environment());
            return self::FAILURE;
        }

        $count = DB::table('users')->count();
        $db    = config('database.connections.' . config('database.default') . '.database');

        if (!$this->option('force')
            && !$this->confirm("ডাটাবেজ '{$db}'-এর {$count} জন ইউজারের পাসওয়ার্ড '" . self::DEV_PASSWORD . "' হবে। চালাবেন?", true)) {
            $this->line('বাতিল করা হয়েছে।');
            return self::SUCCESS;
        }

        // একবার হ্যাশ — সবার জন্য একই মান, প্রতি ইউজারে আলাদা bcrypt লাগে না
        DB::table('users')->update([
            'password'       => Hash::make(self::DEV_PASSWORD),
            'remember_token' => null,
        ]);

        $this->info("✅  {$count} জন ইউজারের পাসওয়ার্ড এখন '" . self::DEV_PASSWORD . "' — ডেমো প্যানেলে ক্লিক করলেই লগইন হবে।");
        return self::SUCCESS;
    }
}
