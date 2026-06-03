<?php

namespace App\Models;

use App\Traits\HasShopScope;
use Illuminate\Database\Eloquent\Model;

class SaleExtraCost extends Model
{
    use HasShopScope;

    protected $fillable = ['sale_id', 'category_name', 'amount'];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
