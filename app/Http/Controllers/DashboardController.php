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

        // NOTE: no caching anywhere — every value below is read fresh from the
        // DB on each load. sale_date is a DATE column, so plain equality is
        // used instead of whereDate() (date(col)=? defeats the index).
        $todayAgg = Sale::where('sale_date', today()->toDateString())
            ->selectRaw('COALESCE(SUM(total_amount),0) as total, COALESCE(SUM(paid_amount),0) as paid, COALESCE(SUM(due_amount),0) as due')
            ->first();

        $stats = [
            'today_sales'      => $todayAgg->total,
            'today_paid'       => $todayAgg->paid,
            'today_due'        => $todayAgg->due,
            'total_customer_due' => \App\Models\Customer::where('due_amount', '>', 0)->sum('due_amount'),
            'low_stock_count'  => Stock::whereRaw('quantity <= min_quantity AND quantity >= 0')->count(),
            'out_stock_count'  => Stock::where('quantity', '<=', 0)->count(),
            'customers'        => \App\Models\Customer::count(),
            'items'            => Item::count(),
        ];

        // 7-day sales trend — one grouped query instead of 14 separate ones
        $trendRaw = Sale::where('sale_date', '>=', today()->subDays(6)->toDateString())
            ->groupBy('sale_date')
            ->selectRaw('sale_date, SUM(total_amount) as total, SUM(paid_amount) as paid')
            ->get()
            ->keyBy(fn($r) => \Carbon\Carbon::parse($r->sale_date)->toDateString());

        $sevenDayTrend = collect(range(6, 0))->map(function ($daysAgo) use ($trendRaw) {
            $date = today()->subDays($daysAgo);
            $row  = $trendRaw->get($date->toDateString());
            return [
                'date'  => $date->format('d/m'),
                'label' => $date->locale('bn')->isoFormat('ddd'),
                'total' => $row->total ?? 0,
                'paid'  => $row->paid ?? 0,
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

        // Item sales base — these are the real sales (count + total)
        $rows = Sale::with('user:id,name')
            ->has('items')
            ->where('sale_date', $today)
            ->get()
            ->groupBy('user_id')
            ->map(fn($grp) => [
                'name'  => $grp->first()->user?->name ?? 'অজানা',
                'count' => $grp->count(),
                'total' => $grp->sum('total_amount'),
                'paid'  => $grp->sum('paid_amount'),
                'due'   => $grp->sum('due_amount'),
            ]);

        // Merge no-item payments (বাকী পরিশোধ) into each user's paid total —
        // this table is for cash reconciliation, so money collected against an
        // old due counts as cash that user handled. Only 'paid' moves: a due
        // collection is not a sale, so count/total/due stay put. Mirrors the
        // same merge in ReportController::salesReport().
        $noItemSales = Sale::with('user:id,name')
            ->doesntHave('items')
            ->where('paid_amount', '>', 0)
            ->where('sale_date', $today)
            ->get();

        foreach ($noItemSales->groupBy('user_id') as $userId => $grp) {
            $noItemPaid = $grp->sum('paid_amount');
            if ($rows->has($userId)) {
                $row = $rows[$userId];
                $row['paid'] += $noItemPaid;
                $rows[$userId] = $row;
            } else {
                $rows[$userId] = [
                    'name'  => $grp->first()->user?->name ?? 'অজানা',
                    'count' => 0,
                    'total' => 0,
                    'paid'  => $noItemPaid,
                    'due'   => 0,
                ];
            }
        }

        $rows = $rows->sortByDesc('total')->values();

        $totals = [
            'count' => $rows->sum('count'),
            'total' => $rows->sum('total'),
            'paid'  => $rows->sum('paid'),
            'due'   => $rows->sum('due'),
        ];

        return response()->json(['rows' => $rows, 'totals' => $totals]);
    }
}
