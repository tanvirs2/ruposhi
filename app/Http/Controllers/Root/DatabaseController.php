<?php

namespace App\Http\Controllers\Root;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DatabaseController extends Controller
{
    public function index()
    {
        $tables = DB::select('SHOW TABLE STATUS');
        $stats  = [];
        foreach ($tables as $t) {
            $stats[] = [
                'name'    => $t->Name,
                'rows'    => $t->Rows,
                'size_kb' => round(($t->Data_length + $t->Index_length) / 1024, 1),
                'engine'  => $t->Engine,
            ];
        }
        $totalRows = array_sum(array_column($stats, 'rows'));
        $totalKb   = array_sum(array_column($stats, 'size_kb'));

        return view('root.database.index', compact('stats', 'totalRows', 'totalKb'));
    }

    public function export()
    {
        $dbName  = config('database.connections.mysql.database');
        $now     = now()->format('Y_m_d_H_i_s');
        $filename = "backup_{$dbName}_{$now}.sql";

        return response()->streamDownload(function () use ($dbName) {
            echo "-- ============================================================\n";
            echo "-- Ruposhi POS — Full Database Backup\n";
            echo "-- Database : $dbName\n";
            echo "-- Generated: " . now()->toDateTimeString() . "\n";
            echo "-- ============================================================\n\n";
            echo "SET FOREIGN_KEY_CHECKS=0;\n";
            echo "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n";
            echo "SET NAMES utf8mb4;\n\n";

            $tables = DB::select('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"');
            foreach ($tables as $tableRow) {
                $table = array_values((array) $tableRow)[0];
                $this->dumpTable($table);
            }

            echo "SET FOREIGN_KEY_CHECKS=1;\n";
            echo "-- ============================================================\n";
            echo "-- Backup complete\n";
            echo "-- ============================================================\n";
        }, $filename, [
            'Content-Type'        => 'application/octet-stream',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function dumpTable(string $table): void
    {
        echo "-- ------------------------------------------------------------\n";
        echo "-- Table: `{$table}`\n";
        echo "-- ------------------------------------------------------------\n";

        // CREATE TABLE
        $createResult = DB::select("SHOW CREATE TABLE `{$table}`");
        $createSql    = array_values((array) $createResult[0])[1];

        echo "DROP TABLE IF EXISTS `{$table}`;\n";
        echo $createSql . ";\n\n";

        // Data in chunks to avoid memory issues
        $chunkSize = 500;
        $offset    = 0;

        while (true) {
            $rows = DB::table($table)->orderByRaw('1')->offset($offset)->limit($chunkSize)->get();
            if ($rows->isEmpty()) break;

            $cols = array_keys((array) $rows->first());
            $colList = implode(', ', array_map(fn($c) => "`{$c}`", $cols));

            echo "INSERT INTO `{$table}` ({$colList}) VALUES\n";

            $values = $rows->map(function ($row) {
                $row = (array) $row;
                $escaped = array_map(function ($v) {
                    if ($v === null) return 'NULL';
                    return "'" . addslashes((string) $v) . "'";
                }, $row);
                return '(' . implode(', ', $escaped) . ')';
            });

            echo $values->implode(",\n") . ";\n\n";

            $offset += $chunkSize;
            if ($rows->count() < $chunkSize) break;
        }
    }

    public function import(Request $request)
    {
        $request->validate([
            'sql_file' => 'required|file|mimes:sql,txt|max:51200', // 50 MB
        ], [
            'sql_file.required' => 'SQL ফাইল আপলোড করুন',
            'sql_file.mimes'    => 'শুধু .sql বা .txt ফাইল গ্রহণযোগ্য',
            'sql_file.max'      => 'ফাইল সর্বোচ্চ ৫০ MB হতে পারবে',
        ]);

        $sql = file_get_contents($request->file('sql_file')->getRealPath());

        if (empty(trim($sql))) {
            return back()->with('error', 'ফাইলটি খালি।');
        }

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::unprepared($sql);
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            return back()->with('success', 'ডেটাবেজ সফলভাবে import হয়েছে।');
        } catch (\Throwable $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            return back()->with('error', 'Import ব্যর্থ: ' . $e->getMessage());
        }
    }
}
