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
        Schema::create('day_closings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shop_id')->index();
            $table->date('close_date');
            $table->decimal('opening_cash', 14, 2)->default(0);   // দিনের শুরুতে ক্যাশবাক্সে যা ছিল
            $table->decimal('system_net', 14, 2)->default(0);     // snapshot: সেদিনের সিস্টেম-হিসাব ক্যাশ নীট
            $table->decimal('counted_cash', 14, 2)->default(0);   // হাতে গোনা ক্যাশ
            $table->decimal('discrepancy', 14, 2)->default(0);    // counted − (opening + system_net); can be negative
            $table->string('note')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();    // who reconciled
            $table->timestamps();

            $table->unique(['shop_id', 'close_date']);            // one reconciliation per shop per day
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('day_closings');
    }
};
