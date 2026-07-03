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
        Schema::create('receipt_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shop_id')->index();
            $table->string('name');
            $table->string('store_name');
            $table->string('store_owner')->nullable();
            $table->string('store_tagline')->nullable();
            $table->string('store_phone')->nullable();
            $table->string('store_phone2')->nullable();
            $table->text('store_address')->nullable();
            $table->string('currency', 10)->default('৳');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipt_profiles');
    }
};
