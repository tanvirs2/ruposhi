<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Snapshot the item's purchase price onto every sale line.
     *
     * Profit reports used to join items.purchase_price live, so changing an
     * item's purchase price silently rewrote the profit of every past sale.
     * The cost now belongs to the sale row and never changes again.
     */
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('cost_price', 12, 2)->nullable()->after('price');
        });

        // Back-fill: keep every existing report showing exactly what it shows
        // today — but frozen from now on.
        DB::statement('
            UPDATE sale_items
            JOIN items ON items.id = sale_items.item_id
            SET sale_items.cost_price = items.purchase_price
            WHERE sale_items.cost_price IS NULL
        ');
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('cost_price');
        });
    }
};
