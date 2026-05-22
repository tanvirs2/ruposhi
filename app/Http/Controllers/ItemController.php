<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Category;
use App\Models\ItemType;
use App\Models\ItemBrand;
use App\Models\UnitType;
use App\Models\Stock;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    private function metaData(): array
    {
        return [
            'categories' => Category::orderBy('name')->get(),
            'itemTypes'  => ItemType::orderBy('name')->get(),
            'itemBrands' => ItemBrand::orderBy('name')->get(),
            'unitTypes'  => UnitType::orderBy('name')->get(),
        ];
    }

    public function index(Request $request)
    {
        $items = Item::with(['category', 'itemType', 'itemBrand', 'unitType', 'stock'])
            ->when($request->search, fn($q) =>
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('code', 'like', "%{$request->search}%")
            )
            ->when($request->category_id, fn($q) =>
                $q->where('category_id', $request->category_id)
            )
            ->when($request->brand_id, fn($q) =>
                $q->where('brand_id', $request->brand_id)
            )
            ->latest()->paginate(20);

        $categories = Category::orderBy('name')->get();
        $itemBrands = ItemBrand::orderBy('name')->get();

        return view('items.index', compact('items', 'categories', 'itemBrands'));
    }

    public function create()
    {
        return view('items.create', $this->metaData());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'code'           => 'nullable|string|unique:items,code',
            'sale_price'     => 'required|numeric|min:0',
            'purchase_price' => 'required|numeric|min:0',
        ]);

        $item = Item::create($request->only('name', 'code', 'category_id', 'type_id', 'brand_id', 'unit_type_id', 'purchase_price', 'sale_price', 'unit', 'description'));

        Stock::create(['item_id' => $item->id, 'quantity' => 0, 'min_quantity' => $request->min_quantity ?? 5]);

        return redirect()->route('items.index')->with('success', 'আইটেম সফলভাবে যোগ করা হয়েছে।');
    }

    public function edit(Item $item)
    {
        return view('items.edit', array_merge(['item' => $item], $this->metaData()));
    }

    public function update(Request $request, Item $item)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'code'           => 'nullable|string|unique:items,code,' . $item->id,
            'sale_price'     => 'required|numeric|min:0',
            'purchase_price' => 'required|numeric|min:0',
        ]);

        $item->update($request->only('name', 'code', 'category_id', 'type_id', 'brand_id', 'unit_type_id', 'purchase_price', 'sale_price', 'unit', 'description'));

        if ($item->stock) {
            $item->stock->update(['min_quantity' => $request->min_quantity ?? $item->stock->min_quantity]);
        }

        return redirect()->route('items.index')->with('success', 'আইটেম সফলভাবে আপডেট করা হয়েছে।');
    }

    public function destroy(Item $item)
    {
        $item->delete();
        return redirect()->route('items.index')->with('success', 'আইটেম মুছে ফেলা হয়েছে।');
    }
}
