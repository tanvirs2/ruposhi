<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SalePrint;
use Illuminate\Http\Request;
use Illuminate\Database\DeadlockException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * ক্যাশ মেমোর প্রিন্ট-লগ।
 *
 * ব্রাউজারে প্রিন্ট ডায়ালগ খোলার সাথে সাথে (beforeprint — Ctrl+P সহ) এই
 * এন্ডপয়েন্টে একটা রেকর্ড জমা হয়। প্রথম কপি মূল মেমো, ২ নম্বর থেকে সবই
 * পুনঃমুদ্রণ — সেগুলোই হিস্টরি পেজে দেখানো হয়।
 */
class SalePrintController extends Controller
{
    /**
     * একই প্রিন্টে দুইবার ইভেন্ট এলে এই সময়ের ভেতরের রেকর্ডটাই ফেরত দেওয়া হয়।
     * ইচ্ছাকৃতভাবে ছোট রাখা — ডুপ্লিকেট ইভেন্ট একই মুহূর্তে আসে, আর সময়টা
     * বড় হলে সত্যিকারের দুইবার প্রিন্ট (যেটা ধরাই আসল উদ্দেশ্য) এক কপি
     * হিসেবে গোনা হত।
     */
    private const DEBOUNCE_SECONDS = 3;

    /** কপি নম্বরে সংঘর্ষ হলে সর্বোচ্চ কতবার আবার চেষ্টা করা হবে */
    private const MAX_ATTEMPTS = 5;

    public function store(Request $request, Sale $sale)
    {
        // Route model binding-এ ShopScope চলে, তাই অন্য শপের মেমো এমনিতেই 404।
        //
        // ⚠️ `(sale_id, copy_no)`-তে unique কনস্ট্রেইন্ট আছে, আর সেটাই একমাত্র
        // নির্ভরযোগ্য গার্ড: টেবিলে ওই মেমোর কোনো সারি না থাকলে নিচের
        // `max(...) lockForUpdate()` কিছুই লক করে না (InnoDB খালি রেঞ্জে
        // সারি-লক নেয় না), তাই একদম প্রথম দুইটা প্রিন্ট ঠিক একসাথে হলে
        // দুইজনেই `copy_no = 1` পেয়ে যেত — দুইটা কাগজ, একটাতেও পুনঃমুদ্রণ
        // সীল নেই। সংঘর্ষ হলে DB রিজেক্ট করে, আর আমরা পরের নম্বর নিয়ে
        // আবার চেষ্টা করি।
        // DeadlockException-ও ধরা হয়: unique ইনডেক্সে ঢোকার সময় InnoDB
        // gap lock নেয়, আর কয়েকজন একসাথে একই মেমো প্রিন্ট করলে দুইটা
        // ট্রানজেকশন উল্টো ক্রমে লক চাইলে ডেডলক হতে পারে (৬ জনের সমান্তরাল
        // টেস্টে বাস্তবেই হয়েছে — তখন প্রিন্ট ৫০০ এরর দিত আর লগও হত না)।
        // দুই ক্ষেত্রেই সমাধান এক: আবার চেষ্টা করা।
        for ($attempt = 1; ; $attempt++) {
            try {
                $print = DB::transaction(fn() => $this->recordPrint($request, $sale));
                break;
            } catch (UniqueConstraintViolationException|DeadlockException $e) {
                if ($attempt >= self::MAX_ATTEMPTS) throw $e;
                // অন্য ট্রানজেকশনটা শেষ হওয়ার একটু সময় দিয়ে আবার — নইলে
                // সবাই একই মুহূর্তে ফিরে এসে আবার সংঘর্ষ বাধাত। প্রতিবার
                // অপেক্ষা বাড়ে, সাথে সামান্য এলোমেলো (jitter)।
                usleep($attempt * 20_000 + random_int(0, 20_000));
                // পরের চেষ্টায় max() নতুন করে পড়া হবে — এর মধ্যে অন্যজনের
                // সারি কমিট হয়ে গেছে, তাই নম্বরটা এগিয়ে যাবে।
            }
        }

        return response()->json([
            'copy_no' => $print->copy_no,
            'reprint' => $print->copy_no > 1,
        ]);
    }

    /** একটা প্রিন্ট রেকর্ড করে — ট্রানজেকশনের ভেতরে চলে */
    private function recordPrint(Request $request, Sale $sale): SalePrint
    {
        // একই ইউজার একই মেমোর জন্য কয়েক সেকেন্ডের ভেতরে আবার ইভেন্ট
        // পাঠালে (Chrome প্রিন্ট-প্রিভিউ কখনো beforeprint দুইবার ছোড়ে,
        // ডায়ালগ বন্ধ করে সাথে সাথে আবার খুললেও একই ব্যাপার) নতুন কপি
        // নম্বর দেওয়া হয় না — নইলে একবার প্রিন্টেই "কপি ২" হয়ে যেত।
        $recent = SalePrint::where('sale_id', $sale->id)
            ->where('user_id', auth()->id())
            ->where('printed_at', '>=', now()->subSeconds(self::DEBOUNCE_SECONDS))
            ->latest('id')
            ->first();

        if ($recent) return $recent;

        // দুইজন একসাথে প্রিন্ট করলে দুইজনেই একই max পড়ে একই কপি নম্বর
        // পেয়ে যেত — অন্তত একটা সারি থাকলে lockForUpdate সেটা সিরিয়ালাইজ
        // করে; খালি টেবিলের কেসটা উপরের unique কনস্ট্রেইন্ট সামলায়।
        $lastCopy = (int) SalePrint::where('sale_id', $sale->id)
            ->lockForUpdate()
            ->max('copy_no');

        return SalePrint::create([
            'sale_id'    => $sale->id,
            'user_id'    => auth()->id(),
            'copy_no'    => $lastCopy + 1,
            'printed_at' => now(),
            'ip'         => $request->ip(),
        ]);
    }
}
