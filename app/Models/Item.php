<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = ['name', 'code', 'category_id', 'type_id', 'brand_id', 'unit_type_id', 'purchase_price', 'sale_price', 'unit', 'description', 'image'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function itemType()
    {
        return $this->belongsTo(ItemType::class, 'type_id');
    }

    public function itemBrand()
    {
        return $this->belongsTo(ItemBrand::class, 'brand_id');
    }

    public function unitType()
    {
        return $this->belongsTo(UnitType::class, 'unit_type_id');
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
