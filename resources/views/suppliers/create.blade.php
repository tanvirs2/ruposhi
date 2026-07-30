@extends('layouts.app')
@section('title', 'নতুন সরবরাহকারী')
@section('page-title', 'নতুন সরবরাহকারী')

@section('content')
<div class="form-card">
    <form method="POST" action="{{ route('suppliers.store') }}">
        @csrf
        <div class="form-grid">
            <div class="form-group-field">
                <label>নাম <span class="req">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required>
            </div>
            <div class="form-group-field">
                <label>প্রোপ্রাইটর (মালিকের নাম)</label>
                <input type="text" name="proprietor" value="{{ old('proprietor') }}" placeholder="মোঃ হুমায়ন মোল্লা">
            </div>
            <div class="form-group-field">
                <label>ফোন নম্বর</label>
                <input type="text" name="phone" value="{{ old('phone') }}">
            </div>
            <div class="form-group-field">
                <label>ইমেইল</label>
                <input type="email" name="email" value="{{ old('email') }}">
            </div>
            @if(auth()->user()->canManageShop())
            <div class="form-group-field">
                <label>পুরনো দেনা (৳)</label>
                <input type="text" inputmode="decimal" name="opening_balance" id="openingBalanceInput" value="{{ old('opening_balance') }}" placeholder="খালি = ০">
                <div id="openingBalanceWords" style="display:none;margin-top:4px;font-size:.75rem;font-weight:600;color:#7c3aed"></div>
                <small style="color:#64748b;font-size:.75rem">সফটওয়্যার চালুর আগের খাতার দেনা। অগ্রিম দেওয়া থাকলে মাইনাস দিন (যেমন -৫০০)</small>
            </div>
            @endif
            <div class="form-group-field form-full">
                <label>ঠিকানা</label>
                <textarea name="address" rows="3">{{ old('address') }}</textarea>
            </div>
        </div>
        <div class="form-actions">
            <a href="{{ route('suppliers.index') }}" class="btn btn-ghost">বাতিল</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> সংরক্ষণ</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('turbo:load', () => bnWatchTakaWords('openingBalanceInput', 'openingBalanceWords'));
</script>
@endpush
