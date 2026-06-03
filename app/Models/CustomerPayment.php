<?php

namespace App\Models;

use App\Traits\HasShopScope;
use Illuminate\Database\Eloquent\Model;

class CustomerPayment extends Model
{
    use HasShopScope;

    protected $fillable = ['customer_id', 'user_id', 'amount', 'previous_due', 'payment_date', 'payment_method', 'notes'];

    protected $casts = ['payment_date' => 'date'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
