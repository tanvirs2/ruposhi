<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseExtraCost;
use App\Models\PurchaseDeposit;
use App\Models\ExtraCostCategory;
use App\Models\DepositCategory;
use App\Models\Supplier;
use App\Models\Item;
use App\Models\Stock;
use App\Models\PendingEdit;
use App\Models\StoreConfig;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\StoreConfigController;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $dateFrom = $request->date_from ?: null;
        $dateTo   = $request->date_to   ?: null;
        $isStaff  = auth()->user()->role === 'staff';

        $query = Purchase::with('supplier', 'items.item', 'user:id,name')
            ->has('items')
            ->when($isStaff, fn($q) => $q->where('user_id', auth()->id()))
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

        $purchases = $query->latest('purchase_date')->latest('id')->paginate(20)->withQueryString();

        $pendingDeleteCount = Purchase::whereNotNull('delete_requested_at')->has('items')->count();
        $pendingEditCount   = PendingEdit::where('model_type', 'purchase')->where('status', 'pending')->count();

        $purchaseIds     = $purchases->pluck('id');
        $pendingEditsMap = PendingEdit::where('model_type', 'purchase')
            ->where('status', 'pending')
            ->whereIn('model_id', $purchaseIds)
            ->with('requestedBy:id,name')
            ->get()
            ->keyBy('model_id');

        $userSummary = (clone $query)
            ->reorder()
            ->select('user_id', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'), DB::raw('SUM(paid_amount) as paid'), DB::raw('SUM(due_amount) as due'))
            ->groupBy('user_id')
            ->with('user:id,name')
            ->get()
            ->map(fn($r) => ['name' => $r->user?->name ?? 'অজানা', 'count' => $r->count, 'total' => $r->total, 'paid' => $r->paid, 'due' => $r->due])
            ->sortByDesc('total')
            ->values();

        $data = compact('purchases', 'grandTotal', 'grandPaid', 'grandDue', 'grossDue', 'totalCredit', 'dateFrom', 'dateTo',
                        'pendingDeleteCount', 'pendingEditCount', 'pendingEditsMap', 'userSummary');

        if ($request->ajax()) {
            return view('purchases._results', $data);
        }

        return view('purchases.index', $data);
    }

    public function create()
    {
        $suppliers      = Supplier::select('id','name','proprietor','phone','address','due_amount')
                            ->orderBy('name')->get();
        $items          = Item::with('stock:id,item_id,quantity')
                            ->select('id','name','purchase_price')
                            ->orderBy('name')->get();
        $paymentMethods    = StoreConfigController::getGroupedPaymentMethods();
        $extraCategories   = ExtraCostCategory::orderBy('name')->pluck('name');
        $depositCategories = DepositCategory::orderBy('name')->pluck('name');
        return view('purchases.create', compact('suppliers', 'items', 'paymentMethods', 'extraCategories', 'depositCategories'));
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
            $depositRows   = collect($request->deposit_rows ?? [])
                ->filter(fn($r) => !empty($r['category']) && isset($r['amount']) && $r['amount'] > 0);
            $extraCost = $extraCostRows->sum(fn($r) => (float) $r['amount']);
            $deposit   = $depositRows->sum(fn($r) => (float) $r['amount']);
            $total     = $itemsTotal + $extraCost;

            // If items exist and paid > (total - deposit), split overpayment into
            // a separate no-item advance purchase so it appears in পরিশোধ তালিকা.
            $hasItems    = !empty($request->items);
            $netCost     = $total - $deposit;
            $overpaid    = $hasItems ? max(0.0, (float) $request->paid_amount - $netCost) : 0.0;
            $effectivePaid = (float) $request->paid_amount - $overpaid;
            $due           = $total - $effectivePaid - $deposit; // 0 when overpaid, positive when underpaid

            $purchase = Purchase::create([
                'supplier_id'    => $request->supplier_id ?: null,
                'user_id'        => auth()->id(),
                'total_amount'   => $total,
                'extra_cost'     => $extraCost,
                'paid_amount'    => $effectivePaid,
                'deposit_amount' => $deposit,
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
            }

            // Save categorised extra costs
            foreach ($extraCostRows as $row) {
                PurchaseExtraCost::create([
                    'purchase_id'   => $purchase->id,
                    'category_name' => $row['category'],
                    'amount'        => $row['amount'],
                ]);
            }

            // Save categorised deposits
            foreach ($depositRows as $row) {
                PurchaseDeposit::create([
                    'purchase_id'   => $purchase->id,
                    'category_name' => $row['category'],
                    'amount'        => $row['amount'],
                ]);
            }

            // Supplier due uses the full original paid amount (effectivePaid + overpaid)
            if ($request->supplier_id) {
                $supplier = Supplier::find($request->supplier_id);
                $supplier->increment('due_amount', $total - (float) $request->paid_amount - $deposit);
            }

            // Create the overpayment as a standalone advance in পরিশোধ তালিকা
            if ($overpaid > 0 && $request->supplier_id) {
                Purchase::create([
                    'supplier_id'    => $request->supplier_id,
                    'user_id'        => auth()->id(),
                    'total_amount'   => 0,
                    'extra_cost'     => 0,
                    'paid_amount'    => $overpaid,
                    'deposit_amount' => 0,
                    'due_amount'     => -$overpaid,
                    'payment_method' => $request->payment_method ?? 'নগদ',
                    'notes'          => '__advance_for:' . $purchase->id,
                    'purchase_date'  => $request->purchase_date,
                ]);
            }
        });

        return redirect()->route('purchases.show', $purchase)->with('success', 'পণ্য গ্রহণ সম্পন্ন হয়েছে। স্টক আপডেট হয়েছে।');
    }

    public function edit(Purchase $purchase)
    {
        $purchase->load('items.item', 'supplier', 'user', 'extraCosts', 'deposits');
        $suppliers         = Supplier::select('id','name','proprietor','phone','address','due_amount')
                                ->orderBy('name')->get();
        $items             = Item::with('stock:id,item_id,quantity')
                                ->select('id','name','purchase_price')
                                ->orderBy('name')->get();
        $paymentMethods    = StoreConfigController::getGroupedPaymentMethods();
        $extraCategories   = ExtraCostCategory::orderBy('name')->pluck('name');
        $depositCategories = DepositCategory::orderBy('name')->pluck('name');
        return view('purchases.edit', compact('purchase', 'suppliers', 'items', 'paymentMethods', 'extraCategories', 'depositCategories'));
    }

    public function update(Request $request, Purchase $purchase)
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

        // Staff: store as pending edit for admin approval
        if (!auth()->user()->canManageShop()) {
            $this->savePurchasePendingEdit($purchase, $request);
            return redirect()->route('purchases.show', $purchase)
                ->with('success', 'সংশোধনের অনুরোধ পাঠানো হয়েছে। অ্যাডমিনের অনুমোদনের অপেক্ষায়।');
        }

        DB::transaction(function () use ($request, $purchase) {
            // 1. Reverse old stock & supplier effect
            foreach ($purchase->items as $pi) {
                Stock::where('item_id', $pi->item_id)->decrement('quantity', $pi->quantity);
            }

            // Find any linked overpayment advance created when this purchase was saved
            $linkedAdvance = Purchase::where('notes', '__advance_for:' . $purchase->id)
                ->doesntHave('items')
                ->first();

            if ($purchase->supplier_id) {
                $supplier = Supplier::find($purchase->supplier_id);
                // Reverse main purchase supplier effect
                $supplier->decrement('due_amount', $purchase->total_amount - $purchase->paid_amount - $purchase->deposit_amount);
                // Also reverse linked advance supplier effect (0 - advance.paid - 0 = -overpaid → reversing adds overpaid)
                if ($linkedAdvance) {
                    $supplier->decrement('due_amount', $linkedAdvance->total_amount - $linkedAdvance->paid_amount - $linkedAdvance->deposit_amount);
                }
            }
            if ($linkedAdvance) {
                $linkedAdvance->delete();
            }

            // 2. Delete old items
            $purchase->items()->delete();

            // 3. Recalculate & save
            $itemsTotal    = collect($request->items ?? [])->sum(fn($i) => $i['qty'] * $i['price']);
            $extraCostRows = collect($request->extra_costs ?? [])
                ->filter(fn($r) => !empty($r['category']) && isset($r['amount']) && $r['amount'] > 0);
            $depositRows   = collect($request->deposit_rows ?? [])
                ->filter(fn($r) => !empty($r['category']) && isset($r['amount']) && $r['amount'] > 0);
            $extraCost = $extraCostRows->sum(fn($r) => (float) $r['amount']);
            $deposit   = $depositRows->sum(fn($r) => (float) $r['amount']);
            $total     = $itemsTotal + $extraCost;

            $hasItems      = !empty($request->items);
            $netCost       = $total - $deposit;
            $overpaid      = $hasItems ? max(0.0, (float) $request->paid_amount - $netCost) : 0.0;
            $effectivePaid = (float) $request->paid_amount - $overpaid;
            $due           = $total - $effectivePaid - $deposit;

            $purchase->update([
                'supplier_id'    => $request->supplier_id ?: null,
                'total_amount'   => $total,
                'extra_cost'     => $extraCost,
                'paid_amount'    => $effectivePaid,
                'deposit_amount' => $deposit,
                'due_amount'     => $due,
                'payment_method' => $request->payment_method ?? 'নগদ',
                'notes'          => $request->notes,
                'purchase_date'  => $request->purchase_date,
            ]);

            // 4. Re-add items & stock
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

            // 4c. Replace deposits
            $purchase->deposits()->delete();
            foreach ($depositRows as $row) {
                PurchaseDeposit::create([
                    'purchase_id'   => $purchase->id,
                    'category_name' => $row['category'],
                    'amount'        => $row['amount'],
                ]);
            }

            // 5. Re-apply supplier due using full original paid amount
            if ($request->supplier_id) {
                $supplier = Supplier::find($request->supplier_id);
                $supplier->increment('due_amount', $total - (float) $request->paid_amount - $deposit);
            }

            // 6. Re-create advance record if still overpaid
            if ($overpaid > 0 && $request->supplier_id) {
                Purchase::create([
                    'supplier_id'    => $request->supplier_id,
                    'user_id'        => auth()->id(),
                    'total_amount'   => 0,
                    'extra_cost'     => 0,
                    'paid_amount'    => $overpaid,
                    'deposit_amount' => 0,
                    'due_amount'     => -$overpaid,
                    'payment_method' => $request->payment_method ?? 'নগদ',
                    'notes'          => '__advance_for:' . $purchase->id,
                    'purchase_date'  => $request->purchase_date,
                ]);
            }
        });

        return redirect()->route('purchases.show', $purchase)->with('success', 'রিসিভ সংশোধন সম্পন্ন। স্টক আপডেট হয়েছে।');
    }

    public function show(Purchase $purchase)
    {
        $purchase->load('items.item', 'supplier', 'user', 'extraCosts', 'deposits');
        return view('purchases.show', compact('purchase'));
    }

    public function destroy(Request $request, Purchase $purchase)
    {
        // Allow shop admin OR super_admin who has entered this shop
        if (!auth()->user()->canManageShop()) {
            abort(403, 'শুধুমাত্র অ্যাডমিন মুছতে পারবেন।');
        }
        // No-item rows are advance payments (shown in the supplier-payment list)
        $hadItems = $purchase->items()->exists();
        DB::transaction(function () use ($purchase) {
            // Restore stock
            foreach ($purchase->items as $pi) {
                Stock::where('item_id', $pi->item_id)->decrement('quantity', $pi->quantity);
            }

            $linkedAdvance = Purchase::where('notes', '__advance_for:' . $purchase->id)
                ->doesntHave('items')
                ->first();

            // Reverse supplier due_amount effect (main + linked advance)
            if ($purchase->supplier_id) {
                $supplier = Supplier::find($purchase->supplier_id);
                $supplier->decrement('due_amount', $purchase->total_amount - $purchase->paid_amount - $purchase->deposit_amount);
                if ($linkedAdvance) {
                    $supplier->decrement('due_amount', $linkedAdvance->total_amount - $linkedAdvance->paid_amount - $linkedAdvance->deposit_amount);
                }
            }

            if ($linkedAdvance) {
                $linkedAdvance->delete();
            }
            $purchase->delete();
        });

        $msg = $hadItems
            ? 'রিসিভ মুছে ফেলা হয়েছে। স্টক কমানো হয়েছে।'
            : 'পরিশোধ মুছে ফেলা হয়েছে। সরবরাহকারীর বকেয়া পুনরুদ্ধার হয়েছে।';

        // Return to the list the delete was triggered from
        if ($request->input('redirect_to') === 'supplier-payments') {
            return redirect()->route('supplier-payments.index')->with('success', $msg);
        }
        return redirect()->route('purchases.index')->with('success', $msg);
    }

    // ── Staff: request deletion approval ─────────────────────
    public function requestDelete(Purchase $purchase)
    {
        if (auth()->user()->canManageShop()) {
            return back()->with('error', 'অ্যাডমিন সরাসরি মুছতে পারেন।');
        }
        if ($purchase->delete_requested_at) {
            return back()->with('error', 'ইতিমধ্যে অনুরোধ পাঠানো হয়েছে।');
        }
        $purchase->update([
            'delete_requested_at' => now(),
            'delete_requested_by' => auth()->id(),
        ]);
        return back()->with('success', 'ডিলিট অনুরোধ পাঠানো হয়েছে। অ্যাডমিনের অনুমোদনের অপেক্ষায়।');
    }

    // ── Admin: approve pending deletion ──────────────────────
    public function approveDelete(Purchase $purchase)
    {
        if (!auth()->user()->canManageShop()) abort(403);
        $hadItems = $purchase->items()->exists();
        DB::transaction(function () use ($purchase) {
            foreach ($purchase->items as $pi) {
                Stock::where('item_id', $pi->item_id)->decrement('quantity', $pi->quantity);
            }
            $linkedAdvance = Purchase::where('notes', '__advance_for:' . $purchase->id)
                ->doesntHave('items')->first();
            if ($purchase->supplier_id) {
                $supplier = Supplier::find($purchase->supplier_id);
                $supplier->decrement('due_amount', $purchase->total_amount - $purchase->paid_amount - $purchase->deposit_amount);
                if ($linkedAdvance) {
                    $supplier->decrement('due_amount', $linkedAdvance->total_amount - $linkedAdvance->paid_amount - $linkedAdvance->deposit_amount);
                }
            }
            if ($linkedAdvance) $linkedAdvance->delete();
            $purchase->delete();
        });
        $msg = $hadItems ? 'রিসিভ মুছে ফেলা হয়েছে। স্টক কমানো হয়েছে।'
                         : 'পরিশোধ মুছে ফেলা হয়েছে।';
        return back()->with('success', $msg);
    }

    // ── Admin: reject pending deletion ───────────────────────
    public function rejectDelete(Purchase $purchase)
    {
        if (!auth()->user()->canManageShop()) abort(403);
        $purchase->update(['delete_requested_at' => null, 'delete_requested_by' => null]);
        return back()->with('success', 'ডিলিট অনুরোধ বাতিল করা হয়েছে।');
    }

    // ── Admin: approve pending edit ───────────────────────────
    public function approveEdit(PendingEdit $pendingEdit)
    {
        if (!auth()->user()->canManageShop()) abort(403);
        if ($pendingEdit->model_type !== 'purchase' || !$pendingEdit->isPending()) {
            return back()->with('error', 'অনুরোধটি আর বৈধ নয়।');
        }

        $purchase = Purchase::findOrFail($pendingEdit->model_id);
        $d        = $pendingEdit->proposed_data;

        DB::transaction(function () use ($purchase, $d) {
            foreach ($purchase->items as $pi) {
                Stock::where('item_id', $pi->item_id)->decrement('quantity', $pi->quantity);
            }
            $linkedAdvance = Purchase::where('notes', '__advance_for:' . $purchase->id)->doesntHave('items')->first();
            if ($purchase->supplier_id) {
                $supplier = Supplier::find($purchase->supplier_id);
                $supplier?->decrement('due_amount', $purchase->total_amount - $purchase->paid_amount - $purchase->deposit_amount);
                if ($linkedAdvance) $supplier?->decrement('due_amount', $linkedAdvance->total_amount - $linkedAdvance->paid_amount - $linkedAdvance->deposit_amount);
            }
            if ($linkedAdvance) $linkedAdvance->delete();
            $purchase->items()->delete();

            $itemsTotal = collect($d['items'] ?? [])->sum(fn($i) => $i['qty'] * $i['price']);
            $extraCosts = collect($d['extra_costs'] ?? [])->filter(fn($r) => !empty($r['category']) && ($r['amount'] ?? 0) > 0);
            $deposits   = collect($d['deposit_rows'] ?? [])->filter(fn($r) => !empty($r['category']) && ($r['amount'] ?? 0) > 0);
            $extraCost  = $extraCosts->sum(fn($r) => (float) $r['amount']);
            $deposit    = $deposits->sum(fn($r) => (float) $r['amount']);
            $total      = $itemsTotal + $extraCost;
            $hasItems   = !empty($d['items']);
            $overpaid   = $hasItems ? max(0.0, (float) $d['paid_amount'] - ($total - $deposit)) : 0.0;
            $effPaid    = (float) $d['paid_amount'] - $overpaid;
            $due        = $total - $effPaid - $deposit;

            $purchase->update([
                'supplier_id'    => $d['supplier_id'] ?: null,
                'total_amount'   => $total,
                'extra_cost'     => $extraCost,
                'paid_amount'    => $effPaid,
                'deposit_amount' => $deposit,
                'due_amount'     => $due,
                'payment_method' => $d['payment_method'] ?? 'নগদ',
                'notes'          => $d['notes'] ?? null,
                'purchase_date'  => $d['purchase_date'],
            ]);

            foreach ($d['items'] ?? [] as $row) {
                PurchaseItem::create(['purchase_id' => $purchase->id, 'item_id' => $row['id'], 'quantity' => $row['qty'], 'price' => $row['price'], 'subtotal' => $row['qty'] * $row['price']]);
                $stock = Stock::firstOrCreate(['item_id' => $row['id']], ['quantity' => 0, 'min_quantity' => 5]);
                $stock->increment('quantity', $row['qty']);
            }

            $purchase->extraCosts()->delete();
            foreach ($extraCosts as $row) {
                PurchaseExtraCost::create(['purchase_id' => $purchase->id, 'category_name' => $row['category'], 'amount' => $row['amount']]);
            }
            $purchase->deposits()->delete();
            foreach ($deposits as $row) {
                PurchaseDeposit::create(['purchase_id' => $purchase->id, 'category_name' => $row['category'], 'amount' => $row['amount']]);
            }

            if ($d['supplier_id'] ?? null) {
                Supplier::find($d['supplier_id'])?->increment('due_amount', $total - (float) $d['paid_amount'] - $deposit);
            }
            if ($overpaid > 0 && ($d['supplier_id'] ?? null)) {
                Purchase::create(['supplier_id' => $d['supplier_id'], 'user_id' => auth()->id(), 'total_amount' => 0, 'extra_cost' => 0, 'paid_amount' => $overpaid, 'deposit_amount' => 0, 'due_amount' => -$overpaid, 'payment_method' => $d['payment_method'] ?? 'নগদ', 'notes' => '__advance_for:' . $purchase->id, 'purchase_date' => $d['purchase_date']]);
            }
        });

        $pendingEdit->update(['status' => 'approved', 'decided_by' => auth()->id(), 'decided_at' => now()]);
        return back()->with('success', 'সংশোধন অনুমোদিত এবং প্রযোজ্য হয়েছে।');
    }

    // ── Admin: reject pending edit ────────────────────────────
    public function rejectEdit(PendingEdit $pendingEdit)
    {
        if (!auth()->user()->canManageShop()) abort(403);
        $pendingEdit->update(['status' => 'rejected', 'decided_by' => auth()->id(), 'decided_at' => now()]);
        return back()->with('success', 'সংশোধন অনুরোধ বাতিল করা হয়েছে।');
    }

    // ── Save pending edit for staff submission ────────────────
    private function savePurchasePendingEdit(Purchase $purchase, Request $request): void
    {
        $purchase->loadMissing(['items.item', 'supplier', 'extraCosts', 'deposits']);

        $original = [
            'purchase_date'  => $purchase->purchase_date?->toDateString(),
            'supplier_id'    => $purchase->supplier_id,
            'supplier_name'  => $purchase->supplier?->name,
            'payment_method' => $purchase->payment_method,
            'notes'          => $purchase->notes ?? '',
            'paid_amount'    => round((float) $purchase->paid_amount, 2),
            'extra_costs'    => $purchase->extraCosts->map(fn($e) => ['category' => $e->category_name, 'amount' => round((float)$e->amount, 2)])->sortBy('category')->values()->toArray(),
            'deposit_rows'   => $purchase->deposits->map(fn($d) => ['category' => $d->category_name, 'amount' => round((float)$d->amount, 2)])->sortBy('category')->values()->toArray(),
            'items'          => $purchase->items->map(fn($i) => ['id' => $i->item_id, 'name' => $i->item?->name ?? '?', 'qty' => round((float)$i->quantity, 3), 'price' => round((float)$i->price, 2)])->sortBy('id')->values()->toArray(),
        ];

        $proposed = [
            'purchase_date'  => $request->purchase_date,
            'supplier_id'    => $request->supplier_id ? (int) $request->supplier_id : null,
            'payment_method' => $request->payment_method ?? 'নগদ',
            'notes'          => $request->notes ?? '',
            'paid_amount'    => round((float) $request->paid_amount, 2),
            'extra_costs'    => collect($request->extra_costs ?? [])->filter(fn($r) => !empty($r['category']) && ($r['amount'] ?? 0) > 0)->map(fn($r) => ['category' => $r['category'], 'amount' => round((float)$r['amount'], 2)])->sortBy('category')->values()->toArray(),
            'deposit_rows'   => collect($request->deposit_rows ?? [])->filter(fn($r) => !empty($r['category']) && ($r['amount'] ?? 0) > 0)->map(fn($r) => ['category' => $r['category'], 'amount' => round((float)$r['amount'], 2)])->sortBy('category')->values()->toArray(),
            'items'          => collect($request->items ?? [])->map(fn($i) => ['id' => (int)$i['id'], 'qty' => round((float)$i['qty'], 3), 'price' => round((float)$i['price'], 2)])->sortBy('id')->values()->toArray(),
        ];

        $origSig = json_encode(array_diff_key($original, array_flip(['supplier_name'])));
        $hasChanges = $origSig !== json_encode($proposed);

        PendingEdit::where('model_type', 'purchase')->where('model_id', $purchase->id)->where('status', 'pending')
            ->update(['status' => 'superseded']);

        $pending = PendingEdit::create([
            'model_type'    => 'purchase',
            'model_id'      => $purchase->id,
            'requested_by'  => auth()->id(),
            'original_data' => $original,
            'proposed_data' => $proposed,
            'has_changes'   => $hasChanges,
            'status'        => $hasChanges ? 'pending' : 'no_change',
        ]);

        if ($hasChanges) {
            $this->sendPurchaseEditSms($purchase, $pending);
        }
    }

    private function sendPurchaseEditSms(Purchase $purchase, PendingEdit $pending): void
    {
        try {
            $phone = StoreConfig::get('store_phone', '');
            if (empty($phone)) return;

            $ref   = '#RCV-' . str_pad($purchase->id, 4, '0', STR_PAD_LEFT);
            $staff = auth()->user()->name;
            $link  = config('app.url') . '/purchases/' . $purchase->id;
            $msg   = "[POS] সংশোধন অনুরোধ\nরেকর্ড: {$ref}\nস্টাফ: {$staff}\nলিংক: {$link}";

            $result = app(SmsService::class)->send($phone, $msg);
            if ($result['success']) $pending->update(['sms_sent' => true]);
        } catch (\Exception $e) {}
    }
}
