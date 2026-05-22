@extends('layouts.app')
@section('title', 'নতুন ক্যাটাগরি')
@section('page-title', 'নতুন ক্যাটাগরি')

@section('content')
<div class="form-card">
    <form method="POST" action="{{ route('categories.store') }}">
        @csrf
        <div class="form-grid">
            <div class="form-group-field">
                <label>নাম <span class="req">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required placeholder="যেমন: চাল, ডাল, তেল">
            </div>
            <div class="form-group-field">
                <label>বিবরণ</label>
                <input type="text" name="description" value="{{ old('description') }}">
            </div>
        </div>
        <div class="form-actions">
            <a href="{{ route('categories.index') }}" class="btn btn-ghost">বাতিল</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> সংরক্ষণ</button>
        </div>
    </form>
</div>
@endsection
