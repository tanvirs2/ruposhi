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
        $stats = [
            'customers'   => Customer::count(),
            'items'       => Item::count(),
            'today_sales' => Sale::whereDate('sale_date', today())->sum('total_amount'),
            'stock_value' => DB::table('stock')
                ->join('items', 'stock.item_id', '=', 'items.id')
                ->where('stock.shop_id', auth()->user()->shop_id)
                ->sum(DB::raw('stock.quantity * items.purchase_price')),
        ];

        $recent_sales = Sale::with('customer')
            ->latest()
            ->take(5)
            ->get();

        $low_stock = Stock::with('item')
            ->whereRaw('quantity <= min_quantity')
            ->take(5)
            ->get();

        return view('dashboard', compact('stats', 'recent_sales', 'low_stock'));
    }
}
