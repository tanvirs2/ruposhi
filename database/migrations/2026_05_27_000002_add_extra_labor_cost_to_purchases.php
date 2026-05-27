<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('extra_cost', 12, 2)->default(0)->after('total_amount');
            $table->decimal('labor_cost', 12, 2)->default(0)->after('extra_cost');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['extra_cost', 'labor_cost']);
        });
    }
};
