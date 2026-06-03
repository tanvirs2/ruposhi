<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to   = $request->get('to',   now()->toDateString());

        $shops = Shop::with('users')->get();

        // Per-shop aggregated stats for the date range
        $shopStats = [];
        foreach ($shops as $shop) {
            $sales = DB::table('sales')
                ->where('shop_id', $shop->id)
                ->whereBetween('sale_date', [$from, $to])
                ->selectRaw('COUNT(*) as count,
                             COALESCE(SUM(total_amount),0) as revenue,
                             COALESCE(SUM(paid_amount),0)  as collected,
                             COALESCE(SUM(due_amount),0)   as sale_due')
                ->first();

            $purchases = DB::table('purchases')
                ->where('shop_id', $shop->id)
                ->whereBetween('purchase_date', [$from, $to])
                ->selectRaw('COUNT(*) as count, COALESCE(SUM(total_amount),0) as cost')
                ->first();

            $expenses = DB::table('extra_expenses')
                ->where('shop_id', $shop->id)
                ->whereBetween('expense_date', [$from, $to])
                ->sum('amount') ?? 0;

            $customerDue = DB::table('customers')
                ->where('shop_id', $shop->id)
                ->sum('due_amount') ?? 0;

            $supplierDue = DB::table('suppliers')
                ->where('shop_id', $shop->id)
                ->sum('due_amount') ?? 0;

            $revenue  = (float) ($sales->revenue ?? 0);
            $cost     = (float) ($purchases->cost ?? 0);
            $exp      = (float) $expenses;

            $shopStats[$shop->id] = [
                'sales_count'    => (int)   ($sales->count    ?? 0),
                'revenue'        => $revenue,
                'collected'      => (float) ($sales->collected ?? 0),
                'sale_due'       => (float) ($sales->sale_due  ?? 0),
                'purchase_cost'  => $cost,
                'purchase_count' => (int)   ($purchases->count ?? 0),
                'expenses'       => $exp,
                'customer_due'   => (float) $customerDue,
                'supplier_due'   => (float) $supplierDue,
                'gross_profit'   => $revenue - $cost,
                'net_profit'     => $revenue - $cost - $exp,
                'staff_count'    => $shop->users->count(),
            ];
        }

        // Grand totals across all shops
        $totals = [
            'revenue'       => array_sum(array_column($shopStats, 'revenue')),
            'collected'     => array_sum(array_column($shopStats, 'collected')),
            'sale_due'      => array_sum(array_column($shopStats, 'sale_due')),
            'purchase_cost' => array_sum(array_column($shopStats, 'purchase_cost')),
            'expenses'      => array_sum(array_column($shopStats, 'expenses')),
            'gross_profit'  => array_sum(array_column($shopStats, 'gross_profit')),
            'net_profit'    => array_sum(array_column($shopStats, 'net_profit')),
            'sales_count'   => array_sum(array_column($shopStats, 'sales_count')),
            'customer_due'  => array_sum(array_column($shopStats, 'customer_due')),
            'supplier_due'  => array_sum(array_column($shopStats, 'supplier_due')),
        ];

        return view('super.reports.index', compact('shops', 'shopStats', 'totals', 'from', 'to'));
    }
}
