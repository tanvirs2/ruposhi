<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\SupplierPayment;
use Illuminate\Http\Request;
use App\Http\Controllers\StoreConfigController;

class SupplierPaymentController extends Controller
{
    public function index(Request $request)
    {
        $payments = SupplierPayment::with(['supplier', 'user'])
            ->when($request->supplier_id, fn($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->from, fn($q) => $q->whereDate('payment_date', '>=', $request->from))
            ->when($request->to,   fn($q) => $q->whereDate('payment_date', '<=', $request->to))
            ->latest('payment_date')->paginate(20);

        $suppliers  = Supplier::orderBy('name')->get();
        $totalPaid  = $payments->sum('amount');

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

        SupplierPayment::create([
            'supplier_id'    => $request->supplier_id,
            'user_id'        => auth()->id(),
            'amount'         => $request->amount,
            'payment_date'   => $request->payment_date,
            'payment_method' => $request->payment_method,
            'notes'          => $request->notes,
        ]);

        // Decrement but never below 0
        $supplier = Supplier::find($request->supplier_id);
        $supplier->update(['due_amount' => max(0, $supplier->due_amount - $request->amount)]);

        return redirect()->route('supplier-payments.index')
            ->with('success', 'সরবরাহকারী পরিশোধ সম্পন্ন হয়েছে।');
    }

    public function destroy(SupplierPayment $supplierPayment)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'শুধুমাত্র অ্যাডমিন মুছতে পারবেন।');
        }
        Supplier::where('id', $supplierPayment->supplier_id)
            ->increment('due_amount', $supplierPayment->amount);
        $supplierPayment->delete();

        return back()->with('success', 'পরিশোধ মুছে ফেলা হয়েছে এবং বকেয়া পুনরুদ্ধার করা হয়েছে।');
    }
}
