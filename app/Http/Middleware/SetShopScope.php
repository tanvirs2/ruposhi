<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetShopScope
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();

            // Super admin belongs in the control panel, not shop pages
            if ($user->role === 'super_admin') {
                return redirect()->route('super.dashboard');
            }

            // Non-super-admin with no shop assigned → block
            if (is_null($user->shop_id)) {
                abort(403, 'আপনার অ্যাকাউন্টে কোনো শপ নির্ধারিত নেই। অ্যাডমিনের সাথে যোগাযোগ করুন।');
            }
        }

        return $next($request);
    }
}
