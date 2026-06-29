@extends('layouts.app')
@section('title', 'ড্যাশবোর্ড')
@section('page-title', 'ড্যাশবোর্ড')

@section('content')

{{-- Quick Actions --}}
<div style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap">
    <a href="{{ route('sales.create') }}" class="btn btn-primary" style="padding:12px 24px;font-size:.95rem;flex:1;min-width:160px;justify-content:center">
        <i class="fas fa-plus-circle"></i> নতুন বিক্রয়
    </a>
    <a href="{{ route('purchases.create') }}" class="btn btn-secondary" style="padding:12px 24px;font-size:.95rem;flex:1;min-width:160px;justify-content:center">
        <i class="fas fa-truck-ramp-box"></i> পণ্য গ্রহণ
    </a>
    <a href="{{ route('customer-payments.create') }}" class="btn btn-ghost" style="padding:12px 24px;font-size:.95rem;flex:1;min-width:160px;justify-content:center">
        <i class="fas fa-hand-holding-dollar"></i> পরিশোধ নিন
    </a>
    <a href="{{ route('reports.sales') }}" class="btn btn-ghost" style="padding:12px 24px;font-size:.95rem;flex:1;min-width:160px;justify-content:center">
        <i class="fas fa-chart-bar"></i> আজকের রিপোর্ট
    </a>
</div>

{{-- Today Stats --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:12px">
    <div class="stat-card stat-orange">
        <div class="stat-icon"><i class="fas fa-receipt"></i></div>
        <div class="stat-body">
            <span class="stat-label">আজকের বিক্রয়</span>
            <span class="stat-value">৳ {{ number_format($stats['today_sales'], 0) }}</span>
            <span style="font-size:.72rem;color:#b45309;margin-top:2px">মোট ইনভয়েস মূল্য</span>
        </div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-icon"><i class="fas fa-wallet"></i></div>
        <div class="stat-body">
            <span class="stat-label">আজকের পরিশোধ</span>
            <span class="stat-value">৳ {{ number_format($stats['today_paid'], 0) }}</span>
            <span style="font-size:.72rem;color:#15803d;margin-top:2px">নগদ/ব্যাংক জমা</span>
        </div>
    </div>
    <div class="stat-card stat-red" style="background:linear-gradient(135deg,#fee2e2,#fecaca)">
        <div class="stat-icon" style="background:#dc2626"><i class="fas fa-hourglass-half"></i></div>
        <div class="stat-body">
            <span class="stat-label">আজকের বাকী</span>
            <span class="stat-value" style="color:#dc2626">৳ {{ number_format($stats['today_due'], 0) }}</span>
            <span style="font-size:.72rem;color:#b91c1c;margin-top:2px">আদায় হয়নি</span>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:20px">
    <div class="stat-card" style="background:linear-gradient(135deg,#fdf4ff,#fae8ff)">
        <div class="stat-icon" style="background:#9333ea"><i class="fas fa-scale-unbalanced-flip"></i></div>
        <div class="stat-body">
            <span class="stat-label">মোট কাস্টমার বকেয়া</span>
            <span class="stat-value" style="color:#9333ea">৳ {{ number_format($stats['total_customer_due'], 0) }}</span>
            <span style="font-size:.72rem;color:#7e22ce;margin-top:2px">সব কাস্টমারের মিলিয়ে</span>
        </div>
    </div>
    <div class="stat-card stat-blue">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-body">
            <span class="stat-label">মোট কাস্টমার</span>
            <span class="stat-value">{{ number_format($stats['customers']) }}</span>
            <span style="font-size:.72rem;color:#1d4ed8;margin-top:2px">নিবন্ধিত</span>
        </div>
    </div>
    <div class="stat-card" style="background:linear-gradient(135deg,#fff7ed,#fed7aa)">
        <div class="stat-icon" style="background:#ea580c"><i class="fas fa-triangle-exclamation"></i></div>
        <div class="stat-body">
            <span class="stat-label">কম / শেষ স্টক</span>
            <span class="stat-value" style="color:#ea580c">{{ $stats['low_stock_count'] }} / {{ $stats['out_stock_count'] }}</span>
            <span style="font-size:.72rem;color:#c2410c;margin-top:2px">আইটেম সংখ্যা</span>
        </div>
    </div>
</div>

{{-- আজকের ইউজার ভিত্তিক বিক্রয় (lazy loaded) --}}
<div class="card" style="margin-bottom:20px">
    <div class="card-header">
        <h3><i class="fas fa-users"></i> আজকের ইউজার ভিত্তিক বিক্রয়</h3>
        <a href="{{ route('reports.sales') }}" class="card-link">বিস্তারিত <i class="fas fa-chevron-right"></i></a>
    </div>
    <div id="dashUserSummary" style="padding:12px 0">
        {{-- skeleton --}}
        <div id="dashUserSummaryLoader" style="padding:16px 20px;display:flex;flex-direction:column;gap:10px">
            @for($i=0;$i<3;$i++)
            <div style="height:36px;background:linear-gradient(90deg,var(--surface-2) 25%,var(--border) 50%,var(--surface-2) 75%);background-size:200% 100%;border-radius:6px;animation:dashSkeleton 1.2s infinite"></div>
            @endfor
        </div>
        <div id="dashUserSummaryContent" style="display:none">
            <div class="table-wrap">
                <table class="data-table" id="dashUserTable">
                    <thead>
                        <tr>
                            <th>ইউজার</th>
                            <th style="text-align:right">বিক্রয় সংখ্যা</th>
                            <th style="text-align:right">মোট বিক্রয়</th>
                            <th style="text-align:right">পরিশোধ</th>
                            <th style="text-align:right">বাকী</th>
                        </tr>
                    </thead>
                    <tbody id="dashUserBody"></tbody>
                    <tfoot id="dashUserFoot" style="display:none">
                        <tr class="tfoot-summary">
                            <td style="font-weight:700">মোট</td>
                            <td id="dashFootCount" style="text-align:right;font-weight:800"></td>
                            <td id="dashFootTotal" style="text-align:right;font-weight:800"></td>
                            <td id="dashFootPaid"  style="text-align:right;font-weight:800;color:#16a34a"></td>
                            <td id="dashFootDue"   style="text-align:right;font-weight:800;color:#dc2626"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
<style>
@keyframes dashSkeleton {
    0%   { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}
</style>
<script>
document.addEventListener('turbo:load', function() {
    fetch('{{ route('dashboard.user-summary') }}', {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(function(r){ return r.json(); })
    .then(function(data) {
        var body = document.getElementById('dashUserBody');
        var foot = document.getElementById('dashUserFoot');
        if (!body) return;

        if (!data.rows || !data.rows.length) {
            body.innerHTML = '<tr><td colspan="5" class="empty-row">আজ কোনো বিক্রয় নেই</td></tr>';
        } else {
            var fmt = function(n){ return '৳ ' + Number(n).toLocaleString('en-IN', {maximumFractionDigits:0}); };
            body.innerHTML = data.rows.map(function(r) {
                return '<tr>'
                    + '<td><strong>' + r.name + '</strong></td>'
                    + '<td style="text-align:right">' + r.count + '</td>'
                    + '<td style="text-align:right;font-weight:700">' + fmt(r.total) + '</td>'
                    + '<td style="text-align:right;color:#16a34a">' + fmt(r.paid) + '</td>'
                    + '<td style="text-align:right;color:' + (r.due > 0 ? '#dc2626' : '#94a3b8') + '">'
                    + (r.due > 0 ? fmt(r.due) : '—') + '</td>'
                    + '</tr>';
            }).join('');
            document.getElementById('dashFootCount').textContent = data.totals.count;
            document.getElementById('dashFootTotal').textContent = fmt(data.totals.total);
            document.getElementById('dashFootPaid').textContent  = fmt(data.totals.paid);
            document.getElementById('dashFootDue').textContent   = data.totals.due > 0 ? fmt(data.totals.due) : '—';
            if (foot) foot.style.display = '';
        }

        document.getElementById('dashUserSummaryLoader').style.display  = 'none';
        document.getElementById('dashUserSummaryContent').style.display = '';
    })
    .catch(function() {
        var loader = document.getElementById('dashUserSummaryLoader');
        if (loader) loader.innerHTML = '<p style="padding:16px;color:#94a3b8;font-size:.85rem">লোড করা যায়নি।</p>';
    });
}, { once: true });
</script>

{{-- 7-Day Trend Chart --}}
<div class="card" style="margin-bottom:20px">
    <div class="card-header">
        <h3><i class="fas fa-chart-line"></i> গত ৭ দিনের বিক্রয়</h3>
        <a href="{{ route('reports.sales') }}" class="card-link">বিস্তারিত <i class="fas fa-chevron-right"></i></a>
    </div>
    <div style="padding:16px 20px">
        @php
            $maxVal = $sevenDayTrend->max('total') ?: 1;
        @endphp
        <div style="display:flex;align-items:flex-end;gap:8px;height:120px;padding-bottom:28px;position:relative">
            {{-- Gridlines --}}
            @foreach([100,66,33] as $pct)
            <div style="position:absolute;left:0;right:0;bottom:calc(28px + {{$pct}}%);border-top:1px dashed var(--border);pointer-events:none"></div>
            @endforeach

            @foreach($sevenDayTrend as $day)
            @php
                $barPct = $maxVal > 0 ? ($day['total'] / $maxVal * 100) : 0;
                $isToday = \Carbon\Carbon::createFromFormat('d/m', $day['date'])->isSameDay(today());
            @endphp
            <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;height:100%;justify-content:flex-end">
                @if($day['total'] > 0)
                <div style="font-size:.62rem;color:var(--text-secondary);white-space:nowrap">৳{{ number_format($day['total']/1000,0) }}ক</div>
                @endif
                <div style="width:100%;background:{{ $isToday ? 'var(--accent)' : 'var(--surface-2)' }};
                            border-radius:5px 5px 0 0;
                            height:{{ max(4, $barPct) }}%;
                            border:1.5px solid {{ $isToday ? 'var(--accent)' : 'var(--border)' }};
                            transition:height .3s ease;min-height:4px">
                </div>
                <div style="font-size:.65rem;color:{{ $isToday ? 'var(--accent)' : 'var(--text-secondary)' }};font-weight:{{ $isToday ? '700' : '400' }};margin-top:4px;text-align:center">
                    {{ $day['date'] }}
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Bottom grid --}}
<div class="bottom-grid">
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-clock-rotate-left"></i> সাম্প্রতিক বিক্রয়</h3>
            <a href="{{ route('sales.index') }}" class="card-link">সব দেখুন <i class="fas fa-chevron-right"></i></a>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr><th>ইনভয়েস</th><th>কাস্টমার</th><th>মোট</th><th>বাকী</th></tr>
                </thead>
                <tbody>
                    @forelse($recent_sales as $sale)
                    <tr>
                        <td>
                            <a href="{{ route('sales.show', $sale) }}" class="link-primary mono">
                                #INV-{{ str_pad($sale->id, 4, '0', STR_PAD_LEFT) }}
                            </a>
                        </td>
                        <td>{{ $sale->customer?->name ?? 'ওয়াক-ইন' }}</td>
                        <td>৳ {{ number_format($sale->total_amount, 0) }}</td>
                        <td>
                            @if($sale->due_amount > 0)
                                <span class="badge badge-red">৳ {{ number_format($sale->due_amount, 0) }}</span>
                            @elseif($sale->due_amount < 0)
                                <span class="badge" style="background:#eff6ff;color:#1d4ed8">অগ্রিম</span>
                            @else
                                <span class="badge badge-green">পরিশোধিত</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="empty-row">আজ কোনো বিক্রয় নেই</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-triangle-exclamation"></i> কম স্টক সতর্কতা</h3>
            <a href="{{ route('stock.low') }}" class="card-link">স্টক দেখুন <i class="fas fa-chevron-right"></i></a>
        </div>
        <div class="alert-list">
            @forelse($low_stock as $s)
            <a href="{{ route('items.edit', $s->item) }}" class="alert-item" style="text-decoration:none;color:inherit">
                <div class="alert-icon" style="background:{{ $s->quantity <= 0 ? '#fee2e2' : '#fef9c3' }};color:{{ $s->quantity <= 0 ? '#ef4444' : '#d97706' }}">
                    <i class="fas fa-box"></i>
                </div>
                <div class="alert-body">
                    <span class="alert-name">{{ $s->item->name }}</span>
                    <div class="stock-bar">
                        @php $pct = $s->min_quantity > 0 ? min(100, max(0, ($s->quantity / $s->min_quantity) * 100)) : 0; @endphp
                        <div class="stock-fill" style="width:{{ $pct }}%;background:{{ $pct < 20 ? '#ef4444' : '#f59e0b' }}"></div>
                    </div>
                </div>
                <span class="alert-qty" style="color:{{ $s->quantity <= 0 ? '#ef4444' : '#d97706' }};font-weight:700">
                    {{ $s->quantity }} {{ $s->item->unit ?: 'বস্তা' }}
                </span>
            </a>
            @empty
            <div style="text-align:center;color:#94a3b8;padding:32px">
                <i class="fas fa-circle-check" style="font-size:2rem;margin-bottom:8px;display:block;color:#16a34a"></i>
                সব স্টক পর্যাপ্ত
            </div>
            @endforelse
        </div>
    </div>
</div>

@endsection
