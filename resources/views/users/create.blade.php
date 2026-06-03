@extends('layouts.app')
@section('title', 'নতুন ব্যবহারকারী')
@section('page-title', 'নতুন ব্যবহারকারী')

@section('content')
<div class="form-card">
    <form method="POST" action="{{ route('users.store') }}">
        @csrf
        <div class="form-grid">
            <div class="form-group-field">
                <label>নাম <span class="req">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required autofocus>
            </div>
            <div class="form-group-field">
                <label>ইমেইল <span class="req">*</span></label>
                <input type="email" name="email" value="{{ old('email') }}" required placeholder="login@example.com">
            </div>
            <div class="form-group-field">
                <label>পাসওয়ার্ড <span class="req">*</span></label>
                <input type="text" name="password" required minlength="6" placeholder="কমপক্ষে ৬ অক্ষর">
            </div>
            <div class="form-group-field">
                <label>রোল <span class="req">*</span></label>
                <select name="role" class="form-select" required>
                    <option value="staff" {{ old('role')==='staff' ? 'selected' : '' }}>স্টাফ</option>
                    <option value="admin" {{ old('role')==='admin' ? 'selected' : '' }}>অ্যাডমিন</option>
                </select>
            </div>
        </div>
        <div class="form-actions">
            <a href="{{ route('users.index') }}" class="btn btn-ghost">বাতিল</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> সংরক্ষণ</button>
        </div>
    </form>
</div>
@endsection
