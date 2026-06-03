@extends('layouts.super')
@section('title', 'শপ সম্পাদনা')
@section('page-title', 'শপ সম্পাদনা')

@section('content')

<div class="sa-card" style="max-width:640px">
    <form method="POST" action="{{ route('super.shops.update', $shop) }}">
        @csrf @method('PUT')

        <h2 style="font-size:1rem;font-weight:700;margin:0 0 18px;color:#f1f5f9">
            <i class="fas fa-store" style="color:#f59e0b"></i> {{ $shop->name }}
        </h2>

        <div class="sa-field">
            <label class="sa-label">শপের নাম <span style="color:#f87171">*</span></label>
            <input type="text" name="name" class="sa-input" value="{{ old('name', $shop->name) }}" required>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
            <div class="sa-field">
                <label class="sa-label">ঠিকানা</label>
                <input type="text" name="address" class="sa-input" value="{{ old('address', $shop->address) }}">
            </div>
            <div class="sa-field">
                <label class="sa-label">ফোন</label>
                <input type="text" name="phone" class="sa-input" value="{{ old('phone', $shop->phone) }}">
            </div>
        </div>

        <div class="sa-field">
            <label class="sa-label" style="display:flex;align-items:center;gap:10px;cursor:pointer">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" {{ $shop->is_active ? 'checked' : '' }}
                       style="width:18px;height:18px;accent-color:#f59e0b">
                শপ সক্রিয় রাখুন
            </label>
        </div>

        @if($admins->isNotEmpty())
        <div style="border-top:1px solid #334155;padding-top:18px;margin-top:8px">
            <div class="sa-label" style="margin-bottom:10px">এই শপের অ্যাডমিন</div>
            @foreach($admins as $admin)
            <div style="display:flex;align-items:center;gap:10px;padding:8px 0;font-size:.86rem;color:#cbd5e1">
                <i class="fas fa-user-shield" style="color:#3b82f6"></i>
                {{ $admin->name }} <span style="color:#64748b">({{ $admin->email }})</span>
            </div>
            @endforeach
        </div>
        @endif

        <div style="display:flex;gap:10px;margin-top:20px">
            <button type="submit" class="sa-btn sa-btn-primary">
                <i class="fas fa-check"></i> আপডেট করুন
            </button>
            <a href="{{ route('super.shops.index') }}" class="sa-btn sa-btn-ghost">
                <i class="fas fa-xmark"></i> বাতিল
            </a>
        </div>
    </form>
</div>

@endsection
