<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables that need shop_id added.
     * sale_items / purchase_items are excluded — they derive shop via their parent.
     */
    private array $tables = [
        'customers',
        'customer_areas',
        'customer_payments',
        'categories',
        'items',
        'stock',
        'suppliers',
        'supplier_payments',
        'sales',
        'purchases',
        'employees',
        'extra_expenses',
        'store_config',
        'sale_logs',
        'sms_logs',
        'chat_messages',
        'group_chat_messages',
        'extra_cost_categories',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tbl) {
            if (!Schema::hasColumn($tbl, 'shop_id')) {
                Schema::table($tbl, function (Blueprint $table) {
                    $table->foreignId('shop_id')->nullable()->after('id')
                          ->constrained('shops')->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tbl) {
            if (Schema::hasColumn($tbl, 'shop_id')) {
                Schema::table($tbl, function (Blueprint $table) {
                    $table->dropForeign(['shop_id']);
                    $table->dropColumn('shop_id');
                });
            }
        }
    }
};
