<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'shop_id',
        'receipt_profile_id',
    ];

    /* ── Role helpers ──────────────────────────────────────── */

    public function isRoot(): bool
    {
        return $this->role === 'root';
    }

    public function isReseller(): bool
    {
        return $this->role === 'reseller';
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    /**
     * Can this user manage the current shop as its admin?
     * True for a real shop admin, OR a super admin who has "entered" a shop
     * (Task 5 — super admin operating inside a shop like its own admin).
     */
    public function canManageShop(): bool
    {
        return $this->role === 'admin'
            || ($this->role === 'super_admin' && session('active_shop_id'));
    }

    /* ── License helpers ────────────────────────────────────── */

    /** Active license record for this super_admin */
    public function activeLicense(): ?License
    {
        return $this->licenses()->latest('expires_at')->first();
    }

    /** Is this super_admin's subscription currently locked (fully expired)? */
    public function isSubscriptionLocked(): bool
    {
        if ($this->role !== 'super_admin') {
            return false;
        }
        $license = $this->activeLicense();
        return is_null($license) || $license->isLocked();
    }

    /* ── Relations ─────────────────────────────────────────── */

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    /** Receipt (cash memo) profile assigned to this user, if any */
    public function receiptProfile()
    {
        return $this->belongsTo(ReceiptProfile::class);
    }

    /** All shops owned by this super_admin */
    public function myShops()
    {
        return $this->hasMany(Shop::class, 'super_admin_id');
    }

    /**
     * Sync shop lock states based on current license max_shops.
     *
     * Rules:
     *   - Data is NEVER deleted — locked shops are just inaccessible.
     *   - Oldest shops (lowest ID) are protected first.
     *   - When license upgrades, extra shops auto-unlock.
     *   - null max_shops = unlimited → unlock all.
     */
    public function syncShopLocks(): void
    {
        if ($this->role !== 'super_admin') {
            return;
        }

        $license  = $this->activeLicense();
        $maxShops = $license?->max_shops; // null = unlimited

        // Get all shops ordered oldest-first (oldest = most protected)
        $shops = Shop::where('super_admin_id', $this->id)
                     ->orderBy('id')
                     ->get();

        if (is_null($maxShops)) {
            // Unlimited license → unlock everything
            Shop::where('super_admin_id', $this->id)
                ->where('is_locked', true)
                ->update(['is_locked' => false]);
            return;
        }

        // Lock/unlock based on position vs limit
        foreach ($shops as $index => $shop) {
            $shouldBeLocked = ($index + 1) > $maxShops;
            if ($shop->is_locked !== $shouldBeLocked) {
                $shop->update(['is_locked' => $shouldBeLocked]);
            }
        }
    }

    public function licenses()
    {
        return $this->hasMany(License::class);
    }

    public function resellerProfile()
    {
        return $this->hasOne(Reseller::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
