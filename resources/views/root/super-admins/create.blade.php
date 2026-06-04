@extends('layouts.root')
@section('title', 'নতুন সুপার অ্যাডমিন')
@section('page-title', 'নতুন সুপার অ্যাডমিন যোগ করুন')

@section('content')
<div class="rt-card" style="max-width:620px">
    <div class="rt-card-title"><i class="fas fa-user-plus"></i> সুপার অ্যাডমিন তৈরি করুন</div>

    @if($errors->any())
        <div class="rt-alert rt-alert-error">
            @foreach($errors->all() as $e) {{ $e }}<br> @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('root.super-admins.store') }}">
        @csrf

        <div class="rt-grid-2">
            <div class="rt-field">
                <label class="rt-label">পূর্ণ নাম *</label>
                <input class="rt-input" name="name" value="{{ old('name') }}" required>
            </div>
            <div class="rt-field">
                <label class="rt-label">ইমেইল *</label>
                <input class="rt-input" type="email" name="email" value="{{ old('email') }}" required>
            </div>
        </div>

        <div class="rt-grid-2">
            <div class="rt-field">
                <label class="rt-label">পাসওয়ার্ড *</label>
                <input class="rt-input" type="password" name="password" required>
            </div>
            <div class="rt-field">
                <label class="rt-label">শপের নাম *</label>
                <input class="rt-input" name="shop_name" value="{{ old('shop_name') }}" required placeholder="প্রথম শাখার নাম">
            </div>
        </div>

        <div class="rt-grid-2">
            <div class="rt-field">
                <label class="rt-label">সাবস্ক্রিপশন প্ল্যান *</label>
                <select class="rt-select" name="plan" id="plan" onchange="toggleCustomDays()">
                    @foreach($plans as $key => $label)
                        <option value="{{ $key }}" {{ old('plan') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="rt-field" id="customDaysField" style="display:none">
                <label class="rt-label">কাস্টম দিন *</label>
                <input class="rt-input" type="number" name="custom_days" value="{{ old('custom_days') }}" min="1" placeholder="যেমন: 45">
            </div>
        </div>

        <div class="rt-field">
            <label class="rt-label">সর্বোচ্চ শাখা সংখ্যা</label>
            <input class="rt-input" type="number" name="max_shops" value="{{ old('max_shops', 1) }}" min="1"
                   placeholder="খালি রাখলে ১টি (basic)">
            <small style="color:#64748b;font-size:.77rem">
                ১ = শুধু প্রথম শাখা (basic) &nbsp;|&nbsp; ২+ = একাধিক শাখা &nbsp;|&nbsp; খালি = ১টি
            </small>
        </div>

        @if($resellers->count())
        <div class="rt-field">
            <label class="rt-label">রিসেলার (ঐচ্ছিক)</label>
            <select class="rt-select" name="reseller_id">
                <option value="">— সরাসরি (কোনো রিসেলার নেই) —</option>
                @foreach($resellers as $r)
                    <option value="{{ $r->id }}" {{ old('reseller_id') == $r->id ? 'selected' : '' }}>
                        {{ $r->name }} ({{ $r->email }})
                    </option>
                @endforeach
            </select>
        </div>
        @endif

        <div class="rt-field">
            <label class="rt-label">নোট (ঐচ্ছিক)</label>
            <textarea class="rt-textarea" name="notes" rows="2">{{ old('notes') }}</textarea>
        </div>

        <div style="display:flex;gap:10px;margin-top:4px">
            <button type="submit" class="rt-btn rt-btn-primary">
                <i class="fas fa-check"></i> তৈরি করুন
            </button>
            <a href="{{ route('root.super-admins.index') }}" class="rt-btn rt-btn-ghost">
                <i class="fas fa-arrow-left"></i> বাতিল
            </a>
        </div>
    </form>
</div>

@push('scripts')
<script>
function toggleCustomDays() {
    const plan = document.getElementById('plan').value;
    document.getElementById('customDaysField').style.display = plan === 'custom' ? 'block' : 'none';
}
toggleCustomDays();
</script>
@endpush
@endsection
