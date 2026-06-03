<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Line-item child tables and their parent (for backfilling shop_id).
     */
    private array $map = [
        'sale_items'           => ['parent' => 'sales',     'fk' => 'sale_id'],
        'purchase_items'       => ['parent' => 'purchases', 'fk' => 'purchase_id'],
        'sale_extra_costs'     => ['parent' => 'sales',     'fk' => 'sale_id'],
        'purchase_extra_costs' => ['parent' => 'purchases', 'fk' => 'purchase_id'],
    ];

    public function up(): void
    {
        foreach ($this->map as $child => $info) {
            if (!Schema::hasColumn($child, 'shop_id')) {
                Schema::table($child, function (Blueprint $table) {
                    $table->foreignId('shop_id')->nullable()->after('id')
                          ->constrained('shops')->nullOnDelete();
                });
            }

            // Backfill shop_id from the parent record
            DB::statement("
                UPDATE {$child} c
                JOIN {$info['parent']} p ON c.{$info['fk']} = p.id
                SET c.shop_id = p.shop_id
                WHERE c.shop_id IS NULL
            ");
        }
    }

    public function down(): void
    {
        foreach (array_keys($this->map) as $child) {
            if (Schema::hasColumn($child, 'shop_id')) {
                Schema::table($child, function (Blueprint $table) {
                    $table->dropForeign(['shop_id']);
                    $table->dropColumn('shop_id');
                });
            }
        }
    }
};
