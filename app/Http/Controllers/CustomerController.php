<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerArea;
use App\Models\CustomerPayment;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $showAll = $request->boolean('show_all'); // toggle: show zero-due customers

        $customers = Customer::with('area')
            ->when($request->search, fn($q) =>
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%")
            )
            ->when($request->area_id, fn($q) => $q->where('area_id', $request->area_id))
            ->when(!$showAll, fn($q) => $q->where('due_amount', '!=', 0)) // hide clean by default
            ->latest()->paginate(15);

        $areas = CustomerArea::orderBy('name')->get();

        $baseQuery = Customer::when($request->search, fn($q) =>
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%")
            )
            ->when($request->area_id, fn($q) => $q->where('area_id', $request->area_id));

        // Gross due (positive only), credit (negative only, shown as positive), net = gross - credit
        $grossDue  = (clone $baseQuery)->where('due_amount', '>', 0)->sum('due_amount');
        $totalCredit = abs((clone $baseQuery)->where('due_amount', '<', 0)->sum('due_amount'));
        $totalDue  = $grossDue - $totalCredit; // net balance

        return view('customers.index', compact('customers', 'areas', 'totalDue', 'grossDue', 'totalCredit', 'showAll'));
    }

    public function create()
    {
        $areas = CustomerArea::orderBy('name')->get();
        return view('customers.create', compact('areas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        Customer::create($request->only('name', 'proprietor', 'phone', 'address', 'area_id'));

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
        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'কাস্টমার মুছে ফেলা হয়েছে।');
    }

    // ── Customer Ledger Selector ─────────────────────────────────
    public function ledgerSelect()
    {
        $customers = Customer::with('area')->orderBy('name')->get();
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
        $sales = Sale::with(['items.item'])
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
            // Extra cost row (debit — adds to balance)
            if ($sale->extra_cost > 0) {
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
            // Labor cost row (debit — adds to balance)
            if ($sale->labor_cost > 0) {
                $rows->push([
                    'sort_key' => $saleTime,
                    'datetime' => $saleTime,
                    'sale_id'  => $sale->id,
                    'type'     => 'labor_cost',
                    'label'    => 'শ্রমিক খরচ',
                    'qty'      => 0,
                    'rate'     => 0,
                    'debit'    => $sale->labor_cost,
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
