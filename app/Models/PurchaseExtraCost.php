<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseExtraCost extends Model
{
    protected $fillable = ['purchase_id', 'category_name', 'amount'];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }
}
