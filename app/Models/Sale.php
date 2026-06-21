<?php

namespace App\Models;

use App\Traits\HasShopScope;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasShopScope;

    protected $fillable = ['customer_id', 'user_id', 'total_amount', 'discount', 'extra_cost', 'paid_amount', 'due_amount', 'previous_due', 'status', 'payment_method', 'notes', 'sale_date', 'is_edited', 'edit_note', 'delete_requested_at', 'delete_requested_by'];

    protected $casts = ['sale_date' => 'date', 'delete_requested_at' => 'datetime'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
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
        return $this->hasMany(SaleItem::class);
    }

    public function extraCosts()
    {
        return $this->hasMany(SaleExtraCost::class);
    }
}
