<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Item;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $shopId = auth()->user()->shop_id;

        $stats = [
            'today_sales'      => Sale::whereDate('sale_date', today())->sum('total_amount'),
            'today_paid'       => Sale::whereDate('sale_date', today())->sum('paid_amount'),
            'today_due'        => Sale::whereDate('sale_date', today())->sum('due_amount'),
            'total_customer_due' => \App\Models\Customer::where('due_amount', '>', 0)->sum('due_amount'),
            'low_stock_count'  => Stock::whereRaw('quantity <= min_quantity AND quantity >= 0')->count(),
            'out_stock_count'  => Stock::where('quantity', '<=', 0)->count(),
            'customers'        => \App\Models\Customer::count(),
            'items'            => Item::count(),
        ];

        // 7-day sales trend
        $sevenDayTrend = collect(range(6, 0))->map(function ($daysAgo) {
            $date = today()->subDays($daysAgo);
            return [
                'date'  => $date->format('d/m'),
                'label' => $date->locale('bn')->isoFormat('ddd'),
                'total' => Sale::whereDate('sale_date', $date)->sum('total_amount'),
                'paid'  => Sale::whereDate('sale_date', $date)->sum('paid_amount'),
            ];
        });

        $recent_sales = Sale::with('customer')
            ->latest()
            ->take(5)
            ->get();

        $low_stock = Stock::with('item')
            ->whereRaw('quantity <= min_quantity')
            ->orderBy('quantity')
            ->take(5)
            ->get();

        return view('dashboard', compact('stats', 'recent_sales', 'low_stock', 'sevenDayTrend'));
    }

    public function userSummary()
    {
        $today = today()->toDateString();

        $rows = Sale::with('user:id,name')
            ->has('items')
            ->whereDate('sale_date', $today)
            ->get()
            ->groupBy('user_id')
            ->map(fn($grp) => [
                'name'  => $grp->first()->user?->name ?? 'অজানা',
                'count' => $grp->count(),
                'total' => $grp->sum('total_amount'),
                'paid'  => $grp->sum('paid_amount'),
                'due'   => $grp->sum('due_amount'),
            ])
            ->sortByDesc('total')
            ->values();

        $totals = [
            'count' => $rows->sum('count'),
            'total' => $rows->sum('total'),
            'paid'  => $rows->sum('paid'),
            'due'   => $rows->sum('due'),
        ];

        return response()->json(['rows' => $rows, 'totals' => $totals]);
    }
}
