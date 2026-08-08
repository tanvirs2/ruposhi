@extends('layouts.app')
@section('title', 'নতুন আইটেম')
@section('page-title', 'নতুন আইটেম')

@section('content')
<div class="form-card">
    <form method="POST" action="{{ route('items.store') }}">
        @csrf
        <div class="form-grid">
            <div class="form-group-field form-full">
                <label>আইটেমের নাম <span class="req">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required placeholder="যেমন: 50kg মিনিকেট চাল">
            </div>

            <div class="form-group-field">
                <label>ক্যাটাগরি</label>
                <select name="category_id" class="form-select">
                    <option value="">ক্যাটাগরি নির্বাচন করুন</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected(old('category_id') == $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group-field">
                <label>ব্র্যান্ড <span style="font-weight:400;color:#94a3b8">(ঐচ্ছিক)</span></label>
                <select name="brand_id" class="form-select">
                    <option value="">ব্র্যান্ড নির্বাচন করুন</option>
                    @foreach($brands as $b)
                        <option value="{{ $b->id }}" @selected(old('brand_id') == $b->id)>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group-field">
                <label>কোড (SKU)</label>
                <input type="text" name="code" value="{{ old('code') }}" placeholder="ঐচ্ছিক">
            </div>

            <div class="form-group-field">
                <label>ক্রয় মূল্য <span class="req">*</span>
                    <button type="button" class="info-btn" data-info="প্রতি বস্তা/ইউনিটের ক্রয় মূল্য। এটি লাভ হিসাব করতে ব্যবহার হয়।">i</button>
                </label>
                <input type="text" inputmode="decimal" name="purchase_price" value="{{ old('purchase_price', 0) }}" required>
            </div>

            <div class="form-group-field">
                <label>বিক্রয় মূল্য
                    <button type="button" class="info-btn" data-info="প্রতি বস্তা/ইউনিটের ডিফল্ট বিক্রয় মূল্য। বিক্রয়ের সময় পরিবর্তন করা যাবে।">i</button>
                </label>
                <input type="text" inputmode="decimal" name="sale_price" value="{{ old('sale_price', 0) }}">
            </div>

            <div class="form-group-field">
                <label>ইউনিট
                    <button type="button" class="info-btn" data-info="যেমন: বস্তা, কেজি, পিস">i</button>
                </label>
                <input type="text" name="unit" value="{{ old('unit', 'বস্তা') }}" placeholder="বস্তা">
            </div>

            <div class="form-group-field">
                <label>সর্বনিম্ন স্টক (সতর্কতার জন্য)
                    <button type="button" class="info-btn" data-info="স্টক এই পরিমাণের নিচে নামলে সতর্কতা দেখাবে।">i</button>
                </label>
                <input type="text" inputmode="decimal" name="min_quantity" value="{{ old('min_quantity', 5) }}">
            </div>
        </div>
        <div class="form-actions">
            <a href="{{ route('items.index') }}" class="btn btn-ghost">বাতিল</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> সংরক্ষণ</button>
        </div>
    </form>
</div>
@endsection
