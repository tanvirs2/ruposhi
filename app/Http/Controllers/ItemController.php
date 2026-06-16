<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Category;
use App\Models\Stock;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $items = Item::with(['category', 'stock'])
            ->when($request->search, fn($q) =>
                $q->where(fn($s) => $s
                    ->where('name', 'like', "%{$request->search}%")
                    ->orWhere('code', 'like', "%{$request->search}%"))
            )
            ->when($request->category_id, fn($q) =>
                $q->where('category_id', $request->category_id)
            )
            ->latest()->paginate(20)->withQueryString();

        $categories = Category::orderBy('name')->get();

        if ($request->ajax()) {
            return view('items._results', compact('items'));
        }

        return view('items.index', compact('items', 'categories'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        return view('items.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'code'           => 'nullable|string|unique:items,code',
            'sale_price'     => 'nullable|numeric|min:0',
            'purchase_price' => 'required|numeric|min:0',
        ]);

        $item = Item::create($request->only('name', 'code', 'category_id', 'purchase_price', 'sale_price', 'unit'));

        Stock::create(['item_id' => $item->id, 'quantity' => 0, 'min_quantity' => $request->min_quantity ?? 5]);

        return redirect()->route('items.index')->with('success', 'আইটেম সফলভাবে যোগ করা হয়েছে।');
    }

    public function edit(Item $item)
    {
        $item->load('stock');
        $categories = Category::orderBy('name')->get();
        return view('items.edit', compact('item', 'categories'));
    }

    public function update(Request $request, Item $item)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'code'           => 'nullable|string|unique:items,code,' . $item->id,
            'sale_price'     => 'nullable|numeric|min:0',
            'purchase_price' => 'required|numeric|min:0',
        ]);

        $item->update($request->only('name', 'code', 'category_id', 'purchase_price', 'sale_price', 'unit'));

        if ($item->stock) {
            $item->stock->update(['min_quantity' => $request->min_quantity ?? $item->stock->min_quantity]);
        }

        return redirect()->route('items.index')->with('success', 'আইটেম সফলভাবে আপডেট করা হয়েছে।');
    }

    public function destroy(Item $item)
    {
        if (!auth()->user()->canManageShop()) {
            abort(403, 'শুধুমাত্র অ্যাডমিন মুছতে পারবেন।');
        }
        $item->delete();
        return redirect()->route('items.index')->with('success', 'আইটেম মুছে ফেলা হয়েছে।');
    }
}
