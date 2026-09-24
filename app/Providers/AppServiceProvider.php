<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\View;
use App\Services\SmsService;
use App\View\Composers\NotificationComposer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.app', NotificationComposer::class);

        // Queue worker-এর heartbeat — SmsService::sendLater() এটা দেখে ঠিক করে
        // Job দেবে নাকি defer() দিয়ে পাঠাবে (worker বন্ধ থাকলে Job পড়ে থাকত)।
        // looping প্রতি কয়েক সেকেন্ডে ফায়ার হয়, তাই ৩০ সেকেন্ডে একবারই লেখা।
        Queue::looping(function () {
            static $last = 0;
            if (time() - $last < 30) return;
            $last = time();
            try {
                Cache::put(SmsService::WORKER_HEARTBEAT_KEY, time(), 600);
            } catch (\Throwable) {
                // heartbeat না লিখতে পারলে sendLater defer পথে যাবে — ক্ষতি নেই
            }
        });
    }
}
