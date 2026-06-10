<?php

namespace App\Models;

use App\Traits\HasShopScope;
use Illuminate\Database\Eloquent\Model;

class DepositCategory extends Model
{
    use HasShopScope;

    protected $table = 'deposit_categories';

    protected $fillable = ['name'];
}
