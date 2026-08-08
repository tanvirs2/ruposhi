@extends('layouts.app')
@section('title', 'ব্র্যান্ড সম্পাদনা')
@section('page-title', 'ব্র্যান্ড সম্পাদনা')

@section('content')
<div class="form-card">
    <form method="POST" action="{{ route('brands.update', $brand) }}">
        @csrf @method('PUT')
        <div class="form-grid">
            <div class="form-group-field">
                <label>নাম <span class="req">*</span></label>
                <input type="text" name="name" value="{{ old('name', $brand->name) }}" required>
            </div>
            <div class="form-group-field">
                <label>বিবরণ</label>
                <input type="text" name="description" value="{{ old('description', $brand->description) }}">
            </div>
        </div>
        <div class="form-actions">
            <a href="{{ route('brands.index') }}" class="btn btn-ghost">বাতিল</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> আপডেট</button>
        </div>
    </form>
</div>
@endsection
