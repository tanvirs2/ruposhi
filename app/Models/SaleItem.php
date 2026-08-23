<?php

namespace App\Models;

use App\Traits\HasShopScope;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    use HasShopScope;

    public $timestamps = false;
    protected $fillable = ['sale_id', 'item_id', 'quantity', 'price', 'cost_price', 'subtotal'];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
