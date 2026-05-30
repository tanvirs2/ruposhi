<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleLog extends Model
{
    protected $fillable = ['sale_id', 'action', 'user_id', 'snapshot', 'note'];
    protected $casts    = ['snapshot' => 'array'];

    public function user() { return $this->belongsTo(User::class); }
}
