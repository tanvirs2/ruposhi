<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerPaymentController extends Controller
{
    public function index(Request $request)
    {
        // Customer payments are now stored as no-item sales (previous-due
        // payments), so this list shows those no-item Sale records.
        $base = Sale::with('customer', 'user')
            ->doesntHave('items')
            ->when($request->customer_id, fn($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->from, fn($q) => $q->whereDate('sale_date', '>=', $request->from))
            ->when($request->to,   fn($q) => $q->whereDate('sale_date', '<=', $request->to));

        $totalPaid = (clone $base)->sum('paid_amount');
        $payments  = $base->latest('sale_date')->latest('id')->paginate(20);

        // Only resolve the currently-filtered customer (for pre-filling the
        // search box) — never load the full customer table here, it can
        // grow into the thousands.
        $selectedCustomer = $request->customer_id ? Customer::find($request->customer_id) : null;

        return view('customer-payments.index', compact('payments', 'selectedCustomer', 'totalPaid'));
    }

    public function create(Request $request)
    {
        $selectedId     = $request->customer_id;
        // Server-side search — only seed the pre-selected customer (from ledger)
        $preCustomer    = null;
        if ($selectedId && $c = Customer::find($selectedId)) {
            $preCustomer = [
                'id'         => $c->id,
                'name'       => $c->name,
                'phone'      => $c->phone ?? '',
                'proprietor' => $c->proprietor ?? '',
                'due'        => floatval($c->due_amount),
            ];
        }
        $paymentMethods = StoreConfigController::getGroupedPaymentMethods();
        return view('customer-payments.create', compact('preCustomer', 'selectedId', 'paymentMethods'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id'    => 'required|exists:customers,id',
            'amount'         => 'required|numeric|min:1',
            'payment_date'   => 'required|date',
            'payment_method' => 'required|string',
        ]);

        // A customer payment with NO items is functionally identical to a
        // no-item sale (paying off previous due). So we create a Sale record:
        //  - shows in the বিক্রয় তালিকা (sales list)
        //  - generates an invoice (sales.show)
        //  - reduces customer due exactly once
        $sale = null;
        DB::transaction(function () use ($request, &$sale) {
            $cust        = Customer::find($request->customer_id);
            $previousDue = $cust->due_amount;

            $sale = Sale::create([
                'customer_id'    => $request->customer_id,
                'user_id'        => auth()->id(),
                'total_amount'   => 0,
                'discount'       => 0,
                'extra_cost'     => 0,
                'paid_amount'    => $request->amount,
                'due_amount'     => 0,
                'previous_due'   => $previousDue,
                'status'         => 'completed',
                'payment_method' => $request->payment_method,
                'notes'          => $request->notes,
                'sale_date'      => $request->payment_date,
            ]);

            // net effect: customer.due -= amount. Atomic decrement avoids
            // lost-update races; allows negative (credit) balance.
            $cust->decrement('due_amount', $request->amount);
        });

        return redirect()->route('sales.show', $sale)->with('success', 'কাস্টমার পরিশোধ সম্পন্ন হয়েছে। বিক্রয় তালিকায় যোগ হয়েছে।');
    }

    public function show(CustomerPayment $customerPayment)
    {
        $customerPayment->load('customer', 'user');
        $store = [
            'name'    => \App\Models\StoreConfig::get('store_name',    'আমার চালের দোকান'),
            'owner'   => \App\Models\StoreConfig::get('store_owner',   ''),
            'tagline' => \App\Models\StoreConfig::get('store_tagline', ''),
            'phone'   => \App\Models\StoreConfig::get('store_phone',   ''),
            'phone2'  => \App\Models\StoreConfig::get('store_phone2',  ''),
            'address' => \App\Models\StoreConfig::get('store_address', ''),
        ];
        return view('customer-payments.show', compact('customerPayment', 'store'));
    }

    public function destroy(CustomerPayment $customerPayment)
    {
        if (!auth()->user()->canManageShop()) {
            abort(403, 'শুধুমাত্র অ্যাডমিন মুছতে পারবেন।');
        }
        DB::transaction(function () use ($customerPayment) {
            // Reverse the payment — add due back
            Customer::where('id', $customerPayment->customer_id)
                    ->increment('due_amount', $customerPayment->amount);
            $customerPayment->delete();
        });

        return redirect()->route('customer-payments.index')->with('success', 'পরিশোধ মুছে ফেলা হয়েছে।');
    }
}
