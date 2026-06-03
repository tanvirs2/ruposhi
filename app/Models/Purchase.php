<?php

namespace App\Models;

use App\Traits\HasShopScope;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasShopScope;

    protected $fillable = ['supplier_id', 'user_id', 'total_amount', 'extra_cost', 'paid_amount', 'due_amount', 'payment_method', 'notes', 'purchase_date'];

    protected $casts = ['purchase_date' => 'date'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function extraCosts()
    {
        return $this->hasMany(PurchaseExtraCost::class);
    }
}
