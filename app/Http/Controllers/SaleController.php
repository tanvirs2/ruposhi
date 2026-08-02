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
use App\Models\PendingEdit;
use App\Services\SmsService;
use App\Models\StoreConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\StoreConfigController;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $today    = now()->toDateString();
        $dateFrom = $request->date_from ?: $today;
        $dateTo   = $request->date_to   ?: $today;

        $isStaff = auth()->user()->role === 'staff';

        $query = Sale::with(['customer', 'items.item', 'user:id,name'])
            ->when($isStaff, fn($q) => $q->where('user_id', auth()->id()))
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

        // User-wise summary cloned BEFORE latest() mutates $query
        $userSummary = (clone $query)
            ->select('user_id', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'), DB::raw('SUM(paid_amount) as paid'), DB::raw('SUM(due_amount) as due'))
            ->groupBy('user_id')
            ->orderBy('user_id')
            ->with('user:id,name')
            ->get()
            ->map(fn($r) => ['name' => $r->user?->name ?? 'অজানা', 'count' => $r->count, 'total' => $r->total, 'paid' => $r->paid, 'due' => $r->due])
            ->sortByDesc('total')
            ->values();

        $sales = $query->latest('sale_date')->latest('id')->paginate(20)->withQueryString();

        $pendingDeleteCount = Sale::whereNotNull('delete_requested_at')->count();
        $pendingEditCount   = PendingEdit::where('model_type', 'sale')->where('status', 'pending')->count();

        $saleIds         = $sales->pluck('id');
        $pendingEditsMap = PendingEdit::where('model_type', 'sale')
            ->where('status', 'pending')
            ->whereIn('model_id', $saleIds)
            ->with('requestedBy:id,name')
            ->get()
            ->keyBy('model_id');

        $data = compact('sales', 'grandTotal', 'grandPaid', 'grandDue', 'dateFrom', 'dateTo',
                        'pendingDeleteCount', 'pendingEditCount', 'pendingEditsMap', 'userSummary');

        if ($request->ajax()) {
            return view('sales._results', $data);
        }

        return view('sales.index', $data);
    }

    public function create()
    {
        // Customers are loaded on demand via server-side search (customers.search)
        // instead of embedding the whole table — keeps the page light at scale.
        // Only the pre-selected customer (e.g. "নতুন বিক্রয়" from a ledger) is sent.
        $preCustomer = request('customer_id')
            ? Customer::with('area:id,name')
                ->select('id','name','proprietor','phone','due_amount','credit_limit','area_id')
                ->find(request('customer_id'))
            : null;

        $items          = Item::with('stock:id,item_id,quantity')
                            ->select('id','name','sale_price','purchase_price')
                            ->orderBy('name')->get();

        // Frequently sold items (last 60 days) — quick-pick list on wide screens.
        // Only the item IDs are computed here; price/stock still come from $items above.
        $frequentItemIds = SaleItem::whereHas('sale', fn($q) => $q->where('sale_date', '>=', now()->subDays(60)))
                            ->selectRaw('item_id, COUNT(*) as cnt')
                            ->groupBy('item_id')
                            ->orderByDesc('cnt')
                            ->limit(10)
                            ->pluck('item_id');

        // User's own favorite (starred) items — separate from the auto-ranked
        // frequent list, since this is a manual per-user preference.
        $favoriteItemIds  = \App\Models\ItemFavorite::where('user_id', auth()->id())->pluck('item_id');

        $paymentMethods   = StoreConfigController::getGroupedPaymentMethods();
        $extraCategories  = ExtraCostCategory::orderBy('name')->pluck('name');
        $areas            = CustomerArea::orderBy('name')->get(['id', 'name']);
        return view('sales.create', compact('preCustomer', 'items', 'frequentItemIds', 'favoriteItemIds', 'paymentMethods', 'extraCategories', 'areas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'sale_date'    => 'required|date',
            'items'        => 'nullable|array',
            'items.*.id'   => 'required_with:items|exists:items,id',
            'items.*.qty'  => 'required_with:items|numeric|min:0.01',
            'items.*.price'=> 'required_with:items|numeric|min:0.01',
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

            // Capture the customer's outstanding balance before this sale.
            // lockForUpdate serializes concurrent sales on the same customer so
            // two simultaneous saves can't both read the same previous_due.
            $previousDue = $request->customer_id
                ? Customer::whereKey($request->customer_id)->lockForUpdate()->value('due_amount')
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
                // Net effect: customer.due += (net - paid). Atomic increment
                // avoids lost-update races; allows negative (credit balance).
                $customer->increment('due_amount', $net - $request->paid_amount);
            }
        });

        // SMS: notify customer (skip walk-in, skip if toggle off)
        $sale->load('customer', 'items.item');
        if (StoreConfig::get('sms_on_sale', '1') == '1' && $sale->customer && $sale->customer->phone) {
            $storeName = StoreConfig::get('store_name', 'দোকান');
            $hasItems  = $sale->items->isNotEmpty();
            if ($hasItems) {
                $msg = "{$storeName}\nবিক্রয় #" . str_pad($sale->id, 6, '0', STR_PAD_LEFT)
                    . "\nমোট: ৳" . number_format($sale->total_amount, 0)
                    . "\nপরিশোধ: ৳" . number_format($sale->paid_amount, 0);
                if ($sale->due_amount > 0) $msg .= "\nবাকী: ৳" . number_format($sale->due_amount, 0);
                $msg .= "\nধন্যবাদ।";
            } else {
                $msg = "{$storeName}\nপেমেন্ট #" . str_pad($sale->id, 6, '0', STR_PAD_LEFT)
                    . "\nপরিশোধ: ৳" . number_format($sale->paid_amount, 0)
                    . "\nধন্যবাদ।";
            }
            app(SmsService::class)->send($sale->customer->phone, $msg, $sale->customer->name);
        }

        return redirect()->route('sales.show', $sale)->with('success', 'বিক্রয় সফলভাবে সম্পন্ন হয়েছে।');
    }

    public function show(Sale $sale)
    {
        $sale->load('items.item', 'customer', 'user.receiptProfile', 'extraCosts');
        // Cash memo shows the profile assigned to the staff who made the sale,
        // if any — otherwise the shop's default store info.
        $profile = $sale->user?->receiptProfile;
        $store = [
            'name'    => $profile->store_name    ?? \App\Models\StoreConfig::get('store_name', 'আমার চালের দোকান'),
            'owner'   => $profile->store_owner   ?? \App\Models\StoreConfig::get('store_owner', ''),
            'tagline' => $profile->store_tagline ?? \App\Models\StoreConfig::get('store_tagline', ''),
            'phone'   => $profile->store_phone   ?? \App\Models\StoreConfig::get('store_phone', ''),
            'phone2'  => $profile->store_phone2  ?? \App\Models\StoreConfig::get('store_phone2', ''),
            'address' => $profile->store_address ?? \App\Models\StoreConfig::get('store_address', ''),
        ];
        return view('sales.show', compact('sale', 'store'));
    }

    public function edit(Sale $sale)
    {
        $sale->load('items.item', 'customer.area', 'extraCosts');
        // Server-side customer search — only seed the sale's existing customer
        $preCustomer = $sale->customer
            ? $sale->customer->only(['id','name','proprietor','phone','due_amount','credit_limit','area_id'])
                + ['area' => $sale->customer->area ? ['id' => $sale->customer->area->id, 'name' => $sale->customer->area->name] : null]
            : null;
        $items           = Item::with('stock:id,item_id,quantity')
                             ->select('id','name','sale_price','purchase_price')
                             ->orderBy('name')->get();
        $paymentMethods  = StoreConfigController::getGroupedPaymentMethods();
        $extraCategories = ExtraCostCategory::orderBy('name')->pluck('name');
        return view('sales.edit', compact('sale', 'preCustomer', 'items', 'paymentMethods', 'extraCategories'));
    }

    public function update(Request $request, Sale $sale)
    {
        $request->validate([
            'sale_date'    => 'required|date',
            'items'        => 'nullable|array',
            'items.*.id'   => 'required_with:items|exists:items,id',
            'items.*.qty'  => 'required_with:items|numeric|min:0.01',
            'items.*.price'=> 'required_with:items|numeric|min:0.01',
            'paid_amount'  => 'required|numeric|min:0',
        ]);

        if (empty($request->items) && !$request->customer_id) {
            return back()->withErrors(['customer_id' => 'আইটেম ছাড়া বিক্রয়ে কাস্টমার নির্বাচন আবশ্যক।'])->withInput();
        }

        // Staff: store as pending edit for admin approval
        if (!auth()->user()->canManageShop()) {
            $this->saveSalePendingEdit($sale, $request);
            return redirect()->route('sales.show', $sale)
                ->with('success', 'সংশোধনের অনুরোধ পাঠানো হয়েছে। অ্যাডমিনের অনুমোদনের অপেক্ষায়।');
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
                $oldCustomer->increment('due_amount', $sale->paid_amount - $sale->total_amount);
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
                ? Customer::whereKey($request->customer_id)->lockForUpdate()->value('due_amount')
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
                $customer->increment('due_amount', $net - $request->paid_amount);
            }
        });

        // SMS: notify admin of edit (skip if toggle off)
        $adminPhone = StoreConfig::get('sms_on_edit', '1') == '1' ? StoreConfig::get('store_phone', '') : '';
        if ($adminPhone) {
            $sale->load('customer');
            $storeName = StoreConfig::get('store_name', 'দোকান');
            $msg = "[{$storeName}] বিক্রয় সংশোধন\n#" . str_pad($sale->id, 6, '0', STR_PAD_LEFT)
                . " | " . ($sale->customer?->name ?? 'ওয়াক-ইন')
                . "\nসংশোধনকারী: " . (auth()->user()->name)
                . "\nনতুন মোট: ৳" . number_format($sale->total_amount, 0);
            if ($request->edit_note) $msg .= "\nকারণ: {$request->edit_note}";
            app(SmsService::class)->send($adminPhone, $msg);
        }

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
                $customer->decrement('due_amount', $sale->total_amount - $sale->paid_amount);
            }
            $sale->delete();
        });

        // No admin SMS here — admin deleted it themselves, they already know.
        return redirect()->route('sales.index')->with('success', 'বিক্রয় মুছে ফেলা হয়েছে।');
    }

    // ── Staff: request deletion approval ─────────────────────
    public function requestDelete(Sale $sale)
    {
        if (auth()->user()->canManageShop()) {
            return back()->with('error', 'অ্যাডমিন সরাসরি মুছতে পারেন।');
        }
        if ($sale->delete_requested_at) {
            return back()->with('error', 'ইতিমধ্যে অনুরোধ পাঠানো হয়েছে।');
        }
        $sale->update([
            'delete_requested_at' => now(),
            'delete_requested_by' => auth()->id(),
        ]);

        // SMS: alert admin the moment a staff member requests a deletion —
        // that's the point admin needs to be told to go review it. No SMS is
        // needed after admin approves — they clicked approve themselves, they
        // already know.
        $adminPhone = StoreConfig::get('sms_on_delete', '1') == '1' ? StoreConfig::get('store_phone', '') : '';
        if ($adminPhone) {
            $sale->load('customer');
            $storeName = StoreConfig::get('store_name', 'দোকান');
            // Link straight to the অনুমোদন কেন্দ্র (approvals hub) — that's the
            // page with the actual অনুমোদন/বাতিল buttons for this request.
            $link = route('approvals.index');
            $msg = "[{$storeName}] বিক্রয় ডিলিট অনুরোধ\n#" . str_pad($sale->id, 6, '0', STR_PAD_LEFT)
                . " | " . ($sale->customer?->name ?? 'ওয়াক-ইন')
                . "\nমোট: ৳" . number_format($sale->total_amount, 0)
                . "\nঅনুরোধ করেছেন: " . auth()->user()->name
                . "\nলিংক: {$link}";
            app(SmsService::class)->send($adminPhone, $msg);
        }

        return back()->with('success', 'ডিলিট অনুরোধ পাঠানো হয়েছে। অ্যাডমিনের অনুমোদনের অপেক্ষায়।');
    }

    // ── Admin: approve pending deletion ──────────────────────
    public function approveDelete(Sale $sale)
    {
        if (!auth()->user()->canManageShop()) abort(403);
        DB::transaction(function () use ($sale) {
            $this->logSale($sale, 'deleted', 'মুছে ফেলা হয়েছে (স্টাফ অনুরোধ অনুমোদিত)');
            foreach ($sale->items as $item) {
                Stock::where('item_id', $item->item_id)->increment('quantity', $item->quantity);
            }
            if ($sale->customer_id) {
                $customer = Customer::find($sale->customer_id);
                $customer->decrement('due_amount', $sale->total_amount - $sale->paid_amount);
            }
            $sale->delete();
        });
        return back()->with('success', 'বিক্রয় মুছে ফেলা হয়েছে।');
    }

    // ── Admin: reject pending deletion ───────────────────────
    public function rejectDelete(Sale $sale)
    {
        if (!auth()->user()->canManageShop()) abort(403);
        $sale->update(['delete_requested_at' => null, 'delete_requested_by' => null]);
        return back()->with('success', 'ডিলিট অনুরোধ বাতিল করা হয়েছে।');
    }

    // ── Admin: approve pending edit ───────────────────────────
    public function approveEdit(PendingEdit $pendingEdit)
    {
        if (!auth()->user()->canManageShop()) abort(403);
        if ($pendingEdit->model_type !== 'sale' || !$pendingEdit->isPending()) {
            return back()->with('error', 'অনুরোধটি আর বৈধ নয়।');
        }

        $sale = Sale::findOrFail($pendingEdit->model_id);
        $d    = $pendingEdit->proposed_data;

        $this->logSale($sale, 'edited', 'সংশোধন অনুরোধ অনুমোদিত (স্টাফ: ' . ($pendingEdit->requestedBy?->name ?? '?') . ')');

        DB::transaction(function () use ($sale, $d) {
            foreach ($sale->items as $oldItem) {
                Stock::where('item_id', $oldItem->item_id)->increment('quantity', $oldItem->quantity);
            }
            if ($sale->customer_id) {
                Customer::find($sale->customer_id)?->increment('due_amount', $sale->paid_amount - $sale->total_amount);
            }
            $sale->items()->delete();

            $total      = collect($d['items'] ?? [])->sum(fn($i) => $i['qty'] * $i['price']);
            $discount   = (float) ($d['discount'] ?? 0);
            $extraCosts = collect($d['extra_costs'] ?? [])->filter(fn($r) => !empty($r['category']) && ($r['amount'] ?? 0) > 0);
            $extraCost  = $extraCosts->sum(fn($r) => (float) $r['amount']);
            $net        = $total - $discount + $extraCost;
            $due        = max(0, $net - (float) $d['paid_amount']);

            $previousDue = isset($d['customer_id']) && $d['customer_id']
                ? Customer::whereKey($d['customer_id'])->lockForUpdate()->value('due_amount') ?? 0
                : 0;

            $sale->update([
                'customer_id'    => $d['customer_id'] ?: null,
                'total_amount'   => $net,
                'discount'       => $discount,
                'extra_cost'     => $extraCost,
                'paid_amount'    => (float) $d['paid_amount'],
                'due_amount'     => $due,
                'previous_due'   => $previousDue,
                'status'         => $d['status'] ?? 'completed',
                'payment_method' => $d['payment_method'] ?? 'নগদ',
                'notes'          => $d['notes'] ?? null,
                'sale_date'      => $d['sale_date'],
                'is_edited'      => true,
                'edit_note'      => $d['edit_note'] ?? null,
            ]);

            foreach ($d['items'] ?? [] as $row) {
                SaleItem::create(['sale_id' => $sale->id, 'item_id' => $row['id'], 'quantity' => $row['qty'], 'price' => $row['price'], 'subtotal' => $row['qty'] * $row['price']]);
                Stock::where('item_id', $row['id'])->decrement('quantity', $row['qty']);
            }

            $sale->extraCosts()->delete();
            foreach ($extraCosts as $row) {
                SaleExtraCost::create(['sale_id' => $sale->id, 'category_name' => $row['category'], 'amount' => $row['amount']]);
            }

            if ($d['customer_id'] ?? null) {
                Customer::find($d['customer_id'])?->increment('due_amount', $net - (float) $d['paid_amount']);
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
        if ($pendingEdit->model_type === 'sale') {
            $sale = Sale::find($pendingEdit->model_id);
            if ($sale) $this->logSale($sale, 'edit_rejected', 'সংশোধন অনুরোধ বাতিল করা হয়েছে');
        }
        return back()->with('success', 'সংশোধন অনুরোধ বাতিল করা হয়েছে।');
    }

    // ── Save pending edit for staff submission ────────────────
    private function saveSalePendingEdit(Sale $sale, Request $request): void
    {
        $sale->loadMissing(['items.item', 'customer', 'extraCosts']);

        $original = [
            'sale_date'      => $sale->sale_date?->toDateString(),
            'customer_id'    => $sale->customer_id,
            'customer_name'  => $sale->customer?->name,
            'status'         => $sale->status,
            'payment_method' => $sale->payment_method,
            'notes'          => $sale->notes ?? '',
            'paid_amount'    => round((float) $sale->paid_amount, 2),
            'discount'       => round((float) $sale->discount, 2),
            'extra_costs'    => $sale->extraCosts->map(fn($e) => ['category' => $e->category_name, 'amount' => round((float)$e->amount, 2)])->sortBy('category')->values()->toArray(),
            'items'          => $sale->items->map(fn($i) => ['id' => $i->item_id, 'name' => $i->item?->name ?? '?', 'qty' => round((float)$i->quantity, 3), 'price' => round((float)$i->price, 2)])->sortBy('id')->values()->toArray(),
        ];

        $proposed = [
            'sale_date'      => $request->sale_date,
            'customer_id'    => $request->customer_id ? (int) $request->customer_id : null,
            'status'         => $request->status ?? 'completed',
            'payment_method' => $request->payment_method ?? 'নগদ',
            'notes'          => $request->notes ?? '',
            'paid_amount'    => round((float) $request->paid_amount, 2),
            'discount'       => round((float) ($request->discount ?? 0), 2),
            'extra_costs'    => collect($request->extra_costs ?? [])->filter(fn($r) => !empty($r['category']) && ($r['amount'] ?? 0) > 0)->map(fn($r) => ['category' => $r['category'], 'amount' => round((float)$r['amount'], 2)])->sortBy('category')->values()->toArray(),
            'items'          => collect($request->items ?? [])->map(fn($i) => ['id' => (int)$i['id'], 'qty' => round((float)$i['qty'], 3), 'price' => round((float)$i['price'], 2)])->sortBy('id')->values()->toArray(),
            'edit_note'      => $request->edit_note ?? '',
        ];

        // Detect changes (compare comparable fields only)
        $origSig = json_encode(array_diff_key($original, array_flip(['customer_name'])));
        $propSig = json_encode(array_diff_key($proposed, array_flip(['edit_note'])));
        $hasChanges = $origSig !== $propSig;

        // Supersede any previous pending edit for this sale
        PendingEdit::where('model_type', 'sale')->where('model_id', $sale->id)->where('status', 'pending')
            ->update(['status' => 'superseded']);

        $pending = PendingEdit::create([
            'model_type'    => 'sale',
            'model_id'      => $sale->id,
            'requested_by'  => auth()->id(),
            'original_data' => $original,
            'proposed_data' => $proposed,
            'has_changes'   => $hasChanges,
            'status'        => $hasChanges ? 'pending' : 'no_change',
        ]);

        // Always log the attempt
        $this->logSale($sale, $hasChanges ? 'edit_requested' : 'edit_attempted_no_change',
            $hasChanges ? 'স্টাফ সংশোধন অনুরোধ পাঠিয়েছেন' : 'স্টাফ সম্পাদনা করার চেষ্টা করেছেন — কোনো পরিবর্তন নেই');

        // Send SMS to admin only if there are actual changes
        if ($hasChanges) {
            $this->sendEditSms('sale', $sale, $pending);
        }
    }

    // ── Send SMS notification to shop admin ───────────────────
    private function sendEditSms(string $type, $model, PendingEdit $pending): void
    {
        try {
            $phone = StoreConfig::get('store_phone', '');
            if (empty($phone)) return;

            $ref      = $type === 'sale' ? '#INV-' . str_pad($model->id, 4, '0', STR_PAD_LEFT) : '#RCV-' . str_pad($model->id, 4, '0', STR_PAD_LEFT);
            $staff    = auth()->user()->name;
            $appUrl   = config('app.url');
            $link     = $appUrl . '/' . ($type === 'sale' ? 'sales' : 'purchases') . '/' . $model->id;

            $changedFields = $this->summarizeChanges($pending->original_data, $pending->proposed_data);
            $msg = "[POS] সংশোধন অনুরোধ\nরেকর্ড: {$ref}\nস্টাফ: {$staff}\nপরিবর্তন: {$changedFields}\nলিংক: {$link}";

            $sms = app(SmsService::class);
            $result = $sms->send($phone, $msg);
            if ($result['success']) {
                $pending->update(['sms_sent' => true]);
            }
        } catch (\Exception $e) {
            // SMS failure should not break the request flow
        }
    }

    // ── Summarize what changed (for SMS) ─────────────────────
    private function summarizeChanges(array $original, array $proposed): string
    {
        $labels = [
            'sale_date'      => 'তারিখ',
            'purchase_date'  => 'তারিখ',
            'customer_id'    => 'কাস্টমার',
            'supplier_id'    => 'সরবরাহকারী',
            'status'         => 'স্ট্যাটাস',
            'payment_method' => 'পরিশোধ মোড',
            'paid_amount'    => 'পরিশোধ',
            'discount'       => 'ছাড়',
            'notes'          => 'নোট',
            'items'          => 'আইটেম',
            'extra_costs'    => 'অতিরিক্ত খরচ',
        ];

        $changed = [];
        foreach ($labels as $key => $label) {
            if (!array_key_exists($key, $original) || !array_key_exists($key, $proposed)) continue;
            if (json_encode($original[$key]) !== json_encode($proposed[$key])) {
                $changed[] = $label;
            }
        }
        return $changed ? implode(', ', $changed) : 'অজানা';
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
