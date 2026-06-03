@extends('layouts.super')
@section('title', 'সকল শপ')
@section('page-title', 'সকল শপ')

@section('content')

<div class="sa-card">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px">
        <h2 style="font-size:1.02rem;font-weight:700;margin:0;color:#f1f5f9">
            <i class="fas fa-store" style="color:#f59e0b"></i> শপ তালিকা ({{ $shops->count() }})
        </h2>
        <a href="{{ route('super.shops.create') }}" class="sa-btn sa-btn-primary sa-btn-sm">
            <i class="fas fa-plus"></i> নতুন শপ
        </a>
    </div>

    @if($shops->isEmpty())
        <div class="sa-empty">
            <i class="fas fa-store-slash"></i>
            <div>এখনো কোনো শপ তৈরি হয়নি</div>
            <a href="{{ route('super.shops.create') }}" class="sa-btn sa-btn-primary" style="margin-top:16px">
                <i class="fas fa-plus"></i> প্রথম শপ তৈরি করুন
            </a>
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
            <tr>
                <td style="font-weight:600">
                    <i class="fas fa-store" style="color:#f59e0b;margin-left:6px"></i>
                    {{ $shop->name }}
                    @if($shop->address)
                        <div style="font-size:.76rem;color:#64748b;font-weight:400;margin-top:2px">
                            <i class="fas fa-location-dot"></i> {{ $shop->address }}
                        </div>
                    @endif
                </td>
                <td>{{ $shop->phone ?? '—' }}</td>
                <td><span class="sa-pill" style="background:#1e3a8a;color:#bfdbfe">{{ $shop->users_count }} জন</span></td>
                <td>
                    <span class="sa-pill {{ $shop->is_active ? 'sa-pill-on' : 'sa-pill-off' }}">
                        {{ $shop->is_active ? 'সক্রিয়' : 'নিষ্ক্রিয়' }}
                    </span>
                </td>
                <td>
                    <div style="display:flex;gap:6px">
                        <a href="{{ route('super.shops.show', $shop) }}" class="sa-btn sa-btn-ghost sa-btn-sm" title="বিস্তারিত">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('super.shops.edit', $shop) }}" class="sa-btn sa-btn-ghost sa-btn-sm" title="সম্পাদনা">
                            <i class="fas fa-pen"></i>
                        </a>
                        <form method="POST" action="{{ route('super.shops.destroy', $shop) }}"
                              onsubmit="return confirm('এই শপ মুছে ফেলবেন? শপের সব ডেটা থেকে যাবে কিন্তু শপ মুছে যাবে।')" style="margin:0">
                            @csrf @method('DELETE')
                            <button class="sa-btn sa-btn-danger sa-btn-sm" title="মুছুন">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

@endsection
