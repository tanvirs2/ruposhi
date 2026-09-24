@extends('layouts.app')
@section('title', 'কাস্টমার সম্পাদনা')
@section('page-title', 'কাস্টমার সম্পাদনা')

@section('content')
<div class="form-card">
    <form method="POST" action="{{ route('customers.update', $customer) }}">
        @csrf @method('PUT')
        <div class="form-grid">
            <div class="form-group-field">
                <label>প্রতিষ্ঠানের নাম <span class="req">*</span></label>
                <input type="text" name="name" value="{{ old('name', $customer->name) }}" required>
            </div>
            <div class="form-group-field">
                <label>প্রোপ্রাইটরের নাম</label>
                <input type="text" name="proprietor" value="{{ old('proprietor', $customer->proprietor) }}">
            </div>
            <div class="form-group-field">
                <label>ফোন নম্বর</label>
                <input type="text" name="phone" value="{{ old('phone', $customer->phone) }}">
            </div>
            <div class="form-group-field">
                <label>এরিয়া</label>
                @include('partials.area-combobox', [
                    'acValue' => old('area_id', $customer->area_id),
                    'acPlaceholder' => 'এরিয়া নির্বাচন করুন (খুঁজুন)',
                    'acAllLabel' => '— এরিয়া নেই —',
                ])
            </div>
            <div class="form-group-field">
                <label>ক্রেডিট লিমিট (৳)</label>
                <input type="text" inputmode="decimal" name="credit_limit" value="{{ old('credit_limit', $customer->credit_limit ? $customer->credit_limit + 0 : '') }}" placeholder="খালি = লিমিট নেই">
                <small style="color:#64748b;font-size:.75rem">বাকী এই সীমা ছাড়ালে বিক্রয়ের সময় সতর্কবার্তা দেখাবে</small>
            </div>
            @php
                // লেনদেন শুরুর পর পুরনো বাকী শুধু অ্যাডমিন বদলাতে পারেন, কারণসহ (GuardsOpeningBalance)
                $obReadonly = ($obLocked ?? false) && !auth()->user()->canManageShop();
                $obNeedsReason = ($obLocked ?? false) && !$obReadonly;
            @endphp
            <div class="form-group-field">
                <label>পুরনো বাকী (৳)</label>
                <input type="text" inputmode="decimal" name="opening_balance" id="openingBalanceInput" value="{{ old('opening_balance', $customer->opening_balance + 0) }}" placeholder="খালি = ০"
                       @if($obReadonly) readonly style="background:#f1f5f9;cursor:not-allowed" @endif>
                <div id="openingBalanceWords" style="display:none;margin-top:4px;font-size:.75rem;font-weight:600;color:#7c3aed"></div>
                @if($obReadonly)
                <small style="color:#b45309;font-size:.75rem"><i class="fas fa-lock"></i> লেনদেন শুরু হয়ে গেছে — পুরনো বাকী এখন শুধু অ্যাডমিন বদলাতে পারেন</small>
                @else
                <small style="color:#64748b;font-size:.75rem">সফটওয়্যার চালুর আগের খাতার বাকী। বদলালে মোট বাকীও সাথে সাথে ঠিক হয়ে যাবে</small>
                @endif
            </div>
            @if($obNeedsReason)
            <div class="form-group-field form-full">
                <label>পুরনো বাকী বদলানোর কারণ <small style="color:#b45309">(বদলালে বাধ্যতামূলক)</small></label>
                <input type="text" name="opening_balance_reason" value="{{ old('opening_balance_reason') }}" maxlength="500" placeholder="যেমন: খাতা থেকে তোলার সময় ভুল অঙ্ক বসেছিল">
                <small style="color:#64748b;font-size:.75rem">লেনদেন শুরু হয়ে গেছে — পরিবর্তনটা আগের/নতুন অঙ্ক, আপনার নাম আর কারণসহ লেজারে রেকর্ড থাকবে</small>
            </div>
            @endif
            <div class="form-group-field form-full">
                <label>ঠিকানা</label>
                <textarea name="address" rows="2">{{ old('address', $customer->address) }}</textarea>
            </div>
        </div>
        <div class="form-actions">
            <a href="{{ route('customers.index') }}" class="btn btn-ghost">বাতিল</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> আপডেট</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('turbo:load', () => bnWatchTakaWords('openingBalanceInput', 'openingBalanceWords'), { once: true });
</script>
@endpush
