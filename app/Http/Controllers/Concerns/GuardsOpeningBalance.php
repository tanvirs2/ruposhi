<?php

namespace App\Http\Controllers\Concerns;

use App\Models\OpeningBalanceLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * কাস্টমারের "পুরনো বাকী" / সাপ্লায়ারের "পুরনো দেনা" বদলানোর নিয়ম।
 *
 * opening_balance মোট বাকীর সূত্রে সরাসরি যোগ হয়, তাই চুপচাপ বদলালে হিসাব
 * উল্টাপাল্টা হয়ে যেত। নিয়ম:
 *  - লেনদেন শুরুর আগে: যে কেউ বদলাতে পারে (খাতা থেকে তোলার সময়ের ভুল শোধরাতে)
 *  - লেনদেন শুরুর পরে: শুধু অ্যাডমিন (canManageShop), আর কারণ লেখা বাধ্যতামূলক
 *  - প্রতিটা পরিবর্তন opening_balance_logs-এ জমা হয়, লেজারে দেখায়
 */
trait GuardsOpeningBalance
{
    /** পরিবর্তন অনুমোদিত না হলে ফেরত পাঠানোর রেসপন্স, নইলে null */
    protected function guardOpeningBalance(Request $request, float $old, bool $hasTransactions, string $label): ?RedirectResponse
    {
        $new = (float) ($request->opening_balance ?? 0);
        if (abs($old - $new) < 0.005 || !$hasTransactions) return null;

        if (!auth()->user()->canManageShop()) {
            return back()->withInput()->withErrors([
                'opening_balance' => "লেনদেন শুরু হয়ে গেছে — {$label} এখন শুধু অ্যাডমিন বদলাতে পারেন।",
            ]);
        }

        $request->validate(
            ['opening_balance_reason' => 'required|string|max:500'],
            ['opening_balance_reason.required' => "{$label} বদলানোর কারণ লিখুন — লেজারে রেকর্ড থাকবে।"]
        );

        return null;
    }

    /** মান বদলালে অডিট-লগে জমা করে */
    protected function logOpeningBalance(Request $request, string $type, int $partyId, float $old, float $new): void
    {
        if (abs($old - $new) < 0.005) return;

        OpeningBalanceLog::create([
            'party_type' => $type,
            'party_id'   => $partyId,
            'old_value'  => $old,
            'new_value'  => $new,
            'reason'     => trim((string) $request->opening_balance_reason) ?: null,
            'user_id'    => auth()->id(),
        ]);
    }
}
