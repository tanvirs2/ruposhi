<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = ['items', 'customers', 'suppliers', 'categories', 'employees', 'customer_areas'];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->unsignedBigInteger('created_by')->nullable()->after('shop_id');
            });
        }
    }

    public function down(): void
    {
        $tables = ['items', 'customers', 'suppliers', 'categories', 'employees', 'customer_areas'];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn('created_by');
            });
        }
    }
};
