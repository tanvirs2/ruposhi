@extends('layouts.app')
@section('title', 'ইউনিট টাইপ সম্পাদনা')
@section('page-title', 'ইউনিট টাইপ সম্পাদনা')

@section('content')
<div class="form-card">
    <form method="POST" action="{{ route('unit-types.update', $unitType) }}">
        @csrf @method('PUT')
        <div class="form-grid">
            <div class="form-group-field">
                <label>ইউনিটের নাম <span class="req">*</span></label>
                <input type="text" name="name" value="{{ old('name', $unitType->name) }}" required>
            </div>
            <div class="form-group-field">
                <label>সংক্ষেপ</label>
                <input type="text" name="short" value="{{ old('short', $unitType->short) }}">
            </div>
        </div>
        <div class="form-actions">
            <a href="{{ route('unit-types.index') }}" class="btn btn-ghost">বাতিল</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> আপডেট</button>
        </div>
    </form>
</div>
@endsection
