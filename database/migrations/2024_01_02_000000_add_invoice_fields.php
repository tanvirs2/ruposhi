<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('previous_due', 12, 2)->default(0)->after('due_amount');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('proprietor')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('previous_due');
        });
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('proprietor');
        });
    }
};
