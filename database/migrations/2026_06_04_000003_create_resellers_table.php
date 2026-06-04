<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resellers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();

            // Can this reseller extend licenses for their own clients?
            $table->boolean('can_extend_license')->default(true);

            // Max number of super_admin accounts they can create (null = unlimited)
            $table->unsignedInteger('max_shops')->nullable();

            // Commission percentage (optional, for tracking)
            $table->decimal('commission_rate', 5, 2)->default(0);

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resellers');
    }
};
