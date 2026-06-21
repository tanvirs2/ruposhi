<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->timestamp('delete_requested_at')->nullable()->after('is_edited');
            $table->unsignedBigInteger('delete_requested_by')->nullable()->after('delete_requested_at');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->timestamp('delete_requested_at')->nullable()->after('is_edited');
            $table->unsignedBigInteger('delete_requested_by')->nullable()->after('delete_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['delete_requested_at', 'delete_requested_by']);
        });
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['delete_requested_at', 'delete_requested_by']);
        });
    }
};
