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

            if ($user->role === 'super_admin') {
                // Super admin may "enter" a shop and operate as its admin.
                $activeShopId = session('active_shop_id');

                // Not inside any shop → back to the control panel.
                if (!$activeShopId) {
                    return redirect()->route('super.dashboard');
                }

                // Act as that shop for THIS request only. Setting shop_id in
                // memory makes every scope, query and auto-fill treat the
                // super admin exactly like that shop's admin. syncOriginal()
                // keeps it dirty-free so it is never written back to the DB.
                $user->shop_id = $activeShopId;
                $user->syncOriginal();
            }
            // Non-super-admin with no shop assigned → block
            elseif (is_null($user->shop_id)) {
                abort(403, 'আপনার অ্যাকাউন্টে কোনো শপ নির্ধারিত নেই। অ্যাডমিনের সাথে যোগাযোগ করুন।');
            }
        }

        return $next($request);
    }
}
