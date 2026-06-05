<?php

namespace App\Http\Controllers\Root;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Models\PaymentLog;
use App\Models\Reseller;
use App\Models\User;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();

        // ── All clients ───────────────────────────────────────────
        $superAdmins = User::where('role', 'super_admin')
            ->with(['licenses' => fn($q) => $q->latest('expires_at')->limit(1)])
            ->get();

        $total   = $superAdmins->count();
        $active  = 0; $warning = 0; $grace = 0; $expired = 0;

        foreach ($superAdmins as $u) {
            $lic = $u->activeLicense();
            if (!$lic) { $expired++; continue; }
            match ($lic->status) {
                'active'  => $active++,
                'warning' => $warning++,
                'grace'   => $grace++,
                default   => $expired++,
            };
        }

        $resellerCount = User::where('role', 'reseller')->count();

        // ── Revenue metrics ───────────────────────────────────────
        $revenueAllTime   = PaymentLog::sum('amount');
        $revenueThisMonth = PaymentLog::whereYear('payment_date', $now->year)
                                      ->whereMonth('payment_date', $now->month)
                                      ->sum('amount');
        $revenueLastMonth = PaymentLog::whereYear('payment_date', $now->copy()->subMonth()->year)
                                      ->whereMonth('payment_date', $now->copy()->subMonth()->month)
                                      ->sum('amount');

        // Month-over-month growth %
        $revenueGrowth = $revenueLastMonth > 0
            ? round((($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100, 1)
            : ($revenueThisMonth > 0 ? 100 : 0);

        // Projected ARR = this month × 12
        $arr = $revenueThisMonth * 12;

        // New clients this month
        $newClientsThisMonth = User::where('role', 'super_admin')
            ->whereYear('created_at', $now->year)
            ->whereMonth('created_at', $now->month)
            ->count();

        // ── Reseller commission summary ───────────────────────────
        $resellerProfiles   = Reseller::with('user')->get();
        $totalCommissionOwed = 0;
        $resellerSummary    = [];

        foreach ($resellerProfiles as $rp) {
            $paid = PaymentLog::where('reseller_id', $rp->user_id)->sum('amount');
            $comm = round($paid * ($rp->commission_rate / 100), 2);
            $totalCommissionOwed += $comm;
            $resellerSummary[] = [
                'name'       => $rp->user->name ?? '—',
                'rate'       => $rp->commission_rate,
                'paid'       => $paid,
                'commission' => $comm,
            ];
        }

        // ── Action items (smart alerts) ───────────────────────────
        $actions = collect();

        // Clients expiring in 7 days — urgent call
        $urgentExpiry = License::with('user')
            ->whereBetween('expires_at', [$now, $now->copy()->addDays(7)])
            ->get();
        foreach ($urgentExpiry as $lic) {
            $actions->push([
                'type'    => 'danger',
                'icon'    => 'fa-phone',
                'message' => "<b>{$lic->user->name}</b>-কে আজই ফোন করুন — লাইসেন্স <b>{$lic->daysUntilExpiry()} দিনে</b> শেষ হবে।",
                'link'    => route('root.super-admins.show', $lic->user),
                'btn'     => 'নবায়ন করুন',
            ]);
        }

        // Grace period — very urgent (status is computed, filter by actual date columns)
        $gracePeriod = License::with('user')
            ->where('expires_at', '<', $now)
            ->where('grace_ends_at', '>', $now)
            ->get();
        foreach ($gracePeriod as $lic) {
            $actions->push([
                'type'    => 'danger',
                'icon'    => 'fa-triangle-exclamation',
                'message' => "<b>{$lic->user->name}</b> গ্রেস পিরিয়ডে আছে — এখনই নবায়ন না করলে লক হবে।",
                'link'    => route('root.super-admins.show', $lic->user),
                'btn'     => 'নবায়ন করুন',
            ]);
        }

        // No license clients
        foreach ($superAdmins as $u) {
            if (!$u->activeLicense()) {
                $actions->push([
                    'type'    => 'warning',
                    'icon'    => 'fa-key',
                    'message' => "<b>{$u->name}</b>-এর কোনো লাইসেন্স নেই — লাইসেন্স দিন।",
                    'link'    => route('root.super-admins.show', $u),
                    'btn'     => 'লাইসেন্স দিন',
                ]);
            }
        }

        // Expiring 8–14 days
        $expiringSoon = License::with('user')
            ->whereBetween('expires_at', [$now->copy()->addDays(8), $now->copy()->addDays(14)])
            ->get();
        foreach ($expiringSoon as $lic) {
            $actions->push([
                'type'    => 'warning',
                'icon'    => 'fa-clock',
                'message' => "<b>{$lic->user->name}</b>-এর লাইসেন্স <b>{$lic->daysUntilExpiry()} দিনে</b> শেষ হবে — আগেভাগে জানান।",
                'link'    => route('root.super-admins.show', $lic->user),
                'btn'     => 'বিস্তারিত',
            ]);
        }

        if ($actions->isEmpty()) {
            $actions->push([
                'type'    => 'success',
                'icon'    => 'fa-circle-check',
                'message' => 'সব ঠিকঠাক আছে! কোনো জরুরি কাজ নেই।',
                'link'    => null,
                'btn'     => null,
            ]);
        }

        return view('root.dashboard', compact(
            'total', 'active', 'warning', 'grace', 'expired',
            'resellerCount', 'expiringSoon', 'superAdmins',
            'revenueAllTime', 'revenueThisMonth', 'revenueLastMonth',
            'revenueGrowth', 'arr', 'newClientsThisMonth',
            'totalCommissionOwed', 'resellerSummary', 'actions'
        ));
    }
}
