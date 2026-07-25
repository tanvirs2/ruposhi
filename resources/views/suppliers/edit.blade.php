@extends('layouts.app')
@section('title', 'সরবরাহকারী সম্পাদনা')
@section('page-title', 'সরবরাহকারী সম্পাদনা')

@section('content')
<div class="form-card">
    <form method="POST" action="{{ route('suppliers.update', $supplier) }}">
        @csrf @method('PUT')
        <div class="form-grid">
            <div class="form-group-field">
                <label>নাম <span class="req">*</span></label>
                <input type="text" name="name" value="{{ old('name', $supplier->name) }}" required>
            </div>
            <div class="form-group-field">
                <label>প্রোপ্রাইটর (মালিকের নাম)</label>
                <input type="text" name="proprietor" value="{{ old('proprietor', $supplier->proprietor) }}" placeholder="মোঃ হুমায়ন মোল্লা">
            </div>
            <div class="form-group-field">
                <label>ফোন নম্বর</label>
                <input type="text" name="phone" value="{{ old('phone', $supplier->phone) }}">
            </div>
            <div class="form-group-field">
                <label>ইমেইল</label>
                <input type="email" name="email" value="{{ old('email', $supplier->email) }}">
            </div>
            <div class="form-group-field">
                <label>পুরনো দেনা (৳)</label>
                <input type="text" inputmode="decimal" name="opening_balance" value="{{ old('opening_balance', $supplier->opening_balance + 0) }}" placeholder="খালি = ০">
                <small style="color:#64748b;font-size:.75rem">সফটওয়্যার চালুর আগের খাতার দেনা। বদলালে মোট দেনাও সাথে সাথে ঠিক হয়ে যাবে</small>
            </div>
            <div class="form-group-field form-full">
                <label>ঠিকানা</label>
                <textarea name="address" rows="3">{{ old('address', $supplier->address) }}</textarea>
            </div>
        </div>
        <div class="form-actions">
            <a href="{{ route('suppliers.index') }}" class="btn btn-ghost">বাতিল</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> আপডেট</button>
        </div>
    </form>
</div>
@endsection
