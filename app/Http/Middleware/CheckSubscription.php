<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Only applies to super_admin users.
     *
     * active  → pass through (no banner)
     * warning → pass through + share $subscriptionWarning with views (amber)
     * grace   → pass through + share $subscriptionGrace with views (red)
     * expired → redirect to /subscription-expired (LOCK)
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // Only check super_admin accounts — others are unaffected
        if (! $user || $user->role !== 'super_admin') {
            return $next($request);
        }

        $license = $user->activeLicense();

        // No license at all → treat as expired
        if (! $license) {
            // Allow the expired page itself and logout
            if ($request->routeIs('subscription.expired') || $request->routeIs('logout')) {
                return $next($request);
            }
            return redirect()->route('subscription.expired');
        }

        $status = $license->status;

        // Fully expired → lock out
        if ($status === 'expired') {
            if ($request->routeIs('subscription.expired') || $request->routeIs('logout')) {
                return $next($request);
            }
            return redirect()->route('subscription.expired');
        }

        // Share warning/grace data with all views
        if ($status === 'warning') {
            view()->share('subscriptionWarning', [
                'days' => $license->daysUntilExpiry(),
                'expires_at' => $license->expires_at->format('d M Y'),
            ]);
        }

        if ($status === 'grace') {
            view()->share('subscriptionGrace', [
                'days_left' => $license->graceDaysLeft(),
                'grace_ends_at' => $license->grace_ends_at->format('d M Y'),
            ]);
        }

        return $next($request);
    }
}
