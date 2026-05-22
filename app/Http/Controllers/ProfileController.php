<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit', ['user' => Auth::user()]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name'  => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,' . $user->id,
        ]);

        $user->name  = $request->name;
        $user->email = $request->email;
        $user->save();

        return back()->with('success', 'প্রোফাইল সফলভাবে আপডেট হয়েছে।');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password'      => 'required',
            'new_password'          => 'required|min:6|confirmed',
        ], [
            'current_password.required'  => 'বর্তমান পাসওয়ার্ড দিন।',
            'new_password.required'      => 'নতুন পাসওয়ার্ড দিন।',
            'new_password.min'           => 'পাসওয়ার্ড কমপক্ষে ৬ অক্ষরের হতে হবে।',
            'new_password.confirmed'     => 'নতুন পাসওয়ার্ড মিলছে না।',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->with('error', 'বর্তমান পাসওয়ার্ড সঠিক নয়।');
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return back()->with('success', 'পাসওয়ার্ড সফলভাবে পরিবর্তন হয়েছে।');
    }
}
