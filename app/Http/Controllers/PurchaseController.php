<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseExtraCost;
use App\Models\ExtraCostCategory;
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
        $dateFrom = $request->date_from ?: null;
        $dateTo   = $request->date_to   ?: null;

        $query = Purchase::with('supplier', 'items.item')
            ->when($request->search, fn($q) =>
                // Wrap in a sub-group so the OR doesn't bypass the global shop_id scope
                $q->where(fn($sub) =>
                    $sub->whereHas('supplier', fn($s) => $s->where('name', 'like', "%{$request->search}%"))
                        ->orWhere('id', $request->search)
                )
            )
            ->when($dateFrom, fn($q) => $q->whereDate('purchase_date', '>=', $dateFrom))
            ->when($dateTo,   fn($q) => $q->whereDate('purchase_date', '<=', $dateTo));

        $grandTotal  = (clone $query)->sum('total_amount');
        $grandPaid   = (clone $query)->sum('paid_amount');
        $grossDue    = (clone $query)->where('due_amount', '>', 0)->sum('due_amount');
        $totalCredit = abs((clone $query)->where('due_amount', '<', 0)->sum('due_amount'));
        $grandDue    = $grossDue - $totalCredit;

        $purchases = $query->latest('purchase_date')->latest('id')->paginate(20);

        return view('purchases.index', compact('purchases', 'grandTotal', 'grandPaid', 'grandDue', 'grossDue', 'totalCredit', 'dateFrom', 'dateTo'));
    }

    public function create()
    {
        $suppliers      = Supplier::select('id','name','proprietor','phone','address','due_amount')
                            ->orderBy('name')->get();
        $items          = Item::with('stock:id,item_id,quantity')
                            ->select('id','name','purchase_price')
                            ->orderBy('name')->get();
        $paymentMethods  = StoreConfigController::getGroupedPaymentMethods();
        $extraCategories = ExtraCostCategory::orderBy('name')->pluck('name');
        return view('purchases.create', compact('suppliers', 'items', 'paymentMethods', 'extraCategories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'purchase_date'       => 'required|date',
            'supplier_id'         => 'required|exists:suppliers,id',
            'items'               => 'nullable|array',
            'items.*.id'          => 'required_with:items|exists:items,id',
            'items.*.qty'         => 'required_with:items|numeric|min:0.01',
            'items.*.price'       => 'required_with:items|numeric|min:0',
            'paid_amount'         => 'required|numeric|min:0',
        ]);

        $purchase = null;
        DB::transaction(function () use ($request, &$purchase) {
            $itemsTotal    = collect($request->items ?? [])->sum(fn($i) => $i['qty'] * $i['price']);
            $extraCostRows = collect($request->extra_costs ?? [])
                ->filter(fn($r) => !empty($r['category']) && isset($r['amount']) && $r['amount'] > 0);
            $extraCost = $extraCostRows->sum(fn($r) => (float) $r['amount']);
            $discount  = (float) ($request->discount ?? 0);
            $total     = $itemsTotal - $discount + $extraCost;
            $due       = $total - $request->paid_amount; // allows negative (credit/advance)

            $purchase = Purchase::create([
                'supplier_id'    => $request->supplier_id ?: null,
                'user_id'        => auth()->id(),
                'total_amount'   => $total,
                'discount'       => $discount,
                'extra_cost'     => $extraCost,
                'paid_amount'    => $request->paid_amount,
                'due_amount'     => $due,
                'payment_method' => $request->payment_method ?? 'নগদ',
                'notes'          => $request->notes,
                'purchase_date'  => $request->purchase_date,
            ]);

            foreach ($request->items ?? [] as $row) {
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
                // Note: items.purchase_price is NOT updated here — it is the master default
                // price set from the items page. Per-receive prices live in purchase_items only.
            }

            // Save categorised extra costs
            foreach ($extraCostRows as $row) {
                PurchaseExtraCost::create([
                    'purchase_id'   => $purchase->id,
                    'category_name' => $row['category'],
                    'amount'        => $row['amount'],
                ]);
            }

            if ($request->supplier_id) {
                $supplier = Supplier::find($request->supplier_id);
                // net effect: supplier.due += (total - paid). Atomic increment
                // avoids lost-update races; allows negative credit.
                $supplier->increment('due_amount', $total - $request->paid_amount);
            }
        });

        return redirect()->route('purchases.show', $purchase)->with('success', 'মাল রিসিভ সম্পন্ন হয়েছে। স্টক আপডেট হয়েছে।');
    }

    public function edit(Purchase $purchase)
    {
        $purchase->load('items.item', 'supplier', 'user', 'extraCosts');
        $suppliers      = Supplier::select('id','name','proprietor','phone','address','due_amount')
                            ->orderBy('name')->get();
        $items          = Item::with('stock:id,item_id,quantity')
                            ->select('id','name','purchase_price')
                            ->orderBy('name')->get();
        $paymentMethods  = StoreConfigController::getGroupedPaymentMethods();
        $extraCategories = ExtraCostCategory::orderBy('name')->pluck('name');
        return view('purchases.edit', compact('purchase', 'suppliers', 'items', 'paymentMethods', 'extraCategories'));
    }

    public function update(Request $request, Purchase $purchase)
    {
        $request->validate([
            'purchase_date'       => 'required|date',
            'supplier_id'         => 'required|exists:suppliers,id',
            'items'               => 'nullable|array',  // nullable: allows editing advance-payment purchases
            'items.*.id'          => 'required_with:items|exists:items,id',
            'items.*.qty'         => 'required_with:items|numeric|min:0.01',
            'items.*.price'       => 'required_with:items|numeric|min:0',
            'paid_amount'         => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request, $purchase) {
            // 1. Reverse old stock & supplier effect
            foreach ($purchase->items as $pi) {
                Stock::where('item_id', $pi->item_id)->decrement('quantity', $pi->quantity);
            }
            if ($purchase->supplier_id) {
                $supplier = Supplier::find($purchase->supplier_id);
                // Reverse: undo (total - paid) that was applied on store
                $supplier->decrement('due_amount', $purchase->total_amount - $purchase->paid_amount);
            }

            // 2. Delete old items
            $purchase->items()->delete();

            // 3. Recalculate & save
            $itemsTotal    = collect($request->items ?? [])->sum(fn($i) => $i['qty'] * $i['price']);
            $extraCostRows = collect($request->extra_costs ?? [])
                ->filter(fn($r) => !empty($r['category']) && isset($r['amount']) && $r['amount'] > 0);
            $extraCost = $extraCostRows->sum(fn($r) => (float) $r['amount']);
            $discount  = (float) ($request->discount ?? 0);
            $total     = $itemsTotal - $discount + $extraCost;
            $due       = $total - $request->paid_amount;

            $purchase->update([
                'supplier_id'    => $request->supplier_id ?: null,
                'total_amount'   => $total,
                'discount'       => $discount,
                'extra_cost'     => $extraCost,
                'paid_amount'    => $request->paid_amount,
                'due_amount'     => $due,
                'payment_method' => $request->payment_method ?? 'নগদ',
                'notes'          => $request->notes,
                'purchase_date'  => $request->purchase_date,
            ]);

            // 4. Re-add items, stock & price
            foreach ($request->items ?? [] as $row) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'item_id'     => $row['id'],
                    'quantity'    => $row['qty'],
                    'price'       => $row['price'],
                    'subtotal'    => $row['qty'] * $row['price'],
                ]);
                $stock = Stock::firstOrCreate(['item_id' => $row['id']], ['quantity' => 0, 'min_quantity' => 5]);
                $stock->increment('quantity', $row['qty']);
                // Note: items.purchase_price is NOT updated here — master price stays as-is.
            }

            // 4b. Replace extra costs
            $purchase->extraCosts()->delete();
            foreach ($extraCostRows as $row) {
                PurchaseExtraCost::create([
                    'purchase_id'   => $purchase->id,
                    'category_name' => $row['category'],
                    'amount'        => $row['amount'],
                ]);
            }

            // 5. Re-apply supplier due (allows negative credit)
            if ($request->supplier_id) {
                $supplier = Supplier::find($request->supplier_id);
                $supplier->increment('due_amount', $total - $request->paid_amount);
            }
        });

        return redirect()->route('purchases.show', $purchase)->with('success', 'রিসিভ সংশোধন সম্পন্ন। স্টক আপডেট হয়েছে।');
    }

    public function show(Purchase $purchase)
    {
        $purchase->load('items.item', 'supplier', 'user', 'extraCosts');
        return view('purchases.show', compact('purchase'));
    }

    public function destroy(Purchase $purchase)
    {
        // Allow shop admin OR super_admin who has entered this shop
        if (!auth()->user()->canManageShop()) {
            abort(403, 'শুধুমাত্র অ্যাডমিন মুছতে পারবেন।');
        }
        DB::transaction(function () use ($purchase) {
            // Restore stock
            foreach ($purchase->items as $pi) {
                Stock::where('item_id', $pi->item_id)->decrement('quantity', $pi->quantity);
            }
            // Reverse supplier due_amount effect
            if ($purchase->supplier_id) {
                $supplier = Supplier::find($purchase->supplier_id);
                // Reverse: undo (total - paid) applied on store
                $supplier->decrement('due_amount', $purchase->total_amount - $purchase->paid_amount);
            }
            $purchase->delete();
        });

        return redirect()->route('purchases.index')->with('success', 'রিসিভ মুছে ফেলা হয়েছে। স্টক কমানো হয়েছে।');
    }
}
