@extends('layouts.app')
@section('title', 'রিপোর্ট')
@section('page-title', 'রিপোর্ট')

@section('content')
<div class="card" style="margin-bottom:20px">
    <div class="card-filter">
        <form method="GET" class="filter-form">
            <div class="form-group-field">
                <label>শুরুর তারিখ</label>
                <input type="date" name="from" value="{{ $from }}">
            </div>
            <div class="form-group-field">
                <label>শেষ তারিখ</label>
                <input type="date" name="to" value="{{ $to }}">
            </div>
            <button type="submit" class="btn btn-primary" style="align-self:flex-end">রিপোর্ট দেখুন</button>
            <div style="align-self:flex-end;display:flex;gap:8px;margin-left:auto">
                <button type="button" class="btn btn-export-print" onclick="window.print()">
                    <i class="fas fa-print"></i> প্রিন্ট
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Summary cards --}}
<div class="stats-grid" style="margin-bottom:24px">
    <div class="stat-card stat-green">
        <div class="stat-icon"><i class="fas fa-arrow-trend-up"></i></div>
        <div class="stat-body">
            <span class="stat-label">মোট বিক্রয় আয়</span>
            <span class="stat-value">৳ {{ number_format($summary['total_revenue'],2) }}</span>
        </div>
    </div>
    <div class="stat-card stat-orange">
        <div class="stat-icon"><i class="fas fa-boxes-stacked"></i></div>
        <div class="stat-body">
            <span class="stat-label">মোট পণ্য খরচ (COGS)</span>
            <span class="stat-value">৳ {{ number_format($summary['total_cogs'],2) }}</span>
        </div>
    </div>
    <div class="stat-card stat-purple">
        <div class="stat-icon"><i class="fas fa-money-bill-transfer"></i></div>
        <div class="stat-body">
            <span class="stat-label">অতিরিক্ত খরচ</span>
            <span class="stat-value">৳ {{ number_format($summary['total_expenses'],2) }}</span>
        </div>
    </div>
    <div class="stat-card {{ $summary['net_profit'] >= 0 ? 'stat-blue' : 'stat-orange' }}">
        <div class="stat-icon"><i class="fas fa-scale-balanced"></i></div>
        <div class="stat-body">
            <span class="stat-label">নিট লাভ</span>
            <span class="stat-value">৳ {{ number_format($summary['net_profit'],2) }}</span>
        </div>
    </div>
</div>

{{-- Profit breakdown banner --}}
@php
    $grossPct = $summary['total_cogs'] > 0
        ? round($summary['gross_profit'] / $summary['total_cogs'] * 100, 1)
        : 0;
@endphp
<div class="card" style="margin-bottom:24px;border-left:4px solid {{ $summary['gross_profit'] >= 0 ? '#16a34a' : '#dc2626' }}">
    <div style="padding:16px 20px;display:flex;flex-wrap:wrap;gap:24px;align-items:center">
        <div>
            <div style="font-size:.8rem;color:#64748b;margin-bottom:2px">মোট আয় (আলোচনাকৃত মূল্যে)</div>
            <div style="font-size:1.1rem;font-weight:700;color:#1e293b">৳ {{ number_format($summary['total_revenue'],2) }}</div>
        </div>
        <div style="font-size:1.2rem;color:#94a3b8">−</div>
        <div>
            <div style="font-size:.8rem;color:#64748b;margin-bottom:2px">পণ্য ক্রয় খরচ</div>
            <div style="font-size:1.1rem;font-weight:700;color:#1e293b">৳ {{ number_format($summary['total_cogs'],2) }}</div>
        </div>
        <div style="font-size:1.2rem;color:#94a3b8">=</div>
        <div>
            <div style="font-size:.8rem;color:#64748b;margin-bottom:2px">গ্রস লাভ</div>
            <div style="font-size:1.2rem;font-weight:700;color:{{ $summary['gross_profit'] >= 0 ? '#16a34a' : '#dc2626' }}">
                {{ $summary['gross_profit'] >= 0 ? '+' : '' }}৳ {{ number_format($summary['gross_profit'],2) }}
            </div>
        </div>
        <div style="margin-left:auto">
            @if($grossPct >= 8)
                <span class="margin-badge good">✓ ভালো লাভ ({{ $grossPct }}%)</span>
            @elseif($grossPct >= 4)
                <span class="margin-badge med">~ মধ্যম লাভ ({{ $grossPct }}%)</span>
            @elseif($grossPct >= 0)
                <span class="margin-badge poor">↓ কম লাভ ({{ $grossPct }}%)</span>
            @else
                <span class="margin-badge poor">✗ লোকসান ({{ $grossPct }}%)</span>
            @endif
        </div>
    </div>
</div>

<div class="bottom-grid">
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-receipt"></i> দৈনিক বিক্রয়</h3></div>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>তারিখ</th><th>বিক্রয় সংখ্যা</th><th>মোট</th></tr></thead>
                <tbody>
                    @forelse($sales as $row)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($row->date)->format('d M Y') }}</td>
                        <td>{{ $row->count }}</td>
                        <td>৳ {{ number_format($row->total,2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="empty-row">এই সময়কালে কোনো বিক্রয় নেই</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3><i class="fas fa-star"></i> আইটেম অনুযায়ী লাভ</h3></div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>আইটেম</th>
                        <th>পরিমাণ</th>
                        <th>আয়</th>
                        <th>খরচ</th>
                        <th>লাভ</th>
                        <th>মার্জিন</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($top_items as $i => $item)
                    @php
                        $pct = $item->cost > 0 ? round($item->profit / $item->cost * 100, 1) : 0;
                        $cls = $pct >= 8 ? 'profit-good' : ($pct >= 4 ? 'profit-med' : 'profit-poor');
                    @endphp
                    <tr>
                        <td>{{ $i+1 }}</td>
                        <td>{{ $item->name }}</td>
                        <td>{{ $item->qty }} বস্তা</td>
                        <td>৳ {{ number_format($item->revenue,0) }}</td>
                        <td style="color:#94a3b8">৳ {{ number_format($item->cost,0) }}</td>
                        <td class="{{ $cls }}">{{ $item->profit >= 0 ? '+' : '' }}৳ {{ number_format($item->profit,0) }}</td>
                        <td>
                            @if($pct >= 8)
                                <span class="margin-badge good">{{ $pct }}%</span>
                            @elseif($pct >= 4)
                                <span class="margin-badge med">{{ $pct }}%</span>
                            @else
                                <span class="margin-badge poor">{{ $pct }}%</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="empty-row">কোনো ডেটা নেই</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.profit-good { color:#16a34a; font-weight:600; }
.profit-med  { color:#d97706; font-weight:600; }
.profit-poor { color:#dc2626; font-weight:600; }

.margin-badge {
    display:inline-block;
    padding:2px 10px;
    border-radius:20px;
    font-size:.78rem;
    font-weight:600;
}
.margin-badge.good { background:#dcfce7; color:#15803d; }
.margin-badge.med  { background:#fef9c3; color:#92400e; }
.margin-badge.poor { background:#fee2e2; color:#b91c1c; }
</style>
@endpush
