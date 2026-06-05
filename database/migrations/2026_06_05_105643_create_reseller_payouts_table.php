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
        Schema::create('reseller_payouts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reseller_id');   // user.id of the reseller
            $table->unsignedBigInteger('recorded_by');   // root user.id
            $table->decimal('amount', 10, 2);
            $table->string('payment_method')->default('cash');
            $table->string('transaction_id')->nullable();
            $table->date('paid_date');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('reseller_id');
            $table->index('paid_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reseller_payouts');
    }
};
