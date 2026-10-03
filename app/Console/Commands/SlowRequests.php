<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * ধীর রিকোয়েস্টের লগ দেখার কমান্ড। লগ লেখে LogSlowRequests middleware —
 * storage/logs/slow-requests-<তারিখ>.log, ১৪ দিন রাখা হয়।
 *
 *   php artisan app:slow-requests              # আজকের সারাংশ (কোন পেজ কতবার, গড়/সর্বোচ্চ)
 *   php artisan app:slow-requests --days=7     # গত ৭ দিনের
 *   php artisan app:slow-requests --recent=30  # সর্বশেষ ৩০টা রিকোয়েস্ট আলাদা করে
 */
class SlowRequests extends Command
{
    protected $signature = 'app:slow-requests
        {--days=1 : কত দিনের লগ দেখবে (আজ সহ)}
        {--recent=15 : সর্বশেষ কতগুলো রিকোয়েস্ট আলাদা করে দেখাবে}';

    protected $description = 'ধীর রিকোয়েস্টের সারাংশ — কোন পেজ, কত ms, Laravel চালু/রেসপন্স/worker আটকে থাকার সময়';

    public function handle(): int
    {
        $days    = max(1, (int) $this->option('days'));
        $entries = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $file = storage_path('logs/slow-requests-' . now()->subDays($i)->format('Y-m-d') . '.log');
            if (!is_file($file)) continue;

            foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                // [2026-10-03 12:00:00] production.WARNING: slow {...json...} []
                if (!preg_match('/^\[([^\]]+)\].*?slow (\{.*\})/', $line, $m)) continue;
                $ctx = json_decode($m[2], true);
                if (!is_array($ctx)) continue;
                $entries[] = ['at' => $m[1]] + $ctx;
            }
        }

        if (!$entries) {
            $this->info("গত {$days} দিনে কোনো ধীর রিকোয়েস্ট নেই (সীমা: " . config('logging.slow_request_ms') . ' ms)।');
            return self::SUCCESS;
        }

        // ── পেজ অনুযায়ী সারাংশ ──
        $groups = collect($entries)->groupBy(fn($e) => $e['method'] . ' ' . ($e['route'] ?? $e['path']));
        $rows = $groups->map(fn($g, $key) => [
            $key,
            $g->count(),
            (int) $g->avg('response_ms'),
            $g->max('response_ms'),
            (int) $g->avg('total_ms'),
            (int) $g->avg('boot_ms'),
            (int) $g->avg('db_ms'),
        ])->sortByDesc(1)->values()->all();

        $this->line("গত {$days} দিনে ধীর রিকোয়েস্ট: " . count($entries) . ' টা (সীমা: ' . config('logging.slow_request_ms') . ' ms)');
        $this->table(['পেজ', 'বার', 'গড় রেসপন্স', 'সর্বোচ্চ', 'গড় worker আটকে', 'গড় boot', 'গড় DB'], $rows);

        // ── সর্বশেষ কয়েকটা ──
        $recent = array_slice(array_reverse($entries), 0, max(1, (int) $this->option('recent')));
        $this->table(
            ['সময়', 'পেজ', 'স্ট্যাটাস', 'boot', 'রেসপন্স', 'worker', 'কুয়েরি', 'DB', 'ইউজার'],
            array_map(fn($e) => [
                $e['at'],
                $e['method'] . ' ' . $e['path'],
                $e['status'],
                $e['boot_ms'],
                $e['response_ms'],
                $e['total_ms'],
                $e['queries'],
                $e['db_ms'],
                $e['user_id'] ?? '—',
            ], $recent)
        );

        $this->line('পড়ার নিয়ম: boot বড় → Laravel চালু হতেই সময় (OPcache দেখুন) · DB বড় → ধীর কুয়েরি ·');
        $this->line('worker রেসপন্সের চেয়ে অনেক বড় → রেসপন্সের পরে কাজ (defer SMS) worker আটকে রাখছে');
        return self::SUCCESS;
    }
}
