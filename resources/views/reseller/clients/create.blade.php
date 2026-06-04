@extends('layouts.reseller')
@section('title', 'নতুন ক্লায়েন্ট')
@section('page-title', 'নতুন ক্লায়েন্ট যোগ করুন')

@section('content')
<div class="rt-card" style="max-width:580px">
    <div class="rt-card-title"><i class="fas fa-user-plus"></i> নতুন সুপার অ্যাডমিন তৈরি করুন</div>

    @if($errors->any())
        <div class="rt-alert rt-alert-error">@foreach($errors->all() as $e){{ $e }}<br>@endforeach</div>
    @endif

    <form method="POST" action="{{ route('reseller.clients.store') }}">
        @csrf
        <div class="rt-grid-2">
            <div class="rt-field">
                <label class="rt-label">নাম *</label>
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
                <input class="rt-input" name="shop_name" value="{{ old('shop_name') }}" required>
            </div>
        </div>
        <div class="rt-field">
            <label class="rt-label">প্ল্যান *</label>
            <select class="rt-select" name="plan">
                @foreach($plans as $key => $label)
                    <option value="{{ $key }}" {{ old('plan') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div style="display:flex;gap:10px">
            <button type="submit" class="rt-btn rt-btn-primary"><i class="fas fa-check"></i> তৈরি করুন</button>
            <a href="{{ route('reseller.clients.index') }}" class="rt-btn rt-btn-ghost"><i class="fas fa-arrow-left"></i> বাতিল</a>
        </div>
    </form>
</div>
@endsection
