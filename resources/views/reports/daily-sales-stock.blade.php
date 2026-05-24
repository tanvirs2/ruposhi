@extends('layouts.app')
@section('title', 'দৈনিক বিক্রয় ও স্টক রিপোর্ট')
@section('page-title', 'দৈনিক বিক্রয় ও স্টক রিপোর্ট')

@section('content')

<div class="card" style="margin-bottom:20px">
    <div class="card-filter">
        <form method="GET" class="filter-form">
            <div class="form-group-field">
                <label>শুরু</label>
                <input type="date" name="from" value="{{ $from }}">
            </div>
            <div class="form-group-field">
                <label>শেষ</label>
                <input type="date" name="to" value="{{ $to }}">
            </div>
            <button type="submit" class="btn btn-primary" style="align-self:flex-end">রিপোর্ট দেখুন</button>
            <div style="align-self:flex-end;display:flex;gap:8px;margin-left:auto">
                <a href="{{ route('reports.export.daily-sales-stock', ['from'=>$from,'to'=>$to]) }}"
                   class="btn btn-export"><i class="fas fa-file-csv"></i> CSV</a>
                <button type="button" class="btn btn-export-print" onclick="window.print()">
                    <i class="fas fa-print"></i> প্রিন্ট
                </button>
            </div>
        </form>
    </div>
</div>

<div class="stats-grid" style="margin-bottom:24px">
    <div class="stat-card stat-green">
        <div class="stat-icon"><i class="fas fa-arrow-trend-up"></i></div>
        <div class="stat-body">
            <span class="stat-label">মোট রাজস্ব</span>
            <span class="stat-value">৳ {{ number_format($grandRevenue, 0) }}</span>
        </div>
    </div>
    <div class="stat-card stat-blue">
        <div class="stat-icon"><i class="fas fa-coins"></i></div>
        <div class="stat-body">
            <span class="stat-label">মোট খরচ (COGS)</span>
            <span class="stat-value">৳ {{ number_format($grandCost, 0) }}</span>
        </div>
    </div>
    <div class="stat-card" style="border-left:4px solid {{ $grandProfit >= 0 ? '#22c55e' : '#ef4444' }}">
        <div class="stat-icon" style="background:{{ $grandProfit >= 0 ? '#dcfce7' : '#fee2e2' }};color:{{ $grandProfit >= 0 ? '#16a34a' : '#dc2626' }}">
            <i class="fas fa-sack-dollar"></i>
        </div>
        <div class="stat-body">
            <span class="stat-label">মোট মুনাফা</span>
            <span class="stat-value" style="color:{{ $grandProfit >= 0 ? '#16a34a' : '#dc2626' }}">
                ৳ {{ number_format($grandProfit, 0) }}
            </span>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="data-table">
            <colgroup>
                <col style="width:50px">    {{-- # --}}
                <col style="width:220px">   {{-- আইটেম --}}
                <col style="width:110px">   {{-- বিক্রয় পরিমাণ --}}
                <col style="width:110px">   {{-- রাজস্ব --}}
                <col style="width:110px">   {{-- খরচ --}}
                <col style="width:110px">   {{-- মুনাফা --}}
                <col style="width:90px">    {{-- মার্জিন --}}
                <col style="width:110px">   {{-- বর্তমান স্টক --}}
            </colgroup>
            <thead>
                <tr>
                    <th>#</th>
                    <th>আইটেম</th>
                    <th style="text-align:right">বিক্রয় পরিমাণ</th>
                    <th style="text-align:right">রাজস্ব</th>
                    <th style="text-align:right">খরচ</th>
                    <th style="text-align:right">মুনাফা</th>
                    <th style="text-align:right">মার্জিন</th>
                    <th style="text-align:right">বর্তমান স্টক</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                @php
                    $profit = $row->revenue - $row->cost;
                    $margin = $row->revenue > 0 ? ($profit / $row->revenue) * 100 : 0;
                    $cls    = $margin >= 8 ? 'good' : ($margin >= 4 ? 'med' : 'poor');
                @endphp
                <tr>
                    <td class="mono">{{ $loop->iteration }}</td>
                    <td><strong>{{ $row->name }}</strong></td>
                    <td style="text-align:right">{{ number_format($row->sold_qty, 0) }}</td>
                    <td style="text-align:right;font-weight:600">৳ {{ number_format($row->revenue, 2) }}</td>
                    <td style="text-align:right">৳ {{ number_format($row->cost, 2) }}</td>
                    <td style="text-align:right" class="profit-{{ $cls }}">
                        ৳ {{ number_format($profit, 2) }}
                    </td>
                    <td style="text-align:right">
                        <span class="margin-badge {{ $cls }}">{{ number_format($margin, 1) }}%</span>
                    </td>
                    <td style="text-align:right">
                        <span class="badge {{ ($row->current_stock ?? 0) <= 5 ? 'badge-red' : 'badge-green' }}">
                            {{ number_format($row->current_stock ?? 0, 0) }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="empty-row">এই সময়কালে কোনো বিক্রয় নেই</td></tr>
                @endforelse
            </tbody>
            @if($rows->isNotEmpty())
            <tfoot>
                <tr style="background:var(--accent-light);font-weight:700">
                    <td colspan="3" style="text-align:right;padding-right:16px">সর্বমোট</td>
                    <td style="text-align:right">৳ {{ number_format($grandRevenue, 2) }}</td>
                    <td style="text-align:right">৳ {{ number_format($grandCost, 2) }}</td>
                    <td style="text-align:right;color:{{ $grandProfit >= 0 ? '#16a34a' : '#dc2626' }}">
                        ৳ {{ number_format($grandProfit, 2) }}
                    </td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection
