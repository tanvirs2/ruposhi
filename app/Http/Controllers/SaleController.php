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
        $query = Sale::with('customer')
            ->when($request->search, fn($q) =>
                $q->whereHas('customer', fn($c) => $c->where('name', 'like', "%{$request->search}%"))
                  ->orWhere('id', $request->search)
            )
            ->when($request->status, fn($q) => $q->where('status', $request->status));

        // Totals across ALL filtered results (not just current page)
        $grandTotal = (clone $query)->sum('total_amount');
        $grandPaid  = (clone $query)->sum('paid_amount');
        $grandDue   = (clone $query)->sum('due_amount');

        $sales = $query->latest()->paginate(15);

        return view('sales.index', compact('sales', 'grandTotal', 'grandPaid', 'grandDue'));
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
            'items'        => 'nullable|array',
            'items.*.id'   => 'required_with:items|exists:items,id',
            'items.*.qty'  => 'required_with:items|numeric|min:0.01',
            'items.*.price'=> 'required_with:items|numeric|min:0',
            'paid_amount'  => 'required|numeric|min:0',
        ]);

        // Payment-only sale (no items): customer required
        if (empty($request->items) && !$request->customer_id) {
            return back()->withErrors(['customer_id' => 'আইটেম ছাড়া বিক্রয়ে কাস্টমার নির্বাচন আবশ্যক।'])->withInput();
        }
        if (empty($request->items) && (!$request->paid_amount || $request->paid_amount <= 0)) {
            return back()->withErrors(['paid_amount' => 'পরিশোধের পরিমাণ লিখুন।'])->withInput();
        }

        $sale = null;
        DB::transaction(function () use ($request, &$sale) {
            $total      = collect($request->items ?? [])->sum(fn($i) => $i['qty'] * $i['price']);
            $discount   = $request->discount   ?? 0;
            $extraCost  = $request->extra_cost ?? 0;
            $laborCost  = $request->labor_cost ?? 0;
            $net        = $total - $discount + $extraCost + $laborCost;
            $due        = max(0, $net - $request->paid_amount);

            // Capture the customer's outstanding balance before this sale
            $previousDue = $request->customer_id
                ? Customer::find($request->customer_id)->due_amount
                : 0;

            $sale = Sale::create([
                'customer_id'  => $request->customer_id ?: null,
                'user_id'      => auth()->id(),
                'total_amount' => $net,
                'discount'     => $discount,
                'extra_cost'   => $extraCost,
                'labor_cost'   => $laborCost,
                'paid_amount'  => $request->paid_amount,
                'due_amount'   => $due,
                'previous_due' => $previousDue,
                'status'         => $request->status ?? 'completed',
                'payment_method' => $request->payment_method ?? 'নগদ',
                'notes'          => $request->notes,
                'sale_date'    => $request->sale_date,
            ]);

            foreach ($request->items ?? [] as $row) {
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

        return redirect()->route('sales.show', $sale)->with('success', 'বিক্রয় সফলভাবে সম্পন্ন হয়েছে।');
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

    public function edit(Sale $sale)
    {
        $sale->load('items.item', 'customer');
        $customers      = Customer::orderBy('name')->get();
        $items          = Item::with('stock')->orderBy('name')->get();
        $paymentMethods = StoreConfigController::getGroupedPaymentMethods();
        return view('sales.edit', compact('sale', 'customers', 'items', 'paymentMethods'));
    }

    public function update(Request $request, Sale $sale)
    {
        $request->validate([
            'sale_date'    => 'required|date',
            'items'        => 'nullable|array',
            'items.*.id'   => 'required_with:items|exists:items,id',
            'items.*.qty'  => 'required_with:items|numeric|min:0.01',
            'items.*.price'=> 'required_with:items|numeric|min:0',
            'paid_amount'  => 'required|numeric|min:0',
        ]);

        if (empty($request->items) && !$request->customer_id) {
            return back()->withErrors(['customer_id' => 'আইটেম ছাড়া বিক্রয়ে কাস্টমার নির্বাচন আবশ্যক।'])->withInput();
        }

        DB::transaction(function () use ($request, $sale) {
            // ── 1. Restore old stock ────────────────────────────────
            foreach ($sale->items as $oldItem) {
                Stock::where('item_id', $oldItem->item_id)->increment('quantity', $oldItem->quantity);
            }

            // ── 2. Reverse old customer due effect ──────────────────
            if ($sale->customer_id) {
                $oldCustomer = Customer::find($sale->customer_id);
                if ($sale->due_amount > 0) {
                    $oldCustomer->due_amount = max(0, $oldCustomer->due_amount - $sale->due_amount);
                } else {
                    $excess = $sale->paid_amount - $sale->total_amount;
                    if ($excess > 0) $oldCustomer->due_amount += $excess;
                }
                $oldCustomer->save();
            }

            // ── 3. Delete old sale items ────────────────────────────
            $sale->items()->delete();

            // ── 4. Calculate new totals ─────────────────────────────
            $total     = collect($request->items ?? [])->sum(fn($i) => $i['qty'] * $i['price']);
            $discount  = $request->discount   ?? 0;
            $extraCost = $request->extra_cost ?? 0;
            $laborCost = $request->labor_cost ?? 0;
            $net       = $total - $discount + $extraCost + $laborCost;
            $due       = max(0, $net - $request->paid_amount);

            // ── 5. Capture previous_due AFTER reversing old effects ─
            $previousDue = $request->customer_id
                ? Customer::find($request->customer_id)->due_amount
                : 0;

            // ── 6. Update the sale record ───────────────────────────
            $sale->update([
                'customer_id'    => $request->customer_id ?: null,
                'total_amount'   => $net,
                'discount'       => $discount,
                'extra_cost'     => $extraCost,
                'labor_cost'     => $laborCost,
                'paid_amount'    => $request->paid_amount,
                'due_amount'     => $due,
                'previous_due'   => $previousDue,
                'status'         => $request->status ?? 'completed',
                'payment_method' => $request->payment_method ?? 'নগদ',
                'notes'          => $request->notes,
                'sale_date'      => $request->sale_date,
                'is_edited'      => true,
                'edit_note'      => $request->edit_note,
            ]);

            // ── 7. Create new items & decrement stock ───────────────
            foreach ($request->items ?? [] as $row) {
                SaleItem::create([
                    'sale_id'  => $sale->id,
                    'item_id'  => $row['id'],
                    'quantity' => $row['qty'],
                    'price'    => $row['price'],
                    'subtotal' => $row['qty'] * $row['price'],
                ]);
                Stock::where('item_id', $row['id'])->decrement('quantity', $row['qty']);
            }

            // ── 8. Apply new customer due effect ────────────────────
            if ($request->customer_id) {
                $customer = Customer::find($request->customer_id);
                if ($due > 0) {
                    $customer->increment('due_amount', $due);
                } else {
                    $excess = $request->paid_amount - $net;
                    $newDue = max(0, $customer->due_amount - $excess);
                    $customer->update(['due_amount' => $newDue]);
                }
            }
        });

        return redirect()->route('sales.show', $sale)->with('success', 'বিক্রয় সফলভাবে সংশোধন করা হয়েছে।');
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
