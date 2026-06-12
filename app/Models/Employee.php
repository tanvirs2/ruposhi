<?php

namespace App\Models;

use App\Traits\HasShopScope;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasShopScope;

    protected $fillable = ['name', 'phone', 'email', 'position', 'salary', 'join_date', 'address', 'status', 'created_by'];

    protected $casts = ['join_date' => 'date'];
}
