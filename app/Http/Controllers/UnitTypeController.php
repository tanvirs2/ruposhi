<?php

namespace App\Http\Controllers;

use App\Models\UnitType;
use Illuminate\Http\Request;

class UnitTypeController extends Controller
{
    public function index()
    {
        $rows = UnitType::withCount('items')->latest()->get();
        return view('item-meta.unit-types.index', compact('rows'));
    }

    public function create()
    {
        return view('item-meta.unit-types.create');
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255|unique:unit_types,name']);
        UnitType::create($request->only('name', 'short'));
        return redirect()->route('unit-types.index')->with('success', 'ইউনিট টাইপ যোগ করা হয়েছে।');
    }

    public function edit(UnitType $unitType)
    {
        return view('item-meta.unit-types.edit', compact('unitType'));
    }

    public function update(Request $request, UnitType $unitType)
    {
        $request->validate(['name' => 'required|string|max:255|unique:unit_types,name,' . $unitType->id]);
        $unitType->update($request->only('name', 'short'));
        return redirect()->route('unit-types.index')->with('success', 'ইউনিট টাইপ আপডেট হয়েছে।');
    }

    public function destroy(UnitType $unitType)
    {
        $unitType->delete();
        return redirect()->route('unit-types.index')->with('success', 'ইউনিট টাইপ মুছে ফেলা হয়েছে।');
    }
}
