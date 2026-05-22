<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('extra_expenses', function (Blueprint $table) {
            // 'expense' = money out, 'deposit' = money in
            $table->string('type')->default('expense')->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('extra_expenses', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
