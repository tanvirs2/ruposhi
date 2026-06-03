<?php

namespace App\Models;

use App\Traits\HasShopScope;
use Illuminate\Database\Eloquent\Model;

class ExtraCostCategory extends Model
{
    use HasShopScope;

    protected $fillable = ['name'];
}
