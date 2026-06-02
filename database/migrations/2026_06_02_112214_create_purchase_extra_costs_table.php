<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ── Create purchase_extra_costs table ─────────────────────
        Schema::create('purchase_extra_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->string('category_name');
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });

        // ── Migrate old single extra_cost rows ────────────────────
        DB::table('purchases')->where('extra_cost', '>', 0)->orderBy('id')->each(function ($p) {
            DB::table('purchase_extra_costs')->insert([
                'purchase_id'   => $p->id,
                'category_name' => 'অতিরিক্ত খরচ',
                'amount'        => $p->extra_cost,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        });

        // ── Migrate old labor_cost rows ───────────────────────────
        DB::table('purchases')->where('labor_cost', '>', 0)->orderBy('id')->each(function ($p) {
            DB::table('purchase_extra_costs')->insert([
                'purchase_id'   => $p->id,
                'category_name' => 'শ্রমিক খরচ',
                'amount'        => $p->labor_cost,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
            // Fold labor_cost into extra_cost so totals stay correct
            DB::table('purchases')->where('id', $p->id)
                ->update(['extra_cost' => $p->extra_cost + $p->labor_cost]);
        });

        // ── Drop purchases.labor_cost ─────────────────────────────
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('labor_cost');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('labor_cost', 12, 2)->default(0)->after('extra_cost');
        });
        Schema::dropIfExists('purchase_extra_costs');
    }
};
