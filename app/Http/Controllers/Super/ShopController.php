<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ShopController extends Controller
{
    /** Only shops owned by the currently logged-in super_admin */
    private function myShops()
    {
        return Shop::where('super_admin_id', auth()->id());
    }

    public function index()
    {
        $shops = $this->myShops()->withCount('users')->latest()->get();
        return view('super.shops.index', compact('shops'));
    }

    /**
     * "Enter" a shop — the super admin starts operating inside it exactly
     * like that shop's own admin (sales, stock, reports, staff, settings).
     */
    public function enter(Shop $shop)
    {
        // Security: only enter shops that belong to this super_admin
        abort_unless($shop->super_admin_id === auth()->id(), 403, 'এই শপে অ্যাক্সেস নেই।');

        // Locked shop — data is safe but access blocked until license upgrade
        if ($shop->is_locked) {
            return redirect()->route('super.shops.index')
                ->with('error', "'{$shop->name}' শাখাটি লাইসেন্স সীমার কারণে লক করা আছে। লাইসেন্স আপগ্রেড করলে এই শাখার সব ডেটা পুনরায় ব্যবহার করতে পারবেন।");
        }

        session([
            'active_shop_id'   => $shop->id,
            'active_shop_name' => $shop->name,
        ]);

        return redirect()->route('dashboard')
                         ->with('success', "'{$shop->name}' শাখায় প্রবেশ করেছেন।");
    }

    /**
     * Leave the entered shop and return to the super admin control panel.
     */
    public function exitShop()
    {
        session()->forget(['active_shop_id', 'active_shop_name']);
        return redirect()->route('super.dashboard');
    }

    public function create()
    {
        $license   = auth()->user()->activeLicense();
        $shopCount = $this->myShops()->count();
        $canAdd    = $license && $license->canAddShops($shopCount);

        return view('super.shops.create', compact('license', 'shopCount', 'canAdd'));
    }

    public function store(Request $request)
    {
        // Check branch limit before creating
        $license   = auth()->user()->activeLicense();
        $shopCount = $this->myShops()->count();

        if (!$license || !$license->canAddShops($shopCount)) {
            $max = $license?->max_shops ?? 1;
            return back()->with('error', "আপনার লাইসেন্সে সর্বোচ্চ {$max}টি শাখা অনুমোদিত। নতুন শাখার জন্য লাইসেন্স আপগ্রেড করুন।");
        }

        $data = $request->validate([
            'name'    => 'required|string|max:150',
            'address' => 'nullable|string|max:255',
            'phone'   => 'nullable|string|max:20',
        ]);

        $shop = Shop::create([
            'name'           => $data['name'],
            'address'        => $data['address'] ?? null,
            'phone'          => $data['phone'] ?? null,
            'super_admin_id' => auth()->id(),
            'is_active'      => true,
        ]);

        return redirect()->route('super.shops.index')
                         ->with('success', "'{$shop->name}' শাখা তৈরি হয়েছে। এখন এতে প্রবেশ করে স্টাফ যোগ করুন।");
    }

    public function show(Shop $shop)
    {
        abort_unless($shop->super_admin_id === auth()->id(), 403);

        $shop->load(['users', 'sales', 'customers', 'items']);
        $totalSales     = $shop->sales()->sum('total_amount');
        $totalCustomers = $shop->customers()->count();
        $totalItems     = $shop->items()->count();

        return view('super.shops.show', compact('shop', 'totalSales', 'totalCustomers', 'totalItems'));
    }

    public function edit(Shop $shop)
    {
        abort_unless($shop->super_admin_id === auth()->id(), 403);

        $admins = $shop->users()->where('role', 'admin')->get();
        return view('super.shops.edit', compact('shop', 'admins'));
    }

    public function update(Request $request, Shop $shop)
    {
        abort_unless($shop->super_admin_id === auth()->id(), 403);

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
        abort_unless($shop->super_admin_id === auth()->id(), 403);

        $shop->delete();
        return redirect()->route('super.shops.index')
                         ->with('success', 'শাখা মুছে ফেলা হয়েছে।');
    }

    /**
     * Reset a shop user's password (super admin only).
     */
    public function resetPassword(Request $request, User $user)
    {
        // Only allow resetting passwords for users in this super_admin's shops
        $myShopIds = $this->myShops()->pluck('id');

        if ($user->role === 'super_admin' || !$myShopIds->contains($user->shop_id)) {
            abort(403, 'এই ব্যবহারকারীর পাসওয়ার্ড পরিবর্তন করার অনুমতি নেই।');
        }

        $request->validate([
            'password' => 'required|min:6',
        ]);

        $user->update(['password' => Hash::make($request->password)]);

        return back()->with('success', "'{$user->name}' এর পাসওয়ার্ড আপডেট হয়েছে।");
    }
}
