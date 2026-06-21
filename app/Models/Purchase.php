<?php

namespace App\Models;

use App\Traits\HasShopScope;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasShopScope;

    protected $fillable = ['supplier_id', 'user_id', 'total_amount', 'extra_cost', 'paid_amount', 'deposit_amount', 'due_amount', 'payment_method', 'notes', 'purchase_date', 'delete_requested_at', 'delete_requested_by'];

    protected $casts = ['purchase_date' => 'date', 'delete_requested_at' => 'datetime'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function deleteRequestedBy()
    {
        return $this->belongsTo(User::class, 'delete_requested_by');
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function extraCosts()
    {
        return $this->hasMany(PurchaseExtraCost::class);
    }

    public function deposits()
    {
        return $this->hasMany(PurchaseDeposit::class);
    }
}
