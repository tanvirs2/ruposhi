@extends('layouts.app')
@section('title', 'বিক্রয় রিপোর্ট')
@section('page-title', 'দৈনিক বিক্রয় রিপোর্ট')
@section('no-print-header', '1')

@push('styles')
<meta name="turbo-cache-control" content="no-cache">
@endpush

@section('content')

{{-- Filter ────────────────────────────────────────────────── --}}
<div class="card no-print" style="margin-bottom:20px">
    <div class="card-filter">
        <form method="GET" class="filter-form" data-date-snap>
            <div class="form-group-field">
                <label>শুরুর তারিখ</label>
                <input type="date" name="from" id="rptDateFrom" value="{{ $from }}">
            </div>
            <div class="form-group-field">
                <label>শেষ তারিখ</label>
                <input type="date" name="to" id="rptDateTo" value="{{ $to }}">
            </div>
            @include('partials.date-range-buttons')
            <div class="form-group-field" style="position:relative;min-width:200px">
                <label>কাস্টমার</label>
                <input type="hidden" name="customer_id" id="rsCustomerId" value="{{ request('customer_id') }}">
                <input type="text" id="rsCustomerSearch" class="form-select" autocomplete="off"
                       placeholder="সব কাস্টমার (খুঁজতে লিখুন)"
                       value="{{ $selectedCustomer?->name }}">
            </div>
            <button type="submit" class="btn btn-primary" style="align-self:flex-end">
                <i class="fas fa-search"></i> রিপোর্ট দেখুন
            </button>
            @if(request()->hasAny(['customer_id']))
                <a href="{{ route('reports.sales', ['from'=>$from,'to'=>$to]) }}"
                   class="btn btn-ghost" style="align-self:flex-end">পরিষ্কার</a>
            @endif
            <div style="align-self:flex-end;display:flex;gap:8px;margin-left:auto">
                <a href="{{ route('reports.export.sales', array_filter(['from'=>$from,'to'=>$to,'customer_id'=>request('customer_id')])) }}"
                   class="btn btn-export" title="CSV ডাউনলোড">
                    <i class="fas fa-file-csv"></i> CSV
                </a>
                <button type="button" class="btn btn-export-print" onclick="window.print()">
                    <i class="fas fa-print"></i> প্রিন্ট
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Print header ────────────────────────────────────────────── --}}
<div class="print-header" style="display:none;text-align:center;margin-bottom:14px">
    @include('partials.store-name-arc', ['name' => \App\Models\StoreConfig::get('store_name','আমার দোকান'), 'size' => 26])
    <div style="font-size:.85rem;color:#555">দৈনিক বিক্রয় রিপোর্ট</div>
    <div style="font-size:.8rem;color:#777">
        তারিখ: {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }}
        @if($from !== $to) — {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }} @endif
    </div>
</div>

{{-- Summary cards (all users, full-shop data) ───────────────── --}}
<div class="sales-stats-grid no-print">
    <div class="stat-card stat-green">
        <div class="stat-icon"><i class="fas fa-receipt"></i></div>
        <div class="stat-body">
            <span class="stat-label">মোট বিক্রয়</span>
            <span class="stat-value">৳ {{ number_format($shopGrandTotal, 0) }}</span>
        </div>
    </div>
    <div class="stat-card stat-blue">
        <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
        <div class="stat-body">
            <span class="stat-label">বিক্রয়ে পরিশোধ</span>
            <span class="stat-value">৳ {{ number_format($shopGrandItemPaid, 0) }}</span>
            @if($shopExtraNote > 0)
            <span style="font-size:.72rem;color:#2563eb;font-weight:600;margin-top:2px;display:block">
                + ৳ {{ number_format($shopExtraNote, 0) }} বাকী পরিশোধ
            </span>
            @endif
        </div>
    </div>
    <div class="stat-card" style="border-left:4px solid #16a34a;background:linear-gradient(135deg,#f0fdf4,#dcfce7)">
        <div class="stat-icon" style="background:#bbf7d0;color:#15803d"><i class="fas fa-hand-holding-dollar"></i></div>
        <div class="stat-body">
            <span class="stat-label" style="color:#15803d;font-weight:700">নগদ আছে</span>
            <span class="stat-value" style="color:#15803d">৳ {{ number_format($shopGrandPaid, 0) }}</span>
            @if($shopExtraNote > 0)
            <span style="font-size:.70rem;color:#64748b;font-weight:500;margin-top:2px;display:block">
                {{ number_format($shopGrandItemPaid,0) }} + {{ number_format($shopExtraNote,0) }}
            </span>
            @endif
        </div>
    </div>
    <div class="stat-card" style="border-left:4px solid #ef4444">
        <div class="stat-icon" style="background:#fee2e2;color:#dc2626"><i class="fas fa-triangle-exclamation"></i></div>
        <div class="stat-body">
            <span class="stat-label">মোট বাকী</span>
            <span class="stat-value" style="color:#dc2626">৳ {{ number_format($shopGrandDue, 0) }}</span>
        </div>
    </div>
    <div class="stat-card stat-purple">
        <div class="stat-icon"><i class="fas fa-hashtag"></i></div>
        <div class="stat-body">
            <span class="stat-label">মোট চালান</span>
            <span class="stat-value">{{ $shopGrandCount }}</span>
        </div>
    </div>
    @if($grandExtraCost > 0 || $grandDiscount > 0)
    <div class="stat-card" style="border-left:4px solid #8b5cf6;background:linear-gradient(135deg,#faf5ff,#ede9fe)">
        <div class="stat-icon" style="background:#ddd6fe;color:#7c3aed"><i class="fas fa-coins"></i></div>
        <div class="stat-body">
            <span class="stat-label" style="color:#6d28d9;font-weight:700">অন্যান্য খরচ / ছাড়</span>
            @if($grandExtraCost > 0)
            <span style="font-size:.72rem;font-weight:700;color:#7c3aed;display:block;margin-top:3px">
                অতি. খরচ: ৳ {{ number_format($grandExtraCost, 0) }}
            </span>
            @foreach($extraCostByCategory as $cat => $amt)
            <span style="font-size:.68rem;color:#6d28d9;display:block;padding-left:8px">
                · {{ $cat }}: ৳ {{ number_format($amt, 0) }}
            </span>
            @endforeach
            @endif
            @if($grandDiscount > 0)
            <span style="font-size:.72rem;color:#15803d;display:block;margin-top:2px">
                ছাড়: − ৳ {{ number_format($grandDiscount, 0) }}
            </span>
            @endif
        </div>
    </div>
    @endif
</div>

@php
    [$namedItems, $walkinItems] = $saleItems->partition(fn($r) => $r->customer_name !== null);
    // Per-sale aggregates (paid/due/extra/disc are repeated per item — take unique sales)
    $namedUniqSales  = $namedItems->unique('sale_id');
    $walkinUniqSales = $walkinItems->unique('sale_id');
    $namedPaid  = $namedUniqSales->sum('paid_amount');
    $namedDue   = $namedUniqSales->sum('due_amount');
    $namedExtra = $namedUniqSales->sum('extra_cost');
    $namedDisc  = $namedUniqSales->sum('discount');
    $walkinPaid  = $walkinUniqSales->sum('paid_amount');
    $walkinDue   = $walkinUniqSales->sum('due_amount');
    $walkinExtra = $walkinUniqSales->sum('extra_cost');
    $walkinDisc  = $walkinUniqSales->sum('discount');
@endphp

{{-- Tab switcher + full detail (admin only) ─────────────────── --}}
@if(auth()->user()->canManageShop())
<div class="no-print" style="display:flex;gap:0;border:1.5px solid var(--border);border-radius:8px;overflow:hidden;width:fit-content;margin-bottom:20px">
    <button id="btnReportDetail" class="report-tab-btn active" onclick="setReportView('detail')">
        <i class="fas fa-table-list"></i> বিক্রয় বিবরণ
    </button>
    <button id="btnReportUser" class="report-tab-btn" onclick="setReportView('user')">
        <i class="fas fa-users"></i> ইউজার ভিত্তিক
    </button>
</div>

<div id="reportDetailView">

{{-- 7-Day Trend Chart ────────────────────────────────────────── --}}
<div class="card no-print" style="margin-bottom:20px">
    <div class="card-header">
        <h3><i class="fas fa-chart-bar"></i> গত ৭ দিনের ট্রেন্ড</h3>
        <span style="font-size:.75rem;color:var(--text-secondary)">সবুজ = পরিশোধ, ধূসর = মোট বিক্রয়</span>
    </div>
    <div style="padding:16px 20px 8px">
        @php $trendMax = $trendDays->max('total') ?: 1; @endphp
        <div style="display:flex;align-items:flex-end;gap:6px;height:130px;padding-bottom:32px;position:relative">
            {{-- Gridlines --}}
            @foreach([100,50] as $pct)
            <div style="position:absolute;left:0;right:0;bottom:calc(32px + {{ $pct }}%);border-top:1px dashed var(--border);pointer-events:none">
                <span style="position:absolute;left:0;top:-14px;font-size:.62rem;color:var(--text-secondary)">
                    ৳{{ number_format($trendMax * $pct / 100 / 1000, 0) }}ক
                </span>
            </div>
            @endforeach

            @foreach($trendDays as $day)
            @php
                $totalPct = $trendMax > 0 ? ($day['total'] / $trendMax * 100) : 0;
                $paidPct  = $trendMax > 0 ? ($day['paid']  / $trendMax * 100) : 0;
                $isToday  = $day['date'] === today()->format('d/m');
            @endphp
            <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:2px;height:100%;justify-content:flex-end;position:relative">
                {{-- Total bar (background) --}}
                <div style="width:100%;position:relative">
                    <div style="width:100%;background:var(--surface-2);border:1.5px solid var(--border);
                                border-radius:4px 4px 0 0;height:{{ max(4, $totalPct) }}px;
                                max-height:98px;position:relative">
                        {{-- Paid bar overlay --}}
                        @if($day['paid'] > 0)
                        <div style="position:absolute;bottom:0;left:0;right:0;
                                    background:#16a34a22;border-bottom:3px solid #16a34a;
                                    height:{{ min(100, $paidPct > 0 ? ($day['paid']/$day['total']*100) : 0) }}%;
                                    border-radius:0 0 2px 2px">
                        </div>
                        @endif
                    </div>
                </div>
                @if($day['total'] > 0)
                <div style="font-size:.58rem;color:var(--text-secondary);white-space:nowrap;margin-top:2px">
                    ৳{{ number_format($day['total']/1000,1) }}ক
                </div>
                @endif
                <div style="font-size:.65rem;color:{{ $isToday ? 'var(--accent)' : 'var(--text-secondary)' }};
                            font-weight:{{ $isToday ? '700' : '400' }};margin-top:2px;text-align:center">
                    {{ $day['date'] }}@if($isToday)<br><span style="font-size:.55rem">আজ</span>@endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- User-wise sales summary ────────────────────────────────── --}}
@if($userSummary->count() > 1)
<div class="card no-print" style="margin-bottom:20px">
    <div class="card-header">
        <h3><i class="fas fa-users" style="color:var(--accent)"></i> ইউজার ভিত্তিক বিক্রয়</h3>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ইউজার</th>
                    <th style="text-align:right">বিক্রয় সংখ্যা</th>
                    <th style="text-align:right">মোট বিক্রয়</th>
                    <th style="text-align:right">পরিশোধ</th>
                    <th style="text-align:right">বাকী</th>
                </tr>
            </thead>
            <tbody>
                @foreach($userSummary as $row)
                <tr>
                    <td style="font-weight:600">{{ $row['name'] }}</td>
                    <td style="text-align:right">{{ $row['count'] }}</td>
                    <td style="text-align:right;font-weight:700">৳ {{ number_format($row['total'],0) }}</td>
                    <td style="text-align:right;color:#16a34a">৳ {{ number_format($row['paid'],0) }}</td>
                    <td style="text-align:right;color:{{ $row['due'] > 0 ? '#dc2626' : 'var(--text-secondary)' }}">
                        {{ $row['due'] > 0 ? '৳ '.number_format($row['due'],0) : '—' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="tfoot-summary">
                    <td style="font-weight:700">মোট</td>
                    <td style="text-align:right;font-weight:700">{{ $userSummary->sum('count') }}</td>
                    <td style="text-align:right;font-weight:800">৳ {{ number_format($shopGrandTotal,0) }}</td>
                    <td style="text-align:right;font-weight:800;color:#16a34a">৳ {{ number_format($userSummary->sum('paid'),0) }}</td>
                    <td style="text-align:right;font-weight:800;color:#dc2626">৳ {{ number_format($userSummary->sum('due'),0) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

{{-- Per-item detail table — named customers ────────────────── --}}
<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <h3><i class="fas fa-table-list"></i>
            তারিখ : {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }}
            @if($from !== $to) — {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }} @endif
        </h3>
        <span style="font-size:.8rem;color:#94a3b8">{{ $namedItems->count() }} টি আইটেম</span>
    </div>
    <div class="table-wrap">
        <table class="data-table sale-detail-table">
            <thead>
                <tr>
                    <th class="tc">চালান নং</th>
                    <th class="tc col-hide-tablet">তারিখ</th>
                    <th>কাস্টমার নাম</th>
                    <th>আইটেম নাম</th>
                    <th class="tc">পরিমাণ</th>
                    <th class="tc col-hide-tablet">পরিমাণ কেজি</th>
                    <th class="tr col-hide-tablet">বিক্রয় মূল্য</th>
                    <th class="tr">মোট মূল্য</th>
                    <th class="tr col-hide-tablet" style="color:#15803d">ছাড়</th>
                    <th class="tr col-hide-tablet" style="color:#7c3aed">অতি. খরচ</th>
                    <th class="tr">জমা</th>
                    <th class="tr">বাকী</th>
                    <th class="tc col-hide-tablet">ইউজার</th>
                    <th class="tc col-hide-tablet">সময়</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $running = 0; $lastSaleId = null;
                    $totalQty = 0; $totalKgSum = 0;
                @endphp
                @forelse($namedItems as $row)
                @php
                    $running += $row->amount;
                    $isNewSale = ($row->sale_id !== $lastSaleId);
                    $lastSaleId = $row->sale_id;
                    $showCosts = $isNewSale;
                    preg_match('/(৫০|২৫|50|25)\s*(কেজি|kg)/ui', $row->item_name, $m);
                    $kgPer = isset($m[1]) ? (int) str_replace(['৫০','২৫'],['50','25'], $m[1]) : null;
                    $totalKg = $kgPer ? (int)$row->qty * $kgPer : null;
                    $totalQty += (int)$row->qty;
                    if ($totalKg) $totalKgSum += $totalKg;
                @endphp
                <tr class="{{ $isNewSale ? 'new-sale-row' : '' }}">
                    <td class="tc mono">
                        <a href="{{ route('sales.show', $row->sale_id) }}" class="link-primary">
                            {{ str_pad($row->sale_id, 6, '0', STR_PAD_LEFT) }}
                        </a>
                    </td>
                    <td class="tc col-hide-tablet" style="font-size:.78rem;color:#64748b;white-space:nowrap">
                        @if($isNewSale) {{ \Carbon\Carbon::parse($row->date)->format('d/m/Y') }} @else — @endif
                    </td>
                    <td>{{ $row->customer_name }}</td>
                    <td>{{ $row->item_name }}</td>
                    <td class="tc">{{ (int)$row->qty }}</td>
                    <td class="tc col-hide-tablet">{{ $totalKg ?? '—' }}</td>
                    <td class="tr col-hide-tablet">{{ number_format($row->rate, 0) }}</td>
                    <td class="tr" style="font-weight:600">{{ number_format($running, 0) }}</td>
                    <td class="tr col-hide-tablet" style="color:#15803d;font-size:.8rem">
                        @if($showCosts && ($row->discount ?? 0) > 0) − {{ number_format($row->discount, 0) }}
                        @else — @endif
                    </td>
                    <td class="tr col-hide-tablet" style="color:#7c3aed;font-size:.8rem">
                        @if($showCosts && ($row->extra_cost ?? 0) > 0) + {{ number_format($row->extra_cost, 0) }}
                        @else — @endif
                    </td>
                    <td class="tr" style="color:#16a34a">
                        @if($isNewSale) {{ number_format($row->paid_amount, 0) }} @else — @endif
                    </td>
                    <td class="tr">
                        @if($isNewSale)
                            @if($row->due_amount > 0)
                                <span style="color:#dc2626;font-weight:600">{{ number_format($row->due_amount, 0) }}</span>
                            @else <span style="color:#16a34a">—</span>
                            @endif
                        @else — @endif
                    </td>
                    <td class="tc col-hide-tablet" style="font-size:.78rem;color:#64748b">{{ $row->user_name ?? '—' }}</td>
                    <td class="tc col-hide-tablet" style="font-size:.78rem;color:#64748b;white-space:nowrap">
                        {{ \Carbon\Carbon::parse($row->sale_time)->format('h:i:s a') }}
                    </td>
                </tr>
                @empty
                <tr><td colspan="14" class="empty-row">এই সময়কালে কোনো নামযুক্ত কাস্টমারের বিক্রয় নেই</td></tr>
                @endforelse
            </tbody>
            @if($namedItems->isNotEmpty())
            <tfoot>
                <tr class="tfoot-summary">
                    <td colspan="4" style="text-align:right;font-weight:700;padding-right:16px">সর্বমোট</td>
                    <td class="tc" style="font-weight:800">{{ $totalQty }}</td>
                    <td class="tc col-hide-tablet" style="font-weight:800">{{ $totalKgSum ?: '—' }}</td>
                    <td></td>
                    <td class="tr" style="font-weight:800">{{ number_format($namedItems->sum('amount'), 0) }}</td>
                    <td class="tr col-hide-tablet" style="color:#15803d;font-weight:700">
                        {{ $namedDisc > 0 ? '− '.number_format($namedDisc, 0) : '—' }}
                    </td>
                    <td class="tr col-hide-tablet" style="color:#7c3aed;font-weight:700">
                        {{ $namedExtra > 0 ? '+ '.number_format($namedExtra, 0) : '—' }}
                    </td>
                    <td class="tr" style="color:#16a34a;font-weight:700">{{ number_format($namedPaid, 0) }}</td>
                    <td class="tr" style="color:#dc2626;font-weight:700">{{ number_format($namedDue, 0) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

{{-- ── ওয়াক-ইন বিক্রয় ────────────────────────────────────────── --}}
@if($walkinItems->isNotEmpty())
<div class="card" style="margin-top:18px">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;background:linear-gradient(135deg,#f0fdfa,#ccfbf1);border-bottom:1px solid #99f6e4">
        <h3 style="font-size:.95rem;color:#0f766e;margin:0">
            <i class="fas fa-person-walking"></i> ওয়াক-ইন বিক্রয়
        </h3>
        <span style="font-size:.8rem;color:#0f766e">{{ $walkinItems->count() }} টি আইটেম</span>
    </div>
    <div class="table-wrap">
        <table class="data-table sale-detail-table">
            <thead>
                <tr>
                    <th class="tc">চালান নং</th>
                    <th class="tc col-hide-tablet">তারিখ</th>
                    <th>আইটেম নাম</th>
                    <th class="tc">পরিমাণ</th>
                    <th class="tc col-hide-tablet">পরিমাণ কেজি</th>
                    <th class="tr col-hide-tablet">বিক্রয় মূল্য</th>
                    <th class="tr">মোট মূল্য</th>
                    <th class="tr col-hide-tablet" style="color:#15803d">ছাড়</th>
                    <th class="tr col-hide-tablet" style="color:#7c3aed">অতি. খরচ</th>
                    <th class="tr">জমা</th>
                    <th class="tr">বাকী</th>
                    <th class="tc col-hide-tablet">ইউজার</th>
                    <th class="tc col-hide-tablet">সময়</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $wRunning = 0; $wLastSaleId = null;
                    $wTotalQty = 0; $wTotalKgSum = 0;
                @endphp
                @foreach($walkinItems as $row)
                @php
                    $wRunning += $row->amount;
                    $wIsNew = ($row->sale_id !== $wLastSaleId);
                    $wLastSaleId = $row->sale_id;
                    preg_match('/(৫০|২৫|50|25)\s*(কেজি|kg)/ui', $row->item_name, $m);
                    $wKgPer = isset($m[1]) ? (int) str_replace(['৫০','২৫'],['50','25'], $m[1]) : null;
                    $wTotalKg = $wKgPer ? (int)$row->qty * $wKgPer : null;
                    $wTotalQty += (int)$row->qty;
                    if ($wTotalKg) $wTotalKgSum += $wTotalKg;
                @endphp
                <tr class="{{ $wIsNew ? 'new-sale-row' : '' }}">
                    <td class="tc mono">
                        <a href="{{ route('sales.show', $row->sale_id) }}" class="link-primary">
                            {{ str_pad($row->sale_id, 6, '0', STR_PAD_LEFT) }}
                        </a>
                    </td>
                    <td class="tc col-hide-tablet" style="font-size:.78rem;color:#64748b;white-space:nowrap">
                        @if($wIsNew) {{ \Carbon\Carbon::parse($row->date)->format('d/m/Y') }} @else — @endif
                    </td>
                    <td>{{ $row->item_name }}</td>
                    <td class="tc">{{ (int)$row->qty }}</td>
                    <td class="tc col-hide-tablet">{{ $wTotalKg ?? '—' }}</td>
                    <td class="tr col-hide-tablet">{{ number_format($row->rate, 0) }}</td>
                    <td class="tr" style="font-weight:600">{{ number_format($wRunning, 0) }}</td>
                    <td class="tr col-hide-tablet" style="color:#15803d;font-size:.8rem">
                        @if($wIsNew && ($row->discount ?? 0) > 0) − {{ number_format($row->discount, 0) }}
                        @else — @endif
                    </td>
                    <td class="tr col-hide-tablet" style="color:#7c3aed;font-size:.8rem">
                        @if($wIsNew && ($row->extra_cost ?? 0) > 0) + {{ number_format($row->extra_cost, 0) }}
                        @else — @endif
                    </td>
                    <td class="tr" style="color:#16a34a">
                        @if($wIsNew) {{ number_format($row->paid_amount, 0) }} @else — @endif
                    </td>
                    <td class="tr">
                        @if($wIsNew)
                            @if($row->due_amount > 0)
                                <span style="color:#dc2626;font-weight:600">{{ number_format($row->due_amount, 0) }}</span>
                            @else <span style="color:#16a34a">—</span>
                            @endif
                        @else — @endif
                    </td>
                    <td class="tc col-hide-tablet" style="font-size:.78rem;color:#64748b">{{ $row->user_name ?? '—' }}</td>
                    <td class="tc col-hide-tablet" style="font-size:.78rem;color:#64748b;white-space:nowrap">
                        {{ \Carbon\Carbon::parse($row->sale_time)->format('h:i:s a') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="tfoot-summary">
                    <td colspan="3" style="text-align:right;font-weight:700;padding-right:16px">সর্বমোট</td>
                    <td class="tc" style="font-weight:800">{{ $wTotalQty }}</td>
                    <td class="tc col-hide-tablet" style="font-weight:800">{{ $wTotalKgSum ?: '—' }}</td>
                    <td></td>
                    <td class="tr" style="font-weight:800">{{ number_format($walkinItems->sum('amount'), 0) }}</td>
                    <td class="tr col-hide-tablet" style="color:#15803d;font-weight:700">
                        {{ $walkinDisc > 0 ? '− '.number_format($walkinDisc, 0) : '—' }}
                    </td>
                    <td class="tr col-hide-tablet" style="color:#7c3aed;font-weight:700">
                        {{ $walkinExtra > 0 ? '+ '.number_format($walkinExtra, 0) : '—' }}
                    </td>
                    <td class="tr" style="color:#16a34a;font-weight:700">{{ number_format($walkinPaid, 0) }}</td>
                    <td class="tr" style="color:#dc2626;font-weight:700">{{ number_format($walkinDue, 0) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

{{-- ── No-item Sales (paying off previous due via sale form, no products) ─── --}}
@if($noItemSales->isNotEmpty())
<div class="card" style="margin-top:18px">
    <div class="card-header" style="padding:12px 16px;background:#fffbeb;border-bottom:1px solid #fde68a">
        <h3 style="font-size:.95rem;color:#92400e;margin:0">
            <i class="fas fa-hand-holding-dollar"></i>
            বাকী পরিশোধ (পণ্য ছাড়া বিক্রয়) — পূর্বের বাকীর বিপরীতে
        </h3>
    </div>
    <div class="table-wrap">
        <table class="data-table sale-detail-table">
            <thead>
                <tr>
                    <th class="tc">চালান নং</th>
                    <th>কাস্টমার</th>
                    <th class="tc">তারিখ</th>
                    <th class="tc">পরিশোধ মোড</th>
                    <th class="tr">পরিশোধ (৳)</th>
                    <th class="tc">ইউজার</th>
                    <th class="tc">সময়</th>
                </tr>
            </thead>
            <tbody>
                @foreach($noItemSales as $s)
                <tr>
                    <td class="tc mono">
                        <a href="{{ route('sales.show', $s->id) }}" class="link-primary">
                            {{ str_pad($s->id, 6, '0', STR_PAD_LEFT) }}
                        </a>
                    </td>
                    <td>{{ $s->customer?->name ?? '<span style="color:#94a3b8">ওয়াক-ইন</span>' }}</td>
                    <td class="tc">{{ \Carbon\Carbon::parse($s->sale_date)->format('d/m/Y') }}</td>
                    <td class="tc">{{ $s->payment_method ?: '—' }}</td>
                    <td class="tr" style="color:#16a34a;font-weight:600">
                        {{ number_format($s->paid_amount, 0) }}
                    </td>
                    <td class="tc" style="font-size:.78rem;color:#64748b">{{ $s->user?->name ?? '—' }}</td>
                    <td class="tc" style="font-size:.78rem;color:#64748b;white-space:nowrap">
                        {{ \Carbon\Carbon::parse($s->created_at)->format('h:i:s a') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="tfoot-summary">
                    <td colspan="4" style="text-align:right;font-weight:700;padding-right:16px">সর্বমোট</td>
                    <td class="tr" style="color:#16a34a;font-weight:800">{{ number_format($noItemSales->sum('paid_amount'), 0) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

{{-- ── Extra Cost Breakdown Table ──────────────────────────────── --}}
@if($saleExtraCosts->isNotEmpty())
<div class="card" style="margin-top:18px">
    <div class="card-header" style="padding:12px 16px;background:linear-gradient(135deg,#faf5ff,#ede9fe);border-bottom:1px solid #ddd6fe;display:flex;justify-content:space-between;align-items:center">
        <h3 style="font-size:.95rem;color:#6d28d9;margin:0">
            <i class="fas fa-coins"></i> অতিরিক্ত খরচের বিবরণ
        </h3>
        <span style="font-size:.78rem;color:#7c3aed;font-weight:600">
            মোট: ৳ {{ number_format($saleExtraCosts->sum('amount'), 0) }}
        </span>
    </div>
    <div class="table-wrap">
        <table class="data-table sale-detail-table">
            <thead>
                <tr>
                    <th class="tc">চালান নং</th>
                    <th>কাস্টমার</th>
                    <th class="tc">তারিখ</th>
                    <th>ক্যাটাগরি</th>
                    <th class="tr">পরিমাণ (৳)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($saleExtraCosts as $ec)
                <tr>
                    <td class="tc mono">
                        <a href="{{ route('sales.show', $ec->sale_id) }}" class="link-primary">
                            {{ str_pad($ec->sale_id, 6, '0', STR_PAD_LEFT) }}
                        </a>
                    </td>
                    <td>
                        @if($ec->customer_name)
                            {{ $ec->customer_name }}
                        @else
                            <span style="color:#94a3b8">ওয়াক-ইন</span>
                        @endif
                    </td>
                    <td class="tc" style="font-size:.8rem;color:#64748b;white-space:nowrap">
                        {{ \Carbon\Carbon::parse($ec->sale_date)->format('d/m/Y') }}
                    </td>
                    <td>
                        <span style="display:inline-flex;align-items:center;gap:5px;background:#ede9fe;color:#6d28d9;
                                     padding:2px 10px;border-radius:20px;font-size:.78rem;font-weight:600">
                            <i class="fas fa-tag" style="font-size:.65rem"></i>
                            {{ $ec->category_name }}
                        </span>
                    </td>
                    <td class="tr" style="color:#7c3aed;font-weight:700">
                        + ৳ {{ number_format($ec->amount, 0) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                {{-- Category subtotals --}}
                @foreach($extraCostByCategory as $cat => $amt)
                <tr style="background:#faf5ff">
                    <td colspan="3" style="text-align:right;font-size:.8rem;color:#6d28d9;padding:5px 10px">
                        {{ $cat }} উপমোট
                    </td>
                    <td style="padding:5px 10px">
                        <span style="background:#ede9fe;color:#6d28d9;padding:1px 10px;border-radius:20px;font-size:.78rem;font-weight:600">
                            {{ $cat }}
                        </span>
                    </td>
                    <td class="tr" style="color:#7c3aed;font-weight:700;padding:5px 10px">
                        ৳ {{ number_format($amt, 0) }}
                    </td>
                </tr>
                @endforeach
                <tr class="tfoot-summary">
                    <td colspan="4" style="text-align:right;font-weight:700;padding-right:16px">সর্বমোট</td>
                    <td class="tr" style="color:#7c3aed;font-weight:800">৳ {{ number_format($saleExtraCosts->sum('amount'), 0) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

{{-- ── Standalone Customer Payments (not tied to any sale) ─────── --}}
@if($standalonePayments->isNotEmpty())
<div class="card" style="margin-top:18px">
    <div class="card-header" style="padding:12px 16px;background:#f0fdf4;border-bottom:1px solid #bbf7d0">
        <h3 style="font-size:.95rem;color:#15803d;margin:0">
            <i class="fas fa-money-bill-wave"></i>
            কাস্টমার পরিশোধ (বিক্রয় ছাড়া) — পূর্বের বাকীর বিপরীতে
        </h3>
    </div>
    <div class="table-wrap">
        <table class="data-table sale-detail-table">
            <thead>
                <tr>
                    <th class="tc">#</th>
                    <th>কাস্টমার</th>
                    <th class="tc">তারিখ</th>
                    <th class="tc">পদ্ধতি</th>
                    <th>মন্তব্য</th>
                    <th class="tr">পরিশোধ (৳)</th>
                    <th class="tc">সময়</th>
                </tr>
            </thead>
            <tbody>
                @foreach($standalonePayments as $p)
                <tr>
                    <td class="tc mono">{{ str_pad($p->id, 6, '0', STR_PAD_LEFT) }}</td>
                    <td>{{ $p->customer?->name ?? '—' }}</td>
                    <td class="tc">{{ \Carbon\Carbon::parse($p->payment_date)->format('Y-m-d') }}</td>
                    <td class="tc">{{ $p->payment_method ?: '—' }}</td>
                    <td style="color:#64748b;font-size:.82rem">{{ $p->notes ?: '—' }}</td>
                    <td class="tr" style="color:#16a34a;font-weight:600">
                        {{ number_format($p->amount, 0) }}
                    </td>
                    <td class="tc" style="font-size:.78rem;color:#64748b;white-space:nowrap">
                        {{ \Carbon\Carbon::parse($p->created_at)->format('h:i:s a') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="tfoot-summary">
                    <td colspan="5" style="text-align:right;font-weight:700;padding-right:16px">সর্বমোট</td>
                    <td class="tr" style="color:#16a34a;font-weight:800">{{ number_format($grandStandalone, 0) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

{{-- ── Grand Combined Total ─────────────────────────────────────── --}}
@if($noItemSales->isNotEmpty() || $standalonePayments->isNotEmpty())
<div style="margin-top:14px;padding:12px 20px;background:#1e293b;border-radius:8px;display:flex;align-items:center;gap:24px;flex-wrap:wrap">
    <span style="color:#94a3b8;font-size:.82rem">সর্বমোট পরিশোধ =</span>
    <span style="color:#fff;font-size:.9rem">
        ৳ {{ number_format($grandItemPaid, 0) }}
        <span style="color:#94a3b8;font-size:.78rem">(বিক্রয়)</span>
    </span>
    @if($noItemSales->isNotEmpty())
    <span style="color:#94a3b8">+</span>
    <span style="color:#fff;font-size:.9rem">
        ৳ {{ number_format($noItemSales->sum('paid_amount'), 0) }}
        <span style="color:#94a3b8;font-size:.78rem">(পণ্য ছাড়া)</span>
    </span>
    @endif
    @if($standalonePayments->isNotEmpty())
    <span style="color:#94a3b8">+</span>
    <span style="color:#fff;font-size:.9rem">
        ৳ {{ number_format($grandStandalone, 0) }}
        <span style="color:#94a3b8;font-size:.78rem">(কাস্টমার পরিশোধ)</span>
    </span>
    @endif
    <span style="color:#94a3b8">=</span>
    <span style="color:#4ade80;font-size:1.05rem;font-weight:800">৳ {{ number_format($grandPaid, 0) }}</span>
    <span style="margin-left:auto;color:#94a3b8;font-size:.82rem">মোট বাকী:</span>
    <span style="color:#f87171;font-size:1rem;font-weight:800">৳ {{ number_format($grandDue, 0) }}</span>
</div>
@endif

</div>{{-- /#reportDetailView --}}

{{-- ══ ইউজার ভিত্তিক — সব ধরনের লেনদেন একটাই টেবিলে (admin tab) ══ --}}
@php
    $reportItemSales  = $sales->filter(fn($s) => !$noItemSales->pluck('id')->contains($s->id));
    $itemByUser       = $reportItemSales->groupBy(fn($s) => ($s->user?->name ?? 'অজানা'));
    $noItemByUser     = $noItemSales->groupBy(fn($s) => ($s->user?->name ?? 'অজানা'));
    $standaloneByUser = $standalonePayments->groupBy(fn($p) => ($p->user?->name ?? 'অজানা'));
    $allUserNames = $itemByUser->keys()
        ->merge($noItemByUser->keys())
        ->merge($standaloneByUser->keys())
        ->unique()
        ->sortByDesc(fn($n) => ($itemByUser->get($n)?->sum('total_amount') ?? 0))
        ->values();
@endphp
<div id="reportUserView" style="display:none">

{{-- Summary table FIRST ──────────────────────────────────── --}}
@php $uSummaryRows = collect($userSummary)->values(); @endphp
@if($uSummaryRows->isNotEmpty())
<div class="card" style="margin-bottom:20px">
    <div class="card-header" style="display:flex;align-items:center;gap:8px">
        <i class="fas fa-users" style="color:#0d9488"></i>
        <strong>ইউজার ভিত্তিক বিক্রয়</strong>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ইউজার</th>
                    <th style="text-align:right">বিক্রয় সংখ্যা</th>
                    <th style="text-align:right">মোট বিক্রয়</th>
                    <th style="text-align:right">পরিশোধ</th>
                    <th style="text-align:right">বাকী</th>
                </tr>
            </thead>
            <tbody>
                @foreach($uSummaryRows as $row)
                <tr>
                    <td><strong>{{ $row['name'] }}</strong></td>
                    <td style="text-align:right">{{ $row['count'] }}</td>
                    <td style="text-align:right;font-weight:700">৳ {{ number_format($row['total'], 0) }}</td>
                    <td style="text-align:right;color:#16a34a;font-weight:700">৳ {{ number_format($row['paid'], 0) }}</td>
                    <td style="text-align:right;color:{{ $row['due'] > 0 ? '#dc2626' : '#94a3b8' }};font-weight:{{ $row['due'] > 0 ? '700' : '400' }}">
                        {{ $row['due'] > 0 ? '৳ '.number_format($row['due'], 0) : '—' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="tfoot-summary">
                    <td><strong>মোট</strong></td>
                    <td style="text-align:right;font-weight:700">{{ $uSummaryRows->sum('count') }}</td>
                    <td style="text-align:right;font-weight:800">৳ {{ number_format($uSummaryRows->sum('total'), 0) }}</td>
                    <td style="text-align:right;font-weight:800;color:#16a34a">৳ {{ number_format($uSummaryRows->sum('paid'), 0) }}</td>
                    <td style="text-align:right;font-weight:800;color:#dc2626">৳ {{ number_format($uSummaryRows->sum('due'), 0) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

    @forelse($allUserNames as $uName)
    @php
        $uItem   = $itemByUser->get($uName)       ?? collect();
        $uNoItem = $noItemByUser->get($uName)     ?? collect();
        $uStand  = $standaloneByUser->get($uName) ?? collect();

        $uTotal      = $uItem->sum('total_amount');
        $uPaid       = $uItem->sum('paid_amount');
        $uDue        = $uItem->sum('due_amount');
        $uDisc       = $uItem->sum('discount');
        $uExtra      = $uItem->sum('extra_cost');
        $uLabor      = $uItem->sum('labor_cost');
        $uNoItemPaid = $uNoItem->sum('paid_amount');
        $uStandPaid  = $uStand->sum('amount');
        $uCashTotal  = $uPaid + $uNoItemPaid + $uStandPaid;

        // Merge all rows into one collection, sorted by date
        $uRows = collect();
        foreach ($uItem as $s) {
            $uRows->push(['type'=>'sale','date'=>$s->sale_date,'obj'=>$s]);
        }
        foreach ($uNoItem as $s) {
            $uRows->push(['type'=>'noitem','date'=>$s->sale_date,'obj'=>$s]);
        }
        foreach ($uStand as $p) {
            $uRows->push(['type'=>'standalone','date'=>$p->payment_date,'obj'=>$p]);
        }
        $uRows = $uRows->sortBy('date')->values();
    @endphp
    <div class="card" style="margin-bottom:18px">
        <div class="card-header" style="background:linear-gradient(135deg,#eff6ff,#dbeafe);border-bottom:1px solid #bfdbfe;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
            <h3 style="font-size:.95rem;color:#1d4ed8;margin:0">
                <i class="fas fa-user" style="margin-right:6px"></i>{{ $uName }}
            </h3>
            <div style="display:flex;gap:16px;font-size:.82rem;flex-wrap:wrap;align-items:center">
                <span style="color:var(--text-secondary)"><strong style="color:var(--text)">{{ $uRows->count() }}টি</strong> লেনদেন</span>
                @if($uTotal > 0)<span style="color:#1d4ed8;font-weight:700">বিক্রয় ৳ {{ number_format($uTotal,0) }}</span>@endif
                @if($uDue > 0)<span style="color:#dc2626;font-weight:600">বাকী ৳ {{ number_format($uDue,0) }}</span>@endif
                @if($uDisc > 0)<span style="color:#9333ea;font-weight:600">ছাড় ৳ {{ number_format($uDisc,0) }}</span>@endif
                @if($uExtra > 0)<span style="color:#ea580c;font-weight:600">অতি. খরচ ৳ {{ number_format($uExtra,0) }}</span>@endif
                @if($uLabor > 0)<span style="color:#ca8a04;font-weight:600">শ্রমিক ৳ {{ number_format($uLabor,0) }}</span>@endif
                <span style="color:#16a34a;font-weight:700;border-left:1px solid #bfdbfe;padding-left:16px">নগদ প্রাপ্তি ৳ {{ number_format($uCashTotal,0) }}</span>
            </div>
        </div>
        <div class="table-wrap">
            <table class="data-table sale-detail-table">
                <thead>
                    <tr>
                        <th class="tc">নং</th>
                        <th class="tc">ধরন</th>
                        <th>কাস্টমার</th>
                        <th class="tc col-hide-tablet">তারিখ</th>
                        <th class="tr">মোট মূল্য</th>
                        <th class="tr">পরিশোধ</th>
                        <th class="tr">বাকী</th>
                        <th class="tc col-hide-tablet">সময়</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($uRows as $row)
                    @php $obj = $row['obj']; $type = $row['type']; @endphp
                    <tr>
                        <td class="tc mono">
                            @if($type === 'standalone')
                                {{ str_pad($obj->id, 6, '0', STR_PAD_LEFT) }}
                            @else
                                <a href="{{ route('sales.show', $obj->id) }}" class="link-primary">
                                    {{ str_pad($obj->id, 6, '0', STR_PAD_LEFT) }}
                                </a>
                            @endif
                        </td>
                        <td class="tc">
                            @if($type === 'sale')
                                <span style="background:#dbeafe;color:#1d4ed8;padding:1px 8px;border-radius:20px;font-size:.72rem;font-weight:700;white-space:nowrap">বিক্রয়</span>
                            @elseif($type === 'noitem')
                                <span style="background:#fef3c7;color:#92400e;padding:1px 8px;border-radius:20px;font-size:.72rem;font-weight:700;white-space:nowrap">বাকী পরিশোধ</span>
                            @else
                                <span style="background:#dcfce7;color:#15803d;padding:1px 8px;border-radius:20px;font-size:.72rem;font-weight:700;white-space:nowrap">পরিশোধ</span>
                            @endif
                        </td>
                        <td>{{ $obj->customer?->name ?? ($type === 'sale' ? '— ওয়াক-ইন' : '—') }}</td>
                        <td class="tc col-hide-tablet" style="font-size:.8rem;color:#64748b;white-space:nowrap">
                            {{ \Carbon\Carbon::parse($row['date'])->format('d/m/Y') }}
                        </td>
                        <td class="tr" style="font-weight:600">
                            @if($type === 'sale')
                                ৳ {{ number_format($obj->total_amount,0) }}
                            @else
                                <span style="color:#94a3b8">—</span>
                            @endif
                        </td>
                        <td class="tr" style="color:#16a34a;font-weight:600">
                            ৳ {{ number_format($type === 'standalone' ? $obj->amount : $obj->paid_amount, 0) }}
                        </td>
                        <td class="tr">
                            @if($type === 'sale' && $obj->due_amount > 0)
                                <span style="color:#dc2626;font-weight:600">৳ {{ number_format($obj->due_amount,0) }}</span>
                            @else
                                <span style="color:#94a3b8">—</span>
                            @endif
                        </td>
                        <td class="tc col-hide-tablet" style="font-size:.78rem;color:#64748b;white-space:nowrap">
                            {{ \Carbon\Carbon::parse($obj->created_at)->format('h:i a') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="tfoot-summary">
                        <td colspan="4" style="text-align:right;font-weight:700;padding-right:16px">সর্বমোট</td>
                        <td class="tr" style="font-weight:800">৳ {{ number_format($uTotal,0) }}</td>
                        <td class="tr" style="color:#16a34a;font-weight:800">৳ {{ number_format($uCashTotal,0) }}</td>
                        <td class="tr" style="color:#dc2626;font-weight:800">{{ $uDue > 0 ? '৳ '.number_format($uDue,0) : '—' }}</td>
                        <td class="col-hide-tablet"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @empty
    <div style="text-align:center;padding:60px;color:var(--text-secondary)">
        <i class="fas fa-users" style="font-size:2.5rem;opacity:.25;display:block;margin-bottom:12px"></i>
        এই সময়কালে কোনো লেনদেন নেই
    </div>
    @endforelse
</div>
@endif {{-- canManageShop --}}

{{-- ══ Staff: নিজের transaction detail ══════════════════════════ --}}
@if(!auth()->user()->canManageShop())
@php
    $staffItemsByDate = $namedItems->groupBy('date')->sortKeys();
@endphp
@if($staffItemsByDate->isNotEmpty())
<div class="card" style="margin-bottom:20px">
    <div class="card-header"><i class="fas fa-receipt" style="color:#0d9488"></i> <strong>আমার বিক্রয় বিবরণ</strong></div>
    @foreach($staffItemsByDate as $date => $rows)
    <div style="padding:10px 16px 4px;font-weight:700;color:var(--text-secondary);font-size:.82rem;border-top:1px solid var(--border)">
        <i class="fas fa-calendar-day"></i> তারিখ: {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}
        <span style="float:right;color:#94a3b8">{{ $rows->count() }}টি আইটেম</span>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>চালান নং</th>
                    <th>কাস্টমার নাম</th>
                    <th>আইটেম নাম</th>
                    <th style="text-align:right">পরিমাণ</th>
                    <th style="text-align:right">বিক্রয় মূল্য</th>
                    <th style="text-align:right">মোট মূল্য</th>
                    <th style="text-align:right">জমা</th>
                    <th style="text-align:right">বাকী</th>
                    <th>সময়</th>
                </tr>
            </thead>
            <tbody>
                @php $staffLastSaleId = null; @endphp
                @foreach($rows as $row)
                @php $staffIsNew = ($row->sale_id !== $staffLastSaleId); $staffLastSaleId = $row->sale_id; @endphp
                <tr>
                    <td class="mono"><a href="{{ route('sales.show', $row->sale_id) }}" style="color:#0d9488">#{{ str_pad($row->sale_id,6,'0',STR_PAD_LEFT) }}</a></td>
                    <td>{{ $row->customer_name ?? 'ওয়াক-ইন' }}</td>
                    <td>{{ $row->item_name }}</td>
                    <td style="text-align:right">{{ number_format($row->qty, 0) }}</td>
                    <td style="text-align:right">৳ {{ number_format($row->rate, 0) }}</td>
                    <td style="text-align:right;font-weight:700">৳ {{ number_format($row->amount, 0) }}</td>
                    <td style="text-align:right;color:#16a34a">
                        @if($staffIsNew) ৳ {{ number_format($row->paid_amount, 0) }} @else — @endif
                    </td>
                    <td style="text-align:right;color:{{ $staffIsNew && $row->due_amount > 0 ? '#dc2626' : '#94a3b8' }}">
                        @if($staffIsNew && $row->due_amount > 0) ৳ {{ number_format($row->due_amount,0) }} @else — @endif
                    </td>
                    <td style="font-size:.8rem;color:#94a3b8">{{ \Carbon\Carbon::parse($row->sale_time)->format('h:i a') }}</td>
                </tr>
                @endforeach
            </tbody>
            @php $staffUniq = $rows->unique('sale_id'); @endphp
            <tfoot>
                <tr class="tfoot-summary">
                    <td colspan="5" style="text-align:right;font-weight:700">সর্বমোট</td>
                    <td style="text-align:right;font-weight:800">৳ {{ number_format($rows->sum('amount'), 0) }}</td>
                    <td style="text-align:right;font-weight:800;color:#16a34a">৳ {{ number_format($staffUniq->sum('paid_amount'), 0) }}</td>
                    <td style="text-align:right;font-weight:800;color:#dc2626">৳ {{ number_format($staffUniq->sum('due_amount'), 0) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endforeach
</div>
@endif

{{-- ── ওয়াক-ইন বিক্রয় (staff's own) ──────────────────────────── --}}
@if($walkinItems->isNotEmpty())
<div class="card" style="margin-top:18px;margin-bottom:20px">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;background:linear-gradient(135deg,#f0fdfa,#ccfbf1);border-bottom:1px solid #99f6e4">
        <h3 style="font-size:.95rem;color:#0f766e;margin:0">
            <i class="fas fa-person-walking"></i> ওয়াক-ইন বিক্রয়
        </h3>
        <span style="font-size:.8rem;color:#0f766e">{{ $walkinItems->count() }} টি আইটেম</span>
    </div>
    <div class="table-wrap">
        <table class="data-table sale-detail-table">
            <thead>
                <tr>
                    <th class="tc">চালান নং</th>
                    <th class="tc col-hide-tablet">তারিখ</th>
                    <th>আইটেম নাম</th>
                    <th class="tc">পরিমাণ</th>
                    <th class="tr">মোট মূল্য</th>
                    <th class="tr">জমা</th>
                    <th class="tr">বাকী</th>
                    <th class="tc col-hide-tablet">সময়</th>
                </tr>
            </thead>
            <tbody>
                @php $swRunning = 0; $swLastSale = null; @endphp
                @foreach($walkinItems as $row)
                @php
                    $swRunning += $row->amount;
                    $swIsNew = ($row->sale_id !== $swLastSale);
                    $swLastSale = $row->sale_id;
                @endphp
                <tr class="{{ $swIsNew ? 'new-sale-row' : '' }}">
                    <td class="tc mono">
                        <a href="{{ route('sales.show', $row->sale_id) }}" class="link-primary">
                            {{ str_pad($row->sale_id, 6, '0', STR_PAD_LEFT) }}
                        </a>
                    </td>
                    <td class="tc col-hide-tablet" style="font-size:.78rem;color:#64748b;white-space:nowrap">
                        @if($swIsNew) {{ \Carbon\Carbon::parse($row->date)->format('d/m/Y') }} @else — @endif
                    </td>
                    <td>{{ $row->item_name }}</td>
                    <td class="tc">{{ (int)$row->qty }}</td>
                    <td class="tr" style="font-weight:600">{{ number_format($swRunning, 0) }}</td>
                    <td class="tr" style="color:#16a34a">
                        @if($swIsNew) {{ number_format($row->paid_amount, 0) }} @else — @endif
                    </td>
                    <td class="tr">
                        @if($swIsNew)
                            @if($row->due_amount > 0)
                                <span style="color:#dc2626;font-weight:600">{{ number_format($row->due_amount, 0) }}</span>
                            @else <span style="color:#16a34a">—</span>
                            @endif
                        @else — @endif
                    </td>
                    <td class="tc col-hide-tablet" style="font-size:.78rem;color:#64748b;white-space:nowrap">
                        {{ \Carbon\Carbon::parse($row->sale_time)->format('h:i:s a') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="tfoot-summary">
                    <td colspan="4" style="text-align:right;font-weight:700;padding-right:16px">সর্বমোট</td>
                    <td class="tr" style="font-weight:800">{{ number_format($walkinItems->sum('amount'), 0) }}</td>
                    <td class="tr" style="color:#16a34a;font-weight:700">{{ number_format($walkinPaid, 0) }}</td>
                    <td class="tr" style="color:#dc2626;font-weight:700">{{ number_format($walkinDue, 0) }}</td>
                    <td class="col-hide-tablet"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

{{-- ── বাকী পরিশোধ (staff's own no-item sales) ─────────────────── --}}
@if($noItemSales->isNotEmpty())
<div class="card" style="margin-top:18px;margin-bottom:20px">
    <div class="card-header" style="padding:12px 16px;background:#fffbeb;border-bottom:1px solid #fde68a">
        <h3 style="font-size:.95rem;color:#92400e;margin:0">
            <i class="fas fa-hand-holding-dollar"></i>
            বাকী পরিশোধ (পণ্য ছাড়া বিক্রয়) — পূর্বের বাকীর বিপরীতে
        </h3>
    </div>
    <div class="table-wrap">
        <table class="data-table sale-detail-table">
            <thead>
                <tr>
                    <th class="tc">চালান নং</th>
                    <th>কাস্টমার</th>
                    <th class="tc">তারিখ</th>
                    <th class="tc">পরিশোধ মোড</th>
                    <th class="tr">পরিশোধ (৳)</th>
                    <th class="tc">সময়</th>
                </tr>
            </thead>
            <tbody>
                @foreach($noItemSales as $s)
                <tr>
                    <td class="tc mono">
                        <a href="{{ route('sales.show', $s->id) }}" class="link-primary">
                            {{ str_pad($s->id, 6, '0', STR_PAD_LEFT) }}
                        </a>
                    </td>
                    <td>{{ $s->customer?->name ?? '<span style="color:#94a3b8">ওয়াক-ইন</span>' }}</td>
                    <td class="tc">{{ \Carbon\Carbon::parse($s->sale_date)->format('d/m/Y') }}</td>
                    <td class="tc">{{ $s->payment_method ?: '—' }}</td>
                    <td class="tr" style="color:#16a34a;font-weight:600">
                        {{ number_format($s->paid_amount, 0) }}
                    </td>
                    <td class="tc" style="font-size:.78rem;color:#64748b;white-space:nowrap">
                        {{ \Carbon\Carbon::parse($s->created_at)->format('h:i:s a') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="tfoot-summary">
                    <td colspan="4" style="text-align:right;font-weight:700;padding-right:16px">সর্বমোট</td>
                    <td class="tr" style="color:#16a34a;font-weight:800">{{ number_format($noItemSales->sum('paid_amount'), 0) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

{{-- ── অতিরিক্ত খরচের বিবরণ (staff's own) ─────────────────────── --}}
@if($saleExtraCosts->isNotEmpty())
<div class="card" style="margin-top:18px;margin-bottom:20px">
    <div class="card-header" style="padding:12px 16px;background:linear-gradient(135deg,#faf5ff,#ede9fe);border-bottom:1px solid #ddd6fe;display:flex;justify-content:space-between;align-items:center">
        <h3 style="font-size:.95rem;color:#6d28d9;margin:0">
            <i class="fas fa-coins"></i> অতিরিক্ত খরচের বিবরণ
        </h3>
        <span style="font-size:.78rem;color:#7c3aed;font-weight:600">
            মোট: ৳ {{ number_format($saleExtraCosts->sum('amount'), 0) }}
        </span>
    </div>
    <div class="table-wrap">
        <table class="data-table sale-detail-table">
            <thead>
                <tr>
                    <th class="tc">চালান নং</th>
                    <th>কাস্টমার</th>
                    <th class="tc">তারিখ</th>
                    <th>ক্যাটাগরি</th>
                    <th class="tr">পরিমাণ (৳)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($saleExtraCosts as $ec)
                <tr>
                    <td class="tc mono">
                        <a href="{{ route('sales.show', $ec->sale_id) }}" class="link-primary">
                            {{ str_pad($ec->sale_id, 6, '0', STR_PAD_LEFT) }}
                        </a>
                    </td>
                    <td>
                        @if($ec->customer_name)
                            {{ $ec->customer_name }}
                        @else
                            <span style="color:#94a3b8">ওয়াক-ইন</span>
                        @endif
                    </td>
                    <td class="tc" style="font-size:.8rem;color:#64748b;white-space:nowrap">
                        {{ \Carbon\Carbon::parse($ec->sale_date)->format('d/m/Y') }}
                    </td>
                    <td>
                        <span style="display:inline-flex;align-items:center;gap:5px;background:#ede9fe;color:#6d28d9;
                                     padding:2px 10px;border-radius:20px;font-size:.78rem;font-weight:600">
                            <i class="fas fa-tag" style="font-size:.65rem"></i>
                            {{ $ec->category_name }}
                        </span>
                    </td>
                    <td class="tr" style="color:#7c3aed;font-weight:700">
                        + ৳ {{ number_format($ec->amount, 0) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="tfoot-summary">
                    <td colspan="4" style="text-align:right;font-weight:700;padding-right:16px">সর্বমোট</td>
                    <td class="tr" style="color:#7c3aed;font-weight:800">৳ {{ number_format($saleExtraCosts->sum('amount'), 0) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

@endif

@endsection

@push('styles')
<style>
.report-tab-btn {
    padding: 7px 18px;
    border: none;
    background: var(--surface);
    color: var(--text-secondary);
    font-family: inherit;
    font-size: .83rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: background .15s, color .15s;
    border-right: 1.5px solid var(--border);
}
.report-tab-btn:last-child { border-right: none; }
.report-tab-btn:hover { background: var(--bg); color: var(--text); }
.report-tab-btn.active { background: var(--accent); color: #fff; }

.tc { text-align: center; }
.tr { text-align: right; }
.mono { font-family: 'Inter', monospace; font-size: .8rem; }

.sale-detail-table th,
.sale-detail-table td { padding: 6px 10px; font-size: .82rem; }

/* Highlight first item of each new invoice */
.new-sale-row td { border-top: 2px solid #e2e8f0 !important; }
.new-sale-row:first-child td { border-top: 1px solid var(--border) !important; }

.tfoot-summary td {
    background: var(--surface-2);
    padding: 8px 10px;
    font-size: .85rem;
    border-top: 2px solid var(--border);
}

/* ── 5-card grid for sales report ─────────────────────────── */
.sales-stats-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 16px;
    margin-bottom: 20px;
}
@media (max-width: 1200px) {
    .sales-stats-grid { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 768px) {
    .sales-stats-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
}
@media (max-width: 480px) {
    .sales-stats-grid { grid-template-columns: 1fr 1fr; gap: 8px; }
}

/* ── Filter form tablet wrap ───────────────────────────────── */
@media (max-width: 900px) {
    .filter-form { flex-wrap: wrap; gap: 10px; }
    .filter-form .form-group-field { min-width: 140px; flex: 1 1 140px; }
    .filter-form .btn { align-self: flex-end; }
}

/* ── Table: hide less-important columns on tablet ─────────── */
@media (max-width: 900px) {
    .sale-detail-table th,
    .sale-detail-table td { padding: 5px 7px; font-size: .78rem; }
    .col-hide-tablet { display: none; }
}
@media (max-width: 640px) {
    .sale-detail-table th,
    .sale-detail-table td { padding: 4px 5px; font-size: .74rem; }
}

@media print {
    .sidebar, .topbar, .no-print { display: none !important; }
    .main-wrapper { margin-left: 0 !important; }
    .content { padding: 6px !important; }
    .print-header { display: block !important; }
    .card { box-shadow: none !important; border: 1px solid #ccc !important; }
    .sale-detail-table th,
    .sale-detail-table td { font-size: .75rem; padding: 4px 6px; }
}
</style>
@endpush

@push('scripts')
<script>
function setReportView(type) {
    if (type === 'user' && !document.getElementById('reportUserView')) type = 'detail';
    var detailEl = document.getElementById('reportDetailView');
    var userEl   = document.getElementById('reportUserView');
    var btnD     = document.getElementById('btnReportDetail');
    var btnU     = document.getElementById('btnReportUser');
    if (detailEl) detailEl.style.display = type === 'detail' ? '' : 'none';
    if (userEl)   userEl.style.display   = type === 'user'   ? '' : 'none';
    if (btnD) btnD.classList.toggle('active', type === 'detail');
    if (btnU) btnU.classList.toggle('active', type === 'user');
    localStorage.setItem('reportView', type);
}

var savedReportView = localStorage.getItem('reportView') || 'detail';
setReportView(savedReportView);

// Date snap: first calendar snaps both dates, second extends range, both auto-submit
(function () {
    var fromEl = document.getElementById('rptDateFrom');
    var toEl   = document.getElementById('rptDateTo');
    if (!fromEl || !toEl) return;
    function submit() { fromEl.form.requestSubmit ? fromEl.form.requestSubmit() : fromEl.form.submit(); }
    fromEl.addEventListener('change', function () { if (fromEl.value) toEl.value = fromEl.value; submit(); });
    fromEl.addEventListener('input',  function () { if (fromEl.value) toEl.value = fromEl.value; submit(); });
    toEl.addEventListener('change', submit);
    toEl.addEventListener('input',  submit);
})();

(function () {
    var input   = document.getElementById('rsCustomerSearch');
    var idInput = document.getElementById('rsCustomerId');
    if (!input || !idInput) return;

    var drop = document.createElement('div');
    document.body.appendChild(drop);
    drop.style.cssText = 'position:fixed;display:none;z-index:9999;background:#fff;' +
        'border:1px solid #e2e8f0;border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.12);' +
        'overflow-y:auto;max-height:260px';

    function position() {
        var r = input.getBoundingClientRect();
        drop.style.top   = (r.bottom + 4) + 'px';
        drop.style.left  = r.left + 'px';
        drop.style.width = r.width + 'px';
    }
    function show(html) { drop.innerHTML = html; position(); drop.style.display = 'block'; }
    function hide() { drop.style.display = 'none'; drop.innerHTML = ''; }

    var matches = [];
    window.rsSelectCustomer = function (id) {
        var c = matches.find(function (x) { return x.id === id; });
        if (!c) return;
        idInput.value = c.id;
        input.value   = c.name;
        hide();
    };
    window.rsClearCustomer = function () {
        idInput.value = '';
        input.value   = '';
        hide();
    };

    function render() {
        if (!matches.length) {
            show('<div class="suggestion-item" style="color:#94a3b8">কোনো কাস্টমার পাওয়া যায়নি</div>');
            return;
        }
        var html = '<div class="suggestion-item" style="color:#0d9488;font-weight:700" onclick="rsClearCustomer()">— সব কাস্টমার —</div>';
        html += matches.map(function (c) {
            return '<div class="suggestion-item" onclick="rsSelectCustomer(' + c.id + ')">' +
                '<strong style="font-size:.9rem">' + c.name + '</strong>' +
                (c.phone ? '<span style="font-size:.76rem;color:#94a3b8;margin-left:8px">' + c.phone + '</span>' : '') +
                '</div>';
        }).join('');
        show(html);
    }

    var timer;
    input.addEventListener('input', function () {
        var q = input.value.trim();
        idInput.value = '';
        clearTimeout(timer);
        if (!q) { hide(); return; }
        show('<div class="suggestion-item" style="color:#94a3b8">খুঁজছি…</div>');
        timer = setTimeout(function () {
            fetch('{{ route('customers.search') }}?q=' + encodeURIComponent(q), {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
                .then(function (r) { return r.json(); })
                .then(function (data) { matches = data; render(); })
                .catch(function () { show('<div class="suggestion-item" style="color:#94a3b8">খুঁজতে সমস্যা হয়েছে</div>'); });
        }, 250);
    });
    input.addEventListener('focus', function () { if (matches.length) render(); });

    window.addEventListener('scroll', position, true);
    window.addEventListener('resize', position);
    document.addEventListener('click', function (e) {
        if (!input.contains(e.target) && !drop.contains(e.target)) hide();
    });
})();
</script>
@endpush
