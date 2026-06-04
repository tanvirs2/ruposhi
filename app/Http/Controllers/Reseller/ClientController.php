<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Models\Shop;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ClientController extends Controller
{
    private function resellerProfile()
    {
        return auth()->user()->resellerProfile;
    }

    public function index()
    {
        $resellerId = auth()->id();
        $clients = User::where('role', 'super_admin')
            ->whereHas('licenses', fn($q) => $q->where('reseller_id', $resellerId))
            ->with(['licenses' => fn($q) => $q->where('reseller_id', $resellerId)->latest('expires_at')->limit(1)])
            ->latest()
            ->get();
        return view('reseller.clients.index', compact('clients'));
    }

    public function create()
    {
        $profile = $this->resellerProfile();

        // Check quota
        if ($profile && $profile->atLimit()) {
            return back()->with('error', 'আপনার সর্বোচ্চ শপ তৈরির সীমা পূর্ণ হয়েছে।');
        }

        $plans = ['monthly' => 'মাসিক (৩০ দিন)', 'quarterly' => 'ত্রৈমাসিক (৯০ দিন)', 'yearly' => 'বার্ষিক (৩৬৫ দিন)'];
        return view('reseller.clients.create', compact('plans'));
    }

    public function store(Request $request)
    {
        $profile = $this->resellerProfile();
        if ($profile && $profile->atLimit()) {
            return back()->with('error', 'আপনার সর্বোচ্চ শপ তৈরির সীমা পূর্ণ হয়েছে।');
        }

        $request->validate([
            'name'      => 'required|string|max:100',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|min:6',
            'shop_name' => 'required|string|max:150',
            'plan'      => 'required|in:monthly,quarterly,yearly',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'super_admin',
            'shop_id'  => null,
        ]);

        Shop::create([
            'name'           => $request->shop_name,
            'super_admin_id' => $user->id,
            'is_active'      => true,
        ]);

        $days    = License::daysForPlan($request->plan);
        $starts  = Carbon::now();
        $expires = $starts->copy()->addDays($days);

        License::create([
            'user_id'       => $user->id,
            'reseller_id'   => auth()->id(),
            'plan'          => $request->plan,
            'starts_at'     => $starts,
            'expires_at'    => $expires,
            'grace_ends_at' => $expires->copy()->addDays(7),
            'extended_by'   => auth()->id(),
            'extended_at'   => Carbon::now(),
        ]);

        return redirect()->route('reseller.clients.index')
            ->with('success', "ক্লায়েন্ট '{$user->name}' তৈরি হয়েছে।");
    }

    public function extendLicense(Request $request, User $user)
    {
        $resellerId = auth()->id();

        // Ensure this client belongs to this reseller
        $license = License::where('user_id', $user->id)
            ->where('reseller_id', $resellerId)
            ->latest('expires_at')
            ->firstOrFail();

        // Check permission
        $profile = $this->resellerProfile();
        if ($profile && ! $profile->can_extend_license) {
            abort(403, 'আপনার লাইসেন্স বাড়ানোর অনুমতি নেই।');
        }

        $request->validate([
            'plan' => 'required|in:monthly,quarterly,yearly',
            'from' => 'required|in:expiry,today',
        ]);

        $days = License::daysForPlan($request->plan);

        if ($request->from === 'today') {
            $license->extendFromToday($days, $resellerId);
        } else {
            $license->extendByDays($days, $resellerId);
        }

        // Auto-lock/unlock shops based on updated license
        $user->refresh()->syncShopLocks();

        return redirect()->back()
            ->with('success', "{$user->name}-এর লাইসেন্স {$days} দিন বাড়ানো হয়েছে।");
    }
}
