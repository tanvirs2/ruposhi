<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerArea;
use App\Models\CustomerPayment;
use App\Models\Sale;
use App\Models\StoreConfig;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'active');     // active|due|advance|clean|all
        $search = trim((string) $request->get('search', ''));
        $areaId = $request->area_id;

        // Reusable scoped builder (search + area). Search wrapped in a sub-closure
        // so the orWhere group can't bypass the shop_id global scope.
        $makeQuery = function () use ($search, $areaId) {
            return Customer::query()
                ->when($search !== '', fn($q) => $q->where(fn($s) => $s
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('proprietor', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")))
                ->when($areaId, fn($q) => $q->where('area_id', $areaId));
        };

        // Per-status counts (for the filter chip badges) — respect search + area
        $counts = [
            'due'     => $makeQuery()->where('due_amount', '>', 0)->count(),
            'advance' => $makeQuery()->where('due_amount', '<', 0)->count(),
            'clean'   => $makeQuery()->where('due_amount', '=', 0)->count(),
        ];
        $counts['active'] = $counts['due'] + $counts['advance'];
        $counts['all']    = $counts['active'] + $counts['clean'];

        // Customer list filtered by chosen status
        $list = $makeQuery()->with('area');
        switch ($status) {
            case 'due':     $list->where('due_amount', '>', 0); break;
            case 'advance': $list->where('due_amount', '<', 0); break;
            case 'clean':   $list->where('due_amount', '=', 0); break;
            case 'all':     break;                                  // no due filter
            default:        $list->where('due_amount', '!=', 0);    // 'active'
        }
        // print=1 → one page with every matching row (the print button fetches this
        // before opening the print dialog, so paper shows the whole filtered list)
        $perPage   = $request->boolean('print') ? 100000 : 15;
        $customers = $list->latest()->paginate($perPage)->withQueryString();

        $areas = CustomerArea::orderBy('name')->get();

        // Gross due (positive only), credit (negative, shown positive), net = gross - credit
        $grossDue    = $makeQuery()->where('due_amount', '>', 0)->sum('due_amount');
        $totalCredit = abs($makeQuery()->where('due_amount', '<', 0)->sum('due_amount'));
        $totalDue    = $grossDue - $totalCredit; // net balance

        $data = compact(
            'customers', 'areas', 'totalDue', 'grossDue', 'totalCredit',
            'status', 'counts', 'search'
        );

        if ($request->ajax()) {
            return view('customers._results', $data);
        }

        return view('customers.index', $data);
    }

    public function create()
    {
        $areas = CustomerArea::orderBy('name')->get();
        return view('customers.create', compact('areas'));
    }

    /**
     * Server-side autocomplete for POS dropdowns.
     * Returns up to 15 shop-scoped matches (global scope handles shop_id).
     * Keeps pages light — they no longer embed the whole customer table.
     */
    public function search(Request $request)
    {
        $q      = trim((string) $request->get('q', ''));
        $areaId = $request->get('area_id');

        // Need at least a query OR an area filter — otherwise return nothing.
        if (mb_strlen($q) < 1 && !$areaId) {
            return response()->json([]);
        }

        $customers = Customer::with('area:id,name')
            ->when(mb_strlen($q) >= 1, fn($query) => $query->where(fn($sub) => $sub
                ->where('name', 'like', "%{$q}%")
                ->orWhere('proprietor', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%")))
            ->when($areaId, fn($query) => $query->where('area_id', $areaId))
            ->orderBy('name')
            ->limit(30)
            ->get(['id', 'name', 'proprietor', 'phone', 'due_amount', 'credit_limit', 'area_id']);

        return response()->json($customers);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'            => 'required|string|max:255',
            'phone'           => 'nullable|string|max:20',
            'credit_limit'    => 'nullable|numeric|min:0',
            'opening_balance' => 'nullable|numeric',
        ]);

        // পুরনো বাকী is admin-only — staff can't set it, even by tampering with
        // the request; a blank field also means "no old due" = 0.
        $request->merge([
            'opening_balance' => auth()->user()->canManageShop() ? ($request->opening_balance ?? 0) : 0,
        ]);

        $customer = Customer::create($request->only('name', 'proprietor', 'phone', 'address', 'area_id', 'credit_limit', 'opening_balance'));

        // Sync due_amount from the opening balance now, so the list shows it
        // immediately instead of "পরিষ্কার" until someone opens the ledger.
        $customer->recalcDue();

        // AJAX (popup from sale form) — return the new customer as JSON
        if ($request->expectsJson() || $request->ajax()) {
            $customer->load('area:id,name');
            return response()->json([
                'success'  => true,
                'customer' => [
                    'id'         => $customer->id,
                    'name'       => $customer->name,
                    'proprietor' => $customer->proprietor,
                    'phone'      => $customer->phone,
                    'due_amount' => $customer->due_amount,
                    'area'       => $customer->area ? ['id' => $customer->area->id, 'name' => $customer->area->name] : null,
                ],
            ]);
        }

        return redirect()->route('customers.index')->with('success', 'কাস্টমার সফলভাবে যোগ করা হয়েছে।');
    }

    public function edit(Customer $customer)
    {
        $areas = CustomerArea::orderBy('name')->get();
        return view('customers.edit', compact('customer', 'areas'));
    }

    public function update(Request $request, Customer $customer)
    {
        $request->validate([
            'name'            => 'required|string|max:255',
            'phone'           => 'nullable|string|max:20',
            'credit_limit'    => 'nullable|numeric|min:0',
            'opening_balance' => 'nullable|numeric',
        ]);

        // পুরনো বাকী is admin-only — staff's request can't change it, even by
        // tampering with the form; blank field means "no old due" = 0.
        $request->merge([
            'opening_balance' => auth()->user()->canManageShop()
                ? ($request->opening_balance ?? 0)
                : $customer->opening_balance,
        ]);

        $customer->update($request->only('name', 'proprietor', 'phone', 'address', 'area_id', 'credit_limit', 'opening_balance'));

        // opening_balance may have changed — resync due_amount from the full
        // formula (this customer may already have transactions) so the list is
        // correct without waiting for the ledger to be opened.
        $customer->recalcDue();

        return redirect()->route('customers.index')->with('success', 'কাস্টমার সফলভাবে আপডেট করা হয়েছে।');
    }

    public function destroy(Customer $customer)
    {
        if (!auth()->user()->canManageShop()) {
            abort(403, 'শুধুমাত্র অ্যাডমিন মুছতে পারবেন।');
        }
        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'কাস্টমার মুছে ফেলা হয়েছে।');
    }

    // ── বাকী Reminder SMS ────────────────────────────────────────
    public function smsReminder(Request $request, Customer $customer, SmsService $sms)
    {
        if (!$customer->phone) {
            return back()->with('error', 'কাস্টমারের ফোন নম্বর নেই।');
        }
        if ($customer->due_amount <= 0) {
            return back()->with('error', 'এই কাস্টমারের কোনো বাকী নেই।');
        }

        $storeName = StoreConfig::get('store_name', 'দোকান');
        $storePhone = StoreConfig::get('store_phone', '');
        $msg = "প্রিয় {$customer->name},\nআপনার কাছে {$storeName}-এ ৳" . number_format($customer->due_amount, 0) . " বাকী আছে।\nঅনুগ্রহ করে দ্রুত পরিশোধ করুন।";
        if ($storePhone) $msg .= "\nযোগাযোগ: {$storePhone}";
        $msg .= "\nধন্যবাদ।";

        $result = $sms->send($customer->phone, $msg, $customer->name);

        return back()->with(
            $result['success'] ? 'success' : 'error',
            $result['success'] ? "{$customer->name}-কে বাকী reminder SMS পাঠানো হয়েছে।" : "SMS ব্যর্থ: {$result['response']}"
        );
    }

    // ── Customer Ledger Selector ─────────────────────────────────
    public function ledgerSelect()
    {
        // Most recent transaction first (customer payments are stored as
        // sales, so the latest sale_date = latest activity).
        // Customers with no transactions sink to the bottom (NULL last in DESC).
        $customers = Customer::with('area')
            ->withMax('sales', 'sale_date')
            ->orderByDesc('sales_max_sale_date')
            ->orderBy('name')
            ->get();
        $areas     = CustomerArea::orderBy('name')->get(['id', 'name']);
        return view('customers.ledger-select', compact('customers', 'areas'));
    }

    // ── Customer Ledger ─────────────────────────────────────────
    public function ledger(Request $request, Customer $customer)
    {
        $from = $request->from ?? now()->toDateString();
        $to   = $request->to   ?? now()->toDateString();

        // ── Opening balance (before $from) — use total_amount (after discount) ──
        $openingSales    = Sale::where('customer_id', $customer->id)
                               ->where('sale_date', '<', $from)->sum('total_amount');
        $openingPaid     = Sale::where('customer_id', $customer->id)
                               ->where('sale_date', '<', $from)->sum('paid_amount');
        $openingPayments = CustomerPayment::where('customer_id', $customer->id)
                               ->where('payment_date', '<', $from)->sum('amount');
        // opening_balance = due carried in from the paper ledger, from before
        // the shop went live. It predates every transaction, so it belongs in
        // both sums below regardless of the date filter.
        $openingBalance  = $customer->opening_balance + $openingSales - $openingPaid - $openingPayments;

        // ── Real total due (all time) — canonical formula lives on the model,
        //    which also writes back the auto-fix. See Customer::recalcDue().
        $realTotalDue = $customer->recalcDue();

        // ── Sales within period → item-level rows ────────────────
        $sales = Sale::with(['items.item', 'extraCosts'])
            ->where('customer_id', $customer->id)
            ->whereBetween('sale_date', [$from, $to])
            ->orderBy('sale_date')->orderBy('id')
            ->get();

        // ── Two separate ledgers ──────────────────────────────────
        //  $ledger   → বিক্রয়/বিল সাইড (item, discount, extra_cost)
        //  $deposits → জমা (payments) — shown in its OWN table, not mixed in
        $ledger   = collect();
        $deposits = collect();

        foreach ($sales as $sale) {
            $saleTime = $sale->created_at ?? $sale->sale_date;
            // One row per item
            foreach ($sale->items as $si) {
                $ledger->push([
                    'sort_key' => $saleTime,
                    'datetime' => $saleTime,
                    'sale_id'  => $sale->id,
                    'type'     => 'item',
                    'label'    => $si->item->name ?? '—',
                    'qty'      => $si->quantity,
                    'rate'     => $si->price,
                    'debit'    => $si->subtotal,
                    'credit'   => 0,
                ]);
            }
            // Discount row (credit — reduces the bill)
            if ($sale->discount > 0) {
                $ledger->push([
                    'sort_key' => $saleTime,
                    'datetime' => $saleTime,
                    'sale_id'  => $sale->id,
                    'type'     => 'discount',
                    'label'    => 'ছাড়',
                    'qty'      => 0,
                    'rate'     => 0,
                    'debit'    => 0,
                    'credit'   => $sale->discount,
                ]);
            }
            // Extra cost rows (categorized — new system)
            if ($sale->extraCosts->isNotEmpty()) {
                foreach ($sale->extraCosts as $ec) {
                    $ledger->push([
                        'sort_key' => $saleTime,
                        'datetime' => $saleTime,
                        'sale_id'  => $sale->id,
                        'type'     => 'extra_cost',
                        'label'    => $ec->category_name,
                        'qty'      => 0,
                        'rate'     => 0,
                        'debit'    => $ec->amount,
                        'credit'   => 0,
                    ]);
                }
            } elseif (($sale->extra_cost ?? 0) > 0) {
                // Legacy fallback: old record without extraCosts rows
                $ledger->push([
                    'sort_key' => $saleTime,
                    'datetime' => $saleTime,
                    'sale_id'  => $sale->id,
                    'type'     => 'extra_cost',
                    'label'    => 'অতিরিক্ত খরচ',
                    'qty'      => 0,
                    'rate'     => 0,
                    'debit'    => $sale->extra_cost,
                    'credit'   => 0,
                ]);
            }
            // Initial payment on the sale (paid_amount > 0) → জমা টেবিল
            if ($sale->paid_amount > 0) {
                $deposits->push([
                    'sort_key' => $saleTime,
                    'datetime' => $saleTime,
                    'sale_id'  => $sale->id,
                    'method'   => $sale->payment_method ?? 'নগদ',
                    'notes'    => null,
                    'amount'   => $sale->paid_amount,
                ]);
            }
        }

        // ── Standalone customer payments → জমা টেবিল ───────────────
        $payments = CustomerPayment::where('customer_id', $customer->id)
            ->whereBetween('payment_date', [$from, $to])
            ->orderBy('payment_date')->orderBy('id')
            ->get();

        foreach ($payments as $p) {
            $pt = $p->created_at ?? $p->payment_date;
            $deposits->push([
                'sort_key' => $pt,
                'datetime' => $pt,
                'sale_id'  => null,
                'method'   => $p->payment_method,
                'notes'    => $p->notes,
                'amount'   => $p->amount,
            ]);
        }

        $ledger        = $ledger->sortBy('sort_key')->values();
        $deposits      = $deposits->sortBy('sort_key')->values();

        // ── Combined (old single-table view) — bills + payments interleaved ──
        //  Users can toggle between this classic one-table layout and the new
        //  split layout; the running balance here nets payments (বাকি − জমা).
        $paymentRows = $deposits->map(fn ($d) => [
            'sort_key' => $d['sort_key'],
            'datetime' => $d['datetime'],
            'sale_id'  => $d['sale_id'],
            'type'     => 'payment',
            'label'    => $d['method'] ?: 'নগদ',
            'qty'      => 0,
            'rate'     => 0,
            'debit'    => 0,
            'credit'   => $d['amount'],
        ]);
        $combined = $ledger->concat($paymentRows)->sortBy('sort_key')->values();

        $totalSales    = $sales->sum('total_amount');          // after discount
        $totalDiscount = $sales->sum('discount');
        $totalCredits  = $sales->sum('paid_amount') + $payments->sum('amount');
        $totalDeposits = $totalCredits;                        // same figure, clearer name for the জমা table
        $periodBalance = $totalSales - $totalCredits;

        // ── CSV export ────────────────────────────────────────────
        if ($request->export === 'csv') {
            $filename = 'ledger_' . $customer->id . '_' . $from . '_' . $to . '.csv';
            $headers  = ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => "attachment; filename={$filename}"];
            $callback = function () use ($customer, $ledger, $deposits, $openingBalance, $totalSales, $totalDiscount, $totalCredits, $totalDeposits, $periodBalance, $from, $to) {
                $f = fopen('php://output', 'w');
                fprintf($f, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($f, ['কাস্টমার লেজার রিপোর্ট']);
                fputcsv($f, ['প্রতিষ্ঠান', $customer->name, 'প্রোপ্রাইটর', $customer->proprietor ?? '']);
                fputcsv($f, ['ফোন', $customer->phone ?? '', 'সময়কাল', $from . ' থেকে ' . $to]);
                fputcsv($f, []);

                // ── Section 1: বিক্রয় খতিয়ান (bills) ──
                fputcsv($f, ['বিক্রয় খতিয়ান']);
                fputcsv($f, ['চালান নং', 'তারিখ', 'বিবরণ', 'পরিমাণ', 'দর', 'বাকি', 'অবশিষ্ট']);
                $bal = $openingBalance;
                if ($openingBalance > 0)
                    fputcsv($f, ['', '', 'পূর্বের অবশিষ্ট', '', '', '', $openingBalance]);
                foreach ($ledger as $row) {
                    $bal += $row['debit'] - $row['credit'];
                    fputcsv($f, [
                        $row['sale_id'] ? str_pad($row['sale_id'], 6, '0', STR_PAD_LEFT) : '',
                        \Carbon\Carbon::parse($row['datetime'])->format('Y-m-d H:i:s'),
                        $row['label'],
                        $row['qty'] ?: '',
                        $row['rate'] ?: '',
                        $row['debit'] ? $row['debit'] : ($row['credit'] ? -$row['credit'] : ''),
                        $bal,
                    ]);
                }
                fputcsv($f, ['', '', '', '', '', 'অবশিষ্ট', $bal]);
                fputcsv($f, []);

                // ── Section 2: জমা তালিকা (deposits) ──
                fputcsv($f, ['জমা তালিকা']);
                fputcsv($f, ['তারিখ', 'পদ্ধতি', 'সূত্র / মন্তব্য', 'পরিমাণ']);
                foreach ($deposits as $d) {
                    $ref = $d['sale_id']
                        ? 'চালান ' . str_pad($d['sale_id'], 6, '0', STR_PAD_LEFT)
                        : ($d['notes'] ?: 'পরিশোধ');
                    fputcsv($f, [
                        \Carbon\Carbon::parse($d['datetime'])->format('Y-m-d H:i:s'),
                        $d['method'],
                        $ref,
                        $d['amount'],
                    ]);
                }
                fputcsv($f, ['', '', 'মোট জমা', $totalDeposits]);
                fputcsv($f, []);
                fputcsv($f, ['', '', 'নিট বাকী (অবশিষ্ট − মোট জমা)', $bal - $totalDeposits]);
                fclose($f);
            };
            return response()->stream($callback, 200, $headers);
        }

        return view('customers.ledger', compact(
            'customer', 'ledger', 'deposits', 'combined', 'from', 'to',
            'openingBalance', 'totalSales', 'totalDiscount', 'totalCredits', 'totalDeposits', 'periodBalance', 'realTotalDue'
        ));
    }
}
