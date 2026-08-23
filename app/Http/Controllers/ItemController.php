<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Category;
use App\Models\Brand;
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
            ->latest()->paginate($request->boolean('print') ? 100000 : 20)->withQueryString();

        $categories = Category::orderBy('name')->get();

        if ($request->ajax()) {
            return view('items._results', compact('items'));
        }

        return view('items.index', compact('items', 'categories'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        $brands     = Brand::orderBy('name')->get();
        return view('items.create', compact('categories', 'brands'));
    }

    public function store(Request $request)
    {
        // sale_price has a DB default of 0, but that only applies when the
        // column is omitted on INSERT — since we always pass the key, a blank
        // field submits an explicit null and violates the NOT NULL default.
        $request->merge(['sale_price' => $request->sale_price ?? 0]);

        $request->validate([
            'name'           => 'required|string|max:255',
            'code'           => 'nullable|string|unique:items,code',
            'sale_price'     => 'nullable|numeric|min:0',
            'purchase_price' => 'required|numeric|min:0',
        ]);

        $item = Item::create($request->only('name', 'code', 'category_id', 'brand_id', 'purchase_price', 'sale_price', 'unit'));

        Stock::create(['item_id' => $item->id, 'quantity' => 0, 'min_quantity' => $request->min_quantity ?? 5]);

        return redirect()->route('items.index')->with('success', 'আইটেম সফলভাবে যোগ করা হয়েছে।');
    }

    public function edit(Item $item)
    {
        $item->load('stock');
        $categories = Category::orderBy('name')->get();
        $brands     = Brand::orderBy('name')->get();
        return view('items.edit', compact('item', 'categories', 'brands'));
    }

    public function update(Request $request, Item $item)
    {
        $request->merge(['sale_price' => $request->sale_price ?? 0]);

        $request->validate([
            'name'           => 'required|string|max:255',
            'code'           => 'nullable|string|unique:items,code,' . $item->id,
            'sale_price'     => 'nullable|numeric|min:0',
            'purchase_price' => 'required|numeric|min:0',
        ]);

        $item->update($request->only('name', 'code', 'category_id', 'brand_id', 'purchase_price', 'sale_price', 'unit'));

        if ($item->stock) {
            $item->stock->update(['min_quantity' => $request->min_quantity ?? $item->stock->min_quantity]);
        }

        return redirect()->route('items.index')->with('success', 'আইটেম সফলভাবে আপডেট করা হয়েছে।');
    }

    /**
     * Recent purchase history for one item — read-only reference shown next to
     * the item while selling, so the shopkeeper can see which way the buying
     * rate is moving. It never feeds the profit calculation: cost is snapshotted
     * onto sale_items.cost_price at sale time and cannot be picked by hand.
     */
    public function purchaseHistory(Item $item)
    {
        $rows = \App\Models\PurchaseItem::where('purchase_items.item_id', $item->id)
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
            ->select(
                'purchase_items.quantity',
                'purchase_items.price',
                'purchases.id as purchase_id',
                'purchases.purchase_date',
                'suppliers.name as supplier_name'
            )
            ->orderByDesc('purchases.purchase_date')
            ->orderByDesc('purchases.id')
            ->limit(10)
            ->get();

        return response()->json([
            'name'           => $item->name,
            'purchase_price' => (float) $item->purchase_price,
            'stock'          => (float) ($item->stock?->quantity ?? 0),
            'rows'           => $rows,
        ]);
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
