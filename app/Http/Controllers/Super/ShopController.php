<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ShopController extends Controller
{
    public function index()
    {
        $shops = Shop::withCount('users')->latest()->get();
        return view('super.shops.index', compact('shops'));
    }

    public function create()
    {
        return view('super.shops.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:150',
            'address'          => 'nullable|string|max:255',
            'phone'            => 'nullable|string|max:20',
            // Admin account for this shop
            'admin_name'       => 'required|string|max:100',
            'admin_email'      => 'required|email|unique:users,email',
            'admin_password'   => 'required|min:6',
        ]);

        // Create shop
        $shop = Shop::create([
            'name'    => $data['name'],
            'address' => $data['address'] ?? null,
            'phone'   => $data['phone'] ?? null,
        ]);

        // Create admin user for this shop
        User::create([
            'name'     => $data['admin_name'],
            'email'    => $data['admin_email'],
            'password' => Hash::make($data['admin_password']),
            'role'     => 'admin',
            'shop_id'  => $shop->id,
        ]);

        return redirect()->route('super.shops.index')
                         ->with('success', "'{$shop->name}' শপ তৈরি হয়েছে।");
    }

    public function show(Shop $shop)
    {
        $shop->load(['users', 'sales', 'customers', 'items']);
        $totalSales    = $shop->sales()->sum('total_amount');
        $totalCustomers = $shop->customers()->count();
        $totalItems    = $shop->items()->count();
        return view('super.shops.show', compact('shop', 'totalSales', 'totalCustomers', 'totalItems'));
    }

    public function edit(Shop $shop)
    {
        $admins = $shop->users()->where('role', 'admin')->get();
        return view('super.shops.edit', compact('shop', 'admins'));
    }

    public function update(Request $request, Shop $shop)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:150',
            'address'   => 'nullable|string|max:255',
            'phone'     => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        $shop->update($data);

        return redirect()->route('super.shops.index')
                         ->with('success', "'{$shop->name}' আপডেট হয়েছে।");
    }

    public function destroy(Shop $shop)
    {
        $shop->delete();
        return redirect()->route('super.shops.index')
                         ->with('success', 'শপ মুছে ফেলা হয়েছে।');
    }

    /**
     * Reset a shop user's password (super admin only).
     * Cannot reset super_admin accounts.
     */
    public function resetPassword(Request $request, User $user)
    {
        if ($user->role === 'super_admin' || !$user->shop_id) {
            abort(403, 'Super admin পাসওয়ার্ড এখান থেকে পরিবর্তন করা যাবে না।');
        }

        $request->validate([
            'password' => 'required|min:6',
        ]);

        $user->update(['password' => Hash::make($request->password)]);

        return back()->with('success', "'{$user->name}' এর পাসওয়ার্ড আপডেট হয়েছে।");
    }
}
