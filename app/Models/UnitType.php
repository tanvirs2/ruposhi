<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitType extends Model
{
    protected $fillable = ['name', 'short'];

    public function items()
    {
        return $this->hasMany(Item::class, 'unit_type_id');
    }
}
