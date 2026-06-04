<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reseller extends Model
{
    protected $fillable = [
        'user_id',
        'can_extend_license',
        'max_shops',
        'commission_rate',
        'notes',
    ];

    protected $casts = [
        'can_extend_license' => 'boolean',
    ];

    /* ── Relations ──────────────────────────────────────────── */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** All super_admin accounts this reseller has created */
    public function clients()
    {
        return $this->hasMany(License::class, 'reseller_id');
    }

    /* ── Helpers ─────────────────────────────────────────────── */

    /** How many super_admin accounts has this reseller created so far */
    public function shopCount(): int
    {
        return License::where('reseller_id', $this->user_id)->count();
    }

    /** Has this reseller hit their shop limit? */
    public function atLimit(): bool
    {
        if (is_null($this->max_shops)) {
            return false; // unlimited
        }
        return $this->shopCount() >= $this->max_shops;
    }
}
