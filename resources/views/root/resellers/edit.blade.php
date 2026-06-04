@extends('layouts.root')
@section('title', 'রিসেলার সম্পাদনা')
@section('page-title', 'রিসেলার সম্পাদনা — ' . $reseller->name)

@section('content')
<div class="rt-card" style="max-width:580px">
    <div class="rt-card-title"><i class="fas fa-pen"></i> {{ $reseller->name }}</div>

    @if($errors->any())
        <div class="rt-alert rt-alert-error">
            @foreach($errors->all() as $e) {{ $e }}<br> @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('root.resellers.update', $reseller) }}">
        @csrf @method('PUT')

        <div class="rt-grid-2">
            <div class="rt-field">
                <label class="rt-label">নাম *</label>
                <input class="rt-input" name="name" value="{{ old('name', $reseller->name) }}" required>
            </div>
            <div class="rt-field">
                <label class="rt-label">ইমেইল *</label>
                <input class="rt-input" type="email" name="email" value="{{ old('email', $reseller->email) }}" required>
            </div>
        </div>

        <div class="rt-field">
            <label class="rt-label">নতুন পাসওয়ার্ড (ফাঁকা রাখলে পরিবর্তন হবে না)</label>
            <input class="rt-input" type="password" name="password">
        </div>

        <div class="rt-grid-2">
            <div class="rt-field">
                <label class="rt-label">সর্বোচ্চ শপ (ফাঁকা = সীমাহীন)</label>
                <input class="rt-input" type="number" name="max_shops" value="{{ old('max_shops', $profile->max_shops) }}" min="1">
            </div>
            <div class="rt-field">
                <label class="rt-label">কমিশন রেট (%)</label>
                <input class="rt-input" type="number" name="commission_rate" value="{{ old('commission_rate', $profile->commission_rate) }}" min="0" max="100" step="0.01">
            </div>
        </div>

        <div class="rt-field">
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:.9rem">
                <input type="checkbox" name="can_extend_license" value="1" {{ old('can_extend_license', $profile->can_extend_license) ? 'checked' : '' }}>
                লাইসেন্স নিজে নবায়ন করতে পারবে
            </label>
        </div>

        <div class="rt-field">
            <label class="rt-label">নোট</label>
            <textarea class="rt-textarea" name="notes" rows="2">{{ old('notes', $profile->notes) }}</textarea>
        </div>

        <div style="display:flex;gap:10px">
            <button type="submit" class="rt-btn rt-btn-primary"><i class="fas fa-check"></i> সংরক্ষণ</button>
            <a href="{{ route('root.resellers.index') }}" class="rt-btn rt-btn-ghost"><i class="fas fa-arrow-left"></i> বাতিল</a>
        </div>
    </form>
</div>
@endsection
