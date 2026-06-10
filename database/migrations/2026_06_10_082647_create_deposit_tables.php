<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Deposit categories (per shop — like extra_cost_categories)
        Schema::create('deposit_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shop_id')->index();
            $table->string('name');
            $table->timestamps();
            $table->unique(['shop_id', 'name']);
        });

        // Per-purchase itemised deposits
        Schema::create('purchase_deposits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shop_id')->index();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->string('category_name');
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });

        // Migrate existing single deposit_amount rows into purchase_deposits
        DB::table('purchases')->where('deposit_amount', '>', 0)->orderBy('id')->each(function ($p) {
            DB::table('purchase_deposits')->insert([
                'shop_id'       => $p->shop_id,
                'purchase_id'   => $p->id,
                'category_name' => 'জমা',
                'amount'        => $p->deposit_amount,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_deposits');
        Schema::dropIfExists('deposit_categories');
    }
};
