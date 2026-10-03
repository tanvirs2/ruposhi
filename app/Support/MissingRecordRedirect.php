<?php

namespace App\Support;

use App\Models\PurchaseLog;
use App\Models\SaleLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * বিক্রয়/ক্রয়ের রুটে রেকর্ড না পেলে (route model binding → 404) ৪০৪ পেজের বদলে
 * তালিকায় ফেরত, কারণসহ। routes/web.php-এ `->missing()` থেকে ডাকা হয়।
 *
 * সাধারণ ঘটনা: স্টাফ ডিলিট-অনুরোধ পাঠিয়ে রেকর্ডের পেজেই আছে, অ্যাডমিন অন্য ডিভাইস
 * থেকে অনুমোদন দিল → স্টাফের পরের ক্লিক/রিফ্রেশে ৪০৪ (প্রোডাকশনে রিসিভ #১৩৯৫,
 * ২০২৬-১০-০২)। একই রেকর্ড দুই ট্যাব থেকে মোছা বা Back-এর পুরনো পেজ থেকেও হয়।
 *
 * ⚠️ লগ মডেল দুটো HasShopScope-এ — অন্য শাখার রেকর্ডের নম্বর দিলে লগ পাওয়া যায় না,
 * তাই শুধু "পাওয়া যায়নি" দেখায়; অন্য শাখার কোনো তথ্য ফাঁস হয় না।
 */
class MissingRecordRedirect
{
    public static function sale(Request $request): RedirectResponse
    {
        $id  = (int) $request->route('sale');
        $log = SaleLog::with('user:id,name')->where('sale_id', $id)
            ->where('action', 'deleted')->latest('id')->first();

        return redirect()->route('sales.index')
            ->with('error', self::message('বিক্রয় #INV-' . str_pad($id, 4, '0', STR_PAD_LEFT), $log));
    }

    public static function purchase(Request $request): RedirectResponse
    {
        $id  = (int) $request->route('purchase');
        $log = PurchaseLog::with('user:id,name')->where('purchase_id', $id)
            ->where('action', 'deleted')->latest('id')->first();

        // পরিশোধ তালিকা থেকে মোছার চেষ্টা হলে সেখানেই ফেরত (destroy()-এর মতো)
        $route = $request->input('redirect_to') === 'supplier-payments'
            ? 'supplier-payments.index'
            : 'purchases.index';

        return redirect()->route($route)
            ->with('error', self::message('রিসিভ #RCV-' . str_pad($id, 4, '0', STR_PAD_LEFT), $log));
    }

    private static function message(string $label, SaleLog|PurchaseLog|null $log): string
    {
        if (!$log) {
            return "{$label} পাওয়া যায়নি।";
        }
        $by = $log->user?->name ?? 'অজানা';
        return "{$label} মুছে ফেলা হয়েছে — {$by}, " . $log->created_at->format('d/m/Y h:i A') . '।';
    }
}
