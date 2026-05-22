@extends('layouts.app')
@section('title', 'নতুন ব্র্যান্ড')
@section('page-title', 'নতুন ব্র্যান্ড')

@section('content')
<div class="form-card">
    <form method="POST" action="{{ route('item-brands.store') }}">
        @csrf
        <div class="form-grid">
            <div class="form-group-field">
                <label>ব্র্যান্ডের নাম <span class="req">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required placeholder="যেমন: গোলাপ ফুল, বরিশাল">
            </div>
        </div>
        <div class="form-actions">
            <a href="{{ route('item-brands.index') }}" class="btn btn-ghost">বাতিল</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> সংরক্ষণ</button>
        </div>
    </form>
</div>
@endsection
