<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\Item;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\StoreConfigController;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $query = Purchase::with('supplier')
            ->when($request->search, fn($q) =>
                $q->whereHas('supplier', fn($s) => $s->where('name', 'like', "%{$request->search}%"))
                  ->orWhere('id', $request->search)
            );

        $grandTotal = (clone $query)->sum('total_amount');
        $grandPaid  = (clone $query)->sum('paid_amount');
        $grandDue   = (clone $query)->sum('due_amount');

        $purchases = $query->latest()->paginate(15);

        return view('purchases.index', compact('purchases', 'grandTotal', 'grandPaid', 'grandDue'));
    }

    public function create()
    {
        $suppliers      = Supplier::orderBy('name')->get();
        $items          = Item::with('stock')->orderBy('name')->get();
        $paymentMethods = StoreConfigController::getGroupedPaymentMethods();
        return view('purchases.create', compact('suppliers', 'items', 'paymentMethods'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'purchase_date'       => 'required|date',
            'supplier_id'         => 'required|exists:suppliers,id',
            'items'               => 'required|array|min:1',
            'items.*.id'          => 'required|exists:items,id',
            'items.*.qty'         => 'required|numeric|min:0.01',
            'items.*.price'       => 'required|numeric|min:0',
            'paid_amount'         => 'required|numeric|min:0',
        ]);

        $purchase = null;
        DB::transaction(function () use ($request, &$purchase) {
            $itemsTotal = collect($request->items)->sum(fn($i) => $i['qty'] * $i['price']);
            $extraCost  = $request->extra_cost ?? 0;
            $laborCost  = $request->labor_cost ?? 0;
            $total      = $itemsTotal + $extraCost + $laborCost;
            $due        = max(0, $total - $request->paid_amount);

            $purchase = Purchase::create([
                'supplier_id'    => $request->supplier_id ?: null,
                'user_id'        => auth()->id(),
                'total_amount'   => $total,
                'extra_cost'     => $extraCost,
                'labor_cost'     => $laborCost,
                'paid_amount'    => $request->paid_amount,
                'due_amount'     => $due,
                'payment_method' => $request->payment_method ?? 'নগদ',
                'notes'          => $request->notes,
                'purchase_date'  => $request->purchase_date,
            ]);

            foreach ($request->items as $row) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'item_id'     => $row['id'],
                    'quantity'    => $row['qty'],
                    'price'       => $row['price'],
                    'subtotal'    => $row['qty'] * $row['price'],
                ]);

                // Add to stock — create stock record if missing
                $stock = Stock::firstOrCreate(
                    ['item_id' => $row['id']],
                    ['quantity' => 0, 'min_quantity' => 5]
                );
                $stock->increment('quantity', $row['qty']);

                // Update the item's purchase_price to the latest received price
                Item::where('id', $row['id'])->update(['purchase_price' => $row['price']]);
            }

            if ($request->supplier_id) {
                $supplier = Supplier::find($request->supplier_id);
                // net effect: supplier.due += (total - paid) — allows negative credit
                $supplier->update(['due_amount' => $supplier->due_amount + $total - $request->paid_amount]);
            }
        });

        return redirect()->route('purchases.show', $purchase)->with('success', 'মাল রিসিভ সম্পন্ন হয়েছে। স্টক আপডেট হয়েছে।');
    }

    public function edit(Purchase $purchase)
    {
        $purchase->load('items.item', 'supplier', 'user');
        $suppliers      = Supplier::orderBy('name')->get();
        $items          = Item::with('stock')->orderBy('name')->get();
        $paymentMethods = StoreConfigController::getGroupedPaymentMethods();
        return view('purchases.edit', compact('purchase', 'suppliers', 'items', 'paymentMethods'));
    }

    public function update(Request $request, Purchase $purchase)
    {
        $request->validate([
            'purchase_date'  => 'required|date',
            'supplier_id'    => 'required|exists:suppliers,id',
            'items'          => 'required|array|min:1',
            'items.*.id'     => 'required|exists:items,id',
            'items.*.qty'    => 'required|numeric|min:0.01',
            'items.*.price'  => 'required|numeric|min:0',
            'paid_amount'    => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request, $purchase) {
            // 1. Reverse old stock & supplier effect
            foreach ($purchase->items as $pi) {
                Stock::where('item_id', $pi->item_id)->decrement('quantity', $pi->quantity);
            }
            if ($purchase->supplier_id) {
                $supplier = Supplier::find($purchase->supplier_id);
                // Reverse: undo (total - paid) that was applied on store
                $supplier->update(['due_amount' => $supplier->due_amount - ($purchase->total_amount - $purchase->paid_amount)]);
            }

            // 2. Delete old items
            $purchase->items()->delete();

            // 3. Recalculate & save
            $itemsTotal = collect($request->items)->sum(fn($i) => $i['qty'] * $i['price']);
            $extraCost  = $request->extra_cost ?? 0;
            $laborCost  = $request->labor_cost ?? 0;
            $total      = $itemsTotal + $extraCost + $laborCost;
            $due        = max(0, $total - $request->paid_amount);

            $purchase->update([
                'supplier_id'    => $request->supplier_id ?: null,
                'total_amount'   => $total,
                'extra_cost'     => $extraCost,
                'labor_cost'     => $laborCost,
                'paid_amount'    => $request->paid_amount,
                'due_amount'     => $due,
                'payment_method' => $request->payment_method ?? 'নগদ',
                'notes'          => $request->notes,
                'purchase_date'  => $request->purchase_date,
            ]);

            // 4. Re-add items, stock & price
            foreach ($request->items as $row) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'item_id'     => $row['id'],
                    'quantity'    => $row['qty'],
                    'price'       => $row['price'],
                    'subtotal'    => $row['qty'] * $row['price'],
                ]);
                $stock = Stock::firstOrCreate(['item_id' => $row['id']], ['quantity' => 0, 'min_quantity' => 5]);
                $stock->increment('quantity', $row['qty']);
                Item::where('id', $row['id'])->update(['purchase_price' => $row['price']]);
            }

            // 5. Re-apply supplier due (allows negative credit)
            if ($request->supplier_id) {
                $supplier = Supplier::find($request->supplier_id);
                $supplier->update(['due_amount' => $supplier->due_amount + $total - $request->paid_amount]);
            }
        });

        return redirect()->route('purchases.show', $purchase)->with('success', 'রিসিভ সংশোধন সম্পন্ন। স্টক আপডেট হয়েছে।');
    }

    public function show(Purchase $purchase)
    {
        $purchase->load('items.item', 'supplier', 'user');
        return view('purchases.show', compact('purchase'));
    }

    public function destroy(Purchase $purchase)
    {
        DB::transaction(function () use ($purchase) {
            // Restore stock
            foreach ($purchase->items as $pi) {
                Stock::where('item_id', $pi->item_id)->decrement('quantity', $pi->quantity);
            }
            // Reverse supplier due_amount effect
            if ($purchase->supplier_id) {
                $supplier = Supplier::find($purchase->supplier_id);
                // Reverse: undo (total - paid) applied on store
                $supplier->update(['due_amount' => $supplier->due_amount - ($purchase->total_amount - $purchase->paid_amount)]);
            }
            $purchase->delete();
        });

        return redirect()->route('purchases.index')->with('success', 'রিসিভ মুছে ফেলা হয়েছে। স্টক কমানো হয়েছে।');
    }
}
