<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\Purchase;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $suppliers = Supplier::when($request->search, fn($q) =>
            $q->where('name', 'like', "%{$request->search}%")
              ->orWhere('phone', 'like', "%{$request->search}%")
        )->latest()->paginate(15);

        return view('suppliers.index', compact('suppliers'));
    }

    public function ledgerSelect()
    {
        $suppliers = Supplier::orderBy('name')->get();
        return view('suppliers.ledger-select', compact('suppliers'));
    }

    public function ledger(Request $request, Supplier $supplier)
    {
        $from = $request->from ?? now()->toDateString();
        $to   = $request->to   ?? now()->toDateString();

        // Opening balance: purchases before $from minus paid and payments
        $openingDebit           = $supplier->purchases()->where('purchase_date', '<', $from)->sum('total_amount');
        $openingPaidOnPurchases = $supplier->purchases()->where('purchase_date', '<', $from)->sum('paid_amount');
        $openingCredit          = $supplier->payments()->where('payment_date',  '<', $from)->sum('amount');
        $openingBalance         = $openingDebit - $openingPaidOnPurchases - $openingCredit;

        // Purchases in range — expand to item-level rows
        $purchases = $supplier->purchases()
            ->with('items.item')
            ->whereBetween('purchase_date', [$from, $to])
            ->orderBy('purchase_date')
            ->orderBy('created_at')
            ->get();

        $rows = collect();
        foreach ($purchases as $p) {
            $ts      = $p->created_at ? $p->created_at->timestamp : 0;
            $baseKey = sprintf('%010d_%06d', $ts, $p->id);
            $iIdx    = 0;
            foreach ($p->items as $pi) {
                $rows->push((object)[
                    'date'        => $p->purchase_date,
                    'sort_key'    => $baseKey . '_i' . sprintf('%04d', $iIdx),
                    'type'        => 'item',
                    'label'       => $pi->item->name ?? 'অজানা আইটেম',
                    'ref'         => '#PUR-' . str_pad($p->id, 4, '0', STR_PAD_LEFT),
                    'qty'         => $pi->quantity,
                    'rate'        => $pi->price,
                    'debit'       => $pi->subtotal,
                    'credit'      => 0,
                    'purchase_id' => $p->id,
                    'link'        => route('purchases.show', $p),
                ]);
                $iIdx++;
            }
            // Credit row for amount paid with this purchase
            if ($p->paid_amount > 0) {
                $rows->push((object)[
                    'date'        => $p->purchase_date,
                    'sort_key'    => $baseKey . '_p',
                    'type'        => 'purchase_payment',
                    'label'       => $p->payment_method ?? 'নগদ',
                    'ref'         => '#PUR-' . str_pad($p->id, 4, '0', STR_PAD_LEFT),
                    'qty'         => 0,
                    'rate'        => 0,
                    'debit'       => 0,
                    'credit'      => $p->paid_amount,
                    'purchase_id' => $p->id,
                    'link'        => null,
                ]);
            }
        }

        // Separate supplier payments in range
        $supplierPayments = $supplier->payments()
            ->whereBetween('payment_date', [$from, $to])
            ->orderBy('payment_date')
            ->orderBy('created_at')
            ->get()
            ->map(fn($p) => (object)[
                'date'        => $p->payment_date,
                'sort_key'    => sprintf('%010d_pay_%06d', $p->created_at ? $p->created_at->timestamp : 0, $p->id),
                'type'        => 'payment',
                'label'       => $p->payment_method,
                'ref'         => null,
                'qty'         => 0,
                'rate'        => 0,
                'debit'       => 0,
                'credit'      => $p->amount,
                'purchase_id' => null,
                'link'        => null,
            ]);

        $ledger = $rows->concat($supplierPayments)->sortBy('sort_key')->values();

        // Running balance
        $balance = $openingBalance;
        foreach ($ledger as $row) {
            $balance     += $row->debit - $row->credit;
            $row->balance = $balance;
        }

        $totalDebit   = $ledger->sum('debit');
        $totalCredit  = $ledger->sum('credit');
        $receiptCount = $purchases->count();

        // Real total due (all time) — auto-fix stale due_amount (allows negative for credit/advance)
        $allTimePurchases = Purchase::where('supplier_id', $supplier->id)->sum('total_amount');
        $allTimePaid      = Purchase::where('supplier_id', $supplier->id)->sum('paid_amount');
        $allTimePayments  = SupplierPayment::where('supplier_id', $supplier->id)->sum('amount');
        $realTotalDue     = $allTimePurchases - $allTimePaid - $allTimePayments; // NO max(0) — allows credit
        if ($supplier->due_amount != $realTotalDue) {
            $supplier->update(['due_amount' => $realTotalDue]);
        }

        // CSV export
        if ($request->export === 'csv') {
            $filename = 'supplier_ledger_' . $supplier->id . '_' . $from . '_' . $to . '.csv';
            $headers  = ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => "attachment; filename={$filename}"];
            $callback = function () use ($supplier, $ledger, $openingBalance, $totalDebit, $totalCredit, $from, $to) {
                $f = fopen('php://output', 'w');
                fprintf($f, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($f, ['সরবরাহকারী লেজার রিপোর্ট']);
                fputcsv($f, ['প্রতিষ্ঠান', $supplier->name, 'ফোন', $supplier->phone ?? '']);
                fputcsv($f, ['ঠিকানা', $supplier->address ?? '', 'সময়কাল', $from . ' থেকে ' . $to]);
                fputcsv($f, []);
                fputcsv($f, ['তারিখ', 'বিবরণ', 'পরিমাণ', 'দর', 'জমা (Cr)', 'মোট মূল্য (Dr)', 'অবশিষ্ট']);
                fputcsv($f, ['', 'পূর্বের অবশিষ্ট', '', '', '', '', $openingBalance]);
                foreach ($ledger as $row) {
                    fputcsv($f, [
                        $row->date,
                        $row->label,
                        $row->qty  > 0 ? $row->qty  : '',
                        $row->rate > 0 ? $row->rate : '',
                        $row->credit > 0 ? $row->credit : '',
                        $row->debit  > 0 ? $row->debit  : '',
                        abs($row->balance) . ($row->balance > 0 ? ' (দেনা)' : ($row->balance < 0 ? ' (পাওনা)' : '')),
                    ]);
                }
                fputcsv($f, []);
                fputcsv($f, ['সর্বমোট', '', '', '', $totalCredit, $totalDebit, '']);
                fclose($f);
            };
            return response()->stream($callback, 200, $headers);
        }

        return view('suppliers.ledger', compact(
            'supplier', 'ledger', 'from', 'to',
            'openingBalance', 'totalDebit', 'totalCredit', 'realTotalDue', 'receiptCount'
        ));
    }

    public function dueReport()
    {
        $suppliers = Supplier::where('due_amount', '>', 0)
            ->orderByDesc('due_amount')
            ->get();
        $totalDue  = $suppliers->sum('due_amount');

        return view('suppliers.due-report', compact('suppliers', 'totalDue'));
    }

    public function create()
    {
        return view('suppliers.create');
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        Supplier::create($request->only('name', 'phone', 'email', 'address'));
        return redirect()->route('suppliers.index')->with('success', 'সরবরাহকারী সফলভাবে যোগ করা হয়েছে।');
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $request->validate(['name' => 'required|string|max:255']);
        $supplier->update($request->only('name', 'phone', 'email', 'address'));
        return redirect()->route('suppliers.index')->with('success', 'সরবরাহকারী সফলভাবে আপডেট করা হয়েছে।');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return redirect()->route('suppliers.index')->with('success', 'সরবরাহকারী মুছে ফেলা হয়েছে।');
    }
}
