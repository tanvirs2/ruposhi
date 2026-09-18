<?php

namespace App\Models;

use App\Traits\HasShopScope;
use Illuminate\Database\Eloquent\Model;

class SalePrint extends Model
{
    use HasShopScope;

    protected $fillable = ['sale_id', 'user_id', 'copy_no', 'printed_at', 'ip'];
    protected $casts    = ['printed_at' => 'datetime'];

    public function sale() { return $this->belongsTo(Sale::class); }
    public function user() { return $this->belongsTo(User::class); }

    /** ১ম কপি মূল মেমো; ২ নম্বর থেকে সবই পুনঃমুদ্রণ */
    public function isReprint(): bool
    {
        return $this->copy_no > 1;
    }
}
