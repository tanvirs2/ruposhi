<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * ধীর রিকোয়েস্ট লগ — storage/logs/slow-requests-<তারিখ>.log (১৪ দিন রাখে)।
 * দেখতে: php artisan app:slow-requests
 *
 * তিনটা সময় আলাদা মাপা হয়, কারণ "ধীর" তিন জায়গা থেকে আসতে পারে:
 *   boot_ms     — PHP শুরু → এই middleware (Laravel চালু হওয়া; OPcache বন্ধ থাকলে বড়)
 *   response_ms — PHP শুরু → রেসপন্স তৈরি (ব্যবহারকারী যতক্ষণ অপেক্ষা করে)
 *   total_ms    — PHP শুরু → worker মুক্ত (defer-এ পাঠানো SMS সহ)। response_ms-এর
 *                 চেয়ে অনেক বড় হলে FPM worker আটকে থাকছে → পরের রিকোয়েস্ট লাইনে দাঁড়ায়
 *
 * ⚠️ FPM-এর লাইনে অপেক্ষার সময় (worker খালি না থাকলে) PHP দেখতে পায় না — সব worker
 * ব্যস্ত থাকলে রিকোয়েস্ট PHP শুরুর আগেই বসে থাকে। লগে সময় কম অথচ ব্রাউজারে ধীর
 * হলে সমস্যা PHP-র বাইরে: FPM pool / নেটওয়ার্ক / ব্রাউজার।
 */
class LogSlowRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = defined('LARAVEL_START') ? LARAVEL_START : ($request->server('REQUEST_TIME_FLOAT') ?: microtime(true));
        $bootMs = (microtime(true) - $start) * 1000;

        $queries = 0;
        $dbMs    = 0.0;
        DB::listen(function (QueryExecuted $q) use (&$queries, &$dbMs) {
            $queries++;
            $dbMs += $q->time;
        });

        $response   = $next($request);
        $responseMs = (microtime(true) - $start) * 1000;
        $status     = $response->getStatusCode();

        // app terminating — সব middleware-এর terminate আর defer() কাজের পরে চলে,
        // তাই এখানের সময় = worker আসলে কতক্ষণ আটকে ছিল।
        app()->terminating(function () use ($request, $start, $bootMs, $responseMs, $status, &$queries, &$dbMs) {
            $totalMs   = (microtime(true) - $start) * 1000;
            $threshold = (int) config('logging.slow_request_ms', 1000);
            if ($responseMs < $threshold && $totalMs < $threshold) {
                return;
            }

            try {
                $user = auth()->user();
                Log::channel('slow_requests')->warning('slow', [
                    'method'      => $request->method(),
                    // কুয়েরি স্ট্রিং বাদ — সার্চের লেখা/ফোন নম্বর লগে না যায়
                    'path'        => '/' . ltrim($request->path(), '/'),
                    'route'       => $request->route()?->getName(),
                    'status'      => $status,
                    'boot_ms'     => (int) round($bootMs),
                    'response_ms' => (int) round($responseMs),
                    'total_ms'    => (int) round($totalMs),
                    'queries'     => $queries,
                    'db_ms'       => (int) round($dbMs),
                    'mem_mb'      => round(memory_get_peak_usage(true) / 1048576, 1),
                    'user_id'     => $user?->id,
                    'shop_id'     => $user?->shop_id,
                    'turbo'       => $request->hasHeader('X-Turbo-Request-Id'),
                ]);
            } catch (\Throwable) {
                // লগ লেখা না গেলেও রিকোয়েস্টের কোনো ক্ষতি নয়
            }
        });

        return $response;
    }
}
