<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Force browser to never cache pages — prevents stale transaction data
        $middleware->web(append: [
            \App\Http\Middleware\NoCacheHeaders::class,
        ]);

        // ধীর রিকোয়েস্ট লগ — storage/logs/slow-requests-*.log, দেখতে: php artisan app:slow-requests
        $middleware->append(\App\Http\Middleware\LogSlowRequests::class);

        // Named middleware aliases
        $middleware->alias([
            'super_admin'        => \App\Http\Middleware\SuperAdmin::class,
            'shop.scope'         => \App\Http\Middleware\SetShopScope::class,
            'shop.admin'         => \App\Http\Middleware\ShopAdmin::class,
            'check.subscription' => \App\Http\Middleware\CheckSubscription::class,
            'root'               => \App\Http\Middleware\RootAdmin::class,
            'reseller'           => \App\Http\Middleware\ResellerAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // লগআউটে CSRF টোকেন না মিললে 419 পেজের বদলে লগইন পেজে পাঠানো।
        // হয় যখন খোলা পেজের সেশন আগেই শেষ (অনেকক্ষণ নিষ্ক্রিয়, বা ব্রাউজার বন্ধ
        // করে খোলা — expire_on_close) — ইউজার আসলে আগেই লগআউট হয়ে আছে।
        // CSRF যাচাই বহাল: এখানে কিছু লগআউট/বদল করা হয় না, শুধু রিডাইরেক্ট।
        // (Laravel TokenMismatchException-কে render callback-এর আগেই 419
        // HttpException-এ মুড়ে দেয়, তাই previous দেখে চেনা হয়।)
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() === 419
                && $e->getPrevious() instanceof \Illuminate\Session\TokenMismatchException
                && $request->routeIs('logout')) {
                return redirect()->route('login')
                    ->with('error', 'সেশনের মেয়াদ আগেই শেষ হয়ে গিয়েছিল — আবার লগইন করুন।');
            }
        });
    })->create();
