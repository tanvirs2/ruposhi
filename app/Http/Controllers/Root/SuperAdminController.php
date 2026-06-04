<?php

namespace App\Http\Controllers\Root;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Models\Shop;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SuperAdminController extends Controller
{
    public function index()
    {
        $superAdmins = User::where('role', 'super_admin')
            ->with([
                'licenses' => fn($q) => $q->latest('expires_at')->limit(1),
                'shop',
            ])
            ->latest()
            ->get();

        return view('root.super-admins.index', compact('superAdmins'));
    }

    public function create()
    {
        $resellers = User::where('role', 'reseller')->get(['id', 'name', 'email']);
        $plans     = ['monthly' => 'মাসিক (৩০ দিন)', 'quarterly' => 'ত্রৈমাসিক (৯০ দিন)', 'yearly' => 'বার্ষিক (৩৬৫ দিন)', 'custom' => 'কাস্টম'];
        return view('root.super-admins.create', compact('resellers', 'plans'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:100',
            'email'       => 'required|email|unique:users,email',
            'password'    => 'required|min:6',
            'shop_name'   => 'required|string|max:150',
            'plan'        => 'required|in:monthly,quarterly,yearly,custom',
            'custom_days' => 'required_if:plan,custom|nullable|integer|min:1',
            'reseller_id' => 'nullable|exists:users,id',
        ]);

        // Create the super_admin user (no shop_id — they manage shops)
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'super_admin',
            'shop_id'  => null,
        ]);

        // Create their first shop
        $shop = Shop::create([
            'name'      => $request->shop_name,
            'is_active' => true,
        ]);

        // Assign user to shop
        $user->update(['shop_id' => $shop->id]);

        // Create license
        $days      = $request->plan === 'custom' ? (int) $request->custom_days : License::daysForPlan($request->plan);
        $starts    = Carbon::now();
        $expires   = $starts->copy()->addDays($days);

        License::create([
            'user_id'       => $user->id,
            'reseller_id'   => $request->reseller_id ?: null,
            'plan'          => $request->plan,
            'starts_at'     => $starts,
            'expires_at'    => $expires,
            'grace_ends_at' => $expires->copy()->addDays(7),
            'extended_by'   => auth()->id(),
            'extended_at'   => Carbon::now(),
            'notes'         => $request->notes,
        ]);

        return redirect()->route('root.super-admins.index')
            ->with('success', "সুপার অ্যাডমিন '{$user->name}' তৈরি হয়েছে।");
    }

    public function show(User $superAdmin)
    {
        abort_unless($superAdmin->role === 'super_admin', 404);
        $licenses = $superAdmin->licenses()->latest('expires_at')->get();
        return view('root.super-admins.show', compact('superAdmin', 'licenses'));
    }

    public function edit(User $superAdmin)
    {
        abort_unless($superAdmin->role === 'super_admin', 404);
        $resellers = User::where('role', 'reseller')->get(['id', 'name', 'email']);
        $license   = $superAdmin->activeLicense();
        $plans     = ['monthly' => 'মাসিক (৩০ দিন)', 'quarterly' => 'ত্রৈমাসিক (৯০ দিন)', 'yearly' => 'বার্ষিক (৩৬৫ দিন)', 'custom' => 'কাস্টম'];
        return view('root.super-admins.edit', compact('superAdmin', 'resellers', 'license', 'plans'));
    }

    public function update(Request $request, User $superAdmin)
    {
        abort_unless($superAdmin->role === 'super_admin', 404);
        $request->validate([
            'name'  => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,' . $superAdmin->id,
        ]);

        $superAdmin->update([
            'name'  => $request->name,
            'email' => $request->email,
        ]);

        if ($request->filled('password')) {
            $superAdmin->update(['password' => Hash::make($request->password)]);
        }

        return redirect()->route('root.super-admins.index')
            ->with('success', 'তথ্য আপডেট হয়েছে।');
    }

    public function destroy(User $superAdmin)
    {
        abort_unless($superAdmin->role === 'super_admin', 404);
        $superAdmin->delete();
        return redirect()->route('root.super-admins.index')
            ->with('success', 'অ্যাকাউন্ট মুছে ফেলা হয়েছে।');
    }

    /* ── License extension ───────────────────────────────────── */

    public function extendLicense(Request $request, User $user)
    {
        abort_unless($user->role === 'super_admin', 404);

        $request->validate([
            'extend_type' => 'required|in:days,plan',
            'days'        => 'required_if:extend_type,days|nullable|integer|min:1|max:3650',
            'plan'        => 'required_if:extend_type,plan|nullable|in:monthly,quarterly,yearly',
            'from'        => 'required|in:expiry,today',
        ]);

        $days    = $request->extend_type === 'plan'
            ? License::daysForPlan($request->plan)
            : (int) $request->days;

        $license = $user->activeLicense();

        if (! $license) {
            // No license yet — create one
            $starts  = Carbon::now();
            $expires = $starts->copy()->addDays($days);
            License::create([
                'user_id'       => $user->id,
                'plan'          => $request->extend_type === 'plan' ? $request->plan : 'custom',
                'starts_at'     => $starts,
                'expires_at'    => $expires,
                'grace_ends_at' => $expires->copy()->addDays(7),
                'extended_by'   => auth()->id(),
                'extended_at'   => Carbon::now(),
                'notes'         => $request->notes,
            ]);
        } elseif ($request->from === 'today') {
            $license->extendFromToday($days, auth()->id());
        } else {
            $license->extendByDays($days, auth()->id());
        }

        if ($request->filled('notes') && $license) {
            $license->update(['notes' => $request->notes]);
        }

        return redirect()->back()
            ->with('success', "{$user->name}-এর লাইসেন্স {$days} দিন বাড়ানো হয়েছে।");
    }
}
