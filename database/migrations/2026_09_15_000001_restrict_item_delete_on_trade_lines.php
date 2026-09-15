<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

/**
 * sale_items.item_id and purchase_items.item_id were created with
 * cascadeOnDelete(). Deleting an item therefore erased its lines from every
 * past চালান while leaving sales.total_amount / purchases.total_amount intact,
 * so the bills silently stopped adding up — customer and supplier ledgers,
 * and the profit report, all drifted by the value of the erased lines.
 *
 * Traded lines are financial history. Switch both to RESTRICT so the database
 * refuses the delete outright; ItemController::destroy() already blocks it with
 * a readable message, and this is the backstop for tinker, seeders and any
 * future code path.
 *
 * Stock is deliberately left on CASCADE — it is derived data, not history.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropForeign(['item_id']);
            $table->foreign('item_id')->references('id')->on('items')->restrictOnDelete();
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropForeign(['item_id']);
            $table->foreign('item_id')->references('id')->on('items')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropForeign(['item_id']);
            $table->foreign('item_id')->references('id')->on('items')->cascadeOnDelete();
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropForeign(['item_id']);
            $table->foreign('item_id')->references('id')->on('items')->cascadeOnDelete();
        });
    }
};
