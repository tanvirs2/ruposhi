<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = ['name', 'phone', 'email', 'position', 'salary', 'join_date', 'address', 'status'];

    protected $casts = ['join_date' => 'date'];
}
