<?php

namespace App\Http\Controllers;

use App\Models\ItemType;
use Illuminate\Http\Request;

class ItemTypeController extends Controller
{
    public function index()
    {
        $rows = ItemType::withCount('items')->latest()->get();
        return view('item-meta.types.index', compact('rows'));
    }

    public function create()
    {
        return view('item-meta.types.create');
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255|unique:item_types,name']);
        ItemType::create($request->only('name'));
        return redirect()->route('item-types.index')->with('success', 'আইটেম টাইপ যোগ করা হয়েছে।');
    }

    public function edit(ItemType $itemType)
    {
        return view('item-meta.types.edit', compact('itemType'));
    }

    public function update(Request $request, ItemType $itemType)
    {
        $request->validate(['name' => 'required|string|max:255|unique:item_types,name,' . $itemType->id]);
        $itemType->update($request->only('name'));
        return redirect()->route('item-types.index')->with('success', 'আইটেম টাইপ আপডেট হয়েছে।');
    }

    public function destroy(ItemType $itemType)
    {
        $itemType->delete();
        return redirect()->route('item-types.index')->with('success', 'আইটেম টাইপ মুছে ফেলা হয়েছে।');
    }
}
