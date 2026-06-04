<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();

            // The super_admin user this license belongs to
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Which reseller created/manages this license (null = root created directly)
            $table->foreignId('reseller_id')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('plan', ['monthly', 'quarterly', 'yearly', 'custom'])->default('monthly');

            $table->datetime('starts_at');
            $table->datetime('expires_at');
            $table->datetime('grace_ends_at'); // expires_at + 7 days

            // Who last extended this license
            $table->foreignId('extended_by')->nullable()->constrained('users')->nullOnDelete();
            $table->datetime('extended_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
