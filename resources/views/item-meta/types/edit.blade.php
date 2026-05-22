@extends('layouts.app')
@section('title', 'আইটেম টাইপ সম্পাদনা')
@section('page-title', 'আইটেম টাইপ সম্পাদনা')

@section('content')
<div class="form-card">
    <form method="POST" action="{{ route('item-types.update', $itemType) }}">
        @csrf @method('PUT')
        <div class="form-grid">
            <div class="form-group-field">
                <label>টাইপের নাম <span class="req">*</span></label>
                <input type="text" name="name" value="{{ old('name', $itemType->name) }}" required>
            </div>
        </div>
        <div class="form-actions">
            <a href="{{ route('item-types.index') }}" class="btn btn-ghost">বাতিল</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> আপডেট</button>
        </div>
    </form>
</div>
@endsection
