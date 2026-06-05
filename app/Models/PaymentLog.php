<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentLog extends Model
{
    protected $fillable = [
        'user_id',
        'license_id',
        'recorded_by',
        'reseller_id',
        'amount',
        'payment_method',
        'transaction_id',
        'payment_date',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount'       => 'decimal:2',
    ];

    /* ── Relations ──────────────────────────────────────────── */

    public function user()
    {
        return $this->belongsTo(User::class);           // super_admin who paid
    }

    public function license()
    {
        return $this->belongsTo(License::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function reseller()
    {
        return $this->belongsTo(User::class, 'reseller_id');
    }

    /* ── Helpers ─────────────────────────────────────────────── */

    public static function paymentMethods(): array
    {
        return [
            'bKash'         => 'bKash',
            'Nagad'         => 'Nagad',
            'Rocket'        => 'Rocket',
            'bank_transfer' => 'Bank Transfer',
            'cash'          => 'নগদ (Cash)',
        ];
    }

    public function getMethodLabelAttribute(): string
    {
        return self::paymentMethods()[$this->payment_method] ?? $this->payment_method;
    }
}
