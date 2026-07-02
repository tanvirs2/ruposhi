<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\Purchase;
use App\Models\PurchaseDeposit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Http\Controllers\StoreConfigController;

class SupplierPaymentController extends Controller
{
    public function index(Request $request)
    {
        $today = now()->toDateString();
        $from  = $request->from ?: $today;
        $to    = $request->to   ?: $today;
        if ($from > $to) [$from, $to] = [$to, $from];

        // Purchase rows (paid_amount > 0)
        $purchaseRows = Purchase::with(['supplier', 'user'])
            ->withCount('items')
            ->where('paid_amount', '>', 0)
            ->when($request->supplier_id, fn($q) => $q->where('supplier_id', $request->supplier_id))
            ->whereDate('purchase_date', '>=', $from)
            ->whereDate('purchase_date', '<=', $to)
            ->get()
            ->map(fn($p) => ['type' => 'purchase', 'row' => $p]);

        $totalPaid = $purchaseRows->sum(fn($r) => $r['row']->paid_amount);

        // Deposit rows from purchases (জমা entries)
        $depositRows = PurchaseDeposit::with(['purchase.supplier', 'purchase.user'])
            ->whereHas('purchase', function ($q) use ($from, $to, $request) {
                $q->when($request->supplier_id, fn($q2) => $q2->where('supplier_id', $request->supplier_id))
                  ->whereDate('purchase_date', '>=', $from)
                  ->whereDate('purchase_date', '<=', $to);
            })
            ->get()
            ->map(fn($d) => ['type' => 'deposit', 'row' => $d]);

        $totalDeposit = $depositRows->sum(fn($r) => $r['row']->amount);

        // Merge and sort by date desc, then purchase id desc
        $merged = $purchaseRows->concat($depositRows)
            ->sortByDesc(function ($item) {
                $ts = $item['type'] === 'purchase'
                    ? $item['row']->purchase_date->timestamp
                    : $item['row']->purchase->purchase_date->timestamp;
                $id = $item['type'] === 'purchase'
                    ? $item['row']->id
                    : $item['row']->purchase_id;
                return $ts * 1000000 + $id;
            })
            ->values();

        // Manual pagination
        $perPage     = 20;
        $currentPage = (int) $request->input('page', 1);
        $payments    = new LengthAwarePaginator(
            $merged->forPage($currentPage, $perPage),
            $merged->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $suppliers = Supplier::orderBy('name')->get();

        return view('supplier-payments.index', compact('payments', 'suppliers', 'totalPaid', 'totalDeposit', 'from', 'to'));
    }

    public function create(Request $request)
    {
        $suppliers        = Supplier::orderBy('name')->get();
        $selectedSupplier = $request->supplier_id
            ? Supplier::find($request->supplier_id)
            : null;
        $paymentMethods   = StoreConfigController::getGroupedPaymentMethods();

        return view('supplier-payments.create', compact('suppliers', 'selectedSupplier', 'paymentMethods'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_id'    => 'required|exists:suppliers,id',
            'amount'         => 'required|numeric|min:0.01',
            'payment_date'   => 'required|date',
            'payment_method' => 'required|string',
        ]);

        // A supplier payment with NO items is functionally identical to a
        // no-item advance purchase. So we create a Purchase record here:
        //  - shows in the পণ্য গ্রহণ তালিকা (receive list) with অগ্রিম badge
        //  - generates an invoice (purchases.show)
        //  - reduces supplier due exactly once (due_amount = total(0) - paid)
        $purchase = null;
        DB::transaction(function () use ($request, &$purchase) {
            $purchase = Purchase::create([
                'supplier_id'    => $request->supplier_id,
                'user_id'        => auth()->id(),
                'total_amount'   => 0,
                'discount'       => 0,
                'extra_cost'     => 0,
                'paid_amount'    => $request->amount,
                'due_amount'     => 0 - $request->amount, // negative = advance/credit
                'payment_method' => $request->payment_method,
                'notes'          => $request->notes,
                'purchase_date'  => $request->payment_date,
            ]);

            // net effect: supplier.due -= amount. Atomic decrement avoids
            // lost-update races; allows negative (credit) balance.
            $supplier = Supplier::find($request->supplier_id);
            $supplier->decrement('due_amount', $request->amount);
        });

        return redirect()->route('purchases.show', $purchase)
            ->with('success', 'সরবরাহকারীকে পরিশোধ সম্পন্ন হয়েছে। অগ্রিম রিসিভ হিসেবে যোগ হয়েছে।');
    }

    public function destroy(SupplierPayment $supplierPayment)
    {
        if (!auth()->user()->canManageShop()) {
            abort(403, 'শুধুমাত্র অ্যাডমিন মুছতে পারবেন।');
        }
        Supplier::where('id', $supplierPayment->supplier_id)
            ->increment('due_amount', $supplierPayment->amount);
        $supplierPayment->delete();

        return back()->with('success', 'পরিশোধ মুছে ফেলা হয়েছে এবং বকেয়া পুনরুদ্ধার করা হয়েছে।');
    }
}
