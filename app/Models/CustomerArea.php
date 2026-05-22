<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerArea extends Model
{
    protected $fillable = ['name'];

    public function customers()
    {
        return $this->hasMany(Customer::class, 'area_id');
    }
}
