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
        Schema::create('sale_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_id');
            $table->string('action');                  // 'edited' | 'deleted'
            $table->unsignedBigInteger('user_id')->nullable();
            $table->json('snapshot');                  // full sale + items before the action
            $table->text('note')->nullable();           // edit_note or delete reason
            $table->timestamps();
            $table->index(['sale_id', 'action']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_logs');
    }
};
