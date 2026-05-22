<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('item_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('item_brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('unit_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');       // e.g. পিস, কেজি, বস্তা, লিটার
            $table->string('short')->nullable(); // e.g. pcs, kg, bag, ltr
            $table->timestamps();
        });

        Schema::table('items', function (Blueprint $table) {
            $table->foreignId('type_id')->nullable()->after('category_id')->constrained('item_types')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->after('type_id')->constrained('item_brands')->nullOnDelete();
            $table->foreignId('unit_type_id')->nullable()->after('brand_id')->constrained('unit_types')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropForeign(['type_id']);
            $table->dropForeign(['brand_id']);
            $table->dropForeign(['unit_type_id']);
            $table->dropColumn(['type_id', 'brand_id', 'unit_type_id']);
        });
        Schema::dropIfExists('unit_types');
        Schema::dropIfExists('item_brands');
        Schema::dropIfExists('item_types');
    }
};
