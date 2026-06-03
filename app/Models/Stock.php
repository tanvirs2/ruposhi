<?php

namespace App\Models;

use App\Traits\HasShopScope;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasShopScope;

    protected $table = 'stock';
    protected $fillable = ['item_id', 'quantity', 'min_quantity'];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function isLow(): bool
    {
        return $this->quantity <= $this->min_quantity;
    }
}
