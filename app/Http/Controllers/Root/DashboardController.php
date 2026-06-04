<?php

namespace App\Http\Controllers\Root;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Models\User;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // All super_admin accounts
        $superAdmins = User::where('role', 'super_admin')
            ->with(['licenses' => fn($q) => $q->latest('expires_at')->limit(1)])
            ->get();

        // Stats
        $total     = $superAdmins->count();
        $active    = 0;
        $warning   = 0;
        $grace     = 0;
        $expired   = 0;

        foreach ($superAdmins as $u) {
            $lic = $u->activeLicense();
            if (! $lic) { $expired++; continue; }
            match ($lic->status) {
                'active'  => $active++,
                'warning' => $warning++,
                'grace'   => $grace++,
                default   => $expired++,
            };
        }

        // Resellers
        $resellerCount = User::where('role', 'reseller')->count();

        // Expiring soon (next 14 days)
        $expiringSoon = License::with('user')
            ->whereBetween('expires_at', [Carbon::now(), Carbon::now()->addDays(14)])
            ->latest('expires_at')
            ->get();

        return view('root.dashboard', compact(
            'total', 'active', 'warning', 'grace', 'expired',
            'resellerCount', 'expiringSoon', 'superAdmins'
        ));
    }
}
