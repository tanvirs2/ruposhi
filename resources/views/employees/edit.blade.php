@extends('layouts.app')
@section('title', 'কর্মচারী সম্পাদনা')
@section('page-title', 'কর্মচারী সম্পাদনা')

@section('content')
<div class="form-card">
    <form method="POST" action="{{ route('employees.update', $employee) }}">
        @csrf @method('PUT')
        <div class="form-grid">
            <div class="form-group-field">
                <label>নাম <span class="req">*</span></label>
                <input type="text" name="name" value="{{ old('name', $employee->name) }}" required>
            </div>
            <div class="form-group-field">
                <label>পদবি</label>
                <input type="text" name="position" value="{{ old('position', $employee->position) }}">
            </div>
            <div class="form-group-field">
                <label>ফোন</label>
                <input type="text" name="phone" value="{{ old('phone', $employee->phone) }}">
            </div>
            <div class="form-group-field">
                <label>ইমেইল</label>
                <input type="email" name="email" value="{{ old('email', $employee->email) }}">
            </div>
            <div class="form-group-field">
                <label>বেতন (৳)
                    <button type="button" class="info-btn" data-info="কর্মচারীর মাসিক বেতন। এটি কর্মচারীর তথ্যে সংরক্ষিত থাকে, সরাসরি বেতন পরিশোধ হয় না।">i</button>
                </label>
                <input type="text" inputmode="decimal" name="salary" value="{{ old('salary', $employee->salary) }}">
            </div>
            <div class="form-group-field">
                <label>যোগদানের তারিখ</label>
                <input type="date" name="join_date" value="{{ old('join_date', $employee->join_date?->toDateString()) }}">
            </div>
            <div class="form-group-field">
                <label>অবস্থা</label>
                <select name="status" class="form-select">
                    <option value="active"   @selected(old('status',$employee->status)==='active')>সক্রিয়</option>
                    <option value="inactive" @selected(old('status',$employee->status)==='inactive')>নিষ্ক্রিয়</option>
                </select>
            </div>
            <div class="form-group-field form-full">
                <label>ঠিকানা</label>
                <textarea name="address" rows="3">{{ old('address', $employee->address) }}</textarea>
            </div>
        </div>
        <div class="form-actions">
            <a href="{{ route('employees.index') }}" class="btn btn-ghost">বাতিল</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> আপডেট</button>
        </div>
    </form>
</div>
@endsection
