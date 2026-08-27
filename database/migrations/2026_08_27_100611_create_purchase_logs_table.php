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
        // Mirrors sale_logs — see 2026_05_30_030721_create_sale_logs_table.php.
        // Created after the shop-scoping migrations, so shop_id + its composite
        // index are included directly instead of via a later ALTER.
        Schema::create('purchase_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained('shops')->nullOnDelete();
            $table->unsignedBigInteger('purchase_id');
            $table->string('action');                  // 'edited' | 'deleted' | 'edit_requested' | 'edit_attempted_no_change' | 'edit_rejected'
            $table->unsignedBigInteger('user_id')->nullable();
            $table->json('snapshot');                  // full purchase + items before the action
            $table->text('note')->nullable();           // edit reason or delete note
            $table->timestamps();
            $table->index(['purchase_id', 'action']);
            $table->index(['shop_id', 'created_at'], 'purchaselogs_shop_created_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_logs');
    }
};
