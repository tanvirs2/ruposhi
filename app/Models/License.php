<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    protected $fillable = [
        'user_id',
        'reseller_id',
        'plan',
        'starts_at',
        'expires_at',
        'grace_ends_at',
        'extended_by',
        'extended_at',
        'notes',
    ];

    protected $casts = [
        'starts_at'    => 'datetime',
        'expires_at'   => 'datetime',
        'grace_ends_at'=> 'datetime',
        'extended_at'  => 'datetime',
    ];

    /* ── Relations ──────────────────────────────────────────── */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reseller()
    {
        return $this->belongsTo(User::class, 'reseller_id');
    }

    public function extendedBy()
    {
        return $this->belongsTo(User::class, 'extended_by');
    }

    /* ── Status Accessors ───────────────────────────────────── */

    /**
     * Computed status based on current time:
     *   active   → expires_at > now + 14 days
     *   warning  → expires_at between now and now + 14 days
     *   grace    → expired but grace_ends_at > now
     *   expired  → grace_ends_at < now  (LOCK)
     */
    public function getStatusAttribute(): string
    {
        $now = Carbon::now();

        if ($this->expires_at > $now->copy()->addDays(14)) {
            return 'active';
        }

        if ($this->expires_at > $now) {
            return 'warning'; // within 14-day warning window
        }

        if ($this->grace_ends_at > $now) {
            return 'grace'; // expired but in grace period
        }

        return 'expired'; // fully locked
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'warning']);
    }

    public function isInGrace(): bool
    {
        return $this->status === 'grace';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }

    public function isLocked(): bool
    {
        return $this->status === 'expired';
    }

    /** Days remaining until expires_at (negative if already expired) */
    public function daysUntilExpiry(): int
    {
        return (int) Carbon::now()->diffInDays($this->expires_at, false);
    }

    /** Days remaining in grace period */
    public function graceDaysLeft(): int
    {
        return (int) Carbon::now()->diffInDays($this->grace_ends_at, false);
    }

    /* ── Scopes ─────────────────────────────────────────────── */

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId)->latest('expires_at');
    }

    /* ── Helpers ─────────────────────────────────────────────── */

    /**
     * Extend this license by N days from the CURRENT expires_at.
     * Grace period always = expires_at + 7 days.
     */
    public function extendByDays(int $days, int $extendedById): void
    {
        $newExpiry = $this->expires_at->copy()->addDays($days);

        $this->update([
            'expires_at'    => $newExpiry,
            'grace_ends_at' => $newExpiry->copy()->addDays(7),
            'extended_by'   => $extendedById,
            'extended_at'   => Carbon::now(),
        ]);
    }

    /**
     * Extend from current date (if already expired, reset from today).
     */
    public function extendFromToday(int $days, int $extendedById): void
    {
        $base      = Carbon::now()->max($this->expires_at); // whichever is later
        $newExpiry = $base->copy()->addDays($days);

        $this->update([
            'expires_at'    => $newExpiry,
            'grace_ends_at' => $newExpiry->copy()->addDays(7),
            'extended_by'   => $extendedById,
            'extended_at'   => Carbon::now(),
        ]);
    }

    /* ── Plan helpers ────────────────────────────────────────── */

    public static function daysForPlan(string $plan): int
    {
        return match($plan) {
            'monthly'   => 30,
            'quarterly' => 90,
            'yearly'    => 365,
            default     => 30,
        };
    }
}
