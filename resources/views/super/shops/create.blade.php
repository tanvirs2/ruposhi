@extends('layouts.super')
@section('title', 'নতুন শাখা')
@section('page-title', 'নতুন শাখা তৈরি')

@section('content')

@if(!$canAdd)
<div class="sa-card" style="max-width:560px;border-color:#f59e0b44">
    <div style="text-align:center;padding:20px 0">
        <i class="fas fa-lock" style="font-size:2.5rem;color:#f59e0b;margin-bottom:14px;display:block"></i>
        <div style="font-size:1.05rem;font-weight:700;color:#f1f5f9;margin-bottom:8px">শাখা সীমা পূর্ণ</div>
        <p style="color:#94a3b8;font-size:.88rem">
            আপনার বর্তমান লাইসেন্সে সর্বোচ্চ <strong style="color:#fbbf24">{{ $license?->max_shops ?? 1 }}টি</strong> শাখা অনুমোদিত।<br>
            নতুন শাখার জন্য আপনার সফটওয়্যার প্রদানকারীকে লাইসেন্স আপগ্রেড করতে বলুন।
        </p>
        <a href="{{ route('super.shops.index') }}" class="sa-btn sa-btn-ghost" style="margin-top:16px">
            <i class="fas fa-arrow-left"></i> ফিরে যান
        </a>
    </div>
</div>
@else

<div class="sa-card" style="max-width:580px">

    @if(session('error'))
        <div style="padding:12px 16px;border-radius:10px;background:#7f1d1d44;color:#fca5a5;border:1px solid #7f1d1d;margin-bottom:18px;font-size:.88rem">
            <i class="fas fa-circle-xmark"></i> {{ session('error') }}
        </div>
    @endif
    @if($errors->any())
        <div style="padding:12px 16px;border-radius:10px;background:#7f1d1d44;color:#fca5a5;border:1px solid #7f1d1d;margin-bottom:18px;font-size:.88rem">
            @foreach($errors->all() as $e) {{ $e }}<br> @endforeach
        </div>
    @endif

    {{-- License limit info --}}
    <div style="padding:10px 14px;border-radius:9px;background:#0f172a;border:1px solid #334155;margin-bottom:20px;font-size:.83rem;color:#94a3b8;display:flex;align-items:center;gap:10px">
        <i class="fas fa-store" style="color:#f59e0b"></i>
        আপনার লাইসেন্স: <strong style="color:#fbbf24">{{ $shopCount }}</strong> /
        <strong style="color:#86efac">{{ $license->max_shops ?? '∞' }}</strong> শাখা ব্যবহৃত
    </div>

    <form method="POST" action="{{ route('super.shops.store') }}">
        @csrf

        <h2 style="font-size:1rem;font-weight:700;margin:0 0 18px;color:#f1f5f9">
            <i class="fas fa-store" style="color:#f59e0b"></i> নতুন শাখার তথ্য
        </h2>

        <div class="sa-field">
            <label class="sa-label">শাখার নাম <span style="color:#f87171">*</span></label>
            <input type="text" name="name" class="sa-input" value="{{ old('name') }}" required autofocus
                   placeholder="যেমন: মিরপুর শাখা, গুলশান শাখা">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
            <div class="sa-field">
                <label class="sa-label">ঠিকানা</label>
                <input type="text" name="address" class="sa-input" value="{{ old('address') }}"
                       placeholder="শাখার ঠিকানা">
            </div>
            <div class="sa-field">
                <label class="sa-label">ফোন</label>
                <input type="text" name="phone" class="sa-input" value="{{ old('phone') }}"
                       placeholder="01XXXXXXXXX">
            </div>
        </div>

        <p style="font-size:.8rem;color:#64748b;margin:0 0 20px;padding:10px 14px;background:#0f172a;border-radius:8px;border:1px solid #1e293b">
            <i class="fas fa-info-circle" style="color:#3b82f6"></i>
            শাখা তৈরির পর এতে প্রবেশ করে অ্যাডমিন ও স্টাফ যোগ করুন।
            শাখা সেটিংস (নাম, ঠিকানা, ফোন) পরেও পরিবর্তন করা যাবে।
        </p>

        <div style="display:flex;gap:10px">
            <button type="submit" class="sa-btn sa-btn-primary">
                <i class="fas fa-check"></i> শাখা তৈরি করুন
            </button>
            <a href="{{ route('super.shops.index') }}" class="sa-btn sa-btn-ghost">
                <i class="fas fa-xmark"></i> বাতিল
            </a>
        </div>
    </form>
</div>
@endif

@endsection
