<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Pure-PHP database backup — no mysqldump binary needed, so it works on
 * any shared hosting. Dumps every table (structure + data) to a gzipped
 * SQL file in storage/app/backups and prunes old files.
 *
 * Run manually:  php artisan app:backup-db
 * Scheduled daily at 03:00 (routes/console.php); production needs one
 * cron entry: * * * * * php artisan schedule:run
 *
 * --tag দিলে ফাইলের নাম হয় backup_<tag>_<timestamp>.sql.gz আর পুরনো মোছার
 * হিসাবও শুধু ওই ট্যাগের ফাইলের মধ্যেই হয়। ডিপ্লয়ের আগের ব্যাকআপ
 * (--tag=predeploy) তাই দৈনিক ব্যাকআপের ঘোরাফেরায় মুছে যায় না — একটা খারাপ
 * মাইগ্রেশন সপ্তাহখানেক পরে ধরা পড়লেও ওই দিনের ডাম্পটা তখনো থাকে।
 */
class BackupDatabase extends Command
{
    protected $signature = 'app:backup-db
        {--keep=14 : কয়টা ব্যাকআপ ফাইল রাখা হবে}
        {--tag= : ফাইলের নামে ট্যাগ (যেমন predeploy) — এই ট্যাগের ফাইলগুলো আলাদা করে গোনা ও মোছা হয়}';

    protected $description = 'পুরো ডাটাবেস SQL ফাইলে ব্যাকআপ করে (gzip), পুরনোগুলো মুছে দেয়';

    public function handle(): int
    {
        $dir = storage_path('app/backups');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $dbName = DB::getDatabaseName();

        // ট্যাগ ফাইলের নামে বসে, তাই শেল-এ নিরাপদ অক্ষরেই সীমিত রাখা হয়
        $tag    = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $this->option('tag'));
        $prefix = 'backup_' . ($tag !== '' ? $tag . '_' : '');
        $file   = $dir . DIRECTORY_SEPARATOR . $prefix . now()->format('Y-m-d_His') . '.sql.gz';

        $gz = gzopen($file, 'wb6');
        if (!$gz) {
            $this->error("ফাইল খোলা যায়নি: {$file}");
            return self::FAILURE;
        }

        gzwrite($gz, "-- Ruposhi POS backup\n-- Database: {$dbName}\n-- Date: " . now()->toDateTimeString() . "\n\n");
        gzwrite($gz, "SET FOREIGN_KEY_CHECKS=0;\nSET NAMES utf8mb4;\n\n");

        $tables = array_map('current', DB::select('SHOW TABLES'));
        $totalRows = 0;

        foreach ($tables as $table) {
            $create = DB::select("SHOW CREATE TABLE `{$table}`")[0]->{'Create Table'};
            gzwrite($gz, "DROP TABLE IF EXISTS `{$table}`;\n{$create};\n\n");

            // Stream rows in chunks — keeps memory flat on big tables
            DB::table($table)->orderByRaw('1')->chunk(500, function ($rows) use ($gz, $table, &$totalRows) {
                $values = [];
                foreach ($rows as $row) {
                    $vals = array_map(function ($v) {
                        if ($v === null) return 'NULL';
                        if (is_int($v) || is_float($v)) return (string) $v;
                        return DB::getPdo()->quote((string) $v);
                    }, (array) $row);
                    $values[] = '(' . implode(',', $vals) . ')';
                    $totalRows++;
                }
                if ($values) {
                    gzwrite($gz, "INSERT INTO `{$table}` VALUES\n" . implode(",\n", $values) . ";\n");
                }
            });
            gzwrite($gz, "\n");
        }

        gzwrite($gz, "SET FOREIGN_KEY_CHECKS=1;\n");
        gzclose($gz);

        $size = round(filesize($file) / 1024, 1);
        $this->info('ব্যাকআপ সম্পন্ন: ' . basename($file) . " ({$size} KB, " . count($tables) . " টেবিল, {$totalRows} সারি)");

        // Prune: keep the newest N backups OF THIS TAG ONLY.
        // গ্লবটা ট্যাগ-ভিত্তিক — নইলে ডিপ্লয়ের ব্যাকআপ দৈনিকগুলোকে (বা
        // উল্টোটা) হিসাবের বাইরে ঠেলে মুছে ফেলত। ট্যাগ ছাড়া ডাকলে
        // backup_YYYY-...  ধরা হয়, backup_predeploy_... নয়।
        $keep = max(1, (int) $this->option('keep'));
        $files = $tag !== ''
            ? glob($dir . DIRECTORY_SEPARATOR . $prefix . '*.sql.gz')
            : array_filter(
                glob($dir . DIRECTORY_SEPARATOR . 'backup_*.sql.gz'),
                fn($f) => (bool) preg_match('/backup_\d{4}-\d{2}-\d{2}_\d{6}\.sql\.gz$/', $f)
            );
        $files = array_values($files);
        rsort($files); // newest first (timestamped names sort naturally)
        foreach (array_slice($files, $keep) as $old) {
            unlink($old);
            $this->line('পুরনো ব্যাকআপ মুছে ফেলা হয়েছে: ' . basename($old));
        }

        return self::SUCCESS;
    }
}
