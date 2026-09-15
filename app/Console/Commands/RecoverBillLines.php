<?php

namespace App\Console\Commands;

use App\Support\BillTotals;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Item deletion used to cascade sale_items / purchase_items away, leaving
 * চালান whose total no longer matches their lines (see BillTotals). The lines
 * are gone from the live database, but a backup taken before that item was
 * deleted still holds them.
 *
 * Scans the retained backups newest-first and, for each drifting চালান,
 * reports the newest backup whose lines add up to the stored total — i.e. the
 * best copy to restore from. With --sql it writes the INSERT statements.
 *
 * Read-only against the database: it never writes to sale_items or
 * purchase_items itself. The generated SQL is for a human to review and run.
 */
class RecoverBillLines extends Command
{
    protected $signature = 'pos:recover-bill-lines
                            {--dir= : ব্যাকআপ ফোল্ডার (ডিফল্ট storage/app/backups)}
                            {--sql= : উদ্ধারের SQL এই ফাইলে লিখুন}
                            {--shop= : নির্দিষ্ট shop_id (না দিলে সব শাখা)}';

    protected $description = 'ব্যাকআপ ঘেঁটে মুছে যাওয়া চালানের লাইন খুঁজে বের করুন';

    public function handle(): int
    {
        $dir = $this->option('dir') ?: storage_path('app/backups');
        if (!is_dir($dir)) {
            $this->error("ফোল্ডার পাওয়া যায়নি: {$dir}");
            return self::FAILURE;
        }

        // Newest first: the newest backup that still has a চালান's lines is the
        // one to restore from — older copies may predate later edits to it.
        $backups = array_merge(glob("{$dir}/*.sql.gz"), glob("{$dir}/*.sql"));
        rsort($backups);

        if (!$backups) {
            $this->error("কোনো ব্যাকআপ ফাইল নেই: {$dir}");
            return self::FAILURE;
        }

        $shopId = $this->option('shop') ? (int) $this->option('shop') : null;

        // target[table][bill_id] = expected sum of that bill's lines
        $targets = ['sale_items' => [], 'purchase_items' => []];
        foreach (BillTotals::driftingSales($shopId) as $r) {
            $targets['sale_items'][(int) $r->id] = (float) $r->gap;
        }
        foreach (BillTotals::driftingPurchases($shopId) as $r) {
            $targets['purchase_items'][(int) $r->id] = (float) $r->gap;
        }

        $wanted = count($targets['sale_items']) + count($targets['purchase_items']);
        if (!$wanted) {
            $this->info('✅ কোনো চালানে গরমিল নেই — উদ্ধারের কিছু নেই।');
            return self::SUCCESS;
        }

        $this->line(sprintf('গরমিল: %d বিক্রয় + %d ক্রয় | ব্যাকআপ: %d টি',
            count($targets['sale_items']), count($targets['purchase_items']), count($backups)));

        $found = [];   // "table#id" => ['backup' => name, 'rows' => [...]]
        foreach ($backups as $path) {
            $pending = $this->pending($targets, $found);
            if (!$pending) {
                break;
            }

            $this->line('  পড়ছি: ' . basename($path));
            $rows = $this->readLines($path, $pending);

            foreach ($pending as $table => $ids) {
                foreach ($ids as $id => $gap) {
                    $key   = "{$table}#{$id}";
                    $these = $rows[$table][$id] ?? [];
                    if (!$these) {
                        continue;
                    }
                    // A backup is only useful if it holds the lines that are
                    // missing now — i.e. its line total exceeds the live one by
                    // the gap. Anything less is a partial copy; skip it and
                    // keep looking further back.
                    $live    = $this->liveSum($table, $id);
                    $backup  = array_sum(array_column($these, 'subtotal'));
                    if ($backup - $live >= $gap - 0.01) {
                        $found[$key] = ['backup' => basename($path), 'rows' => $these, 'recovered' => $backup - $live];
                    }
                }
            }
        }

        $this->report($targets, $found);

        if ($this->option('sql')) {
            $this->writeSql($this->option('sql'), $found);
        }

        return self::SUCCESS;
    }

    /** Targets not yet satisfied by a newer backup. */
    private function pending(array $targets, array $found): array
    {
        $out = [];
        foreach ($targets as $table => $ids) {
            foreach ($ids as $id => $gap) {
                if (!isset($found["{$table}#{$id}"])) {
                    $out[$table][$id] = $gap;
                }
            }
        }
        return $out;
    }

    private function liveSum(string $table, int $id): float
    {
        $fk = $table === 'sale_items' ? 'sale_id' : 'purchase_id';
        return (float) DB::table($table)->where($fk, $id)->sum('subtotal');
    }

    /**
     * Pull the wanted tables' rows out of one backup. Both dump formats write
     * one row per line, so we can stream instead of loading the whole file.
     */
    private function readLines(string $path, array $pending): array
    {
        $gz    = str_ends_with($path, '.gz');
        $fh    = $gz ? gzopen($path, 'rb') : fopen($path, 'rb');
        $out   = [];
        $table = null;
        $cols  = null;

        while (($line = $gz ? gzgets($fh) : fgets($fh)) !== false) {
            if (str_starts_with($line, 'INSERT INTO ')) {
                $table = null;
                if (preg_match('/^INSERT INTO `([a-z_]+)`\s*(\(([^)]*)\))?\s*VALUES/i', $line, $m)) {
                    if (isset($pending[$m[1]])) {
                        $table = $m[1];
                        $cols  = !empty($m[3]) ? preg_split('/\s*,\s*/', str_replace('`', '', trim($m[3]))) : null;
                    }
                }
                continue;
            }
            if (!$table || $line === '' || $line[0] !== '(') {
                continue;
            }

            $vals = $this->splitRow($line);
            if (!$vals) {
                continue;
            }
            // No column list (the app's own dump) → fall back to the live
            // schema's column order, which is what that dump was written from.
            $names = $cols ?: Schema::getColumnListing($table);
            if (count($names) !== count($vals)) {
                continue;
            }
            $row = array_combine($names, $vals);
            $fk  = $table === 'sale_items' ? 'sale_id' : 'purchase_id';
            $id  = (int) ($row[$fk] ?? 0);

            if (isset($pending[$table][$id])) {
                $out[$table][$id][] = $row;
            }
        }

        $gz ? gzclose($fh) : fclose($fh);
        return $out;
    }

    /** Split one `(...)` row, honouring quoted strings and backslash escapes. */
    private function splitRow(string $line): array
    {
        $line = rtrim(trim($line), ";,");
        if ($line === '' || $line[0] !== '(') {
            return [];
        }
        $line = substr($line, 1, -1);

        $vals   = [];
        $cur    = '';
        $q      = false;   // inside a quoted value right now
        $quoted = false;   // this value was quoted at all
        for ($i = 0, $n = strlen($line); $i < $n; $i++) {
            $ch = $line[$i];
            if ($q) {
                if ($ch === '\\' && $i + 1 < $n) { $cur .= $line[++$i]; continue; }
                if ($ch === "'")                 { $q = false; continue; }
                $cur .= $ch;
                continue;
            }
            if ($ch === "'") {
                // Dumps write `, 'x'` — the separating space sits outside the
                // quotes and is not part of the value. Drop it, or every string
                // comes back with a leading space.
                $cur = ''; $q = true; $quoted = true;
                continue;
            }
            if ($ch === ',') {
                $vals[] = $this->finish($cur, $quoted);
                $cur = ''; $quoted = false;
                continue;
            }
            $cur .= $ch;
        }
        $vals[] = $this->finish($cur, $quoted);

        return $vals;
    }

    /** Unquoted values carry the dump's padding; NULL is only NULL unquoted. */
    private function finish(string $cur, bool $quoted): ?string
    {
        if ($quoted) {
            return $cur;
        }
        $cur = trim($cur);
        return $cur === 'NULL' ? null : $cur;
    }

    private function report(array $targets, array $found): void
    {
        foreach ($targets as $table => $ids) {
            $label = $table === 'sale_items' ? 'বিক্রয় (চালান)' : 'ক্রয় (রিসিট)';
            $this->newLine();
            $this->line("── {$label}");

            $rows = [];
            foreach ($ids as $id => $gap) {
                $hit    = $found["{$table}#{$id}"] ?? null;
                $rows[] = [
                    str_pad($id, 6, '0', STR_PAD_LEFT),
                    number_format($gap, 0),
                    $hit ? number_format($hit['recovered'], 0) : '—',
                    $hit ? count($hit['rows']) . ' লাইন' : '—',
                    $hit['backup'] ?? '❌ কোনো ব্যাকআপে নেই',
                ];
            }
            $this->table(['নং', 'ফারাক', 'উদ্ধারযোগ্য', 'লাইন', 'ব্যাকআপ'], $rows);
        }

        $ok = count($found);
        $this->newLine();
        $this->info("উদ্ধারযোগ্য: {$ok} টি");

        if ($ok) {
            $this->line('--sql=recover.sql দিলে INSERT স্টেটমেন্ট ফাইলে লেখা হবে।');
            $this->warn('SQL চালানোর আগে নিজে পড়ে দেখুন, এবং আগে একটা ব্যাকআপ নিন।');
        }
    }

    private function writeSql(string $path, array $found): void
    {
        $out = "-- মুছে যাওয়া চালানের লাইন পুনরুদ্ধার\n"
             . '-- তৈরি: ' . now()->toDateTimeString() . "\n"
             . "-- চালানোর আগে: php artisan app:backup-db\n\n";

        foreach ($found as $key => $hit) {
            [$table] = explode('#', $key);
            $out .= "-- {$key} — উৎস: {$hit['backup']}\n";
            foreach ($hit['rows'] as $row) {
                // Drop the old primary key so the row re-inserts cleanly.
                unset($row['id']);
                $cols = implode(', ', array_map(fn ($c) => "`{$c}`", array_keys($row)));
                $vals = implode(', ', array_map(
                    fn ($v) => $v === null ? 'NULL' : "'" . addslashes($v) . "'",
                    $row
                ));
                $out .= "INSERT INTO `{$table}` ({$cols}) VALUES ({$vals});\n";
            }
            $out .= "\n";
        }

        file_put_contents($path, $out);
        $this->info("SQL লেখা হয়েছে: {$path}");
    }
}
