@extends('layouts.app')
@section('title', 'ব্যবসার প্রবৃদ্ধি')
@section('page-title', 'ব্যবসার প্রবৃদ্ধি')

@php
    function growthPct($current, $previous) {
        if ($previous == 0) return $current > 0 ? 100 : 0;
        return round((($current - $previous) / abs($previous)) * 100, 1);
    }
    function growthClass($pct) { return $pct >= 0 ? 'growth-up' : 'growth-down'; }
    function growthIcon($pct)  { return $pct >= 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down'; }

    $revGrowth  = growthPct($summary['revenue'],  $prev['revenue']);
    $cogGrowth  = growthPct($summary['cogs'],     $prev['cogs']);
    $expGrowth  = growthPct($summary['expenses'], $prev['expenses']);
    $proGrowth  = growthPct($summary['profit'],   $prev['profit']);

    $periodLabel = match($period) {
        'yearly' => 'বার্ষিক',
        'range'  => $dateFrom . ' — ' . $dateTo,
        default  => $year . ' সাল',
    };
@endphp

@section('content')

{{-- ── Filter bar ──────────────────────────────────────────── --}}
<div class="card" style="margin-bottom:20px">
    <form method="GET" action="{{ route('reports.growth') }}" class="growth-filter-form">
        {{-- Period tabs --}}
        <div class="growth-period-tabs">
            <a href="{{ route('reports.growth', ['period'=>'monthly', 'year'=>$year]) }}"
               class="gp-tab {{ $period==='monthly' ? 'active' : '' }}">
                <i class="fas fa-calendar-days"></i> মাসিক
            </a>
            <a href="{{ route('reports.growth', ['period'=>'yearly']) }}"
               class="gp-tab {{ $period==='yearly' ? 'active' : '' }}">
                <i class="fas fa-calendar-check"></i> বার্ষিক
            </a>
            <a href="{{ route('reports.growth', ['period'=>'range', 'date_from'=>$dateFrom, 'date_to'=>$dateTo]) }}"
               class="gp-tab {{ $period==='range' ? 'active' : '' }}">
                <i class="fas fa-calendar-range"></i> তারিখ পরিসর
            </a>
        </div>

        <input type="hidden" name="period" value="{{ $period }}">

        @if($period === 'monthly')
        <div style="display:flex;align-items:center;gap:8px">
            <label class="form-label" style="margin:0">সাল</label>
            <select name="year" class="form-input" style="width:110px;height:36px" onchange="this.form.submit()">
                @foreach($years as $y)
                <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </div>
        @endif

        @if($period === 'range')
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
            <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-input" style="height:36px;width:150px">
            <span style="color:#94a3b8">—</span>
            <input type="date" name="date_to" value="{{ $dateTo }}" class="form-input" style="height:36px;width:150px">
            @include('partials.date-range-buttons', ['fromName'=>'date_from','toName'=>'date_to','formClass'=>'.growth-filter-form'])
            <button type="submit" class="btn btn-primary" style="height:36px">খুঁজুন</button>
        </div>
        @endif
    </form>
</div>

{{-- ── Summary KPI cards ───────────────────────────────────── --}}
<div class="growth-kpi-grid">

    <div class="kpi-card kpi-blue">
        <div class="kpi-icon"><i class="fas fa-sack-dollar"></i></div>
        <div class="kpi-body">
            <div class="kpi-label">মোট বিক্রয় আয়</div>
            <div class="kpi-value">৳ {{ number_format($summary['revenue'], 0) }}</div>
            <div class="kpi-growth {{ growthClass($revGrowth) }}">
                <i class="fas {{ growthIcon($revGrowth) }}"></i>
                {{ abs($revGrowth) }}%
                <span>আগের তুলনায়</span>
            </div>
        </div>
    </div>

    <div class="kpi-card kpi-red">
        <div class="kpi-icon"><i class="fas fa-cart-flatbed"></i></div>
        <div class="kpi-body">
            <div class="kpi-label">মোট ক্রয় ব্যয়</div>
            <div class="kpi-value">৳ {{ number_format($summary['cogs'], 0) }}</div>
            <div class="kpi-growth {{ growthClass(-$cogGrowth) }}">
                <i class="fas {{ growthIcon(-$cogGrowth) }}"></i>
                {{ abs($cogGrowth) }}%
                <span>আগের তুলনায়</span>
            </div>
        </div>
    </div>

    <div class="kpi-card kpi-orange">
        <div class="kpi-icon"><i class="fas fa-money-bill-transfer"></i></div>
        <div class="kpi-body">
            <div class="kpi-label">পরিচালনা খরচ</div>
            <div class="kpi-value">৳ {{ number_format($summary['expenses'], 0) }}</div>
            <div class="kpi-growth {{ growthClass(-$expGrowth) }}">
                <i class="fas {{ growthIcon(-$expGrowth) }}"></i>
                {{ abs($expGrowth) }}%
                <span>আগের তুলনায়</span>
            </div>
        </div>
    </div>

    <div class="kpi-card {{ $summary['profit'] >= 0 ? 'kpi-green' : 'kpi-crimson' }}">
        <div class="kpi-icon"><i class="fas fa-scale-balanced"></i></div>
        <div class="kpi-body">
            <div class="kpi-label">নিট লাভ / লোকসান</div>
            <div class="kpi-value" style="color:{{ $summary['profit'] >= 0 ? '#16a34a' : '#dc2626' }}">
                {{ $summary['profit'] >= 0 ? '+' : '' }}৳ {{ number_format($summary['profit'], 0) }}
            </div>
            <div class="kpi-growth {{ growthClass($proGrowth) }}">
                <i class="fas {{ growthIcon($proGrowth) }}"></i>
                {{ abs($proGrowth) }}%
                <span>আগের তুলনায়</span>
            </div>
        </div>
    </div>

    <div class="kpi-card kpi-purple">
        <div class="kpi-icon"><i class="fas fa-receipt"></i></div>
        <div class="kpi-body">
            <div class="kpi-label">মোট বিক্রয় সংখ্যা</div>
            <div class="kpi-value">{{ number_format($summary['sales_count']) }}</div>
            <div class="kpi-growth" style="color:#94a3b8;font-size:.75rem">
                <i class="fas fa-hashtag"></i> লেনদেন
            </div>
        </div>
    </div>

    @php
        $margin = $summary['revenue'] > 0 ? round(($summary['profit'] / $summary['revenue']) * 100, 1) : 0;
    @endphp
    <div class="kpi-card kpi-teal">
        <div class="kpi-icon"><i class="fas fa-percent"></i></div>
        <div class="kpi-body">
            <div class="kpi-label">লাভের মার্জিন</div>
            <div class="kpi-value" style="color:{{ $margin >= 0 ? '#0d9488' : '#dc2626' }}">
                {{ $margin }}%
            </div>
            <div class="kpi-growth" style="color:#94a3b8;font-size:.75rem">
                <i class="fas fa-chart-pie"></i> নিট মার্জিন
            </div>
        </div>
    </div>

</div>

{{-- ── Main chart ──────────────────────────────────────────── --}}
<div class="card" style="margin-bottom:20px">
    <div class="card-header-row">
        <span style="font-weight:700;font-size:.9rem">
            <i class="fas fa-chart-line" style="color:var(--accent);margin-right:6px"></i>
            বিক্রয় / ক্রয় / লাভ — {{ $periodLabel }}
        </span>
        <div style="display:flex;gap:10px;font-size:.75rem">
            <span class="chart-legend" style="--lc:#3b82f6">বিক্রয় আয়</span>
            <span class="chart-legend" style="--lc:#ef4444">ক্রয় ব্যয়</span>
            <span class="chart-legend" style="--lc:#f59e0b">পরিচালনা খরচ</span>
            <span class="chart-legend" style="--lc:#22c55e">নিট লাভ</span>
        </div>
    </div>
    <div style="padding:16px 20px 20px">
        <canvas id="mainChart" style="max-height:320px"></canvas>
    </div>
</div>

{{-- ── Bottom two charts ──────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

    <div class="card">
        <div class="card-header-row">
            <span style="font-weight:700;font-size:.9rem">
                <i class="fas fa-chart-bar" style="color:#7c3aed;margin-right:6px"></i>
                বিক্রয় বনাম ক্রয়
            </span>
        </div>
        <div style="padding:16px 20px 20px">
            <canvas id="barChart" style="max-height:260px"></canvas>
        </div>
    </div>

    <div class="card">
        <div class="card-header-row">
            <span style="font-weight:700;font-size:.9rem">
                <i class="fas fa-chart-pie" style="color:#f59e0b;margin-right:6px"></i>
                ব্যয়ের বিভাজন
            </span>
        </div>
        <div style="padding:16px;display:flex;align-items:center;justify-content:center">
            <canvas id="doughnutChart" style="max-height:240px;max-width:300px"></canvas>
        </div>
    </div>

</div>

{{-- ── Data table ──────────────────────────────────────────── --}}
<div class="card" style="margin-top:20px">
    <div class="card-header-row">
        <span style="font-weight:700;font-size:.9rem">
            <i class="fas fa-table" style="color:#64748b;margin-right:6px"></i>
            বিস্তারিত তথ্য
        </span>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>সময়কাল</th>
                    <th class="tr">বিক্রয় আয়</th>
                    <th class="tr">ক্রয় ব্যয়</th>
                    <th class="tr">পরিচালনা খরচ</th>
                    <th class="tr">গ্রস লাভ</th>
                    <th class="tr">নিট লাভ</th>
                    <th class="tc">মার্জিন</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $periodNames = $period === 'monthly'
                        ? ['01'=>'জানুয়ারি','02'=>'ফেব্রুয়ারি','03'=>'মার্চ','04'=>'এপ্রিল',
                           '05'=>'মে','06'=>'জুন','07'=>'জুলাই','08'=>'আগস্ট',
                           '09'=>'সেপ্টেম্বর','10'=>'অক্টোবর','11'=>'নভেম্বর','12'=>'ডিসেম্বর']
                        : [];
                @endphp
                @foreach($labels as $i => $lbl)
                @php
                    $r  = $revenue[$i]  ?? 0;
                    $c  = $cogs[$i]     ?? 0;
                    $e  = $expenses[$i] ?? 0;
                    $gp = $r - $c;
                    $np = $r - $c - $e;
                    $mg = $r > 0 ? round($np / $r * 100, 1) : 0;
                    $displayLbl = $period === 'monthly' ? ($periodNames[$lbl] ?? $lbl) : $lbl;
                @endphp
                @if($r > 0 || $c > 0)
                <tr>
                    <td style="font-weight:600">{{ $displayLbl }}</td>
                    <td class="tr">৳ {{ number_format($r, 0) }}</td>
                    <td class="tr" style="color:#ef4444">৳ {{ number_format($c, 0) }}</td>
                    <td class="tr" style="color:#f59e0b">৳ {{ number_format($e, 0) }}</td>
                    <td class="tr" style="color:{{ $gp >= 0 ? '#0d9488' : '#dc2626' }}">৳ {{ number_format($gp, 0) }}</td>
                    <td class="tr" style="color:{{ $np >= 0 ? '#16a34a' : '#dc2626' }};font-weight:700">
                        {{ $np >= 0 ? '' : '-' }}৳ {{ number_format(abs($np), 0) }}
                    </td>
                    <td class="tc">
                        <span class="badge {{ $mg >= 0 ? 'badge-green' : 'badge-red' }}">{{ $mg }}%</span>
                    </td>
                </tr>
                @endif
                @endforeach
            </tbody>
            <tfoot>
                <tr class="tfoot-summary">
                    <td style="font-weight:700">মোট</td>
                    <td class="tr" style="font-weight:800">৳ {{ number_format($summary['revenue'], 0) }}</td>
                    <td class="tr" style="color:#ef4444;font-weight:700">৳ {{ number_format($summary['cogs'], 0) }}</td>
                    <td class="tr" style="color:#f59e0b;font-weight:700">৳ {{ number_format($summary['expenses'], 0) }}</td>
                    <td class="tr" style="color:#0d9488;font-weight:700">৳ {{ number_format($summary['revenue'] - $summary['cogs'], 0) }}</td>
                    <td class="tr" style="color:{{ $summary['profit'] >= 0 ? '#16a34a' : '#dc2626' }};font-weight:800">
                        ৳ {{ number_format($summary['profit'], 0) }}
                    </td>
                    <td class="tc">
                        <span class="badge {{ $margin >= 0 ? 'badge-green' : 'badge-red' }}">{{ $margin }}%</span>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@endsection

@push('styles')
<style>
/* ── Filter form ─────────────────────────────────────────── */
.growth-filter-form {
    padding: 14px 20px;
    display: flex; align-items: center; gap: 16px; flex-wrap: wrap;
}
.growth-period-tabs { display: flex; gap: 4px; }
.gp-tab {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 16px;
    font-size: .82rem; font-weight: 600;
    border: 1.5px solid var(--border);
    border-radius: var(--radius-sm);
    text-decoration: none; color: var(--text-secondary);
    background: var(--surface);
    transition: .12s;
}
.gp-tab:hover { border-color: var(--accent); color: var(--accent); }
.gp-tab.active { background: var(--accent); color: #fff; border-color: var(--accent); }

/* ── KPI grid ────────────────────────────────────────────── */
.growth-kpi-grid {
    display: grid;
    grid-template-columns: repeat(6,1fr);
    gap: 14px;
    margin-bottom: 20px;
}
@media(max-width:1200px){ .growth-kpi-grid{ grid-template-columns: repeat(3,1fr); } }
@media(max-width:700px) { .growth-kpi-grid{ grid-template-columns: repeat(2,1fr); } }

.kpi-card {
    background: var(--surface);
    border: 1.5px solid var(--border);
    border-radius: var(--radius);
    padding: 16px;
    display: flex; align-items: flex-start; gap: 12px;
    transition: box-shadow .15s, transform .15s;
}
.kpi-card:hover { box-shadow: 0 4px 18px rgba(0,0,0,.08); transform: translateY(-2px); }
.kpi-icon {
    width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center; font-size: .95rem;
}
.kpi-blue   { border-top: 3px solid #3b82f6; } .kpi-blue   .kpi-icon { background:#eff6ff;color:#3b82f6; }
.kpi-red    { border-top: 3px solid #ef4444; } .kpi-red    .kpi-icon { background:#fff1f2;color:#ef4444; }
.kpi-orange { border-top: 3px solid #f59e0b; } .kpi-orange .kpi-icon { background:#fffbeb;color:#f59e0b; }
.kpi-green  { border-top: 3px solid #22c55e; } .kpi-green  .kpi-icon { background:#f0fdf4;color:#22c55e; }
.kpi-crimson{ border-top: 3px solid #dc2626; } .kpi-crimson .kpi-icon { background:#fff1f2;color:#dc2626; }
.kpi-purple { border-top: 3px solid #7c3aed; } .kpi-purple .kpi-icon { background:#f5f3ff;color:#7c3aed; }
.kpi-teal   { border-top: 3px solid #0d9488; } .kpi-teal   .kpi-icon { background:#f0fdfa;color:#0d9488; }

.kpi-body { flex: 1; min-width: 0; }
.kpi-label { font-size: .74rem; color: #94a3b8; font-weight: 600; margin-bottom: 4px; }
.kpi-value { font-size: 1.1rem; font-weight: 800; color: var(--text-primary); line-height: 1.2; margin-bottom: 4px; }
.kpi-growth { font-size: .72rem; font-weight: 700; display: flex; align-items: center; gap: 4px; }
.kpi-growth span { font-weight: 400; color: #94a3b8; }
.growth-up   { color: #16a34a; }
.growth-down { color: #dc2626; }

/* ── Chart card header ───────────────────────────────────── */
.card-header-row {
    padding: 14px 20px;
    border-bottom: 1.5px solid var(--border);
    display: flex; align-items: center; justify-content: space-between;
}
.chart-legend {
    display: inline-flex; align-items: center; gap: 5px;
    color: var(--text-secondary);
}
.chart-legend::before {
    content: ''; width: 10px; height: 10px; border-radius: 50%;
    background: var(--lc); flex-shrink: 0;
}
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/vendor/chart.umd.min.js') }}"></script>
<script>
var LABELS   = @json($labels);
var REVENUE  = @json($revenue);
var COGS     = @json($cogs);
var EXPENSES = @json($expenses);
var PROFIT   = @json($profit);

@php
$monthNames = ['01'=>'জানু','02'=>'ফেব্রু','03'=>'মার্চ','04'=>'এপ্রিল',
               '05'=>'মে','06'=>'জুন','07'=>'জুলাই','08'=>'আগস্ট',
               '09'=>'সেপ্টে','10'=>'অক্টো','11'=>'নভে','12'=>'ডিসে'];
$dispLabels = array_map(fn($l) => $monthNames[$l] ?? $l, $labels);
@endphp
var DISP_LABELS = @json($dispLabels);

var chartDefaults = {
    responsive: true,
    maintainAspectRatio: true,
    plugins: { legend: { display: false } },
    scales: {
        x: { grid: { color: 'rgba(0,0,0,.04)' }, ticks: { font: { size: 11 } } },
        y: { grid: { color: 'rgba(0,0,0,.04)' }, ticks: { font: { size: 11 },
             callback: v => '৳' + (v >= 1000 ? (v/1000).toFixed(0)+'k' : v) } },
    }
};

// ── 1. Main line chart ────────────────────────────────────
new Chart(document.getElementById('mainChart'), {
    type: 'line',
    data: {
        labels: DISP_LABELS,
        datasets: [
            { label:'বিক্রয় আয়',  data: REVENUE,  borderColor:'#3b82f6', backgroundColor:'rgba(59,130,246,.08)', tension:.35, fill:true, pointRadius:4, pointHoverRadius:6 },
            { label:'ক্রয় ব্যয়',  data: COGS,     borderColor:'#ef4444', backgroundColor:'rgba(239,68,68,.05)',   tension:.35, fill:false, pointRadius:3 },
            { label:'খরচ',         data: EXPENSES, borderColor:'#f59e0b', backgroundColor:'rgba(245,158,11,.05)',  tension:.35, fill:false, pointRadius:3, borderDash:[4,4] },
            { label:'নিট লাভ',    data: PROFIT,   borderColor:'#22c55e', backgroundColor:'rgba(34,197,94,.1)',    tension:.35, fill:true,  pointRadius:4, pointHoverRadius:6, borderWidth:2.5 },
        ]
    },
    options: { ...chartDefaults, plugins: { legend: { display: true, position:'bottom', labels:{ font:{size:11}, boxWidth:12, padding:16 } }, tooltip: { mode:'index', intersect:false } } }
});

// ── 2. Grouped bar chart ──────────────────────────────────
new Chart(document.getElementById('barChart'), {
    type: 'bar',
    data: {
        labels: DISP_LABELS,
        datasets: [
            { label:'বিক্রয়', data: REVENUE, backgroundColor:'rgba(59,130,246,.75)', borderRadius:4 },
            { label:'ক্রয়',   data: COGS,    backgroundColor:'rgba(239,68,68,.65)',  borderRadius:4 },
        ]
    },
    options: { ...chartDefaults, plugins: { legend: { display:true, position:'bottom', labels:{ font:{size:11}, boxWidth:12 } }, tooltip:{ mode:'index' } } }
});

// ── 3. Doughnut chart ────────────────────────────────────
var totalCogs = COGS.reduce((a,b)=>a+b,0);
var totalExp  = EXPENSES.reduce((a,b)=>a+b,0);
var totalPro  = Math.max(0, PROFIT.reduce((a,b)=>a+b,0));
new Chart(document.getElementById('doughnutChart'), {
    type: 'doughnut',
    data: {
        labels: ['ক্রয় ব্যয়','পরিচালনা খরচ','নিট লাভ'],
        datasets: [{
            data: [totalCogs, totalExp, totalPro],
            backgroundColor: ['#ef4444','#f59e0b','#22c55e'],
            borderWidth: 2, borderColor: '#fff',
        }]
    },
    options: {
        responsive:true, maintainAspectRatio:true, cutout:'65%',
        plugins: {
            legend: { display:true, position:'bottom', labels:{ font:{size:11}, boxWidth:12, padding:12 } },
            tooltip: { callbacks: { label: ctx => ' ৳' + ctx.parsed.toLocaleString() } }
        }
    }
});
</script>
@endpush
