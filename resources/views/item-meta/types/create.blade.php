@extends('layouts.app')
@section('title', 'নতুন আইটেম টাইপ')
@section('page-title', 'নতুন আইটেম টাইপ')

@section('content')
<div class="form-card">
    <form method="POST" action="{{ route('item-types.store') }}">
        @csrf
        <div class="form-grid">
            <div class="form-group-field">
                <label>টাইপের নাম <span class="req">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required placeholder="যেমন: মিনিকেট, নাজিরশাইল">
            </div>
        </div>
        <div class="form-actions">
            <a href="{{ route('item-types.index') }}" class="btn btn-ghost">বাতিল</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> সংরক্ষণ</button>
        </div>
    </form>
</div>
@endsection
