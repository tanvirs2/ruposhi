<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\Item;
use App\Models\SaleItem;
use App\Models\PurchaseItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class StockController extends Controller
{
    /* স্টক তথ্য — full stock list with inline adjust */
    public function index(Request $request)
    {
        // updated_date filter: show only stock updated on this date, sorted by most recent
        // Defaults to today — shows what was received/sold today
        $updatedDate = $request->updated_date ?: now()->toDateString();

        $stock = Stock::with(['item.category'])
            ->where('quantity', '!=', 0)   // hide exactly-zero stock; show negative & positive
            ->when($request->search, fn($q) =>
                $q->whereHas('item', fn($i) => $i->where('name', 'like', "%{$request->search}%"))
            )
            ->when($updatedDate, fn($q) =>
                $q->whereDate('stock.updated_at', $updatedDate)
            )
            ->orderBy($updatedDate ? 'stock.updated_at' : 'quantity', $updatedDate ? 'desc' : 'asc')
            ->paginate(20);

        // Date filter — defaults to today
        $filterDate = $request->date ?: now()->toDateString();

        // Sales qty per item on the selected date
        $todaySales = SaleItem::select('item_id', DB::raw('SUM(quantity) as total_qty'))
            ->whereHas('sale', fn($q) => $q->whereDate('sale_date', $filterDate))
            ->groupBy('item_id')
            ->pluck('total_qty', 'item_id');

        // All-time total sales qty per item
        $totalSales = SaleItem::select('item_id', DB::raw('SUM(quantity) as total_qty'))
            ->groupBy('item_id')
            ->pluck('total_qty', 'item_id');

        // Today's received qty per item (from purchases on filterDate)
        $todayReceive = PurchaseItem::select('purchase_items.item_id', DB::raw('SUM(purchase_items.quantity) as total_qty'))
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->whereDate('purchases.purchase_date', $filterDate)
            ->groupBy('purchase_items.item_id')
            ->pluck('total_qty', 'purchase_items.item_id');

        // ── Grand totals (across ALL stock items, not just the current page) ──
        $grandStockQty      = Stock::sum('quantity');
        $grandStockValue    = Stock::join('items', 'items.id', '=', 'stock.item_id')
            ->selectRaw('SUM(stock.quantity * COALESCE(items.purchase_price, 0)) as v')
            ->value('v') ?? 0;
        $grandTodaySales    = SaleItem::whereHas('sale', fn($q) => $q->whereDate('sale_date', $filterDate))
            ->sum('quantity');
        $grandTotalSales    = SaleItem::sum('quantity');
        $grandTodayReceive  = PurchaseItem::join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->whereDate('purchases.purchase_date', $filterDate)
            ->sum('purchase_items.quantity');

        $data = compact(
            'stock', 'todaySales', 'totalSales', 'todayReceive', 'filterDate', 'updatedDate',
            'grandStockQty', 'grandStockValue', 'grandTodaySales', 'grandTotalSales', 'grandTodayReceive'
        );

        if ($request->ajax()) {
            return view('stock._results', $data);
        }

        return view('stock.index', $data);
    }

    /* স্টক রিপোর্ট — search a specific item and see full details */
    public function report(Request $request)
    {
        $results = collect();
        if ($request->filled('search')) {
            $results = Stock::with(['item.category'])
                ->whereHas('item', fn($q) =>
                    $q->where('name', 'like', "%{$request->search}%")
                      ->orWhere('code', 'like', "%{$request->search}%")
                )
                ->get();
        }
        return view('stock.report', compact('results'));
    }

    /* স্টক শেষ — items at or below minimum quantity */
    public function low(Request $request)
    {
        $stock = Stock::with(['item.category'])
            ->whereRaw('quantity <= min_quantity')
            ->orderBy('quantity')
            ->paginate(20);

        // Date filter — defaults to today
        $filterDate = $request->date ?: now()->toDateString();

        // Sales qty per item on the selected date
        $todaySales = SaleItem::select('item_id', DB::raw('SUM(quantity) as total_qty'))
            ->whereHas('sale', fn($q) => $q->whereDate('sale_date', $filterDate))
            ->groupBy('item_id')
            ->pluck('total_qty', 'item_id');

        // All-time total sales qty per item
        $totalSales = SaleItem::select('item_id', DB::raw('SUM(quantity) as total_qty'))
            ->groupBy('item_id')
            ->pluck('total_qty', 'item_id');

        // ── Grand totals across LOW-stock items only ──
        $lowStockIds = Stock::whereRaw('quantity <= min_quantity')->pluck('id', 'item_id')->keys();

        $grandStockQty   = Stock::whereRaw('quantity <= min_quantity')->sum('quantity');
        $grandStockValue = Stock::join('items', 'items.id', '=', 'stock.item_id')
            ->whereRaw('stock.quantity <= stock.min_quantity')
            ->selectRaw('SUM(stock.quantity * COALESCE(items.purchase_price, 0)) as v')
            ->value('v') ?? 0;
        $grandDeficit    = Stock::whereRaw('quantity <= min_quantity')
            ->selectRaw('SUM(GREATEST(min_quantity - quantity, 0)) as d')
            ->value('d') ?? 0;
        $grandTodaySales = SaleItem::whereIn('item_id', $lowStockIds)
            ->whereHas('sale', fn($q) => $q->whereDate('sale_date', $filterDate))
            ->sum('quantity');
        $grandTotalSales = SaleItem::whereIn('item_id', $lowStockIds)->sum('quantity');

        return view('stock.low', compact(
            'stock', 'todaySales', 'totalSales', 'filterDate',
            'grandStockQty', 'grandStockValue', 'grandDeficit',
            'grandTodaySales', 'grandTotalSales'
        ));
    }

    public function adjust(Request $request, Stock $stock)
    {
        if (!auth()->user()->canManageShop()) {
            abort(403, 'শুধুমাত্র অ্যাডমিন স্টক সমন্বয় করতে পারবেন।');
        }
        $request->validate([
            'quantity' => 'required|numeric|min:0',
        ]);

        $stock->update(['quantity' => $request->quantity]);

        return back()->with('success', 'স্টক সফলভাবে আপডেট করা হয়েছে।');
    }
}
