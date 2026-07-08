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
 */
class BackupDatabase extends Command
{
    protected $signature = 'app:backup-db {--keep=14 : কয়টা ব্যাকআপ ফাইল রাখা হবে}';

    protected $description = 'পুরো ডাটাবেস SQL ফাইলে ব্যাকআপ করে (gzip), পুরনোগুলো মুছে দেয়';

    public function handle(): int
    {
        $dir = storage_path('app/backups');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $dbName = DB::getDatabaseName();
        $file   = $dir . DIRECTORY_SEPARATOR . 'backup_' . now()->format('Y-m-d_His') . '.sql.gz';

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

        // Prune: keep the newest N backups
        $keep  = max(1, (int) $this->option('keep'));
        $files = glob($dir . DIRECTORY_SEPARATOR . 'backup_*.sql.gz');
        rsort($files); // newest first (timestamped names sort naturally)
        foreach (array_slice($files, $keep) as $old) {
            unlink($old);
            $this->line('পুরনো ব্যাকআপ মুছে ফেলা হয়েছে: ' . basename($old));
        }

        return self::SUCCESS;
    }
}
