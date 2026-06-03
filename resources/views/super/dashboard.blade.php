@extends('layouts.super')
@section('title', 'ড্যাশবোর্ড')
@section('page-title', 'সুপার অ্যাডমিন ড্যাশবোর্ড')

@section('content')

{{-- Stat cards --}}
<div class="sa-stat-grid">
    <div class="sa-stat">
        <div class="sa-stat-icon" style="background:linear-gradient(135deg,#f59e0b,#ef4444)">
            <i class="fas fa-store"></i>
        </div>
        <div>
            <div class="sa-stat-val">{{ $totalShops }}</div>
            <div class="sa-stat-lbl">মোট শপ</div>
        </div>
    </div>
    <div class="sa-stat">
        <div class="sa-stat-icon" style="background:linear-gradient(135deg,#10b981,#059669)">
            <i class="fas fa-circle-check"></i>
        </div>
        <div>
            <div class="sa-stat-val">{{ $shops->where('is_active', true)->count() }}</div>
            <div class="sa-stat-lbl">সক্রিয় শপ</div>
        </div>
    </div>
    <div class="sa-stat">
        <div class="sa-stat-icon" style="background:linear-gradient(135deg,#3b82f6,#6366f1)">
            <i class="fas fa-users"></i>
        </div>
        <div>
            <div class="sa-stat-val">{{ $totalUsers }}</div>
            <div class="sa-stat-lbl">মোট ব্যবহারকারী</div>
        </div>
    </div>
    <div class="sa-stat">
        <div class="sa-stat-icon" style="background:linear-gradient(135deg,#fbbf24,#f59e0b)">
            <i class="fas fa-money-bill-wave"></i>
        </div>
        <div>
            <div class="sa-stat-val" style="font-size:1.35rem">৳ {{ number_format($monthRevenue,0) }}</div>
            <div class="sa-stat-lbl">এই মাসের বিক্রয়</div>
        </div>
    </div>
    <div class="sa-stat">
        <div class="sa-stat-icon" style="background:linear-gradient(135deg,#ef4444,#b91c1c)">
            <i class="fas fa-file-invoice-dollar"></i>
        </div>
        <div>
            <div class="sa-stat-val" style="font-size:1.35rem">৳ {{ number_format($totalCustomerDue,0) }}</div>
            <div class="sa-stat-lbl">মোট কাস্টমার বাকী</div>
        </div>
    </div>
</div>

{{-- Per-shop sales summary --}}
<div class="sa-card">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px">
        <h2 style="font-size:1.02rem;font-weight:700;margin:0;color:#f1f5f9">
            <i class="fas fa-chart-column" style="color:#f59e0b"></i> শপ অনুযায়ী বিক্রয়
        </h2>
        <a href="{{ route('super.shops.create') }}" class="sa-btn sa-btn-primary sa-btn-sm">
            <i class="fas fa-plus"></i> নতুন শপ
        </a>
    </div>

    @if($shopSales->isEmpty())
        <div class="sa-empty">
            <i class="fas fa-store-slash"></i>
            <div>এখনো কোনো শপ তৈরি হয়নি</div>
        </div>
    @else
    <table class="sa-table">
        <thead>
            <tr>
                <th>শপ</th>
                <th>স্ট্যাটাস</th>
                <th>বিক্রয় সংখ্যা</th>
                <th>মোট বিক্রয় (৳)</th>
                <th>স্টাফ</th>
                <th>অ্যাকশন</th>
            </tr>
        </thead>
        <tbody>
            @foreach($shopSales as $shop)
            <tr>
                <td style="font-weight:600">
                    <i class="fas fa-store" style="color:#f59e0b;margin-left:6px"></i>
                    {{ $shop->name }}
                </td>
                <td>
                    <span class="sa-pill {{ $shop->is_active ? 'sa-pill-on' : 'sa-pill-off' }}">
                        {{ $shop->is_active ? 'সক্রিয়' : 'নিষ্ক্রিয়' }}
                    </span>
                </td>
                <td style="color:#94a3b8">{{ $shop->sales_count }}</td>
                <td style="font-weight:600;color:#fbbf24">৳ {{ number_format($shop->total_revenue ?? 0, 0) }}</td>
                <td style="color:#94a3b8">{{ $shop->users_count }}</td>
                <td>
                    <a href="{{ route('super.shops.show', $shop) }}" class="sa-btn sa-btn-ghost sa-btn-sm">
                        <i class="fas fa-eye"></i> দেখুন
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

@endsection
