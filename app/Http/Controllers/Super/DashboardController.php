<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\Sale;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $shops       = Shop::withCount('users')->get();
        $totalShops  = $shops->count();
        $totalUsers  = User::whereNotNull('shop_id')->count();

        // Per-shop sales summary (bypass ShopScope for super admin)
        $shopSales = Shop::with(['sales' => function ($q) {
            $q->selectRaw('shop_id, COUNT(*) as count, SUM(total_amount) as total')
              ->groupBy('shop_id');
        }])->get();

        return view('super.dashboard', compact('shops', 'totalShops', 'totalUsers', 'shopSales'));
    }
}
