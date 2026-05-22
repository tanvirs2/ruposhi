@extends('layouts.app')
@section('title', 'ক্যাটাগরি সম্পাদনা')
@section('page-title', 'ক্যাটাগরি সম্পাদনা')

@section('content')
<div class="form-card">
    <form method="POST" action="{{ route('categories.update', $category) }}">
        @csrf @method('PUT')
        <div class="form-grid">
            <div class="form-group-field">
                <label>নাম <span class="req">*</span></label>
                <input type="text" name="name" value="{{ old('name', $category->name) }}" required>
            </div>
            <div class="form-group-field">
                <label>বিবরণ</label>
                <input type="text" name="description" value="{{ old('description', $category->description) }}">
            </div>
        </div>
        <div class="form-actions">
            <a href="{{ route('categories.index') }}" class="btn btn-ghost">বাতিল</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> আপডেট</button>
        </div>
    </form>
</div>
@endsection
