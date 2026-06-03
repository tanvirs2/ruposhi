<?php

namespace App\Models;

use App\Traits\HasShopScope;
use Illuminate\Database\Eloquent\Model;

class PurchaseExtraCost extends Model
{
    use HasShopScope;

    protected $fillable = ['purchase_id', 'category_name', 'amount'];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }
}
