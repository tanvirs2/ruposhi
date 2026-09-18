<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * ডিপ্লয় লগ দেখার কমান্ড। লগ ফাইল লেখে `deploy.sh` —
 * storage/app/deploy-logs/deploy_<timestamp>.md, সর্বশেষ ৩০টা রাখা হয়।
 *
 *   php artisan app:deploy-log            # সর্বশেষ ডিপ্লয়ের পুরো লগ
 *   php artisan app:deploy-log --list     # সাম্প্রতিক ডিপ্লয়ের তালিকা
 *   php artisan app:deploy-log --file=deploy_2026-09-18_041811.md
 */
class DeployLog extends Command
{
    protected $signature = 'app:deploy-log
        {--list : সাম্প্রতিক ডিপ্লয়গুলোর তালিকা দেখায়}
        {--file= : নির্দিষ্ট একটা লগ ফাইল দেখায়}
        {--limit=15 : তালিকায় কতগুলো দেখাবে}';

    protected $description = 'ডিপ্লয় লগ দেখায় — কোন কমিট, কী বদলেছে, কোন মাইগ্রেশন চলেছে';

    public function handle(): int
    {
        $dir = storage_path('app/deploy-logs');
        if (!is_dir($dir)) {
            $this->warn('কোনো ডিপ্লয় লগ নেই — deploy.sh দিয়ে ডিপ্লয় করা হলে তৈরি হবে।');
            return self::SUCCESS;
        }

        $files = glob($dir . DIRECTORY_SEPARATOR . 'deploy_*.md');
        rsort($files);   // নামের টাইমস্ট্যাম্প অনুযায়ীই নতুন থেকে পুরনো

        if (!$files) {
            $this->warn('কোনো ডিপ্লয় লগ নেই।');
            return self::SUCCESS;
        }

        if ($this->option('list')) {
            $rows = [];
            foreach (array_slice($files, 0, max(1, (int) $this->option('limit'))) as $f) {
                $body = file_get_contents($f);
                // লগের হেডার থেকে দরকারি লাইনগুলো টেনে নেওয়া
                $grab = function (string $label) use ($body) {
                    return preg_match('/\*\*' . preg_quote($label, '/') . ':\*\* (.+)/u', $body, $m)
                        ? trim(strip_tags($m[1]))
                        : '—';
                };
                $rows[] = [
                    basename($f),
                    $grab('নতুন কমিট'),
                    str_contains($body, 'সফল') ? '✅' : '❌',
                    // "## ডাটাবেস পরিবর্তন" অংশে নতুন মাইগ্রেশন ছিল কি না
                    str_contains($body, 'নতুন মাইগ্রেশন ফাইল:') ? 'হ্যাঁ' : 'না',
                ];
            }
            $this->table(['ফাইল', 'কমিট', 'ফলাফল', 'DB বদল'], $rows);
            $this->line('');
            $this->line('পুরো লগ দেখতে: php artisan app:deploy-log --file=<ফাইলের নাম>');
            return self::SUCCESS;
        }

        $target = $this->option('file')
            ? $dir . DIRECTORY_SEPARATOR . basename((string) $this->option('file'))
            : $files[0];

        if (!is_file($target)) {
            $this->error('ফাইল পাওয়া যায়নি: ' . basename($target));
            return self::FAILURE;
        }

        $this->line(file_get_contents($target));
        return self::SUCCESS;
    }
}
