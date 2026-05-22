<?php

namespace App\Http\Controllers;

use App\Models\ItemBrand;
use Illuminate\Http\Request;

class ItemBrandController extends Controller
{
    public function index()
    {
        $rows = ItemBrand::withCount('items')->latest()->get();
        return view('item-meta.brands.index', compact('rows'));
    }

    public function create()
    {
        return view('item-meta.brands.create');
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255|unique:item_brands,name']);
        ItemBrand::create($request->only('name'));
        return redirect()->route('item-brands.index')->with('success', 'ব্র্যান্ড যোগ করা হয়েছে।');
    }

    public function edit(ItemBrand $itemBrand)
    {
        return view('item-meta.brands.edit', compact('itemBrand'));
    }

    public function update(Request $request, ItemBrand $itemBrand)
    {
        $request->validate(['name' => 'required|string|max:255|unique:item_brands,name,' . $itemBrand->id]);
        $itemBrand->update($request->only('name'));
        return redirect()->route('item-brands.index')->with('success', 'ব্র্যান্ড আপডেট হয়েছে।');
    }

    public function destroy(ItemBrand $itemBrand)
    {
        $itemBrand->delete();
        return redirect()->route('item-brands.index')->with('success', 'ব্র্যান্ড মুছে ফেলা হয়েছে।');
    }
}
