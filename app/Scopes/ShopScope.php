<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ShopScope implements Scope
{
    /**
     * Automatically filter all queries by the logged-in user's shop_id.
     * Super admins (shop_id = null) bypass the filter and see everything.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (!auth()->check()) {
            return;
        }

        $user = auth()->user();

        // Super admin with NO active shop sees everything (control panel).
        // When a super admin "enters" a shop, SetShopScope sets shop_id in
        // memory for the request — then they are filtered exactly like that
        // shop's own admin.
        if ($user->role === 'super_admin' && empty($user->shop_id)) {
            return;
        }

        // Everyone else is filtered to their own shop
        $builder->where($model->getTable() . '.shop_id', $user->shop_id);
    }
}
