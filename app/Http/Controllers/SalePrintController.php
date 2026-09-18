<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SalePrint;
use Illuminate\Http\Request;
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

    public function store(Request $request, Sale $sale)
    {
        // Route model binding-এ ShopScope চলে, তাই অন্য শপের মেমো এমনিতেই 404।
        $print = DB::transaction(function () use ($request, $sale) {
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
            // পেয়ে যেত — lockForUpdate সেটা সিরিয়ালাইজ করে।
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
        });

        return response()->json([
            'copy_no' => $print->copy_no,
            'reprint' => $print->copy_no > 1,
        ]);
    }
}
