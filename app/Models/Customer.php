<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = ['name', 'proprietor', 'phone', 'email', 'address', 'area_id', 'due_amount'];

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
