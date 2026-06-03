<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * The User model has NO global ShopScope (it powers auth), so every
     * query here is MANUALLY scoped to the logged-in admin's shop.
     */
    private function shopId(): int
    {
        return (int) auth()->user()->shop_id;
    }

    private function scopedUsers()
    {
        // Never expose super admins; only this shop's accounts
        return User::where('shop_id', $this->shopId())
                   ->where('role', '!=', 'super_admin');
    }

    /** Resolve a user that belongs to this shop, or 404. */
    private function findInShop(string $id): User
    {
        return $this->scopedUsers()->findOrFail($id);
    }

    public function index()
    {
        $users = $this->scopedUsers()->orderBy('name')->get();
        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role'     => ['required', Rule::in(['admin', 'staff'])],
        ]);

        User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'role'     => $data['role'],
            'shop_id'  => $this->shopId(),   // forced to admin's own shop
        ]);

        return redirect()->route('users.index')
                         ->with('success', "'{$data['name']}' যোগ হয়েছে।");
    }

    public function edit(string $id)
    {
        $user = $this->findInShop($id);
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, string $id)
    {
        $user = $this->findInShop($id);

        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'role'     => ['required', Rule::in(['admin', 'staff'])],
            'password' => 'nullable|min:6',
        ]);

        // An admin may not demote themselves (avoid locking the shop out)
        if ($user->id === auth()->id() && $data['role'] !== 'admin') {
            throw ValidationException::withMessages([
                'role' => 'আপনি নিজের অ্যাডমিন রোল পরিবর্তন করতে পারবেন না।',
            ]);
        }

        $user->name  = $data['name'];
        $user->email = $data['email'];
        $user->role  = $data['role'];
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        return redirect()->route('users.index')
                         ->with('success', "'{$user->name}' আপডেট হয়েছে।");
    }

    public function destroy(string $id)
    {
        $user = $this->findInShop($id);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'আপনি নিজের অ্যাকাউন্ট মুছতে পারবেন না।');
        }

        // Don't allow removing the shop's last admin
        if ($user->role === 'admin' && $this->scopedUsers()->where('role', 'admin')->count() <= 1) {
            return back()->with('error', 'শপের শেষ অ্যাডমিনকে মোছা যাবে না।');
        }

        $user->delete();

        return back()->with('success', 'ব্যবহারকারী মুছে ফেলা হয়েছে।');
    }
}
