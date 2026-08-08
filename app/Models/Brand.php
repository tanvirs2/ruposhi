<?php

namespace App\Models;

use App\Traits\HasShopScope;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use HasShopScope;

    protected $fillable = ['name', 'description', 'created_by'];

    public function items()
    {
        return $this->hasMany(Item::class);
    }
}
