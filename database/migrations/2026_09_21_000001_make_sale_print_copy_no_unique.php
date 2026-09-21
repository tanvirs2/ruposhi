<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;

/**
 * একই মেমোতে দুইবার একই কপি নম্বর ঠেকানো।
 *
 * SalePrintController কপি নম্বর বের করে `max(copy_no) + 1` দিয়ে, আর
 * `lockForUpdate()` দিয়ে সেটা সিরিয়ালাইজ করার চেষ্টা করে। কিন্তু InnoDB-তে
 * কোনো সারি না থাকলে `SELECT max(...) FOR UPDATE` সারি-লক নেয় না — তাই একটা
 * মেমোর একদম প্রথম দুইটা প্রিন্ট ঠিক একই মুহূর্তে হলে দুইজনেই `max = NULL`
 * পড়ে দুইজনেই `copy_no = 1` পেয়ে যেতে পারত। ফলে দুইটা কাগজ বেরোত যার
 * একটাতেও "পুনঃমুদ্রণ" সীল নেই — ঠিক যেটা ধরার জন্য পুরো ফিচার।
 *
 * ডাটাবেসের unique কনস্ট্রেইন্টই এর একমাত্র নির্ভরযোগ্য গার্ড; কন্ট্রোলার
 * এখন সংঘর্ষ হলে ধরে নিয়ে পরের নম্বর দিয়ে আবার চেষ্টা করে।
 */
return new class extends Migration
{
    public function up(): void
    {
        // পুরনো ডেটায় ডুপ্লিকেট থাকলে unique ইনডেক্স বসানো যাবে না — আগে
        // সেগুলোকে নতুন নম্বর দিয়ে সরিয়ে দেওয়া হয় (রেকর্ড মোছা হয় না,
        // প্রিন্ট-লগ অডিটের জিনিস)।
        $dupes = DB::table('sale_prints')
            ->selectRaw('sale_id, copy_no, COUNT(*) as c')
            ->groupBy('sale_id', 'copy_no')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($dupes as $d) {
            $ids  = DB::table('sale_prints')
                ->where('sale_id', $d->sale_id)->where('copy_no', $d->copy_no)
                ->orderBy('id')->pluck('id')->slice(1);      // প্রথমটা থাক
            $next = (int) DB::table('sale_prints')->where('sale_id', $d->sale_id)->max('copy_no');
            foreach ($ids as $id) {
                DB::table('sale_prints')->where('id', $id)->update(['copy_no' => ++$next]);
            }
        }

        // ⚠️ ক্রম উল্টালে চলবে না। `sale_id`-এর ফরেন কী-টা তার ইনডেক্স হিসেবে
        // পুরনো `(sale_id, copy_no)` ইনডেক্সটাই ব্যবহার করছে, তাই আগে ওটা
        // ড্রপ করতে গেলে MySQL আটকে দেয় (errno 1553)। নতুন unique ইনডেক্সটাও
        // `sale_id` দিয়ে শুরু, তাই সেটা আগে বানালে FK ওটাতেই সরে যায় — তখন
        // পুরনোটা নির্ভয়ে ড্রপ করা যায়।
        Schema::table('sale_prints', function (Blueprint $table) {
            $table->unique(['sale_id', 'copy_no']);
        });
        Schema::table('sale_prints', function (Blueprint $table) {
            $table->dropIndex(['sale_id', 'copy_no']);
        });
    }

    public function down(): void
    {
        // up()-এর মতোই উল্টো ক্রমে — FK-এর জন্য সবসময় অন্তত একটা ইনডেক্স থাকে
        Schema::table('sale_prints', function (Blueprint $table) {
            $table->index(['sale_id', 'copy_no']);
        });
        Schema::table('sale_prints', function (Blueprint $table) {
            $table->dropUnique(['sale_id', 'copy_no']);
        });
    }
};
