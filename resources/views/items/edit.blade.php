@extends('layouts.app')
@section('title', 'আইটেম সম্পাদনা')
@section('page-title', 'আইটেম সম্পাদনা')

@section('content')
@php
    $qty     = $item->stock?->quantity ?? 0;
    $minQty  = $item->stock?->min_quantity ?? 5;
    $isLow   = $qty > 0 && $qty <= $minQty;
    $isOut   = $qty <= 0;
    $isNeg   = $qty < 0;
    $stockColor = $isNeg ? '#dc2626' : ($isOut ? '#dc2626' : ($isLow ? '#d97706' : '#16a34a'));
    $stockBg    = $isNeg ? '#fee2e2' : ($isOut ? '#fee2e2' : ($isLow ? '#fef9c3' : '#dcfce7'));
    $stockLabel = $isNeg ? 'ঋণাত্মক স্টক' : ($isOut ? 'স্টক শেষ' : ($isLow ? 'কম স্টক' : 'পর্যাপ্ত স্টক'));
@endphp
<div style="display:flex;align-items:center;gap:16px;padding:14px 20px;background:{{ $stockBg }};border:1px solid {{ $stockColor }}22;border-radius:10px;margin-bottom:18px">
    <div style="font-size:2rem;line-height:1">📦</div>
    <div>
        <div style="font-size:.75rem;font-weight:600;color:{{ $stockColor }};text-transform:uppercase;letter-spacing:.04em">বর্তমান স্টক</div>
        <div style="font-size:1.6rem;font-weight:800;color:{{ $stockColor }};line-height:1.1">
            {{ number_format($qty, 0) }} <span style="font-size:.9rem;font-weight:500">{{ $item->unit ?: 'বস্তা' }}</span>
        </div>
        <div style="font-size:.75rem;color:{{ $stockColor }};opacity:.8;margin-top:2px">{{ $stockLabel }} · সর্বনিম্ন: {{ $minQty }} {{ $item->unit ?: 'বস্তা' }}</div>
    </div>
    <div style="margin-left:auto">
        <a href="{{ route('stock.index') }}" class="btn btn-ghost" style="font-size:.8rem">স্টক তালিকা</a>
    </div>
</div>

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
                <label>ব্র্যান্ড <span style="font-weight:400;color:#94a3b8">(ঐচ্ছিক)</span></label>
                <select name="brand_id" class="form-select">
                    <option value="">ব্র্যান্ড নির্বাচন করুন</option>
                    @foreach($brands as $b)
                        <option value="{{ $b->id }}" @selected(old('brand_id', $item->brand_id) == $b->id)>{{ $b->name }}</option>
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
