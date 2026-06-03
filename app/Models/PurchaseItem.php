<?php

namespace App\Models;

use App\Traits\HasShopScope;
use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    use HasShopScope;

    public $timestamps = false;
    protected $fillable = ['purchase_id', 'item_id', 'quantity', 'price', 'subtotal'];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
