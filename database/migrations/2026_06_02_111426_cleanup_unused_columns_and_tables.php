<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ── 1. Migrate sales.labor_cost → sale_extra_costs ────────────
        DB::table('sales')->where('labor_cost', '>', 0)->orderBy('id')->each(function ($sale) {
            DB::table('sale_extra_costs')->insert([
                'sale_id'       => $sale->id,
                'category_name' => 'শ্রমিক খরচ',
                'amount'        => $sale->labor_cost,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
            // Fold labor_cost into extra_cost so totals remain correct
            DB::table('sales')->where('id', $sale->id)
                ->update(['extra_cost' => $sale->extra_cost + $sale->labor_cost]);
        });

        // ── 2. Drop sales.labor_cost ──────────────────────────────────
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('labor_cost');
        });

        // ── 3. Drop item FK columns + image + description ─────────────
        Schema::table('items', function (Blueprint $table) {
            $table->dropForeign(['type_id']);
            $table->dropForeign(['brand_id']);
            $table->dropForeign(['unit_type_id']);
            $table->dropColumn(['type_id', 'brand_id', 'unit_type_id', 'image', 'description']);
        });

        // ── 4. Drop unused meta tables ────────────────────────────────
        Schema::dropIfExists('unit_types');
        Schema::dropIfExists('item_brands');
        Schema::dropIfExists('item_types');

        // ── 5. Drop customers.email ───────────────────────────────────
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('email');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('email')->nullable()->after('phone');
        });

        Schema::create('item_types',  fn(Blueprint $t) => [$t->id(), $t->string('name'), $t->timestamps()]);
        Schema::create('item_brands', fn(Blueprint $t) => [$t->id(), $t->string('name'), $t->timestamps()]);
        Schema::create('unit_types',  function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('short')->nullable(); $t->timestamps();
        });

        Schema::table('items', function (Blueprint $table) {
            $table->foreignId('type_id')->nullable()->constrained('item_types')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('item_brands')->nullOnDelete();
            $table->foreignId('unit_type_id')->nullable()->constrained('unit_types')->nullOnDelete();
            $table->string('image')->nullable();
            $table->text('description')->nullable();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('labor_cost', 12, 2)->default(0)->after('extra_cost');
        });
    }
};
