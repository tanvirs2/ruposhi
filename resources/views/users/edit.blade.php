@extends('layouts.app')
@section('title', 'ব্যবহারকারী সম্পাদনা')
@section('page-title', 'ব্যবহারকারী সম্পাদনা')

@section('content')
<div class="form-card">
    <form method="POST" action="{{ route('users.update', $user) }}">
        @csrf @method('PUT')
        <div class="form-grid">
            <div class="form-group-field">
                <label>নাম <span class="req">*</span></label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required autofocus>
            </div>
            <div class="form-group-field">
                <label>ইমেইল <span class="req">*</span></label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
            </div>
            <div class="form-group-field">
                <label>নতুন পাসওয়ার্ড
                    <button type="button" class="info-btn" data-info="খালি রাখলে পাসওয়ার্ড অপরিবর্তিত থাকবে।">i</button>
                </label>
                <input type="text" name="password" minlength="6" placeholder="পরিবর্তন না করতে চাইলে খালি রাখুন">
            </div>
            <div class="form-group-field">
                <label>রোল <span class="req">*</span></label>
                <select name="role" class="form-select" required {{ $user->id === auth()->id() ? 'disabled' : '' }}>
                    <option value="staff" {{ old('role', $user->role)==='staff' ? 'selected' : '' }}>স্টাফ</option>
                    <option value="admin" {{ old('role', $user->role)==='admin' ? 'selected' : '' }}>অ্যাডমিন</option>
                </select>
                @if($user->id === auth()->id())
                    {{-- keep own role submitted even though select is disabled --}}
                    <input type="hidden" name="role" value="{{ $user->role }}">
                    <small style="color:#94a3b8">নিজের রোল পরিবর্তন করা যাবে না।</small>
                @endif
            </div>
        </div>
        <div class="form-actions">
            <a href="{{ route('users.index') }}" class="btn btn-ghost">বাতিল</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> আপডেট</button>
        </div>
    </form>
</div>
@endsection
