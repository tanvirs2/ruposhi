@extends('layouts.app')
@section('title', 'কাস্টমার সম্পাদনা')
@section('page-title', 'কাস্টমার সম্পাদনা')

@section('content')
<div class="form-card">
    <form method="POST" action="{{ route('customers.update', $customer) }}">
        @csrf @method('PUT')
        <div class="form-grid">
            <div class="form-group-field">
                <label>প্রতিষ্ঠানের নাম <span class="req">*</span></label>
                <input type="text" name="name" value="{{ old('name', $customer->name) }}" required>
            </div>
            <div class="form-group-field">
                <label>প্রোপ্রাইটরের নাম</label>
                <input type="text" name="proprietor" value="{{ old('proprietor', $customer->proprietor) }}">
            </div>
            <div class="form-group-field">
                <label>ফোন নম্বর</label>
                <input type="text" name="phone" value="{{ old('phone', $customer->phone) }}">
            </div>
            <div class="form-group-field">
                <label>এরিয়া</label>
                @include('partials.area-combobox', [
                    'acValue' => old('area_id', $customer->area_id),
                    'acPlaceholder' => 'এরিয়া নির্বাচন করুন (খুঁজুন)',
                    'acAllLabel' => '— এরিয়া নেই —',
                ])
            </div>
            <div class="form-group-field">
                <label>ক্রেডিট লিমিট (৳)</label>
                <input type="number" name="credit_limit" value="{{ old('credit_limit', $customer->credit_limit ? $customer->credit_limit + 0 : '') }}" min="0" step="any" placeholder="খালি = লিমিট নেই">
                <small style="color:#64748b;font-size:.75rem">বাকী এই সীমা ছাড়ালে বিক্রয়ের সময় সতর্কবার্তা দেখাবে</small>
            </div>
            <div class="form-group-field">
                <label>পুরনো বাকী (৳)</label>
                <input type="number" name="opening_balance" value="{{ old('opening_balance', $customer->opening_balance + 0) }}" step="any" placeholder="খালি = ০">
                <small style="color:#64748b;font-size:.75rem">সফটওয়্যার চালুর আগের খাতার বাকী। বদলালে মোট বাকীও সাথে সাথে ঠিক হয়ে যাবে</small>
            </div>
            <div class="form-group-field form-full">
                <label>ঠিকানা</label>
                <textarea name="address" rows="2">{{ old('address', $customer->address) }}</textarea>
            </div>
        </div>
        <div class="form-actions">
            <a href="{{ route('customers.index') }}" class="btn btn-ghost">বাতিল</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> আপডেট</button>
        </div>
    </form>
</div>
@endsection
