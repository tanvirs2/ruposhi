<?php

namespace App\Models;

use App\Traits\HasShopScope;
use Illuminate\Database\Eloquent\Model;

class ItemFavorite extends Model
{
    use HasShopScope;

    protected $fillable = ['user_id', 'item_id'];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
