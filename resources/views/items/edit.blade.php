@extends('layouts.app')
@section('title', 'আইটেম সম্পাদনা')
@section('page-title', 'আইটেম সম্পাদনা')

@section('content')
<div class="form-card">
    <form method="POST" action="{{ route('items.update', $item) }}">
        @csrf @method('PUT')
        <div class="form-grid">
            <div class="form-group-field form-full">
                <label>আইটেমের নাম <span class="req">*</span></label>
                <input type="text" name="name" value="{{ old('name', $item->name) }}" required>
            </div>

            <div class="form-group-field">
                <label>ক্যাটাগরি</label>
                <select name="category_id" class="form-select">
                    <option value="">ক্যাটাগরি নির্বাচন করুন</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected(old('category_id', $item->category_id) == $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group-field">
                <label>কোড (SKU)</label>
                <input type="text" name="code" value="{{ old('code', $item->code) }}">
            </div>

            <div class="form-group-field">
                <label>ক্রয় মূল্য <span class="req">*</span>
                    <button type="button" class="info-btn" data-info="প্রতি বস্তা/ইউনিটের ক্রয় মূল্য। লাভ হিসাবে ব্যবহার হয়।">i</button>
                </label>
                <input type="text" inputmode="decimal" name="purchase_price" value="{{ old('purchase_price', $item->purchase_price) }}" required>
            </div>

            <div class="form-group-field">
                <label>বিক্রয় মূল্য
                    <button type="button" class="info-btn" data-info="ডিফল্ট বিক্রয় মূল্য। বিক্রয়ের সময় পরিবর্তন করা যাবে।">i</button>
                </label>
                <input type="text" inputmode="decimal" name="sale_price" value="{{ old('sale_price', $item->sale_price) }}">
            </div>

            <div class="form-group-field">
                <label>ইউনিট</label>
                <input type="text" name="unit" value="{{ old('unit', $item->unit) }}" placeholder="বস্তা">
            </div>

            <div class="form-group-field">
                <label>সর্বনিম্ন স্টক
                    <button type="button" class="info-btn" data-info="স্টক এই পরিমাণের নিচে নামলে সতর্কতা দেখাবে।">i</button>
                </label>
                <input type="text" inputmode="decimal" name="min_quantity" value="{{ old('min_quantity', $item->stock?->min_quantity ?? 5) }}">
            </div>
        </div>
        <div class="form-actions">
            <a href="{{ route('items.index') }}" class="btn btn-ghost">বাতিল</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> আপডেট</button>
        </div>
    </form>
</div>
@endsection
