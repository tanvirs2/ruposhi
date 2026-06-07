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
    public function index(\Illuminate\Http\Request $request)
    {
        $search = $request->input('q');

        $superAdmins = User::where('role', 'super_admin')
            ->with(['licenses' => fn($q) => $q->latest('expires_at')->limit(1)])
            ->withCount('myShops')
            ->when($search, function ($q) use ($search) {
                // Search by name, email, or SA-ID (e.g. "SA-3" or just "3")
                $id = preg_replace('/[^0-9]/', '', $search); // extract digits
                $q->where(function ($s) use ($search, $id) {
                    $s->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                    if ($id !== '') {
                        $s->orWhere('id', (int) $id);
                    }
                });
            })
            ->latest()
            ->get();

        return view('root.super-admins.index', compact('superAdmins', 'search'));
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
            'max_shops'   => 'nullable|integer|min:1',
        ]);

        // Create the super_admin user (shop_id stays null — shops point back via super_admin_id)
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'super_admin',
            'shop_id'  => null,
        ]);

        // Create their first shop (owned by this super_admin)
        Shop::create([
            'name'           => $request->shop_name,
            'super_admin_id' => $user->id,
            'is_active'      => true,
        ]);

        // Create license
        $days      = $request->plan === 'custom' ? (int) $request->custom_days : License::daysForPlan($request->plan);
        $starts    = Carbon::now();
        $expires   = $starts->copy()->addDays($days);

        // max_shops: null=unlimited, default=1 (basic)
        $maxShops = $request->filled('max_shops') ? (int) $request->max_shops : 1;

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
            'max_shops'     => $maxShops,
        ]);

        return redirect()->route('root.super-admins.index')
            ->with('success', "সুপার অ্যাডমিন '{$user->name}' তৈরি হয়েছে।");
    }

    public function show(User $superAdmin)
    {
        abort_unless($superAdmin->role === 'super_admin', 404);
        $licenses = $superAdmin->licenses()->latest('expires_at')->get();
        $payments = \App\Models\PaymentLog::with(['recordedBy'])
            ->where('user_id', $superAdmin->id)
            ->latest('payment_date')->latest('id')
            ->get();
        return view('root.super-admins.show', compact('superAdmin', 'licenses', 'payments'));
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
            'from'        => 'required|in:expiry,today,override',
            'max_shops'   => 'nullable|integer|min:1',
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
        } elseif ($request->from === 'override') {
            // Directly set: today + N days (ignores current expiry completely)
            $license->setFromToday($days, auth()->id());
        } elseif ($request->from === 'today') {
            $license->extendFromToday($days, auth()->id());
        } else {
            $license->extendByDays($days, auth()->id());
        }

        // Update notes and/or max_shops on the license if provided
        $updateFields = [];
        if ($request->filled('notes')) $updateFields['notes'] = $request->notes;
        if ($request->filled('max_shops')) $updateFields['max_shops'] = (int) $request->max_shops;

        $latestLicense = $user->activeLicense();
        if ($latestLicense && $updateFields) {
            $latestLicense->update($updateFields);
        }

        // Auto-lock/unlock shops based on the new max_shops limit
        $user->refresh()->syncShopLocks();

        return redirect()->back()
            ->with('success', "{$user->name}-এর লাইসেন্স {$days} দিন বাড়ানো হয়েছে।");
    }

    /* ── Force-expire a license immediately ─────────────────── */

    public function expireLicense(User $user)
    {
        abort_unless($user->role === 'super_admin', 404);

        $license = $user->activeLicense();

        if (! $license) {
            return redirect()->back()->with('error', 'এই client-এর কোনো সক্রিয় লাইসেন্স নেই।');
        }

        $license->update([
            'expires_at'    => Carbon::now()->subMinute(),
            'grace_ends_at' => Carbon::now()->subMinute(),
        ]);

        // Lock all their shops immediately
        $user->refresh()->syncShopLocks();

        return redirect()->back()
            ->with('success', "{$user->name}-এর লাইসেন্স তাৎক্ষণিকভাবে মেয়াদ শেষ করা হয়েছে।");
    }
}
