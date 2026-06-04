<?php

namespace App\Http\Controllers;

use App\Models\CustomerArea;
use Illuminate\Http\Request;

class CustomerAreaController extends Controller
{
    public function index()
    {
        $areas = CustomerArea::withCount('customers')->latest()->get();
        return view('customer-areas.index', compact('areas'));
    }

    public function create()
    {
        return view('customer-areas.create');
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255|unique:customer_areas,name']);
        CustomerArea::create($request->only('name'));
        return redirect()->route('customer-areas.index')->with('success', 'এরিয়া যোগ করা হয়েছে।');
    }

    public function edit(CustomerArea $customerArea)
    {
        return view('customer-areas.edit', compact('customerArea'));
    }

    public function update(Request $request, CustomerArea $customerArea)
    {
        $request->validate(['name' => 'required|string|max:255|unique:customer_areas,name,'.$customerArea->id]);
        $customerArea->update($request->only('name'));
        return redirect()->route('customer-areas.index')->with('success', 'এরিয়া আপডেট হয়েছে।');
    }

    public function destroy(CustomerArea $customerArea)
    {
        if (!auth()->user()->canManageShop()) {
            abort(403, 'শুধুমাত্র অ্যাডমিন মুছতে পারবেন।');
        }
        $customerArea->delete();
        return redirect()->route('customer-areas.index')->with('success', 'এরিয়া মুছে ফেলা হয়েছে।');
    }
}
