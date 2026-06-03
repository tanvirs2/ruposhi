<?php

namespace App\Traits;

use App\Scopes\ShopScope;

trait HasShopScope
{
    /**
     * Boot the trait — attach ShopScope to every query.
     */
    protected static function bootHasShopScope(): void
    {
        static::addGlobalScope(new ShopScope());

        // Auto-fill shop_id on create
        static::creating(function ($model) {
            if (auth()->check() && auth()->user()->role !== 'super_admin') {
                $model->shop_id = $model->shop_id ?? auth()->user()->shop_id;
            }
        });
    }
}
