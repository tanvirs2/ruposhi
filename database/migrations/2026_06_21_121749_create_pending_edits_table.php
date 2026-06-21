<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_edits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shop_id');
            $table->string('model_type');           // 'sale' | 'purchase'
            $table->unsignedBigInteger('model_id');
            $table->unsignedBigInteger('requested_by');
            $table->json('original_data');
            $table->json('proposed_data');
            $table->boolean('has_changes')->default(true);
            // pending | approved | rejected | superseded | no_change
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('decided_by')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->boolean('sms_sent')->default(false);
            $table->timestamps();

            $table->index(['model_type', 'model_id', 'status']);
            $table->index('shop_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_edits');
    }
};
