<?php

namespace App\Models;

use App\Traits\HasShopScope;
use Illuminate\Database\Eloquent\Model;

class ReceiptProfile extends Model
{
    use HasShopScope;

    protected $fillable = [
        'name', 'store_name', 'store_owner', 'store_tagline',
        'store_phone', 'store_phone2', 'store_address', 'currency',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
