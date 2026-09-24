<?php

namespace App\Models;

use App\Traits\HasShopScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * পুরনো বাকী/দেনা (opening_balance) বদলানোর অডিট-লগ।
 * party_type = 'customer' | 'supplier', party_id = তার id।
 */
class OpeningBalanceLog extends Model
{
    use HasShopScope;

    protected $fillable = ['party_type', 'party_id', 'old_value', 'new_value', 'reason', 'user_id'];
    protected $casts    = ['old_value' => 'float', 'new_value' => 'float'];

    public function user() { return $this->belongsTo(User::class); }

    /** একজন কাস্টমার/সাপ্লায়ারের সব পরিবর্তন, নতুনটা আগে */
    public static function forParty(string $type, int $id): Collection
    {
        return static::with('user:id,name')
            ->where('party_type', $type)
            ->where('party_id', $id)
            ->latest('id')
            ->get();
    }
}
