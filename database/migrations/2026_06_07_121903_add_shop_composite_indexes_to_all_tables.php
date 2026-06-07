<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Adds shop_id-leading composite indexes.
 *
 * The ShopScope global scope appends `WHERE shop_id = X` to every tenant
 * query. With multi-shop (SaaS) data, indexes that LEAD with shop_id let
 * MySQL jump straight to one shop's slice before applying date / name /
 * due filters — keeping reports and lookups fast at millions of rows.
 *
 * Existing (non-shop-leading) indexes are kept untouched.
 */
return new class extends Migration
{
    /**
     * [table => [indexName => [columns...]]]
     */
    private array $indexes = [
        'sales' => [
            'sales_shop_date_idx'       => ['shop_id', 'sale_date'],
            'sales_shop_cust_date_idx'  => ['shop_id', 'customer_id', 'sale_date'],
            'sales_shop_due_idx'        => ['shop_id', 'due_amount'],
        ],
        'purchases' => [
            'purchases_shop_date_idx'     => ['shop_id', 'purchase_date'],
            'purchases_shop_sup_date_idx' => ['shop_id', 'supplier_id', 'purchase_date'],
        ],
        'customers' => [
            'customers_shop_name_idx' => ['shop_id', 'name'],
            'customers_shop_due_idx'  => ['shop_id', 'due_amount'],
        ],
        'suppliers' => [
            'suppliers_shop_name_idx' => ['shop_id', 'name'],
            'suppliers_shop_due_idx'  => ['shop_id', 'due_amount'],
        ],
        'items' => [
            'items_shop_name_idx' => ['shop_id', 'name'],
        ],
        'stock' => [
            'stock_shop_item_idx' => ['shop_id', 'item_id'],
        ],
        'sale_items' => [
            'saleitems_shop_sale_idx' => ['shop_id', 'sale_id'],
            'saleitems_shop_item_idx' => ['shop_id', 'item_id'],
        ],
        'purchase_items' => [
            'purchitems_shop_purch_idx' => ['shop_id', 'purchase_id'],
            'purchitems_shop_item_idx'  => ['shop_id', 'item_id'],
        ],
        'sale_logs' => [
            'salelogs_shop_created_idx' => ['shop_id', 'created_at'],
        ],
        'customer_payments' => [
            'custpay_shop_date_idx' => ['shop_id', 'payment_date'],
        ],
        'supplier_payments' => [
            'suppay_shop_date_idx' => ['shop_id', 'payment_date'],
        ],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $table => $defs) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            $existing = $this->existingIndexNames($table);

            foreach ($defs as $name => $columns) {
                // Skip if an index with this name already exists, or if any
                // target column is missing (defensive against schema drift).
                if (in_array($name, $existing, true)) {
                    continue;
                }
                if (!$this->columnsExist($table, $columns)) {
                    continue;
                }
                Schema::table($table, function (Blueprint $t) use ($columns, $name) {
                    $t->index($columns, $name);
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $table => $defs) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            $existing = $this->existingIndexNames($table);

            foreach ($defs as $name => $columns) {
                if (!in_array($name, $existing, true)) {
                    continue;
                }
                Schema::table($table, function (Blueprint $t) use ($name) {
                    $t->dropIndex($name);
                });
            }
        }
    }

    private function existingIndexNames(string $table): array
    {
        return collect(DB::select("SHOW INDEX FROM `{$table}`"))
            ->pluck('Key_name')
            ->unique()
            ->values()
            ->all();
    }

    private function columnsExist(string $table, array $columns): bool
    {
        foreach ($columns as $col) {
            if (!Schema::hasColumn($table, $col)) {
                return false;
            }
        }
        return true;
    }
};
