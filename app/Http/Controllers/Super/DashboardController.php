<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $superAdminId = auth()->id();

        // Only this super_admin's shops
        $myShopIds = Shop::where('super_admin_id', $superAdminId)->pluck('id');

        $shops      = Shop::whereIn('id', $myShopIds)->withCount('users')->get();
        $totalShops = $shops->count();
        $totalUsers = User::whereIn('shop_id', $myShopIds)->count();

        // Revenue & dues scoped to this super_admin's shops
        $thisMonth = now()->startOfMonth()->toDateString();
        $today     = now()->toDateString();

        $monthRevenue = DB::table('sales')
            ->whereIn('shop_id', $myShopIds)
            ->whereBetween('sale_date', [$thisMonth, $today])
            ->sum('total_amount') ?? 0;

        $totalCustomerDue = DB::table('customers')
            ->whereIn('shop_id', $myShopIds)
            ->sum('due_amount') ?? 0;

        // Per-shop sales summary
        $shopSales = Shop::whereIn('id', $myShopIds)
            ->withCount('users')
            ->withSum('sales as total_revenue', 'total_amount')
            ->withCount('sales as sales_count')
            ->latest()
            ->get();

        // License info for the "add branch" button
        $license   = auth()->user()->activeLicense();
        $shopCount = $totalShops;

        return view('super.dashboard', compact(
            'shops', 'totalShops', 'totalUsers',
            'shopSales', 'monthRevenue', 'totalCustomerDue',
            'license', 'shopCount'
        ));
    }
}
