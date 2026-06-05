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
        Schema::create('payment_logs', function (Blueprint $table) {
            $table->id();

            // Who paid
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // super_admin

            // Which license (nullable — payment can be logged before license issued)
            $table->foreignId('license_id')->nullable()->constrained()->nullOnDelete();

            // Who recorded this payment
            $table->foreignId('recorded_by')->constrained('users')->cascadeOnDelete();

            // Via reseller?
            $table->foreignId('reseller_id')->nullable()->constrained('users')->nullOnDelete();

            // Payment details
            $table->decimal('amount', 10, 2);
            $table->string('payment_method');           // bKash, Nagad, bank, cash
            $table->string('transaction_id')->nullable(); // reference/trxID
            $table->date('payment_date');
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('reseller_id');
            $table->index('payment_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_logs');
    }
};
