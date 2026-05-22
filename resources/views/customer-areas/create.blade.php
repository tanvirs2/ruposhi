@extends('layouts.app')
@section('title', 'নতুন এরিয়া')
@section('page-title', 'নতুন এরিয়া')

@section('content')
<div class="form-card">
    <form method="POST" action="{{ route('customer-areas.store') }}">
        @csrf
        <div class="form-grid">
            <div class="form-group-field form-full">
                <label>এরিয়ার নাম <span class="req">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required placeholder="যেমন: মিরপুর, গাজীপুর, নারায়ণগঞ্জ">
            </div>
        </div>
        <div class="form-actions">
            <a href="{{ route('customer-areas.index') }}" class="btn btn-ghost">বাতিল</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> সংরক্ষণ</button>
        </div>
    </form>
</div>
@endsection
