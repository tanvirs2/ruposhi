<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleExtraCost extends Model
{
    protected $fillable = ['sale_id', 'category_name', 'amount'];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
