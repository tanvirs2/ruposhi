<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShopAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only a shop admin may manage staff/settings — or a super admin who
        // has "entered" this shop (Task 5). Plain staff are blocked.
        if (!auth()->check() || !auth()->user()->canManageShop()) {
            abort(403, 'শুধুমাত্র শপ অ্যাডমিন ব্যবহারকারী পরিচালনা করতে পারবেন।');
        }

        return $next($request);
    }
}
