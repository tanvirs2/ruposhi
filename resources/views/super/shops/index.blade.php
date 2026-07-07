@extends('layouts.super')
@section('title', 'সকল শপ')
@section('page-title', 'সকল শপ')

@section('content')

@php
    $license   = auth()->user()->activeLicense();
    $shopCount = $shops->count();
    $canAdd    = $license && $license->canAddShops($shopCount);
@endphp

<div class="sa-card">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px">
        <h2 style="font-size:1.02rem;font-weight:700;margin:0;color:#f1f5f9">
            <i class="fas fa-store" style="color:#f59e0b"></i> আমার শাখা
            <span style="font-size:.78rem;color:#64748b;font-weight:400;margin-right:6px">
                {{ $shopCount }}{{ $license ? '/'.($license->max_shops ?? '∞') : '' }} শাখা
            </span>
        </h2>
        @if($canAdd)
            <a href="{{ route('super.shops.create') }}" class="sa-btn sa-btn-primary sa-btn-sm">
                <i class="fas fa-plus"></i> নতুন শাখা
            </a>
        @else
            <span title="লাইসেন্স সীমা পূর্ণ — নতুন শাখার জন্য লাইসেন্স আপগ্রেড করুন"
                  style="display:inline-flex;align-items:center;gap:6px;padding:6px 11px;border-radius:8px;
                         background:#1e293b;color:#475569;font-size:.78rem;font-weight:600;cursor:not-allowed">
                <i class="fas fa-lock"></i> শাখা সীমা পূর্ণ
            </span>
        @endif
    </div>

    @if($shops->isEmpty())
        <div class="sa-empty">
            <i class="fas fa-store-slash"></i>
            <div>এখনো কোনো শাখা তৈরি হয়নি</div>
            @if($canAdd)
                <a href="{{ route('super.shops.create') }}" class="sa-btn sa-btn-primary" style="margin-top:16px">
                    <i class="fas fa-plus"></i> প্রথম শাখা তৈরি করুন
                </a>
            @endif
        </div>
    @else
    <table class="sa-table">
        <thead>
            <tr>
                <th>শপের নাম</th>
                <th>ফোন</th>
                <th>ব্যবহারকারী</th>
                <th>স্ট্যাটাস</th>
                <th>অ্যাকশন</th>
            </tr>
        </thead>
        <tbody>
            @foreach($shops as $shop)
            <tr style="{{ $shop->is_locked ? 'opacity:.6' : '' }}">
                <td style="font-weight:600">
                    @if($shop->is_locked)
                        <i class="fas fa-lock" style="color:#f87171;margin-left:6px"></i>
                    @else
                        <i class="fas fa-store" style="color:#f59e0b;margin-left:6px"></i>
                    @endif
                    {{ $shop->name }}
                    @if($shop->is_locked)
                        <div style="font-size:.74rem;color:#f87171;font-weight:400;margin-top:3px">
                            <i class="fas fa-triangle-exclamation"></i>
                            লাইসেন্স সীমা — ডেটা সুরক্ষিত, অ্যাক্সেস বন্ধ
                        </div>
                    @elseif($shop->address)
                        <div style="font-size:.76rem;color:#64748b;font-weight:400;margin-top:2px">
                            <i class="fas fa-location-dot"></i> {{ $shop->address }}
                        </div>
                    @endif
                </td>
                <td>{{ $shop->phone ?? '—' }}</td>
                <td>
                    @if($shop->is_locked)
                        <span class="sa-pill" style="background:#7f1d1d44;color:#fca5a5;border:1px solid #7f1d1d66">লক</span>
                    @else
                        <span class="sa-pill" style="background:#1e3a8a;color:#bfdbfe">{{ $shop->users_count }} জন</span>
                    @endif
                </td>
                <td>
                    @if($shop->is_locked)
                        <span class="sa-pill" style="background:#1e293b;color:#475569">🔒 লক</span>
                    @else
                        <span class="sa-pill {{ $shop->is_active ? 'sa-pill-on' : 'sa-pill-off' }}">
                            {{ $shop->is_active ? 'সক্রিয়' : 'নিষ্ক্রিয়' }}
                        </span>
                    @endif
                </td>
                <td>
                    <div style="display:flex;gap:6px">
                        @if($shop->is_locked)
                            {{-- Locked: show upgrade prompt, no entry --}}
                            <span class="sa-btn sa-btn-sm"
                                  style="background:#7f1d1d44;color:#fca5a5;border:1px solid #7f1d1d66;cursor:not-allowed"
                                  title="লাইসেন্স আপগ্রেড করলে অ্যাক্সেস ফিরে পাবেন">
                                <i class="fas fa-lock"></i> লক
                            </span>
                        @else
                            <form method="POST" action="{{ route('super.shops.enter', $shop) }}" style="margin:0">
                                @csrf
                                <button class="sa-btn sa-btn-primary sa-btn-sm" title="এই শাখায় প্রবেশ করুন">
                                    <i class="fas fa-right-to-bracket"></i> প্রবেশ
                                </button>
                            </form>
                            <a href="{{ route('super.shops.show', $shop) }}" class="sa-btn sa-btn-ghost sa-btn-sm" title="বিস্তারিত">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('super.shops.edit', $shop) }}" class="sa-btn sa-btn-ghost sa-btn-sm" title="সম্পাদনা">
                                <i class="fas fa-pen"></i>
                            </a>
                        @endif
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

@endsection
