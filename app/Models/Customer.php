<?php

namespace App\Models;

use App\Traits\HasShopScope;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasShopScope;

    protected $fillable = ['name', 'proprietor', 'phone', 'address', 'area_id', 'due_amount', 'created_by'];

    public function area()
    {
        return $this->belongsTo(CustomerArea::class, 'area_id');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function payments()
    {
        return $this->hasMany(CustomerPayment::class);
    }
}
