<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\StoreConfigController;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $sales = Sale::with('customer')
            ->when($request->search, fn($q) =>
                $q->whereHas('customer', fn($c) => $c->where('name', 'like', "%{$request->search}%"))
                  ->orWhere('id', $request->search)
            )
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()->paginate(15);

        return view('sales.index', compact('sales'));
    }

    public function create()
    {
        $customers          = Customer::orderBy('name')->get();
        $items              = Item::with('stock')->orderBy('name')->get();
        $paymentMethods     = StoreConfigController::getGroupedPaymentMethods();
        return view('sales.create', compact('customers', 'items', 'paymentMethods'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'sale_date'    => 'required|date',
            'items'        => 'required|array|min:1',
            'items.*.id'   => 'required|exists:items,id',
            'items.*.qty'  => 'required|numeric|min:0.01',
            'items.*.price'=> 'required|numeric|min:0',
            'paid_amount'  => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request) {
            $total    = collect($request->items)->sum(fn($i) => $i['qty'] * $i['price']);
            $discount = $request->discount ?? 0;
            $net      = $total - $discount;
            $due      = max(0, $net - $request->paid_amount);

            // Capture the customer's outstanding balance before this sale
            $previousDue = $request->customer_id
                ? Customer::find($request->customer_id)->due_amount
                : 0;

            $sale = Sale::create([
                'customer_id'  => $request->customer_id ?: null,
                'user_id'      => auth()->id(),
                'total_amount' => $net,
                'discount'     => $discount,
                'paid_amount'  => $request->paid_amount,
                'due_amount'   => $due,
                'previous_due' => $previousDue,
                'status'         => $request->status ?? 'completed',
                'payment_method' => $request->payment_method ?? 'নগদ',
                'notes'          => $request->notes,
                'sale_date'    => $request->sale_date,
            ]);

            foreach ($request->items as $row) {
                SaleItem::create([
                    'sale_id'  => $sale->id,
                    'item_id'  => $row['id'],
                    'quantity' => $row['qty'],
                    'price'    => $row['price'],
                    'subtotal' => $row['qty'] * $row['price'],
                ]);

                Stock::where('item_id', $row['id'])->decrement('quantity', $row['qty']);
            }

            if ($request->customer_id) {
                $customer = Customer::find($request->customer_id);
                if ($due > 0) {
                    // Customer didn't pay in full — add remaining due
                    $customer->increment('due_amount', $due);
                } else {
                    // Customer paid in full or overpaid — excess clears old dues
                    $excess  = $request->paid_amount - $net;
                    $newDue  = max(0, $customer->due_amount - $excess);
                    $customer->update(['due_amount' => $newDue]);
                }
            }
        });

        return redirect()->route('sales.index')->with('success', 'বিক্রয় সফলভাবে সম্পন্ন হয়েছে।');
    }

    public function show(Sale $sale)
    {
        $sale->load('items.item', 'customer', 'user');
        $store = [
            'name'    => \App\Models\StoreConfig::get('store_name', 'আমার চালের দোকান'),
            'owner'   => \App\Models\StoreConfig::get('store_owner', ''),
            'tagline' => \App\Models\StoreConfig::get('store_tagline', ''),
            'phone'   => \App\Models\StoreConfig::get('store_phone', ''),
            'phone2'  => \App\Models\StoreConfig::get('store_phone2', ''),
            'address' => \App\Models\StoreConfig::get('store_address', ''),
        ];
        return view('sales.show', compact('sale', 'store'));
    }

    public function destroy(Sale $sale)
    {
        DB::transaction(function () use ($sale) {
            // Restore stock
            foreach ($sale->items as $item) {
                Stock::where('item_id', $item->item_id)->increment('quantity', $item->quantity);
            }
            // Reverse customer due_amount effect
            if ($sale->customer_id) {
                $customer = Customer::find($sale->customer_id);
                if ($sale->due_amount > 0) {
                    // Sale had unpaid portion — remove it from due
                    $newDue = max(0, $customer->due_amount - $sale->due_amount);
                    $customer->update(['due_amount' => $newDue]);
                } else {
                    // Sale was fully/over paid — restore excess that cleared old dues
                    $excess = $sale->paid_amount - $sale->total_amount;
                    if ($excess > 0) {
                        $customer->increment('due_amount', $excess);
                    }
                }
            }
            $sale->delete();
        });

        return redirect()->route('sales.index')->with('success', 'বিক্রয় মুছে ফেলা হয়েছে।');
    }
}
