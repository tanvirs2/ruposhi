<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleExtraCost;
use App\Models\SaleLog;
use App\Models\Customer;
use App\Models\CustomerArea;
use App\Models\Item;
use App\Models\Stock;
use App\Models\ExtraCostCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\StoreConfigController;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $today    = now()->toDateString();
        $dateFrom = $request->date_from ?: null;
        $dateTo   = $request->date_to   ?: null;

        $query = Sale::with(['customer', 'items.item'])
            ->when($request->search, fn($q) =>
                // Wrap in a sub-group so the OR doesn't bypass the global shop_id scope
                $q->where(fn($sub) =>
                    $sub->whereHas('customer', fn($c) => $c->where('name', 'like', "%{$request->search}%"))
                        ->orWhere('id', $request->search)
                )
            )
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($dateFrom, fn($q) => $q->whereDate('sale_date', '>=', $dateFrom))
            ->when($dateTo,   fn($q) => $q->whereDate('sale_date', '<=', $dateTo));

        // Totals across ALL filtered results (not just current page)
        $grandTotal = (clone $query)->sum('total_amount');
        $grandPaid  = (clone $query)->sum('paid_amount');
        $grandDue   = (clone $query)->sum('due_amount');

        $sales = $query->latest('sale_date')->latest('id')->paginate(20);

        return view('sales.index', compact('sales', 'grandTotal', 'grandPaid', 'grandDue', 'dateFrom', 'dateTo'));
    }

    public function create()
    {
        // Select only columns needed by the JS dropdown — reduces payload significantly
        $customers      = Customer::with('area:id,name')
                            ->select('id','name','proprietor','phone','due_amount','area_id')
                            ->orderBy('name')->get();
        $items          = Item::with('stock:id,item_id,quantity')
                            ->select('id','name','sale_price','purchase_price')
                            ->orderBy('name')->get();
        $paymentMethods   = StoreConfigController::getGroupedPaymentMethods();
        $extraCategories  = ExtraCostCategory::orderBy('name')->pluck('name');
        $areas            = CustomerArea::orderBy('name')->get(['id', 'name']);
        return view('sales.create', compact('customers', 'items', 'paymentMethods', 'extraCategories', 'areas'));
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
            $discount   = $request->discount ?? 0;

            // Categorised extra costs — sum all rows
            $extraCostRows = collect($request->extra_costs ?? [])
                ->filter(fn($r) => !empty($r['category']) && isset($r['amount']) && $r['amount'] > 0);
            $extraCost = $extraCostRows->sum(fn($r) => (float) $r['amount']);

            $net        = $total - $discount + $extraCost;
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

            // Save categorised extra costs
            foreach ($extraCostRows as $row) {
                SaleExtraCost::create([
                    'sale_id'       => $sale->id,
                    'category_name' => $row['category'],
                    'amount'        => $row['amount'],
                ]);
            }

            if ($request->customer_id) {
                $customer = Customer::find($request->customer_id);
                // Net effect: customer.due += (net - paid)
                // Allows negative (credit balance when overpaid)
                $customer->update(['due_amount' => $customer->due_amount + $net - $request->paid_amount]);
            }
        });

        return redirect()->route('sales.show', $sale)->with('success', 'বিক্রয় সফলভাবে সম্পন্ন হয়েছে।');
    }

    public function show(Sale $sale)
    {
        $sale->load('items.item', 'customer', 'user', 'extraCosts');
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
        $sale->load('items.item', 'customer.area', 'extraCosts');
        $customers       = Customer::with('area:id,name')
                             ->select('id','name','proprietor','phone','due_amount','area_id')
                             ->orderBy('name')->get();
        $items           = Item::with('stock:id,item_id,quantity')
                             ->select('id','name','sale_price','purchase_price')
                             ->orderBy('name')->get();
        $paymentMethods  = StoreConfigController::getGroupedPaymentMethods();
        $extraCategories = ExtraCostCategory::orderBy('name')->pluck('name');
        return view('sales.edit', compact('sale', 'customers', 'items', 'paymentMethods', 'extraCategories'));
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

        // Log current state BEFORE changes
        $this->logSale($sale, 'edited', $request->edit_note);

        DB::transaction(function () use ($request, $sale) {
            // ── 1. Restore old stock ────────────────────────────────
            foreach ($sale->items as $oldItem) {
                Stock::where('item_id', $oldItem->item_id)->increment('quantity', $oldItem->quantity);
            }

            // ── 2. Reverse old customer due effect ──────────────────
            // Reverse: customer.due -= (old_net - old_paid) i.e. += (old_paid - old_net)
            if ($sale->customer_id) {
                $oldCustomer = Customer::find($sale->customer_id);
                $oldCustomer->due_amount += $sale->paid_amount - $sale->total_amount;
                $oldCustomer->save();
            }

            // ── 3. Delete old sale items ────────────────────────────
            $sale->items()->delete();

            // ── 4. Calculate new totals ─────────────────────────────
            $total = collect($request->items ?? [])->sum(fn($i) => $i['qty'] * $i['price']);
            $discount = $request->discount ?? 0;

            $extraCostRows = collect($request->extra_costs ?? [])
                ->filter(fn($r) => !empty($r['category']) && isset($r['amount']) && $r['amount'] > 0);
            $extraCost = $extraCostRows->sum(fn($r) => (float) $r['amount']);
            $net       = $total - $discount + $extraCost;
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

            // ── 7b. Replace extra costs ─────────────────────────────
            $sale->extraCosts()->delete();
            foreach ($extraCostRows as $row) {
                SaleExtraCost::create([
                    'sale_id'       => $sale->id,
                    'category_name' => $row['category'],
                    'amount'        => $row['amount'],
                ]);
            }

            // ── 8. Apply new customer due effect ────────────────────
            if ($request->customer_id) {
                $customer = Customer::find($request->customer_id);
                $customer->update(['due_amount' => $customer->due_amount + $net - $request->paid_amount]);
            }
        });

        return redirect()->route('sales.show', $sale)->with('success', 'বিক্রয় সফলভাবে সংশোধন করা হয়েছে।');
    }

    public function destroy(Sale $sale)
    {
        // Allow shop admin OR super_admin who has entered this shop
        if (!auth()->user()->canManageShop()) {
            abort(403, 'শুধুমাত্র অ্যাডমিন মুছতে পারবেন।');
        }
        DB::transaction(function () use ($sale) {
            // Log before delete
            $this->logSale($sale, 'deleted', 'মুছে ফেলা হয়েছে');

            // Restore stock
            foreach ($sale->items as $item) {
                Stock::where('item_id', $item->item_id)->increment('quantity', $item->quantity);
            }
            // Reverse customer due effect: undo (net - paid) that was applied on create
            if ($sale->customer_id) {
                $customer = Customer::find($sale->customer_id);
                $customer->update(['due_amount' => $customer->due_amount - ($sale->total_amount - $sale->paid_amount)]);
            }
            $sale->delete();
        });

        return redirect()->route('sales.index')->with('success', 'বিক্রয় মুছে ফেলা হয়েছে।');
    }

    // ── Helper: snapshot sale and log the action ─────────────
    private function logSale(Sale $sale, string $action, ?string $note = null): void
    {
        $sale->loadMissing(['items.item', 'customer']);
        SaleLog::create([
            'sale_id' => $sale->id,
            'action'  => $action,
            'user_id' => auth()->id(),
            'note'    => $note,
            'snapshot' => [
                'id'             => $sale->id,
                'sale_date'      => $sale->sale_date?->toDateString(),
                'customer_name'  => $sale->customer?->name,
                'total_amount'   => $sale->total_amount,
                'paid_amount'    => $sale->paid_amount,
                'due_amount'     => $sale->due_amount,
                'discount'       => $sale->discount,
                'extra_cost'     => $sale->extra_cost,
                'payment_method' => $sale->payment_method,
                'status'         => $sale->status,
                'notes'          => $sale->notes,
                'items'          => $sale->items->map(fn($si) => [
                    'item_name' => $si->item?->name,
                    'quantity'  => $si->quantity,
                    'price'     => $si->price,
                    'subtotal'  => $si->subtotal,
                ])->toArray(),
            ],
        ]);
    }
}
