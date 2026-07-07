<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Item;
use App\Models\Supplier;
use Illuminate\Http\Request;

/* Topbar global search — invokable so the route stays cacheable (no closures) */
class GlobalSearchController extends Controller
{
    public function __invoke(Request $r)
    {
        $q = trim($r->get('q', ''));
        if (mb_strlen($q) < 1) {
            return response()->json(['items' => [], 'customers' => [], 'suppliers' => []]);
        }
        return response()->json([
            'items'     => Item::where('name', 'like', "%{$q}%")->limit(6)->get(['id', 'name']),
            'customers' => Customer::where(fn($w) => $w->where('name', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%"))->limit(5)->get(['id', 'name', 'phone']),
            'suppliers' => Supplier::where(fn($w) => $w->where('name', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%"))->limit(5)->get(['id', 'name', 'phone']),
        ]);
    }
}
