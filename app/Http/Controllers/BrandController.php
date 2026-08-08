<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    public function index()
    {
        $rows = Brand::withCount('items')->latest()->get();
        return view('item-meta.brands.index', compact('rows'));
    }

    public function create()
    {
        return view('item-meta.brands.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('brands', 'name')->where('shop_id', auth()->user()->shop_id)],
        ]);
        Brand::create($request->only('name', 'description'));
        return redirect()->route('brands.index')->with('success', 'ব্র্যান্ড যোগ করা হয়েছে।');
    }

    public function edit(Brand $brand)
    {
        return view('item-meta.brands.edit', compact('brand'));
    }

    public function update(Request $request, Brand $brand)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('brands', 'name')->where('shop_id', auth()->user()->shop_id)->ignore($brand->id)],
        ]);
        $brand->update($request->only('name', 'description'));
        return redirect()->route('brands.index')->with('success', 'ব্র্যান্ড আপডেট হয়েছে।');
    }

    public function destroy(Brand $brand)
    {
        if (!auth()->user()->canManageShop()) {
            abort(403, 'শুধুমাত্র অ্যাডমিন মুছতে পারবেন।');
        }
        $brand->delete();
        return redirect()->route('brands.index')->with('success', 'ব্র্যান্ড মুছে ফেলা হয়েছে।');
    }
}
