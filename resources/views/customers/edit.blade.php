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
                <label>ইমেইল</label>
                <input type="email" name="email" value="{{ old('email', $customer->email) }}">
            </div>
            <div class="form-group-field">
                <label>এরিয়া</label>
                <select name="area_id" class="form-select">
                    <option value="">এরিয়া নির্বাচন করুন</option>
                    @foreach($areas as $area)
                        <option value="{{ $area->id }}" {{ old('area_id', $customer->area_id) == $area->id ? 'selected' : '' }}>
                            {{ $area->name }}
                        </option>
                    @endforeach
                </select>
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
