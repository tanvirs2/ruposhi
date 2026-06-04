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
