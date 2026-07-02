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
        $customers = $list->latest()->paginate(15)->withQueryString();

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
            ->get(['id', 'name', 'proprietor', 'phone', 'due_amount', 'area_id']);

        return response()->json($customers);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $customer = Customer::create($request->only('name', 'proprietor', 'phone', 'address', 'area_id'));

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
            'name'  => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $customer->update($request->only('name', 'proprietor', 'phone', 'address', 'area_id'));

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
        if ($from > $to) [$from, $to] = [$to, $from];

        // ── Opening balance (before $from) — use total_amount (after discount) ──
        $openingSales    = Sale::where('customer_id', $customer->id)
                               ->where('sale_date', '<', $from)->sum('total_amount');
        $openingPaid     = Sale::where('customer_id', $customer->id)
                               ->where('sale_date', '<', $from)->sum('paid_amount');
        $openingPayments = CustomerPayment::where('customer_id', $customer->id)
                               ->where('payment_date', '<', $from)->sum('amount');
        $openingBalance  = $openingSales - $openingPaid - $openingPayments;

        // ── Real total due (all time, calculated from DB) ─────────
        $allTimeSales    = Sale::where('customer_id', $customer->id)->sum('total_amount');
        $allTimePaid     = Sale::where('customer_id', $customer->id)->sum('paid_amount');
        $allTimePayments = CustomerPayment::where('customer_id', $customer->id)->sum('amount');
        // Allow negative (credit balance from overpayment) — no max(0,...) cap
        $realTotalDue    = $allTimeSales - $allTimePaid - $allTimePayments;

        // ── Auto-fix stale due_amount on customer record ───────────
        if (abs($customer->due_amount - $realTotalDue) > 0.01) {
            $customer->update(['due_amount' => $realTotalDue]);
        }

        // ── Sales within period → item-level rows ────────────────
        $sales = Sale::with(['items.item', 'extraCosts'])
            ->where('customer_id', $customer->id)
            ->whereBetween('sale_date', [$from, $to])
            ->orderBy('sale_date')->orderBy('id')
            ->get();

        $rows = collect();

        foreach ($sales as $sale) {
            $saleTime = $sale->created_at ?? $sale->sale_date;
            // One row per item
            foreach ($sale->items as $si) {
                $rows->push([
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
            // Discount row (credit — reduces balance)
            if ($sale->discount > 0) {
                $rows->push([
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
                    $rows->push([
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
                $rows->push([
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
            // Initial payment on the sale (paid_amount > 0)
            if ($sale->paid_amount > 0) {
                $rows->push([
                    'sort_key' => $saleTime,
                    'datetime' => $saleTime,
                    'sale_id'  => $sale->id,
                    'type'     => 'payment',
                    'label'    => $sale->payment_method ?? 'নগদ',
                    'qty'      => 0,
                    'rate'     => 0,
                    'debit'    => 0,
                    'credit'   => $sale->paid_amount,
                ]);
            }
        }

        // ── Separate customer payments ────────────────────────────
        $payments = CustomerPayment::where('customer_id', $customer->id)
            ->whereBetween('payment_date', [$from, $to])
            ->orderBy('payment_date')->orderBy('id')
            ->get();

        foreach ($payments as $p) {
            $pt = $p->created_at ?? $p->payment_date;
            $rows->push([
                'sort_key' => $pt,
                'datetime' => $pt,
                'sale_id'  => null,
                'type'     => 'payment',
                'label'    => $p->payment_method,
                'qty'      => 0,
                'rate'     => 0,
                'debit'    => 0,
                'credit'   => $p->amount,
            ]);
        }

        $ledger        = $rows->sortBy('sort_key')->values();
        $totalSales    = $sales->sum('total_amount');          // after discount
        $totalDiscount = $sales->sum('discount');
        $totalCredits  = $sales->sum('paid_amount') + $payments->sum('amount');
        $periodBalance = $totalSales - $totalCredits;

        // ── CSV export ────────────────────────────────────────────
        if ($request->export === 'csv') {
            $filename = 'ledger_' . $customer->id . '_' . $from . '_' . $to . '.csv';
            $headers  = ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => "attachment; filename={$filename}"];
            $callback = function () use ($customer, $ledger, $openingBalance, $totalSales, $totalDiscount, $totalCredits, $periodBalance, $from, $to) {
                $f = fopen('php://output', 'w');
                fprintf($f, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($f, ['কাস্টমার লেজার রিপোর্ট']);
                fputcsv($f, ['প্রতিষ্ঠান', $customer->name, 'প্রোপ্রাইটর', $customer->proprietor ?? '']);
                fputcsv($f, ['ফোন', $customer->phone ?? '', 'সময়কাল', $from . ' থেকে ' . $to]);
                fputcsv($f, []);
                fputcsv($f, ['চালান নং', 'তারিখ', 'বিবরণ', 'পরিমাণ', 'দর', 'জমা', 'বাকি', 'অবশিষ্ট']);
                $bal = $openingBalance;
                if ($openingBalance > 0)
                    fputcsv($f, ['', '', 'পূর্বের অবশিষ্ট', '', '', '', '', $openingBalance]);
                foreach ($ledger as $row) {
                    $bal += $row['debit'] - $row['credit'];
                    fputcsv($f, [
                        $row['sale_id'] ? str_pad($row['sale_id'], 6, '0', STR_PAD_LEFT) : '',
                        \Carbon\Carbon::parse($row['datetime'])->format('Y-m-d H:i:s'),
                        $row['label'],
                        $row['qty'] ?: '',
                        $row['rate'] ?: '',
                        $row['credit'] ?: '',
                        $row['debit'] ?: '',
                        $bal,
                    ]);
                }
                fputcsv($f, []);
                fputcsv($f, ['মোট', '', '', '', '', $totalCredits, $totalSales, $bal]);
                fclose($f);
            };
            return response()->stream($callback, 200, $headers);
        }

        return view('customers.ledger', compact(
            'customer', 'ledger', 'from', 'to',
            'openingBalance', 'totalSales', 'totalDiscount', 'totalCredits', 'periodBalance', 'realTotalDue'
        ));
    }
}
