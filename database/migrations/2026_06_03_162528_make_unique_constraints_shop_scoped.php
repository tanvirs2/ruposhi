<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * These columns were globally unique in v1. In multi-shop they must be
     * unique *per shop* — two shops may legitimately reuse the same code/name/key.
     * (users.email and jobs.uuid stay globally unique on purpose.)
     */
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->unique(['shop_id', 'code']);
        });

        Schema::table('store_config', function (Blueprint $table) {
            $table->dropUnique(['key']);
            $table->unique(['shop_id', 'key']);
        });

        Schema::table('extra_cost_categories', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->unique(['shop_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropUnique(['shop_id', 'code']);
            $table->unique(['code']);
        });

        Schema::table('store_config', function (Blueprint $table) {
            $table->dropUnique(['shop_id', 'key']);
            $table->unique(['key']);
        });

        Schema::table('extra_cost_categories', function (Blueprint $table) {
            $table->dropUnique(['shop_id', 'name']);
            $table->unique(['name']);
        });
    }
};
