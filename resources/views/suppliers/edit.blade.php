@extends('layouts.app')
@section('title', 'সরবরাহকারী সম্পাদনা')
@section('page-title', 'সরবরাহকারী সম্পাদনা')

@section('content')
<div class="form-card">
    <form method="POST" action="{{ route('suppliers.update', $supplier) }}">
        @csrf @method('PUT')
        <div class="form-grid">
            <div class="form-group-field">
                <label>নাম <span class="req">*</span></label>
                <input type="text" name="name" value="{{ old('name', $supplier->name) }}" required>
            </div>
            <div class="form-group-field">
                <label>প্রোপ্রাইটর (মালিকের নাম)</label>
                <input type="text" name="proprietor" value="{{ old('proprietor', $supplier->proprietor) }}" placeholder="মোঃ হুমায়ন মোল্লা">
            </div>
            <div class="form-group-field">
                <label>ফোন নম্বর</label>
                <input type="text" name="phone" value="{{ old('phone', $supplier->phone) }}">
            </div>
            <div class="form-group-field">
                <label>ইমেইল</label>
                <input type="email" name="email" value="{{ old('email', $supplier->email) }}">
            </div>
            @php
                // লেনদেন শুরুর পর পুরনো দেনা শুধু অ্যাডমিন বদলাতে পারেন, কারণসহ (GuardsOpeningBalance)
                $obReadonly = ($obLocked ?? false) && !auth()->user()->canManageShop();
                $obNeedsReason = ($obLocked ?? false) && !$obReadonly;
            @endphp
            <div class="form-group-field">
                <label>পুরনো দেনা (৳)</label>
                <input type="text" inputmode="decimal" name="opening_balance" id="openingBalanceInput" value="{{ old('opening_balance', $supplier->opening_balance + 0) }}" placeholder="খালি = ০"
                       @if($obReadonly) readonly style="background:#f1f5f9;cursor:not-allowed" @endif>
                <div id="openingBalanceWords" style="display:none;margin-top:4px;font-size:.75rem;font-weight:600;color:#7c3aed"></div>
                @if($obReadonly)
                <small style="color:#b45309;font-size:.75rem"><i class="fas fa-lock"></i> লেনদেন শুরু হয়ে গেছে — পুরনো দেনা এখন শুধু অ্যাডমিন বদলাতে পারেন</small>
                @else
                <small style="color:#64748b;font-size:.75rem">সফটওয়্যার চালুর আগের খাতার দেনা। বদলালে মোট দেনাও সাথে সাথে ঠিক হয়ে যাবে</small>
                @endif
            </div>
            @if($obNeedsReason)
            <div class="form-group-field form-full">
                <label>পুরনো দেনা বদলানোর কারণ <small style="color:#b45309">(বদলালে বাধ্যতামূলক)</small></label>
                <input type="text" name="opening_balance_reason" value="{{ old('opening_balance_reason') }}" maxlength="500" placeholder="যেমন: খাতা থেকে তোলার সময় ভুল অঙ্ক বসেছিল">
                <small style="color:#64748b;font-size:.75rem">লেনদেন শুরু হয়ে গেছে — পরিবর্তনটা আগের/নতুন অঙ্ক, আপনার নাম আর কারণসহ লেজারে রেকর্ড থাকবে</small>
            </div>
            @endif
            <div class="form-group-field form-full">
                <label>ঠিকানা</label>
                <textarea name="address" rows="3">{{ old('address', $supplier->address) }}</textarea>
            </div>
        </div>
        <div class="form-actions">
            <a href="{{ route('suppliers.index') }}" class="btn btn-ghost">বাতিল</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> আপডেট</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('turbo:load', () => bnWatchTakaWords('openingBalanceInput', 'openingBalanceWords'));
</script>
@endpush
