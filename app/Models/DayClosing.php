<?php

namespace App\Models;

use App\Traits\HasShopScope;
use Illuminate\Database\Eloquent\Model;

class DayClosing extends Model
{
    use HasShopScope;

    protected $fillable = [
        'shop_id', 'close_date', 'opening_cash', 'system_net',
        'counted_cash', 'discrepancy', 'note', 'user_id',
    ];

    protected $casts = [
        'close_date'   => 'date',
        'opening_cash' => 'decimal:2',
        'system_net'   => 'decimal:2',
        'counted_cash' => 'decimal:2',
        'discrepancy'  => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
