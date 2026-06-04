<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResellerAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (! $user || ! in_array($user->role, ['root', 'reseller'])) {
            abort(403, 'শুধুমাত্র রিসেলার বা রুট অ্যাডমিন এই পেজ দেখতে পারবেন।');
        }

        return $next($request);
    }
}
