<?php

namespace App\Http\Controllers\Root;

use App\Http\Controllers\Controller;
use App\Models\PaymentLog;
use App\Models\Reseller;
use App\Models\ResellerPayout;
use App\Models\User;
use Illuminate\Http\Request;

class ResellerPayoutController extends Controller
{
    /** Show reseller detail + commission + payout history */
    public function show(User $reseller)
    {
        abort_unless($reseller->role === 'reseller', 404);

        $profile = $reseller->resellerProfile;
        $rate    = $profile?->commission_rate ?? 0;

        // Total client payments that came through this reseller
        $totalClientPayments = PaymentLog::where('reseller_id', $reseller->id)->sum('amount');

        // Commission earned (total)
        $commissionEarned = round($totalClientPayments * ($rate / 100), 2);

        // Total already paid out to reseller
        $totalPaidOut = ResellerPayout::where('reseller_id', $reseller->id)->sum('amount');

        // Outstanding balance
        $balanceDue = $commissionEarned - $totalPaidOut;

        // Payout history
        $payouts = ResellerPayout::where('reseller_id', $reseller->id)
            ->with('recordedBy')
            ->latest('paid_date')->latest('id')
            ->get();

        // Client payment history for this reseller
        $clientPayments = PaymentLog::where('reseller_id', $reseller->id)
            ->with('user')
            ->latest('payment_date')->latest('id')
            ->limit(20)
            ->get();

        // Reseller's clients
        $clients = User::where('role', 'super_admin')
            ->whereHas('licenses', fn($q) => $q->where('reseller_id', $reseller->id))
            ->with(['licenses' => fn($q) => $q->latest('expires_at')->limit(1)])
            ->get();

        $methods = ResellerPayout::paymentMethods();

        return view('root.resellers.show', compact(
            'reseller', 'profile', 'rate',
            'totalClientPayments', 'commissionEarned', 'totalPaidOut', 'balanceDue',
            'payouts', 'clientPayments', 'clients', 'methods'
        ));
    }

    /** Record a payout to reseller */
    public function store(Request $request, User $reseller)
    {
        abort_unless($reseller->role === 'reseller', 404);

        $request->validate([
            'amount'         => 'required|numeric|min:1',
            'payment_method' => 'required|string',
            'paid_date'      => 'required|date',
            'transaction_id' => 'nullable|string|max:100',
            'notes'          => 'nullable|string|max:500',
        ]);

        ResellerPayout::create([
            'reseller_id'    => $reseller->id,
            'recorded_by'    => auth()->id(),
            'amount'         => $request->amount,
            'payment_method' => $request->payment_method,
            'transaction_id' => $request->transaction_id,
            'paid_date'      => $request->paid_date,
            'notes'          => $request->notes,
        ]);

        return redirect()->route('root.resellers.show', $reseller)
            ->with('success', "৳" . number_format($request->amount, 0) . " পেমেন্ট রেকর্ড হয়েছে।");
    }

    /** Delete a payout record */
    public function destroy(User $reseller, ResellerPayout $payout)
    {
        abort_unless($payout->reseller_id === $reseller->id, 404);
        $payout->delete();
        return redirect()->route('root.resellers.show', $reseller)
            ->with('success', 'পেমেন্ট রেকর্ড মুছে ফেলা হয়েছে।');
    }
}
