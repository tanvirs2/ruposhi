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
        $shops      = Shop::withCount('users')->get();
        $totalShops = $shops->count();
        $totalUsers = User::whereNotNull('shop_id')->count();

        // Aggregate totals across all shops (this month)
        $thisMonth  = now()->startOfMonth()->toDateString();
        $today      = now()->toDateString();

        $monthRevenue = DB::table('sales')
            ->whereBetween('sale_date', [$thisMonth, $today])
            ->sum('total_amount') ?? 0;

        $totalCustomerDue = DB::table('customers')->sum('due_amount') ?? 0;

        // Per-shop sales summary
        $shopSales = Shop::withCount('users')
            ->withSum('sales as total_revenue', 'total_amount')
            ->withCount('sales as sales_count')
            ->latest()
            ->get();

        return view('super.dashboard', compact(
            'shops', 'totalShops', 'totalUsers',
            'shopSales', 'monthRevenue', 'totalCustomerDue'
        ));
    }
}
