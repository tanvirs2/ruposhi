@extends('layouts.super')
@section('title', $shop->name)
@section('page-title', $shop->name . ' — বিস্তারিত')

@section('content')

<div style="display:flex;gap:10px;margin-bottom:20px">
    <a href="{{ route('super.shops.index') }}" class="sa-btn sa-btn-ghost sa-btn-sm">
        <i class="fas fa-arrow-left"></i> ফিরে যান
    </a>
    <a href="{{ route('super.shops.edit', $shop) }}" class="sa-btn sa-btn-primary sa-btn-sm">
        <i class="fas fa-pen"></i> সম্পাদনা
    </a>
</div>

{{-- Stats --}}
<div class="sa-stat-grid">
    <div class="sa-stat">
        <div class="sa-stat-icon" style="background:linear-gradient(135deg,#f59e0b,#ef4444)">
            <i class="fas fa-money-bill-wave"></i>
        </div>
        <div>
            <div class="sa-stat-val" style="font-size:1.3rem">৳ {{ number_format($totalSales, 0) }}</div>
            <div class="sa-stat-lbl">মোট বিক্রয়</div>
        </div>
    </div>
    <div class="sa-stat">
        <div class="sa-stat-icon" style="background:linear-gradient(135deg,#3b82f6,#6366f1)">
            <i class="fas fa-users"></i>
        </div>
        <div>
            <div class="sa-stat-val">{{ $totalCustomers }}</div>
            <div class="sa-stat-lbl">গ্রাহক</div>
        </div>
    </div>
    <div class="sa-stat">
        <div class="sa-stat-icon" style="background:linear-gradient(135deg,#10b981,#059669)">
            <i class="fas fa-box"></i>
        </div>
        <div>
            <div class="sa-stat-val">{{ $totalItems }}</div>
            <div class="sa-stat-lbl">পণ্য</div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
    {{-- Shop info --}}
    <div class="sa-card">
        <h2 style="font-size:1rem;font-weight:700;margin:0 0 16px;color:#f1f5f9">
            <i class="fas fa-circle-info" style="color:#f59e0b"></i> শপ তথ্য
        </h2>
        <table class="sa-table">
            <tbody>
                <tr><td style="color:#94a3b8">নাম</td><td>{{ $shop->name }}</td></tr>
                <tr><td style="color:#94a3b8">ঠিকানা</td><td>{{ $shop->address ?? '—' }}</td></tr>
                <tr><td style="color:#94a3b8">ফোন</td><td>{{ $shop->phone ?? '—' }}</td></tr>
                <tr><td style="color:#94a3b8">স্ট্যাটাস</td><td>
                    <span class="sa-pill {{ $shop->is_active ? 'sa-pill-on' : 'sa-pill-off' }}">
                        {{ $shop->is_active ? 'সক্রিয়' : 'নিষ্ক্রিয়' }}
                    </span>
                </td></tr>
                <tr><td style="color:#94a3b8">তৈরি</td><td>{{ $shop->created_at->format('d M Y') }}</td></tr>
            </tbody>
        </table>
    </div>

    {{-- Users --}}
    <div class="sa-card">
        <h2 style="font-size:1rem;font-weight:700;margin:0 0 16px;color:#f1f5f9">
            <i class="fas fa-users" style="color:#3b82f6"></i> ব্যবহারকারী ({{ $shop->users->count() }})
        </h2>
        @if($shop->users->isEmpty())
            <div style="color:#64748b;font-size:.86rem;padding:10px 0">কোনো ব্যবহারকারী নেই</div>
        @else
        <table class="sa-table">
            <tbody>
                @foreach($shop->users as $u)
                <tr>
                    <td>
                        <i class="fas {{ $u->role==='admin' ? 'fa-user-shield' : 'fa-user' }}"
                           style="color:{{ $u->role==='admin' ? '#3b82f6' : '#64748b' }};margin-left:6px"></i>
                        {{ $u->name }}
                        <div style="font-size:.76rem;color:#64748b">{{ $u->email }}</div>
                    </td>
                    <td>
                        <span class="sa-pill" style="background:#334155;color:#cbd5e1">
                            {{ $u->role==='admin' ? 'অ্যাডমিন' : 'স্টাফ' }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

@endsection
