<?php

namespace App\Http\Controllers\Root;

use App\Http\Controllers\Controller;
use App\Models\Reseller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ResellerController extends Controller
{
    public function index()
    {
        $resellers = User::where('role', 'reseller')
            ->with('resellerProfile')
            ->latest()
            ->get();
        return view('root.resellers.index', compact('resellers'));
    }

    public function create()
    {
        return view('root.resellers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'               => 'required|string|max:100',
            'email'              => 'required|email|unique:users,email',
            'password'           => 'required|min:6',
            'can_extend_license' => 'boolean',
            'max_shops'          => 'nullable|integer|min:1',
            'commission_rate'    => 'nullable|numeric|min:0|max:100',
            'notes'              => 'nullable|string',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'reseller',
            'shop_id'  => null,
        ]);

        Reseller::create([
            'user_id'            => $user->id,
            'can_extend_license' => $request->boolean('can_extend_license', true),
            'max_shops'          => $request->max_shops ?: null,
            'commission_rate'    => $request->commission_rate ?? 0,
            'notes'              => $request->notes,
        ]);

        return redirect()->route('root.resellers.index')
            ->with('success', "রিসেলার '{$user->name}' তৈরি হয়েছে।");
    }

    public function edit(User $reseller)
    {
        abort_unless($reseller->role === 'reseller', 404);
        $profile = $reseller->resellerProfile ?? new Reseller();
        return view('root.resellers.edit', compact('reseller', 'profile'));
    }

    public function update(Request $request, User $reseller)
    {
        abort_unless($reseller->role === 'reseller', 404);
        $request->validate([
            'name'               => 'required|string|max:100',
            'email'              => 'required|email|unique:users,email,' . $reseller->id,
            'can_extend_license' => 'boolean',
            'max_shops'          => 'nullable|integer|min:1',
            'commission_rate'    => 'nullable|numeric|min:0|max:100',
        ]);

        $reseller->update([
            'name'  => $request->name,
            'email' => $request->email,
        ]);

        if ($request->filled('password')) {
            $reseller->update(['password' => Hash::make($request->password)]);
        }

        $reseller->resellerProfile()->updateOrCreate(
            ['user_id' => $reseller->id],
            [
                'can_extend_license' => $request->boolean('can_extend_license', true),
                'max_shops'          => $request->max_shops ?: null,
                'commission_rate'    => $request->commission_rate ?? 0,
                'notes'              => $request->notes,
            ]
        );

        return redirect()->route('root.resellers.index')
            ->with('success', 'রিসেলার তথ্য আপডেট হয়েছে।');
    }

    public function destroy(User $reseller)
    {
        abort_unless($reseller->role === 'reseller', 404);
        $reseller->delete();
        return redirect()->route('root.resellers.index')
            ->with('success', 'রিসেলার মুছে ফেলা হয়েছে।');
    }
}
