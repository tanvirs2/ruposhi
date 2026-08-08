@extends('layouts.app')
@section('title', 'নতুন ব্র্যান্ড')
@section('page-title', 'নতুন ব্র্যান্ড')

@section('content')
<div class="form-card">
    <form method="POST" action="{{ route('brands.store') }}">
        @csrf
        <div class="form-grid">
            <div class="form-group-field">
                <label>নাম <span class="req">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required placeholder="যেমন: আকিজ, সিটি, ফ্রেশ">
            </div>
            <div class="form-group-field">
                <label>বিবরণ</label>
                <input type="text" name="description" value="{{ old('description') }}">
            </div>
        </div>
        <div class="form-actions">
            <a href="{{ route('brands.index') }}" class="btn btn-ghost">বাতিল</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> সংরক্ষণ</button>
        </div>
    </form>
</div>
@endsection
