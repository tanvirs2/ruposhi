@extends('layouts.app')
@section('title', 'নতুন কর্মচারী')
@section('page-title', 'নতুন কর্মচারী')

@section('content')
<div class="form-card">
    <form method="POST" action="{{ route('employees.store') }}">
        @csrf
        <div class="form-grid">
            <div class="form-group-field">
                <label>নাম <span class="req">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required>
            </div>
            <div class="form-group-field">
                <label>পদবি</label>
                <input type="text" name="position" value="{{ old('position') }}">
            </div>
            <div class="form-group-field">
                <label>ফোন</label>
                <input type="text" name="phone" value="{{ old('phone') }}">
            </div>
            <div class="form-group-field">
                <label>ইমেইল</label>
                <input type="email" name="email" value="{{ old('email') }}">
            </div>
            <div class="form-group-field">
                <label>বেতন (৳)
                    <button type="button" class="info-btn" data-info="কর্মচারীর মাসিক বেতন। এটি কর্মচারীর তথ্যে সংরক্ষিত থাকে, সরাসরি বেতন পরিশোধ হয় না।">i</button>
                </label>
                <input type="text" inputmode="decimal" name="salary" value="{{ old('salary', 0) }}">
            </div>
            <div class="form-group-field">
                <label>যোগদানের তারিখ</label>
                <input type="date" name="join_date" value="{{ old('join_date') }}">
            </div>
            <div class="form-group-field">
                <label>অবস্থা</label>
                <select name="status" class="form-select">
                    <option value="active">সক্রিয়</option>
                    <option value="inactive">নিষ্ক্রিয়</option>
                </select>
            </div>
            <div class="form-group-field form-full">
                <label>ঠিকানা</label>
                <textarea name="address" rows="3">{{ old('address') }}</textarea>
            </div>
        </div>
        <div class="form-actions">
            <a href="{{ route('employees.index') }}" class="btn btn-ghost">বাতিল</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> সংরক্ষণ</button>
        </div>
    </form>
</div>
@endsection
