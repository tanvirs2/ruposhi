<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResellerPayout extends Model
{
    protected $fillable = [
        'reseller_id', 'recorded_by', 'amount',
        'payment_method', 'transaction_id', 'paid_date', 'notes',
    ];

    protected $casts = ['paid_date' => 'date'];

    public function reseller()   { return $this->belongsTo(User::class, 'reseller_id'); }
    public function recordedBy() { return $this->belongsTo(User::class, 'recorded_by'); }

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
}
