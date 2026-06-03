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

        // super_admin sees all shops — no filter
        if ($user->role === 'super_admin') {
            return;
        }

        // Everyone else is filtered to their own shop
        $builder->where($model->getTable() . '.shop_id', $user->shop_id);
    }
}
