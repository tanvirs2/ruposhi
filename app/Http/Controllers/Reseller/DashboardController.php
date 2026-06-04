<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Models\User;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $resellerId = auth()->id();

        // Clients this reseller created
        $clients = User::where('role', 'super_admin')
            ->whereHas('licenses', fn($q) => $q->where('reseller_id', $resellerId))
            ->with(['licenses' => fn($q) => $q->where('reseller_id', $resellerId)->latest('expires_at')->limit(1)])
            ->get();

        $total   = $clients->count();
        $active  = 0; $warning = 0; $grace = 0; $expired = 0;

        foreach ($clients as $c) {
            $lic = $c->activeLicense();
            if (! $lic) { $expired++; continue; }
            match ($lic->status) {
                'active'  => $active++,
                'warning' => $warning++,
                'grace'   => $grace++,
                default   => $expired++,
            };
        }

        // Expiring soon
        $expiringSoon = License::with('user')
            ->where('reseller_id', $resellerId)
            ->whereBetween('expires_at', [Carbon::now(), Carbon::now()->addDays(14)])
            ->get();

        return view('reseller.dashboard', compact(
            'clients', 'total', 'active', 'warning', 'grace', 'expired', 'expiringSoon'
        ));
    }
}
