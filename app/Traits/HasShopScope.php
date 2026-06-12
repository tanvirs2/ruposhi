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

        // Auto-fill shop_id on create using the auth user's effective shop.
        // For a super admin who has "entered" a shop, SetShopScope sets that
        // shop_id in memory, so new records land in the entered shop. A super
        // admin outside any shop has a null shop_id and is skipped.
        static::creating(function ($model) {
            if (auth()->check() && auth()->user()->shop_id) {
                $model->shop_id = $model->shop_id ?? auth()->user()->shop_id;
            }
            if (auth()->check() && isset($model->getAttributes()['created_by']) === false
                && in_array('created_by', $model->getFillable())) {
                $model->created_by = $model->created_by ?? auth()->id();
            }
        });
    }
}
