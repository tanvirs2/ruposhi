<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $role = Auth::user()->role;

            // Root → root panel
            if ($role === 'root') {
                return redirect()->intended(route('root.dashboard'));
            }

            // Reseller → reseller panel
            if ($role === 'reseller') {
                return redirect()->intended(route('reseller.dashboard'));
            }

            // Super admin → check subscription first
            if ($role === 'super_admin') {
                if (Auth::user()->isSubscriptionLocked()) {
                    return redirect()->route('subscription.expired');
                }
                return redirect()->intended(route('super.dashboard'));
            }

            // Admin / Staff → shop dashboard
            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors(['email' => 'ইমেইল বা পাসওয়ার্ড ভুল।'])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
