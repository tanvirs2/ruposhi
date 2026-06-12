<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\StoreConfigController;

class SupplierPaymentController extends Controller
{
    public function index(Request $request)
    {
        $base = Purchase::with(['supplier', 'user'])
            ->withCount('items')
            ->where('paid_amount', '>', 0)
            ->when($request->supplier_id, fn($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->from, fn($q) => $q->whereDate('purchase_date', '>=', $request->from))
            ->when($request->to,   fn($q) => $q->whereDate('purchase_date', '<=', $request->to));

        $totalPaid = (clone $base)->sum('paid_amount');
        $payments  = $base->latest('purchase_date')->latest('id')->paginate(20);

        $suppliers = Supplier::orderBy('name')->get();

        return view('supplier-payments.index', compact('payments', 'suppliers', 'totalPaid'));
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
        //  - shows in the মাল রিসিভ তালিকা (receive list) with অগ্রিম badge
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
