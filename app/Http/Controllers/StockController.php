<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\Item;
use Illuminate\Http\Request;

class StockController extends Controller
{
    /* স্টক তথ্য — full stock list with inline adjust */
    public function index(Request $request)
    {
        $stock = Stock::with(['item.category', 'item.itemBrand', 'item.unitType'])
            ->when($request->search, fn($q) =>
                $q->whereHas('item', fn($i) => $i->where('name', 'like', "%{$request->search}%"))
            )
            ->orderBy('quantity')
            ->paginate(20);

        return view('stock.index', compact('stock'));
    }

    /* স্টক রিপোর্ট — search a specific item and see full details */
    public function report(Request $request)
    {
        $results = collect();
        if ($request->filled('search')) {
            $results = Stock::with(['item.category', 'item.itemBrand', 'item.itemType', 'item.unitType'])
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
        $stock = Stock::with(['item.category', 'item.itemBrand', 'item.unitType'])
            ->whereRaw('quantity <= min_quantity')
            ->orderBy('quantity')
            ->paginate(20);

        return view('stock.low', compact('stock'));
    }

    public function adjust(Request $request, Stock $stock)
    {
        $request->validate([
            'quantity' => 'required|numeric|min:0',
        ]);

        $stock->update(['quantity' => $request->quantity]);

        return back()->with('success', 'স্টক সফলভাবে আপডেট করা হয়েছে।');
    }
}
