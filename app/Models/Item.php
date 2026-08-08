<?php

namespace App\Models;

use App\Traits\HasShopScope;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasShopScope;

    protected $fillable = ['name', 'code', 'category_id', 'brand_id', 'purchase_price', 'sale_price', 'unit', 'created_by'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function stock()
    {
        return $this->hasOne(Stock::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function getStockQuantityAttribute(): float
    {
        return $this->stock?->quantity ?? 0;
    }
}
