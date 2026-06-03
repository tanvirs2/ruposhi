<?php

namespace App\Models;

use App\Traits\HasShopScope;
use Illuminate\Database\Eloquent\Model;

class CustomerArea extends Model
{
    use HasShopScope;

    protected $fillable = ['name'];

    public function customers()
    {
        return $this->hasMany(Customer::class, 'area_id');
    }
}
