<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerPaymentController extends Controller
{
    public function index(Request $request)
    {
        $payments = CustomerPayment::with('customer', 'user')
            ->when($request->customer_id, fn($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->from, fn($q) => $q->whereDate('payment_date', '>=', $request->from))
            ->when($request->to,   fn($q) => $q->whereDate('payment_date', '<=', $request->to))
            ->latest('payment_date')->paginate(20);

        $customers = Customer::orderBy('name')->get();
        $totalPaid = $payments->sum('amount');

        return view('customer-payments.index', compact('payments', 'customers', 'totalPaid'));
    }

    public function create(Request $request)
    {
        $customers      = Customer::orderBy('name')->get();
        $selectedId     = $request->customer_id;
        $paymentMethods = StoreConfigController::getGroupedPaymentMethods();
        return view('customer-payments.create', compact('customers', 'selectedId', 'paymentMethods'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id'    => 'required|exists:customers,id',
            'amount'         => 'required|numeric|min:1',
            'payment_date'   => 'required|date',
            'payment_method' => 'required|string',
        ]);

        DB::transaction(function () use ($request) {
            CustomerPayment::create([
                'customer_id'    => $request->customer_id,
                'user_id'        => auth()->id(),
                'amount'         => $request->amount,
                'payment_date'   => $request->payment_date,
                'payment_method' => $request->payment_method,
                'notes'          => $request->notes,
            ]);

            // Reduce customer's due — never below 0
            $cust = Customer::find($request->customer_id);
            $cust->update(['due_amount' => max(0, $cust->due_amount - $request->amount)]);
        });

        return redirect()->route('customer-payments.index')->with('success', 'পরিশোধ সফলভাবে রেকর্ড হয়েছে।');
    }

    public function destroy(CustomerPayment $customerPayment)
    {
        DB::transaction(function () use ($customerPayment) {
            // Reverse the payment — add due back
            Customer::where('id', $customerPayment->customer_id)
                    ->increment('due_amount', $customerPayment->amount);
            $customerPayment->delete();
        });

        return redirect()->route('customer-payments.index')->with('success', 'পরিশোধ মুছে ফেলা হয়েছে।');
    }
}
