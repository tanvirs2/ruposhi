<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RootAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check() || auth()->user()->role !== 'root') {
            abort(403, 'শুধুমাত্র রুট অ্যাডমিন এই পেজ দেখতে পারবেন।');
        }

        return $next($request);
    }
}
