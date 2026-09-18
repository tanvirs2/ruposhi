<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

/**
 * ক্যাশ মেমোর প্রিন্ট-লগ (নিরাপত্তা)।
 *
 * দোকানের স্টাফ একই মেমোর বাড়তি কপি প্রিন্ট করে সেটা দিয়ে মাল ডেলিভারি
 * করে দিতে পারে — কাগজটা আসল মেমোর মতোই দেখায়, অথচ খাতায় দ্বিতীয় বিক্রয়
 * নেই। ব্রাউজার থেকে প্রিন্ট করা পুরোপুরি আটকানো সম্ভব নয় (Ctrl+P, PDF
 * সেভ, ফটোকপি), তাই প্রতিটা প্রিন্ট এখানে রেকর্ড হয়: কে, কখন, কোন মেমোর
 * কত নম্বর কপি। দ্বিতীয় কপি থেকে মেমোর উপরে "পুনঃমুদ্রণ — কপি নং X"
 * ছাপা হয়, ফলে বাড়তি কপি দেখেই আলাদা চেনা যায়।
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_prints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            // মেমো মুছে ফেললে তার প্রিন্ট-লগও যায় — লগটা মেমোর ইতিহাস,
            // মুছে ফেলার আলাদা হিসাব sale_logs-এ থাকে।
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('copy_no');   // ১ = মূল কপি, ২+ = পুনঃমুদ্রণ
            $table->timestamp('printed_at');
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index(['sale_id', 'copy_no']);
            $table->index(['shop_id', 'printed_at']);
            $table->index('copy_no');            // হিস্টরি পেজ শুধু copy_no >= 2 দেখায়
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_prints');
    }
};
