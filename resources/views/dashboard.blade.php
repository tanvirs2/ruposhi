@extends('layouts.app')
@section('title', 'ড্যাশবোর্ড')
@section('page-title', 'ড্যাশবোর্ড')

@section('content')

{{-- Stats --}}
<div class="stats-grid">
    <div class="stat-card stat-blue">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-body">
            <span class="stat-label">মোট কাস্টমার</span>
            <span class="stat-value">{{ number_format($stats['customers']) }}</span>
        </div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-icon"><i class="fas fa-box-open"></i></div>
        <div class="stat-body">
            <span class="stat-label">মোট আইটেম</span>
            <span class="stat-value">{{ number_format($stats['items']) }}</span>
        </div>
    </div>
    <div class="stat-card stat-orange">
        <div class="stat-icon"><i class="fas fa-receipt"></i></div>
        <div class="stat-body">
            <span class="stat-label">আজকের বিক্রয়</span>
            <span class="stat-value">৳ {{ number_format($stats['today_sales'], 2) }}</span>
        </div>
    </div>
    <div class="stat-card stat-purple">
        <div class="stat-icon"><i class="fas fa-warehouse"></i></div>
        <div class="stat-body">
            <span class="stat-label">স্টক মূল্য</span>
            <span class="stat-value">৳ {{ number_format($stats['stock_value'], 2) }}</span>
        </div>
    </div>
</div>

{{-- Module Grid --}}
<div class="section-header">
    <h2>মডিউল সমূহ</h2>
    <span class="section-sub">একটি মডিউলে ক্লিক করে শুরু করুন</span>
</div>

<div class="module-grid">
    @php
    $modules = [
        ['href' => route('customers.index'),   'color' => '#3b82f6', 'icon' => 'fas fa-users',             'title' => 'কাস্টমার',       'desc' => 'যোগ, হালনাগাদ, মুছে ফেলুন এবং কাস্টমার অনুসন্ধান'],
        ['href' => route('items.index'),        'color' => '#10b981', 'icon' => 'fas fa-box-open',          'title' => 'আইটেমস',         'desc' => 'যোগ, হালনাগাদ, মুছে ফেলুন এবং আইটেম অনুসন্ধান'],
        ['href' => route('suppliers.index'),    'color' => '#f59e0b', 'icon' => 'fas fa-truck',             'title' => 'সরবরাহকারী',     'desc' => 'যোগ, হালনাগাদ, মুছে ফেলুন এবং সরবরাহকারী অনুসন্ধান'],
        ['href' => route('reports.index'),      'color' => '#8b5cf6', 'icon' => 'fas fa-chart-bar',         'title' => 'রিপোর্ট',        'desc' => 'দেখুন এবং রিপোর্ট তৈরি করুন'],
        ['href' => route('purchases.index'),    'color' => '#ef4444', 'icon' => 'fas fa-cart-plus',         'title' => 'গ্রহণ',          'desc' => 'ক্রয় অর্ডার প্রক্রিয়া করুন'],
        ['href' => route('sales.index'),        'color' => '#f97316', 'icon' => 'fas fa-receipt',           'title' => 'বিক্রয়',        'desc' => 'বিক্রি এবং ফেরত প্রক্রিয়া করুন'],
        ['href' => route('employees.index'),    'color' => '#06b6d4', 'icon' => 'fas fa-id-badge',          'title' => 'কর্মচারী',      'desc' => 'যোগ, হালনাগাদ, মুছে ফেলুন এবং কর্মচারী অনুসন্ধান'],
        ['href' => route('store-config.index'), 'color' => '#0d9488', 'icon' => 'fas fa-store',             'title' => 'স্টোর কনফিগ',   'desc' => 'স্টোর এর কনফিগারেশন পরিবর্তন'],
        ['href' => route('stock.index'),        'color' => '#14b8a6', 'icon' => 'fas fa-warehouse',         'title' => 'স্টক',           'desc' => 'স্টক ব্যবস্থাপনা'],
        ['href' => route('expenses.index'),     'color' => '#ec4899', 'icon' => 'fas fa-money-bill-transfer','title' => 'অতিরিক্ত খরচ', 'desc' => 'অতিরিক্ত খরচের বিবরণ'],
    ];
    @endphp

    @foreach($modules as $m)
    <a href="{{ $m['href'] }}" class="module-card">
        <div class="module-icon-wrap" style="--c:{{ $m['color'] }}">
            <i class="{{ $m['icon'] }}"></i>
        </div>
        <div class="module-info">
            <h3>{{ $m['title'] }}</h3>
            <p>{{ $m['desc'] }}</p>
        </div>
        <span class="module-arrow"><i class="fas fa-arrow-right"></i></span>
    </a>
    @endforeach
</div>

{{-- Bottom --}}
<div class="bottom-grid">
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-clock-rotate-left"></i> সাম্প্রতিক বিক্রয়</h3>
            <a href="{{ route('sales.index') }}" class="card-link">সব দেখুন <i class="fas fa-chevron-right"></i></a>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr><th>ইনভয়েস</th><th>কাস্টমার</th><th>পরিমাণ</th><th>স্ট্যাটাস</th></tr>
                </thead>
                <tbody>
                    @forelse($recent_sales as $sale)
                    <tr>
                        <td><span class="mono">#INV-{{ str_pad($sale->id, 4, '0', STR_PAD_LEFT) }}</span></td>
                        <td>{{ $sale->customer?->name ?? 'ওয়াক-ইন' }}</td>
                        <td>৳ {{ number_format($sale->total_amount, 2) }}</td>
                        <td>
                            @if($sale->status === 'completed')
                                <span class="badge badge-green">সম্পন্ন</span>
                            @elseif($sale->status === 'pending')
                                <span class="badge badge-yellow">মুলতুবি</span>
                            @else
                                <span class="badge badge-red">বাতিল</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:24px">কোনো বিক্রয় নেই</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-triangle-exclamation"></i> কম স্টক সতর্কতা</h3>
            <a href="{{ route('stock.index') }}?filter=low" class="card-link">স্টক দেখুন <i class="fas fa-chevron-right"></i></a>
        </div>
        <div class="alert-list">
            @forelse($low_stock as $s)
            <div class="alert-item">
                <div class="alert-icon" style="background:#fee2e2;color:#ef4444"><i class="fas fa-box"></i></div>
                <div class="alert-body">
                    <span class="alert-name">{{ $s->item->name }}</span>
                    <div class="stock-bar">
                        @php $pct = $s->min_quantity > 0 ? min(100, ($s->quantity / $s->min_quantity) * 100) : 0; @endphp
                        <div class="stock-fill" style="width:{{ $pct }}%;background:{{ $pct < 20 ? '#ef4444' : '#f59e0b' }}"></div>
                    </div>
                </div>
                <span class="alert-qty" style="color:{{ $pct < 20 ? '#ef4444' : '#f59e0b' }}">{{ $s->quantity }} টি</span>
            </div>
            @empty
            <div style="text-align:center;color:#94a3b8;padding:32px">সব স্টক পর্যাপ্ত</div>
            @endforelse
        </div>
    </div>
</div>

@endsection
