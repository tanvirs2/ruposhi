<?php

namespace App\Models;

use App\Traits\HasShopScope;
use Illuminate\Database\Eloquent\Model;

class SupplierPayment extends Model
{
    use HasShopScope;

    protected $fillable = ['supplier_id', 'user_id', 'amount', 'payment_date', 'payment_method', 'notes'];

    protected $casts = ['payment_date' => 'date'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
