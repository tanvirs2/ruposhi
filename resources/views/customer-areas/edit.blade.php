@extends('layouts.app')
@section('title', 'এরিয়া সম্পাদনা')
@section('page-title', 'এরিয়া সম্পাদনা')

@section('content')
<div class="form-card">
    <form method="POST" action="{{ route('customer-areas.update', $customerArea) }}">
        @csrf @method('PUT')
        <div class="form-grid">
            <div class="form-group-field form-full">
                <label>এরিয়ার নাম <span class="req">*</span></label>
                <input type="text" name="name" value="{{ old('name', $customerArea->name) }}" required>
            </div>
        </div>
        <div class="form-actions">
            <a href="{{ route('customer-areas.index') }}" class="btn btn-ghost">বাতিল</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> আপডেট</button>
        </div>
    </form>
</div>
@endsection
