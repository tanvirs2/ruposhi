<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

/**
 * কাস্টমারের "পুরনো বাকী" / সাপ্লায়ারের "পুরনো দেনা" (opening_balance)
 * বদলানোর অডিট-লগ।
 *
 * opening_balance মোট বাকীর সূত্রে সরাসরি যোগ হয়, তাই এটা চুপচাপ বদলালে
 * হিসাব উল্টাপাল্টা হয়ে যায় অথচ কোথাও ধরা পড়ে না। এখন প্রতিটা পরিবর্তন
 * এখানে জমা হয় — আগের মান, নতুন মান, কে, কখন, কেন — আর লেজারে দেখায়।
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opening_balance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('party_type', 20);            // 'customer' | 'supplier'
            $table->unsignedBigInteger('party_id');      // কাস্টমার/সাপ্লায়ার মুছলেও ইতিহাস থাকে, তাই FK নয়
            $table->decimal('old_value', 15, 2);
            $table->decimal('new_value', 15, 2);
            $table->string('reason', 500)->nullable();   // লেনদেন শুরুর পর বাধ্যতামূলক (কন্ট্রোলারে)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['shop_id', 'party_type', 'party_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opening_balance_logs');
    }
};
