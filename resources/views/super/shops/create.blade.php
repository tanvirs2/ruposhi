@extends('layouts.super')
@section('title', 'নতুন শপ')
@section('page-title', 'নতুন শপ তৈরি')

@section('content')

<div class="sa-card" style="max-width:640px">
    <form method="POST" action="{{ route('super.shops.store') }}">
        @csrf

        <h2 style="font-size:1rem;font-weight:700;margin:0 0 18px;color:#f1f5f9">
            <i class="fas fa-store" style="color:#f59e0b"></i> শপের তথ্য
        </h2>

        <div class="sa-field">
            <label class="sa-label">শপের নাম <span style="color:#f87171">*</span></label>
            <input type="text" name="name" class="sa-input" value="{{ old('name') }}" required autofocus
                   placeholder="যেমন: মিরপুর শাখা">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
            <div class="sa-field">
                <label class="sa-label">ঠিকানা</label>
                <input type="text" name="address" class="sa-input" value="{{ old('address') }}"
                       placeholder="শপের ঠিকানা">
            </div>
            <div class="sa-field">
                <label class="sa-label">ফোন</label>
                <input type="text" name="phone" class="sa-input" value="{{ old('phone') }}"
                       placeholder="01XXXXXXXXX">
            </div>
        </div>

        <h2 style="font-size:1rem;font-weight:700;margin:24px 0 18px;color:#f1f5f9;border-top:1px solid #334155;padding-top:20px">
            <i class="fas fa-user-shield" style="color:#3b82f6"></i> শপ অ্যাডমিন অ্যাকাউন্ট
        </h2>
        <p style="font-size:.82rem;color:#94a3b8;margin:-10px 0 16px">
            এই অ্যাডমিন শুধু এই শপ পরিচালনা করতে পারবে।
        </p>

        <div class="sa-field">
            <label class="sa-label">অ্যাডমিনের নাম <span style="color:#f87171">*</span></label>
            <input type="text" name="admin_name" class="sa-input" value="{{ old('admin_name') }}" required
                   placeholder="পূর্ণ নাম">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
            <div class="sa-field">
                <label class="sa-label">ইমেইল <span style="color:#f87171">*</span></label>
                <input type="email" name="admin_email" class="sa-input" value="{{ old('admin_email') }}" required
                       placeholder="admin@shop.com">
            </div>
            <div class="sa-field">
                <label class="sa-label">পাসওয়ার্ড <span style="color:#f87171">*</span></label>
                <input type="text" name="admin_password" class="sa-input" required
                       placeholder="কমপক্ষে ৬ অক্ষর" minlength="6">
            </div>
        </div>

        <div style="display:flex;gap:10px;margin-top:8px">
            <button type="submit" class="sa-btn sa-btn-primary">
                <i class="fas fa-check"></i> শপ তৈরি করুন
            </button>
            <a href="{{ route('super.shops.index') }}" class="sa-btn sa-btn-ghost">
                <i class="fas fa-xmark"></i> বাতিল
            </a>
        </div>
    </form>
</div>

@endsection
