<?php

namespace App\Http\Controllers\Root;

use App\Http\Controllers\Controller;
use App\Models\PaymentLog;
use App\Models\User;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /** All payments list (root view) */
    public function index(Request $request)
    {
        $query = PaymentLog::with(['user', 'recordedBy', 'reseller', 'license'])
            ->latest('payment_date')
            ->latest('id');

        // Filter by super_admin
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by method
        if ($request->filled('method')) {
            $query->where('payment_method', $request->method);
        }

        // Filter by date range — defaults to today
        $today = now()->toDateString();
        $from  = $request->from ?: $today;
        $to    = $request->to   ?: $today;
        $query->whereDate('payment_date', '>=', $from)
              ->whereDate('payment_date', '<=', $to);

        $totalAmount = $query->sum('amount');          // compute BEFORE paginate
        $payments    = $query->paginate(30)->withQueryString();

        $superAdmins = User::where('role', 'super_admin')->orderBy('name')->get();

        return view('root.payments.index', compact('payments', 'totalAmount', 'superAdmins', 'from', 'to'));
    }

    /** Record a new payment for a super_admin */
    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id'        => 'required|exists:users,id',
            'license_id'     => 'nullable|exists:licenses,id',
            'amount'         => 'required|numeric|min:1',
            'payment_method' => 'required|string',
            'transaction_id' => 'nullable|string|max:100',
            'payment_date'   => 'required|date',
            'notes'          => 'nullable|string|max:500',
        ]);

        // Detect reseller_id from the license if not provided
        $resellerIdFromLicense = null;
        if (! empty($data['license_id'])) {
            $lic = \App\Models\License::find($data['license_id']);
            $resellerIdFromLicense = $lic?->reseller_id;
        }

        PaymentLog::create([
            ...$data,
            'recorded_by' => auth()->id(),
            'reseller_id' => $resellerIdFromLicense,
        ]);

        return back()->with('success', 'পেমেন্ট সফলভাবে রেকর্ড হয়েছে।');
    }

    /** Delete a payment record */
    public function destroy(PaymentLog $payment)
    {
        $payment->delete();
        return back()->with('success', 'পেমেন্ট রেকর্ড মুছে ফেলা হয়েছে।');
    }
}
