<?php

namespace App\Http\Controllers;

use App\Models\DepositCategory;
use Illuminate\Http\Request;

class DepositCategoryController extends Controller
{
    public function index()
    {
        $categories = DepositCategory::orderBy('name')->get();
        return view('deposit-categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $shopId = auth()->user()->shop_id;
        $request->validate([
            'name' => [
                'required', 'string', 'max:100',
                \Illuminate\Validation\Rule::unique('deposit_categories')->where('shop_id', $shopId),
            ],
        ]);
        DepositCategory::create(['name' => $request->name]);
        return back()->with('success', 'ক্যাটাগরি যোগ করা হয়েছে।');
    }

    public function update(Request $request, DepositCategory $depositCategory)
    {
        $shopId = auth()->user()->shop_id;
        $request->validate([
            'name' => [
                'required', 'string', 'max:100',
                \Illuminate\Validation\Rule::unique('deposit_categories')->where('shop_id', $shopId)->ignore($depositCategory->id),
            ],
        ]);
        $depositCategory->update(['name' => $request->name]);
        return back()->with('success', 'ক্যাটাগরি আপডেট হয়েছে।');
    }

    public function destroy(DepositCategory $depositCategory)
    {
        $depositCategory->delete();
        return back()->with('success', 'ক্যাটাগরি মুছে ফেলা হয়েছে।');
    }
}
