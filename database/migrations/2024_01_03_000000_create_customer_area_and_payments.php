<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Customer areas / zones
        Schema::create('customer_areas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // Add area_id to customers
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('area_id')->nullable()->after('address')
                  ->constrained('customer_areas')->nullOnDelete();
        });

        // Customer payments (recording payments against dues)
        Schema::create('customer_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('payment_date');
            $table->string('payment_method')->default('নগদ'); // নগদ / ব্যাংক / বিকাশ
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('area_id');
        });
        Schema::dropIfExists('customer_payments');
        Schema::dropIfExists('customer_areas');
    }
};
