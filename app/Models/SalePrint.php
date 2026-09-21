<?php

namespace App\Models;

use App\Traits\HasShopScope;
use Illuminate\Database\Eloquent\Model;

class SalePrint extends Model
{
    use HasShopScope;

    protected $fillable = ['sale_id', 'user_id', 'copy_no', 'printed_at', 'ip'];
    protected $casts    = ['printed_at' => 'datetime'];

    public function sale() { return $this->belongsTo(Sale::class); }
    public function user() { return $this->belongsTo(User::class); }

    /** ১ম কপি মূল মেমো; ২ নম্বর থেকে সবই পুনঃমুদ্রণ */
    public function isReprint(): bool
    {
        return $this->copy_no > 1;
    }

    /**
     * "পুনঃমুদ্রণের ব্যাজ শেষ কবে দেখা হয়েছে" — এই সময়টা রাখার
     * `store_config` কী, **প্রতি ইউজারে আলাদা**।
     *
     * ⚠️ শপ-ভিত্তিক একটা কী রাখলে এক অ্যাডমিন হিস্টরি খুললেই বাকি সব
     * অ্যাডমিনের ব্যাজ মুছে যেত — যে স্টাফের বাড়তি কপি ধরার জন্য পুরো
     * ফিচার, সেটাই অন্য অ্যাডমিনদের চোখে না পড়েই "দেখা হয়েছে" হয়ে যেত।
     * store_config-এর unique key `(shop_id, key)`, তাই ইউজার আইডি কী-তে
     * জুড়ে দিলেই প্রতি শপে প্রতি অ্যাডমিনের নিজের হিসাব থাকে।
     */
    public static function seenKey(int $userId): string
    {
        return 'reprint_alert_seen_at_' . $userId;
    }
}
