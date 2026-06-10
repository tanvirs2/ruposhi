<?php

namespace App\Models;

use App\Traits\HasShopScope;
use Illuminate\Database\Eloquent\Model;

class PurchaseDeposit extends Model
{
    use HasShopScope;

    protected $table = 'purchase_deposits';

    protected $fillable = ['purchase_id', 'category_name', 'amount'];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }
}
