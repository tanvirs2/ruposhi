<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Categories for extra costs (e.g. পরিবহন, প্যাকেজিং, লোডিং…)
        Schema::create('extra_cost_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // Per-sale itemised extra costs
        Schema::create('sale_extra_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->onDelete('cascade');
            $table->string('category_name');   // denormalized — preserved even if category renamed/deleted
            $table->decimal('amount', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_extra_costs');
        Schema::dropIfExists('extra_cost_categories');
    }
};
