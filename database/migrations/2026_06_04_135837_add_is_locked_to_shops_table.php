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
        Schema::table('shops', function (Blueprint $table) {
            // System-controlled lock — triggered by license downgrade.
            // is_active = owner choice (turn shop on/off manually)
            // is_locked = system choice (license limit exceeded — data preserved, access blocked)
            $table->boolean('is_locked')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn('is_locked');
        });
    }
};
