<?php

namespace App\Http\Controllers;

use App\Models\ExtraCostCategory;
use Illuminate\Http\Request;

class ExtraCostCategoryController extends Controller
{
    public function index()
    {
        $categories = ExtraCostCategory::orderBy('name')->get();
        return view('extra-cost-categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:100|unique:extra_cost_categories,name']);
        ExtraCostCategory::create(['name' => $request->name]);
        return back()->with('success', 'ক্যাটাগরি যোগ করা হয়েছে।');
    }

    public function update(Request $request, ExtraCostCategory $extraCostCategory)
    {
        $request->validate(['name' => 'required|string|max:100|unique:extra_cost_categories,name,'.$extraCostCategory->id]);
        $extraCostCategory->update(['name' => $request->name]);
        return back()->with('success', 'ক্যাটাগরি আপডেট হয়েছে।');
    }

    public function destroy(ExtraCostCategory $extraCostCategory)
    {
        $extraCostCategory->delete();
        return back()->with('success', 'ক্যাটাগরি মুছে ফেলা হয়েছে।');
    }
}
