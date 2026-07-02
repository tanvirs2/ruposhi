<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleLog;
use App\Models\Purchase;
use App\Models\ExtraExpense;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\SupplierPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    // ══════════════════════════════════════════════════════════
    // Business Growth Module
    // ══════════════════════════════════════════════════════════
    public function growth(Request $request)
    {
        $period   = $request->get('period', 'monthly');
        $year     = (int) $request->get('year', now()->year);
        $dateFrom = $request->get('date_from', now()->startOfMonth()->toDateString());
        $dateTo   = $request->get('date_to',   now()->toDateString());

        [$labels, $revenue, $cogs, $expenses, $profit, $summary, $prev] =
            $this->buildGrowthData($period, $year, $dateFrom, $dateTo);

        $years = Sale::selectRaw('YEAR(sale_date) as y')->groupBy('y')->orderByDesc('y')->pluck('y');
        if ($years->isEmpty()) $years = collect([now()->year]);

        return view('reports.growth', compact(
            'period','year','dateFrom','dateTo',
            'labels','revenue','cogs','expenses','profit',
            'summary','prev','years'
        ));
    }

    private function buildGrowthData(string $period, int $year, string $dateFrom, string $dateTo): array
    {
        [$from, $to, $prevFrom, $prevTo, $labels] = match ($period) {
            'yearly' => $this->yearlyRange(),
            'range'  => $this->rangeData($dateFrom, $dateTo),
            default  => $this->monthlyRange($year),
        };

        $revenue  = $this->groupedSales($from, $to, $period, $year, $dateFrom, $dateTo);
        $cogs     = $this->groupedPurchases($from, $to, $period, $year, $dateFrom, $dateTo);
        $expenses = $this->groupedExpenses($from, $to, $period, $year, $dateFrom, $dateTo);

        // Align to labels
        $rev = $cog = $exp = $pro = [];
        foreach ($labels as $lbl) {
            $r = $revenue[$lbl]  ?? 0;
            $c = $cogs[$lbl]     ?? 0;
            $e = $expenses[$lbl] ?? 0;
            $rev[] = round($r, 2);
            $cog[] = round($c, 2);
            $exp[] = round($e, 2);
            $pro[] = round($r - $c - $e, 2);
        }

        $summary = [
            'revenue'  => array_sum($rev),
            'cogs'     => array_sum($cog),
            'expenses' => array_sum($exp),
            'profit'   => array_sum($pro),
            'sales_count' => Sale::whereBetween('sale_date', [$from, $to])->count(),
        ];

        // Previous period for growth %
        $prev = [
            'revenue'  => Sale::whereBetween('sale_date', [$prevFrom, $prevTo])->sum('total_amount'),
            'cogs'     => Purchase::whereBetween('purchase_date', [$prevFrom, $prevTo])->sum('total_amount'),
            'expenses' => ExtraExpense::where('type','expense')->whereBetween('expense_date',[$prevFrom,$prevTo])->sum('amount'),
        ];
        $prev['profit'] = $prev['revenue'] - $prev['cogs'] - $prev['expenses'];

        return [$labels, $rev, $cog, $exp, $pro, $summary, $prev];
    }

    private function yearlyRange(): array
    {
        $endYear   = now()->year;
        $startYear = $endYear - 5;
        $labels    = range($startYear, $endYear);
        return [
            "$startYear-01-01", "$endYear-12-31",
            ($startYear-1)."-01-01", ($startYear-1)."-12-31",
            array_map('strval', $labels),
        ];
    }

    private function monthlyRange(int $year): array
    {
        $labels = ['01','02','03','04','05','06','07','08','09','10','11','12'];
        return [
            "$year-01-01", "$year-12-31",
            ($year-1)."-01-01", ($year-1)."-12-31",
            $labels,
        ];
    }

    private function rangeData(string $from, string $to): array
    {
        $start = \Carbon\Carbon::parse($from);
        $end   = \Carbon\Carbon::parse($to);
        $days  = $start->diffInDays($end) + 1;

        // Labels: each day as d-M
        $labels = [];
        for ($d = clone $start; $d <= $end; $d->addDay()) {
            $labels[] = $d->format('d/m');
        }

        // Previous period of same length
        $prevTo   = $start->copy()->subDay()->toDateString();
        $prevFrom = $start->copy()->subDays($days)->toDateString();

        return [$from, $to, $prevFrom, $prevTo, $labels];
    }

    private function groupedSales(string $from, string $to, string $period, int $year, string $dfrom, string $dto)
    {
        $fmt = $period === 'yearly' ? '%Y' : ($period === 'range' ? '%d/%m' : '%m');
        return Sale::whereBetween('sale_date', [$from, $to])
            ->selectRaw("DATE_FORMAT(sale_date, '$fmt') as p, SUM(total_amount) as total")
            ->groupBy('p')->pluck('total', 'p')->toArray();
    }

    private function groupedPurchases(string $from, string $to, string $period, int $year, string $dfrom, string $dto)
    {
        $fmt = $period === 'yearly' ? '%Y' : ($period === 'range' ? '%d/%m' : '%m');
        return Purchase::whereBetween('purchase_date', [$from, $to])
            ->selectRaw("DATE_FORMAT(purchase_date, '$fmt') as p, SUM(total_amount) as total")
            ->groupBy('p')->pluck('total', 'p')->toArray();
    }

    private function groupedExpenses(string $from, string $to, string $period, int $year, string $dfrom, string $dto)
    {
        $fmt = $period === 'yearly' ? '%Y' : ($period === 'range' ? '%d/%m' : '%m');
        return ExtraExpense::where('type','expense')->whereBetween('expense_date', [$from, $to])
            ->selectRaw("DATE_FORMAT(expense_date, '$fmt') as p, SUM(amount) as total")
            ->groupBy('p')->pluck('total', 'p')->toArray();
    }

    public function index(Request $request)
    {
        $from = $request->from ?? now()->toDateString();
        $to   = $request->to   ?? now()->toDateString();

        $sales = Sale::whereBetween('sale_date', [$from, $to])
            ->selectRaw('DATE(sale_date) as date, SUM(total_amount) as total, COUNT(*) as count')
            ->groupBy('date')->orderBy('date')->get();

        $purchases = Purchase::whereBetween('purchase_date', [$from, $to])
            ->selectRaw('DATE(purchase_date) as date, SUM(total_amount) as total, COUNT(*) as count')
            ->groupBy('date')->orderBy('date')->get();

        $expenses = ExtraExpense::whereBetween('expense_date', [$from, $to])
            ->selectRaw('SUM(amount) as total')->value('total') ?? 0;

        // Real profit = negotiated revenue - cost of goods sold - expenses
        // COGS = sum(sale_items.price * qty) used as revenue; cost = sum(items.purchase_price * qty)
        $cogsSummary = DB::table('sale_items')
            ->join('items', 'sale_items.item_id', '=', 'items.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereBetween('sales.sale_date', [$from, $to])
            ->where('sales.shop_id', auth()->user()->shop_id)
            ->selectRaw('
                SUM(sale_items.subtotal) as revenue,
                SUM(items.purchase_price * sale_items.quantity) as cost
            ')
            ->first();

        $totalRevenue = $cogsSummary->revenue ?? 0;
        $totalCogs    = $cogsSummary->cost    ?? 0;
        $grossProfit  = $totalRevenue - $totalCogs;
        $netProfit    = $grossProfit - $expenses;

        $summary = [
            'total_sales'     => $sales->sum('total'),
            'total_purchases' => $purchases->sum('total'),
            'total_expenses'  => $expenses,
            'total_revenue'   => $totalRevenue,
            'total_cogs'      => $totalCogs,
            'gross_profit'    => $grossProfit,
            'net_profit'      => $netProfit,
        ];

        $top_items = DB::table('sale_items')
            ->join('items', 'sale_items.item_id', '=', 'items.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereBetween('sales.sale_date', [$from, $to])
            ->where('sales.shop_id', auth()->user()->shop_id)
            ->selectRaw('
                items.name,
                items.purchase_price,
                SUM(sale_items.quantity) as qty,
                SUM(sale_items.subtotal) as revenue,
                SUM(items.purchase_price * sale_items.quantity) as cost,
                SUM(sale_items.subtotal) - SUM(items.purchase_price * sale_items.quantity) as profit
            ')
            ->groupBy('items.id', 'items.name', 'items.purchase_price')
            ->orderByDesc('profit')
            ->limit(10)
            ->get();

        return view('reports.index', compact('sales', 'purchases', 'summary', 'top_items', 'from', 'to'));
    }

    // ── Daily Customer Payment Report ────────────────────────────
    public function dailyPayments(Request $request)
    {
        $from = $request->from ?? now()->toDateString();
        $to   = $request->to   ?? now()->toDateString();

        // Customer payments are now stored as no-item sales
        $daily = \App\Models\Sale::with('customer')
            ->doesntHave('items')
            ->whereBetween('sale_date', [$from, $to])
            ->orderBy('sale_date')
            ->get()
            ->groupBy(fn($p) => $p->sale_date->toDateString());

        $payments = \App\Models\Sale::with('customer', 'user')
            ->doesntHave('items')
            ->whereBetween('sale_date', [$from, $to])
            ->latest('sale_date')->get();

        $grandTotal = $payments->sum('paid_amount');

        return view('reports.daily-payments', compact('daily', 'payments', 'grandTotal', 'from', 'to'));
    }

    // ── Daily Supplier Payment Report ───────────────────────────
    public function dailySupplierPayments(Request $request)
    {
        $from = $request->from ?? now()->toDateString();
        $to   = $request->to   ?? now()->toDateString();

        // Supplier payments are now stored as no-item advance purchases
        $daily = \App\Models\Purchase::with(['supplier', 'user'])
            ->doesntHave('items')
            ->whereBetween('purchase_date', [$from, $to])
            ->orderBy('purchase_date')
            ->get()
            ->groupBy(fn($p) => $p->purchase_date->toDateString());

        $grandTotal = $daily->flatten()->sum('paid_amount');

        return view('reports.daily-supplier-payments', compact('daily', 'grandTotal', 'from', 'to'));
    }

    // ── বিক্রয় রিপোর্ট — detailed sales with customer filter ───
    public function salesReport(Request $request)
    {
        $from = $request->from ?? now()->toDateString();
        $to   = $request->to   ?? now()->toDateString();
        $selectedCustomer = $request->customer_id ? Customer::find($request->customer_id) : null;

        $isStaff = auth()->user()->role === 'staff';
        $uid     = auth()->id();

        // Full-shop stats (always unfiltered — shown in cards for all users)
        $allShopItemSales  = Sale::with(['user:id,name'])
            ->has('items')
            ->whereBetween('sale_date', [$from, $to])
            ->when($request->customer_id, fn($q) => $q->where('customer_id', $request->customer_id))
            ->get();
        $allShopNoItemSales = Sale::doesntHave('items')
            ->where('paid_amount', '>', 0)
            ->whereBetween('sale_date', [$from, $to])
            ->when($request->customer_id, fn($q) => $q->where('customer_id', $request->customer_id))
            ->get();
        $allShopStandalone  = \App\Models\CustomerPayment::whereBetween('payment_date', [$from, $to])
            ->when($request->customer_id, fn($q) => $q->where('customer_id', $request->customer_id))
            ->sum('amount');
        $shopGrandTotal    = $allShopItemSales->sum('total_amount');
        $shopGrandItemPaid = $allShopItemSales->sum('paid_amount');
        $shopGrandNoItem   = $allShopNoItemSales->sum('paid_amount');
        $shopGrandPaid     = $shopGrandItemPaid + $shopGrandNoItem + $allShopStandalone;
        $shopGrandDue      = max(0, $allShopItemSales->sum('due_amount')
                             - $allShopNoItemSales->sum(fn($s) => min($s->paid_amount, max(0, $s->previous_due ?? 0)))
                             - $allShopStandalone);
        $shopGrandCount    = $allShopItemSales->count() + $allShopNoItemSales->count();
        $shopExtraNote     = $shopGrandNoItem + $allShopStandalone;

        // Full-shop user summary — item sales base
        $userSummaryMap = $allShopItemSales->groupBy('user_id')
            ->map(fn($grp) => [
                'name'  => $grp->first()->user?->name ?? 'অজানা',
                'count' => $grp->count(),
                'total' => $grp->sum('total_amount'),
                'paid'  => $grp->sum('paid_amount'),
                'due'   => $grp->sum('due_amount'),
            ]);

        // Merge no-item payments (বাকী পরিশোধ) into each user's paid total
        foreach ($allShopNoItemSales->groupBy('user_id') as $userId => $grp) {
            $noItemPaid = $grp->sum('paid_amount');
            if ($userSummaryMap->has($userId)) {
                $row = $userSummaryMap[$userId];
                $row['paid'] += $noItemPaid;
                $userSummaryMap[$userId] = $row;
            } else {
                $userSummaryMap[$userId] = [
                    'name'  => $grp->first()->user?->name ?? 'অজানা',
                    'count' => 0,
                    'total' => 0,
                    'paid'  => $noItemPaid,
                    'due'   => 0,
                ];
            }
        }

        $userSummary = $userSummaryMap->sortByDesc('total');

        // Staff only see their own data in the detail section; admins see everything
        $sales = Sale::with(['customer', 'user:id,name'])
            ->whereBetween('sale_date', [$from, $to])
            ->when($isStaff, fn($q) => $q->where('user_id', $uid))
            ->when($request->customer_id, fn($q) => $q->where('customer_id', $request->customer_id))
            ->orderBy('sale_date')->orderBy('id')
            ->get();

        // Standalone customer payments (not tied to any sale) in the same date range
        $standalonePayments = \App\Models\CustomerPayment::with(['customer', 'user:id,name'])
            ->whereBetween('payment_date', [$from, $to])
            ->when($isStaff, fn($q) => $q->where('user_id', $uid))
            ->when($request->customer_id, fn($q) => $q->where('customer_id', $request->customer_id))
            ->orderBy('payment_date')->orderBy('id')
            ->get();

        // Sales with NO items (customer paying off previous due via sale form, no products sold)
        $noItemSales = Sale::with(['customer', 'user:id,name'])
            ->whereDoesntHave('items')
            ->where('paid_amount', '>', 0)
            ->whereBetween('sale_date', [$from, $to])
            ->when($isStaff, fn($q) => $q->where('user_id', $uid))
            ->when($request->customer_id, fn($q) => $q->where('customer_id', $request->customer_id))
            ->orderBy('sale_date')->orderBy('id')
            ->get();

        $grandTotal         = $sales->sum('total_amount');
        // Separate item-bearing sales from no-item (pure payment) sales
        $itemSales          = $sales->filter(fn($s) => !$noItemSales->contains('id', $s->id));
        $grandStandalone    = $standalonePayments->sum('amount');
        $grandNoItemPaid    = $noItemSales->sum('paid_amount');
        $grandItemPaid      = $itemSales->sum('paid_amount');
        $grandPaid          = $grandItemPaid + $grandNoItemPaid + $grandStandalone;
        $grandNoItemDueReduction = $noItemSales->sum(fn($s) => min($s->paid_amount, max(0, $s->previous_due ?? 0)));
        $grandDue           = max(0, $itemSales->sum('due_amount') - $grandNoItemDueReduction - $grandStandalone);

        // Per-item rows for the detail table (admin only — staff won't see this section)
        $saleItems = DB::table('sale_items')
            ->join('items',     'sale_items.item_id',    '=', 'items.id')
            ->join('sales',     'sale_items.sale_id',    '=', 'sales.id')
            ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id')
            ->leftJoin('users',     'sales.user_id',     '=', 'users.id')
            ->whereBetween('sales.sale_date', [$from, $to])
            ->where('sales.shop_id', auth()->user()->shop_id)
            ->when($isStaff, fn($q) => $q->where('sales.user_id', $uid))
            ->when($request->customer_id, fn($q) => $q->where('sales.customer_id', $request->customer_id))
            ->select(
                DB::raw('DATE(sales.sale_date) as date'),
                'sales.id           as sale_id',
                'sales.created_at   as sale_time',
                'sales.paid_amount',
                'sales.due_amount',
                'sales.extra_cost',
                'sales.discount',
                'customers.name     as customer_name',
                'items.name         as item_name',
                'sale_items.quantity as qty',
                'sale_items.price    as rate',
                'sale_items.subtotal as amount',
                'users.name         as user_name'
            )
            ->orderBy('sales.sale_date')
            ->orderBy('sales.id')
            ->orderBy('sale_items.id')
            ->get();

        $grandExtraCost = $itemSales->sum('extra_cost');
        $grandDiscount  = $itemSales->sum('discount');

        // Extra cost breakdown (categorised rows) for the separate table
        $saleExtraCosts = DB::table('sale_extra_costs')
            ->join('sales',          'sale_extra_costs.sale_id', '=', 'sales.id')
            ->leftJoin('customers',  'sales.customer_id',        '=', 'customers.id')
            ->whereBetween('sales.sale_date', [$from, $to])
            ->where('sales.shop_id', auth()->user()->shop_id)
            ->when($isStaff, fn($q) => $q->where('sales.user_id', $uid))
            ->when($request->customer_id, fn($q) => $q->where('sales.customer_id', $request->customer_id))
            ->select(
                'sales.id          as sale_id',
                'sales.sale_date',
                'sales.created_at  as sale_time',
                'customers.name    as customer_name',
                'sale_extra_costs.category_name',
                'sale_extra_costs.amount'
            )
            ->orderBy('sales.sale_date')
            ->orderBy('sales.id')
            ->orderBy('sale_extra_costs.id')
            ->get();

        // Category-level summary for the stat card
        $extraCostByCategory = $saleExtraCosts->groupBy('category_name')
            ->map(fn($rows) => $rows->sum('amount'));

        // 7-day trend for the chart (always last 7 days, not filtered)
        $trendDays = collect(range(6, 0))->map(function ($daysAgo) {
            $date = today()->subDays($daysAgo);
            $base = Sale::whereDate('sale_date', $date);
            return [
                'date'  => $date->format('d/m'),
                'total' => (clone $base)->sum('total_amount'),
                'paid'  => (clone $base)->sum('paid_amount'),
            ];
        });

        return view('reports.sales', compact(
            'sales', 'saleItems', 'selectedCustomer',
            'standalonePayments', 'noItemSales',
            'grandTotal', 'grandPaid', 'grandItemPaid', 'grandDue', 'grandStandalone',
            'grandExtraCost', 'grandDiscount',
            'saleExtraCosts', 'extraCostByCategory',
            'userSummary',
            'shopGrandTotal', 'shopGrandPaid', 'shopGrandItemPaid', 'shopGrandDue',
            'shopGrandCount', 'shopExtraNote',
            'trendDays',
            'from', 'to'
        ));
    }

    // ── দৈনিক রিসিভ রিপোর্ট — purchases grouped by date ──────
    public function dailyReceive(Request $request)
    {
        $from = $request->from ?? now()->toDateString();
        $to   = $request->to   ?? now()->toDateString();

        $daily = Purchase::with(['supplier', 'user', 'items.item'])
            ->whereBetween('purchase_date', [$from, $to])
            ->orderBy('purchase_date')->orderBy('id')
            ->get()
            ->groupBy(fn($p) => $p->purchase_date->toDateString());

        $grandTotal   = $daily->flatten()->sum('total_amount');
        $grandPaid    = $daily->flatten()->sum('paid_amount');
        $grandDeposit = $daily->flatten()->sum('deposit_amount');
        $grandDue     = $daily->flatten()->sum('due_amount');

        return view('reports.daily-receive', compact('daily', 'grandTotal', 'grandPaid', 'grandDeposit', 'grandDue', 'from', 'to'));
    }

    // ── কাস্টমার বাকী রিপোর্ট ─────────────────────────────────
    public function customerDue()
    {
        $customers = Customer::where('due_amount', '>', 0)
            ->orderByDesc('due_amount')
            ->get();
        $totalDue = $customers->sum('due_amount');

        return view('reports.customer-due', compact('customers', 'totalDue'));
    }

    // ── দৈনিক বিক্রয় + স্টক রিপোর্ট ─────────────────────────
    public function dailySalesStock(Request $request)
    {
        $from = $request->from ?? now()->toDateString();
        $to   = $request->to   ?? now()->toDateString();

        $rows = DB::table('sale_items')
            ->join('items', 'sale_items.item_id', '=', 'items.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->leftJoin('stock', 'items.id', '=', 'stock.item_id')
            ->whereBetween('sales.sale_date', [$from, $to])
            ->where('sales.shop_id', auth()->user()->shop_id)
            ->selectRaw('
                items.id,
                items.name,
                items.purchase_price,
                SUM(sale_items.quantity) as sold_qty,
                SUM(sale_items.subtotal) as revenue,
                SUM(items.purchase_price * sale_items.quantity) as cost,
                MAX(stock.quantity) as current_stock
            ')
            ->groupBy('items.id', 'items.name', 'items.purchase_price')
            ->orderByDesc('sold_qty')
            ->get();

        $grandRevenue = $rows->sum('revenue');
        $grandCost    = $rows->sum('cost');
        $grandProfit  = $grandRevenue - $grandCost;

        return view('reports.daily-sales-stock', compact('rows', 'grandRevenue', 'grandCost', 'grandProfit', 'from', 'to'));
    }

    // ════════════════════════════════════════════════════════════════
    //  CSV EXPORT HELPERS
    // ════════════════════════════════════════════════════════════════

    private function csvResponse(string $filename, array $headers, callable $rowsCallback): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rowsCallback) {
            // BOM for Excel UTF-8 recognition
            echo "\xEF\xBB\xBF";
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            $rowsCallback($out);
            fclose($out);
        }, $filename . '.csv', [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
        ]);
    }

    // ── বিক্রয় সংশোধন ও মুছে ফেলার লগ ──────────────────────────
    public function saleLogs(Request $request)
    {
        $from   = $request->from ?? now()->toDateString();
        $to     = $request->to   ?? now()->toDateString();
        $action = $request->action ?? '';

        $logs = SaleLog::with('user')
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->when($action, fn($q) => $q->where('action', $action))
            ->orderByDesc('created_at')
            ->paginate(20);

        // Per-type counts for the filter chips (respect date range, ignore action)
        $countBase = SaleLog::whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59']);
        $counts = [
            ''        => (clone $countBase)->count(),
            'edited'  => (clone $countBase)->where('action', 'edited')->count(),
            'deleted' => (clone $countBase)->where('action', 'deleted')->count(),
        ];

        return view('reports.sale-logs', compact('logs', 'from', 'to', 'action', 'counts'));
    }

    // ── Export: বিক্রয় রিপোর্ট ──────────────────────────────────
    public function exportSales(Request $request): StreamedResponse
    {
        $from  = $request->from ?? now()->toDateString();
        $to    = $request->to   ?? now()->toDateString();
        $sales = Sale::with(['customer', 'items.item'])
            ->whereBetween('sale_date', [$from, $to])
            ->when($request->customer_id, fn($q) => $q->where('customer_id', $request->customer_id))
            ->orderBy('sale_date')->orderBy('id')
            ->get();

        return $this->csvResponse(
            "বিক্রয়-রিপোর্ট_{$from}_{$to}",
            ['তারিখ', 'বিক্রয় #', 'কাস্টমার', 'পণ্য', 'মোট (৳)', 'পরিশোধ (৳)', 'বাকী (৳)'],
            function ($out) use ($sales) {
                foreach ($sales as $s) {
                    $items = $s->items->map(fn($i) => $i->item->name . ' ×' . $i->quantity)->join(', ');
                    fputcsv($out, [
                        $s->sale_date->format('d/m/Y'),
                        '#' . $s->id,
                        $s->customer?->name ?? '—',
                        $items,
                        number_format($s->total_amount, 2),
                        number_format($s->paid_amount,  2),
                        number_format($s->due_amount,   2),
                    ]);
                }
            }
        );
    }

    // ── Export: দৈনিক কাস্টমার পরিশোধ ──────────────────────────
    public function exportDailyPayments(Request $request): StreamedResponse
    {
        $from     = $request->from ?? now()->toDateString();
        $to       = $request->to   ?? now()->toDateString();
        // Customer payments are now stored as no-item sales
        $payments = \App\Models\Sale::with('customer')
            ->doesntHave('items')
            ->whereBetween('sale_date', [$from, $to])
            ->orderBy('sale_date')
            ->get();

        return $this->csvResponse(
            "কাস্টমার-পরিশোধ_{$from}_{$to}",
            ['তারিখ', 'কাস্টমার', 'ফোন', 'পরিশোধ (৳)', 'নোট'],
            function ($out) use ($payments) {
                foreach ($payments as $p) {
                    fputcsv($out, [
                        $p->sale_date->format('d/m/Y'),
                        $p->customer?->name ?? '—',
                        $p->customer?->phone ?? '—',
                        number_format($p->paid_amount, 2),
                        $p->notes ?? '',
                    ]);
                }
            }
        );
    }

    // ── Export: দৈনিক সরবরাহকারী পরিশোধ ───────────────────────
    public function exportDailySupplierPayments(Request $request): StreamedResponse
    {
        $from     = $request->from ?? now()->toDateString();
        $to       = $request->to   ?? now()->toDateString();
        // Supplier payments are now stored as no-item advance purchases
        $payments = \App\Models\Purchase::with('supplier')
            ->doesntHave('items')
            ->whereBetween('purchase_date', [$from, $to])
            ->orderBy('purchase_date')
            ->get();

        return $this->csvResponse(
            "সরবরাহকারী-পরিশোধ_{$from}_{$to}",
            ['তারিখ', 'সরবরাহকারী', 'ফোন', 'পরিশোধ (৳)', 'নোট'],
            function ($out) use ($payments) {
                foreach ($payments as $p) {
                    fputcsv($out, [
                        $p->purchase_date->format('d/m/Y'),
                        $p->supplier?->name ?? '—',
                        $p->supplier?->phone ?? '—',
                        number_format($p->paid_amount, 2),
                        $p->notes ?? '',
                    ]);
                }
            }
        );
    }

    // ── Export: কাস্টমার বাকী রিপোর্ট ──────────────────────────
    public function exportCustomerDue(): StreamedResponse
    {
        $customers = Customer::where('due_amount', '>', 0)
            ->orderByDesc('due_amount')
            ->get();

        return $this->csvResponse(
            'কাস্টমার-বাকী-রিপোর্ট_' . now()->format('d-m-Y'),
            ['#', 'কাস্টমার', 'মালিক', 'ফোন', 'এরিয়া', 'বকেয়া (৳)'],
            function ($out) use ($customers) {
                foreach ($customers as $i => $c) {
                    fputcsv($out, [
                        $i + 1,
                        $c->name,
                        $c->proprietor ?? '',
                        $c->phone ?? '',
                        $c->area?->name ?? '',
                        number_format($c->due_amount, 2),
                    ]);
                }
            }
        );
    }

    // ── Export: দৈনিক রিসিভ রিপোর্ট ────────────────────────────
    public function exportDailyReceive(Request $request): StreamedResponse
    {
        $from  = $request->from ?? now()->toDateString();
        $to    = $request->to   ?? now()->toDateString();
        $items = Purchase::with(['supplier', 'items.item'])
            ->whereBetween('purchase_date', [$from, $to])
            ->orderBy('purchase_date')->orderBy('id')
            ->get();

        return $this->csvResponse(
            "রিসিভ-রিপোর্ট_{$from}_{$to}",
            ['তারিখ', 'রিসিভ #', 'সরবরাহকারী', 'পণ্য', 'মোট (৳)', 'পরিশোধ (৳)', 'জমা (৳)', 'বাকী (৳)'],
            function ($out) use ($items) {
                foreach ($items as $p) {
                    $products = $p->items->map(fn($i) => $i->item->name . ' ×' . $i->quantity)->join(', ');
                    fputcsv($out, [
                        $p->purchase_date->format('d/m/Y'),
                        '#' . $p->id,
                        $p->supplier?->name ?? '—',
                        $products,
                        number_format($p->total_amount, 2),
                        number_format($p->paid_amount,  2),
                        number_format($p->deposit_amount ?? 0, 2),
                        number_format($p->due_amount,   2),
                    ]);
                }
            }
        );
    }

    // ── লাভ-লোকসান রিপোর্ট ──────────────────────────────────────
    public function profitLoss(Request $request)
    {
        abort_unless(auth()->user()->canManageShop(), 403);

        $from = $request->from ?? now()->toDateString();
        $to   = $request->to   ?? now()->toDateString();

        // ── Revenue ───────────────────────────────────────────────
        $grossSales = Sale::whereBetween('sale_date', [$from, $to])->sum('total_amount');
        $discounts  = Sale::whereBetween('sale_date', [$from, $to])->sum('discount');
        $extraCost  = Sale::whereBetween('sale_date', [$from, $to])->sum('extra_cost');
        // Extras are pass-through (collected from customer, paid out) — exclude from net revenue
        $netRevenue = $grossSales - $extraCost;

        // ── COGS (cost of goods sold) ─────────────────────────────
        $cogs = DB::table('sale_items')
            ->join('items', 'sale_items.item_id', '=', 'items.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereBetween('sales.sale_date', [$from, $to])
            ->where('sales.shop_id', auth()->user()->shop_id)
            ->sum(DB::raw('items.purchase_price * sale_items.quantity'));

        $grossProfit  = $netRevenue - $cogs;
        $grossMargin  = $netRevenue > 0 ? round($grossProfit / $netRevenue * 100, 2) : 0;

        // ── Operating expenses (by category) ─────────────────────
        $expenseCategories = ExtraExpense::whereBetween('expense_date', [$from, $to])
            ->selectRaw('COALESCE(category, "অন্যান্য") as category, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();
        $totalExpenses = $expenseCategories->sum('total');

        // ── Net profit ────────────────────────────────────────────
        $netProfit   = $grossProfit - $totalExpenses;
        $netMargin   = $netRevenue > 0 ? round($netProfit / $netRevenue * 100, 2) : 0;

        // ── Monthly breakdown ─────────────────────────────────────
        $monthly = DB::table('sales')
            ->whereBetween('sale_date', [$from, $to])
            ->where('shop_id', auth()->user()->shop_id)
            ->selectRaw("DATE_FORMAT(sale_date,'%Y-%m') as month,
                SUM(total_amount) as revenue,
                COUNT(*) as sale_count")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Attach COGS per month
        $cogsByMonth = DB::table('sale_items')
            ->join('items', 'sale_items.item_id', '=', 'items.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereBetween('sales.sale_date', [$from, $to])
            ->where('sales.shop_id', auth()->user()->shop_id)
            ->selectRaw("DATE_FORMAT(sales.sale_date,'%Y-%m') as month,
                SUM(items.purchase_price * sale_items.quantity) as cost")
            ->groupBy('month')
            ->pluck('cost', 'month');

        $expsByMonth = DB::table('extra_expenses')
            ->whereBetween('expense_date', [$from, $to])
            ->where('shop_id', auth()->user()->shop_id)
            ->selectRaw("DATE_FORMAT(expense_date,'%Y-%m') as month, SUM(amount) as total")
            ->groupBy('month')
            ->pluck('total', 'month');

        foreach ($monthly as $row) {
            $row->cost     = $cogsByMonth[$row->month] ?? 0;
            $row->expenses = $expsByMonth[$row->month] ?? 0;
            $row->profit   = $row->revenue - $row->cost - $row->expenses;
        }

        // ── Item-wise profit breakdown (aggregated) ───────────────
        $itemBreakdown = DB::table('sale_items')
            ->join('items', 'sale_items.item_id', '=', 'items.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereBetween('sales.sale_date', [$from, $to])
            ->where('sales.shop_id', auth()->user()->shop_id)
            ->selectRaw('
                items.name,
                SUM(sale_items.quantity) as qty,
                SUM(sale_items.subtotal) as revenue,
                SUM(items.purchase_price * sale_items.quantity) as cost,
                SUM(sale_items.subtotal) - SUM(items.purchase_price * sale_items.quantity) as profit
            ')
            ->groupBy('items.id', 'items.name')
            ->orderByDesc('profit')
            ->get();

        // ── User-wise performance breakdown ──────────────────────
        $userPerformance = DB::table('sale_items')
            ->join('items', 'sale_items.item_id', '=', 'items.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->leftJoin('users', 'sales.user_id', '=', 'users.id')
            ->whereBetween('sales.sale_date', [$from, $to])
            ->where('sales.shop_id', auth()->user()->shop_id)
            ->selectRaw('
                users.id as user_id,
                COALESCE(users.name, "অজানা") as user_name,
                COUNT(DISTINCT sales.id) as sale_count,
                SUM(sale_items.subtotal) as revenue,
                SUM(items.purchase_price * sale_items.quantity) as cost,
                SUM(sale_items.subtotal) - SUM(items.purchase_price * sale_items.quantity) as profit
            ')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('profit')
            ->get()
            ->map(function ($row) {
                $row->margin = $row->revenue > 0
                    ? round($row->profit / $row->revenue * 100, 1)
                    : 0;
                return $row;
            });

        // ── Daily detail rows (one row per sale item) ─────────────
        $dailyDetail = DB::table('sale_items')
            ->join('items', 'sale_items.item_id', '=', 'items.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereBetween('sales.sale_date', [$from, $to])
            ->where('sales.shop_id', auth()->user()->shop_id)
            ->selectRaw('
                sales.sale_date,
                sales.id as sale_id,
                items.name,
                sale_items.quantity as qty,
                sale_items.price as unit_price,
                sale_items.subtotal as revenue,
                items.purchase_price,
                (sale_items.price - items.purchase_price) * sale_items.quantity as profit
            ')
            ->orderBy('sales.sale_date')
            ->orderBy('sales.id')
            ->get();

        return view('reports.profit-loss', compact(
            'from', 'to',
            'grossSales', 'discounts', 'extraCost', 'netRevenue',
            'cogs', 'grossProfit', 'grossMargin',
            'expenseCategories', 'totalExpenses',
            'netProfit', 'netMargin',
            'monthly', 'itemBreakdown', 'dailyDetail', 'userPerformance'
        ));
    }

    // ── Export: লাভ-লোকসান CSV ───────────────────────────────────
    public function exportProfitLoss(Request $request): StreamedResponse
    {
        abort_unless(auth()->user()->canManageShop(), 403);

        $from = $request->from ?? now()->toDateString();
        $to   = $request->to   ?? now()->toDateString();

        $grossSales    = Sale::whereBetween('sale_date', [$from, $to])->sum('total_amount');
        $discounts     = Sale::whereBetween('sale_date', [$from, $to])->sum('discount');
        $cogs          = DB::table('sale_items')
            ->join('items', 'sale_items.item_id', '=', 'items.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereBetween('sales.sale_date', [$from, $to])
            ->where('sales.shop_id', auth()->user()->shop_id)
            ->sum(DB::raw('items.purchase_price * sale_items.quantity'));
        $grossProfit   = $grossSales - $cogs;
        $totalExpenses = ExtraExpense::whereBetween('expense_date', [$from, $to])->sum('amount');
        $netProfit     = $grossProfit - $totalExpenses;

        $itemBreakdown = DB::table('sale_items')
            ->join('items', 'sale_items.item_id', '=', 'items.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereBetween('sales.sale_date', [$from, $to])
            ->where('sales.shop_id', auth()->user()->shop_id)
            ->selectRaw('items.name,
                SUM(sale_items.quantity) as qty,
                SUM(sale_items.subtotal) as revenue,
                SUM(items.purchase_price * sale_items.quantity) as cost,
                SUM(sale_items.subtotal) - SUM(items.purchase_price * sale_items.quantity) as profit')
            ->groupBy('items.id', 'items.name')
            ->orderByDesc('profit')->get();

        return $this->csvResponse(
            "লাভ-লোকসান_{$from}_{$to}",
            ['বিবরণ', 'পরিমাণ (৳)'],
            function ($out) use ($grossSales, $discounts, $cogs, $grossProfit, $totalExpenses, $netProfit, $itemBreakdown) {
                fputcsv($out, ['মোট বিক্রয়',       number_format($grossSales,   2)]);
                fputcsv($out, ['ছাড়',              number_format($discounts,    2)]);
                fputcsv($out, ['নিট বিক্রয় আয়',   number_format($grossSales,   2)]);
                fputcsv($out, ['পণ্য ক্রয় মূল্য',  number_format($cogs,         2)]);
                fputcsv($out, ['গ্রস লাভ',          number_format($grossProfit,  2)]);
                fputcsv($out, ['পরিচালনা ব্যয়',     number_format($totalExpenses,2)]);
                fputcsv($out, ['নিট লাভ/লোকসান',    number_format($netProfit,    2)]);
                fputcsv($out, []);
                fputcsv($out, ['— পণ্যভিত্তিক বিবরণ —', '']);
                fputcsv($out, ['পণ্য', 'পরিমাণ', 'আয় (৳)', 'খরচ (৳)', 'লাভ (৳)']);
                foreach ($itemBreakdown as $r) {
                    fputcsv($out, [
                        $r->name, $r->qty,
                        number_format($r->revenue, 2),
                        number_format($r->cost,    2),
                        number_format($r->profit,  2),
                    ]);
                }
            }
        );
    }

    // ── দৈনিক বিক্রয় লেজার রিপোর্ট ────────────────────────────
    public function dailySalesLedger(Request $request)
    {
        $from = $request->from ?? now()->toDateString();
        $to   = $request->to   ?? now()->toDateString();

        // Each sale_item becomes one row (সব বিক্রয়ের আইটেম)
        $rows = DB::table('sale_items')
            ->join('items',    'sale_items.item_id',  '=', 'items.id')
            ->join('sales',    'sale_items.sale_id',  '=', 'sales.id')
            ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id')
            ->whereBetween('sales.sale_date', [$from, $to])
            ->where('sales.shop_id', auth()->user()->shop_id)
            ->select(
                DB::raw('DATE(sales.sale_date) as date'),
                'sales.id          as sale_id',
                'customers.name    as customer_name',
                'items.name        as item_name',
                'sale_items.quantity as qty',
                'sale_items.price  as rate',
                'sale_items.subtotal as amount'
            )
            ->orderBy('sales.sale_date')
            ->orderBy('sales.id')
            ->orderBy('sale_items.id')
            ->get();

        $grandTotal = $rows->sum('amount');

        return view('reports.daily-sales-ledger', compact('rows', 'grandTotal', 'from', 'to'));
    }

    // ── Export: দৈনিক বিক্রয় লেজার CSV ─────────────────────────
    public function exportDailySalesLedger(Request $request): StreamedResponse
    {
        $from = $request->from ?? now()->toDateString();
        $to   = $request->to   ?? now()->toDateString();

        $rows = DB::table('sale_items')
            ->join('items',    'sale_items.item_id',  '=', 'items.id')
            ->join('sales',    'sale_items.sale_id',  '=', 'sales.id')
            ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id')
            ->whereBetween('sales.sale_date', [$from, $to])
            ->where('sales.shop_id', auth()->user()->shop_id)
            ->select(
                DB::raw('DATE(sales.sale_date) as date'),
                'sales.id as sale_id', 'customers.name as customer_name',
                'items.name as item_name',
                'sale_items.quantity as qty', 'sale_items.price as rate',
                'sale_items.subtotal as amount'
            )
            ->orderBy('sales.sale_date')->orderBy('sales.id')->orderBy('sale_items.id')
            ->get();

        $grandTotal = $rows->sum('amount');

        return $this->csvResponse(
            "দৈনিক-বিক্রয়-লেজার_{$from}_{$to}",
            ['তারিখ', 'চালান #', 'কাস্টমার', 'বিবরণ', 'পরিমাণ', 'দর (৳)', 'জমা', 'খরচ (৳)', 'অবশিষ্ট (৳)'],
            function ($out) use ($rows, $grandTotal) {
                $running = 0;
                foreach ($rows as $r) {
                    $running += $r->amount;
                    fputcsv($out, [
                        $r->date,
                        '#INV-' . str_pad($r->sale_id, 4, '0', STR_PAD_LEFT),
                        $r->customer_name ?? '—',
                        $r->item_name,
                        $r->qty,
                        number_format($r->rate, 0),
                        '-',
                        number_format($r->amount, 0),
                        number_format($running, 0),
                    ]);
                }
                fputcsv($out, []);
                fputcsv($out, ['', '', '', 'সর্বমোট', '', '', '', number_format($grandTotal, 0), '']);
            }
        );
    }

    // ── Export: বিক্রয় + স্টক রিপোর্ট ─────────────────────────
    public function exportDailySalesStock(Request $request): StreamedResponse
    {
        $from = $request->from ?? now()->toDateString();
        $to   = $request->to   ?? now()->toDateString();

        $rows = DB::table('sale_items')
            ->join('items', 'sale_items.item_id', '=', 'items.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->leftJoin('stock', 'items.id', '=', 'stock.item_id')
            ->whereBetween('sales.sale_date', [$from, $to])
            ->where('sales.shop_id', auth()->user()->shop_id)
            ->selectRaw('items.name, items.purchase_price,
                SUM(sale_items.quantity) as sold_qty,
                SUM(sale_items.subtotal) as revenue,
                SUM(items.purchase_price * sale_items.quantity) as cost,
                MAX(stock.quantity) as current_stock')
            ->groupBy('items.id', 'items.name', 'items.purchase_price')
            ->orderByDesc('sold_qty')
            ->get();

        return $this->csvResponse(
            "বিক্রয়-স্টক-রিপোর্ট_{$from}_{$to}",
            ['পণ্য', 'বিক্রীত পরিমাণ', 'রাজস্ব (৳)', 'ক্রয়মূল্য মোট (৳)', 'লাভ (৳)', 'বর্তমান স্টক'],
            function ($out) use ($rows) {
                foreach ($rows as $r) {
                    fputcsv($out, [
                        $r->name,
                        $r->sold_qty,
                        number_format($r->revenue, 2),
                        number_format($r->cost, 2),
                        number_format($r->revenue - $r->cost, 2),
                        $r->current_stock ?? 0,
                    ]);
                }
            }
        );
    }
}
