@extends('layouts.app')
@section('title', 'নতুন ইউনিট টাইপ')
@section('page-title', 'নতুন ইউনিট টাইপ')

@section('content')
<div class="form-card">
    <form method="POST" action="{{ route('unit-types.store') }}">
        @csrf
        <div class="form-grid">
            <div class="form-group-field">
                <label>ইউনিটের নাম <span class="req">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required placeholder="যেমন: বস্তা, কেজি, পিস, লিটার">
            </div>
            <div class="form-group-field">
                <label>সংক্ষেপ</label>
                <input type="text" name="short" value="{{ old('short') }}" placeholder="যেমন: bag, kg, pcs, ltr">
            </div>
        </div>
        <div class="form-actions">
            <a href="{{ route('unit-types.index') }}" class="btn btn-ghost">বাতিল</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> সংরক্ষণ</button>
        </div>
    </form>
</div>
@endsection
