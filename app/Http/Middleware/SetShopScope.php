<?php

namespace App\Http\Middleware;

use App\Models\Shop;
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

                // Security: verify this shop actually belongs to this super_admin.
                $shop = Shop::where('id', $activeShopId)
                             ->where('super_admin_id', $user->id)
                             ->first();

                if (!$shop) {
                    // Invalid shop in session — clear it and send back to panel.
                    session()->forget(['active_shop_id', 'active_shop_name']);
                    return redirect()->route('super.dashboard')
                                     ->with('error', 'শপ অ্যাক্সেস নিশ্চিত করা যায়নি।');
                }

                // Shop locked after session was started (e.g. license downgraded remotely)
                if ($shop->is_locked) {
                    session()->forget(['active_shop_id', 'active_shop_name']);
                    return redirect()->route('super.dashboard')
                                     ->with('error', "'{$shop->name}' শাখাটি লাইসেন্স সীমার কারণে লক হয়েছে। লাইসেন্স আপগ্রেড করুন।");
                }

                // Act as that shop for THIS request only. Setting shop_id in
                // memory makes every scope, query and auto-fill treat the
                // super admin exactly like that shop's admin. syncOriginal()
                // keeps it dirty-free so it is never written back to the DB.
                $user->shop_id = $activeShopId;
                $user->syncOriginal();
            }
            // Wrong-role user on a shop route → redirect to their proper panel
            elseif (is_null($user->shop_id)) {
                if ($user->role === 'root') {
                    return redirect()->route('root.dashboard');
                }
                if ($user->role === 'reseller') {
                    return redirect()->route('reseller.dashboard');
                }
                abort(403, 'আপনার অ্যাকাউন্টে কোনো শপ নির্ধারিত নেই। অ্যাডমিনের সাথে যোগাযোগ করুন।');
            }
            // Admin/staff: check if their assigned shop is locked OR owner license expired
            else {
                $shop = Shop::where('id', $user->shop_id)->first();

                if ($shop && $shop->is_locked) {
                    auth()->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    return redirect()->route('login')
                        ->with('error', "'{$shop->name}' শাখাটি বর্তমানে লক করা আছে। লাইসেন্স সমস্যার জন্য আপনার মালিকের সাথে যোগাযোগ করুন।");
                }

                // Also check the shop owner's license — catches expiry before syncShopLocks() runs
                if ($shop && $shop->super_admin_id) {
                    $owner = \App\Models\User::find($shop->super_admin_id);
                    if ($owner) {
                        $license = $owner->activeLicense();
                        if (!$license || $license->status === 'expired') {
                            // Lock the shops now so future requests hit is_locked fast-path
                            $owner->syncShopLocks();
                            auth()->logout();
                            $request->session()->invalidate();
                            $request->session()->regenerateToken();
                            return redirect()->route('login')
                                ->with('error', "'{$shop->name}' শাখার সাবস্ক্রিপশন মেয়াদ শেষ হয়েছে। মালিকের সাথে যোগাযোগ করুন।");
                        }
                    }
                }
            }
        }

        return $next($request);
    }
}
