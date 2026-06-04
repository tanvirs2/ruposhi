<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add super_admin_id to shops: each shop belongs to one super_admin
        Schema::table('shops', function (Blueprint $table) {
            $table->foreignId('super_admin_id')
                  ->nullable()
                  ->after('id')
                  ->constrained('users')
                  ->nullOnDelete();
            $table->index('super_admin_id');
        });

        // Add max_shops to licenses:
        //   null  = unlimited branches
        //   1     = basic (default) — only their first shop
        //   2+    = extended — can create that many branches
        Schema::table('licenses', function (Blueprint $table) {
            $table->unsignedSmallInteger('max_shops')
                  ->default(1)
                  ->nullable()
                  ->after('notes')
                  ->comment('null=unlimited, 1=basic, 2+=extended');
        });
    }

    public function down(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->dropColumn('max_shops');
        });

        Schema::table('shops', function (Blueprint $table) {
            $table->dropForeign(['super_admin_id']);
            $table->dropIndex(['super_admin_id']);
            $table->dropColumn('super_admin_id');
        });
    }
};
