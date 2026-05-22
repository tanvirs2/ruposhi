<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerPayment extends Model
{
    protected $fillable = ['customer_id', 'user_id', 'amount', 'payment_date', 'payment_method', 'notes'];

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
