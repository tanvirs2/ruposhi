@extends('layouts.app')
@section('title', 'লাভ-লোকসান রিপোর্ট')
@section('page-title', 'লাভ-লোকসান হিসাব')

@section('content')

{{-- Filter ──────────────────────────────────────────────────── --}}
<div class="card no-print" style="margin-bottom:20px">
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
            {{-- Quick range buttons --}}
            <div style="align-self:flex-end;display:flex;gap:6px;flex-wrap:wrap">
                <button type="button" onclick="setRange('this_month')" class="btn btn-ghost" style="font-size:.8rem;padding:8px 12px">এই মাস</button>
                <button type="button" onclick="setRange('last_month')" class="btn btn-ghost" style="font-size:.8rem;padding:8px 12px">গত মাস</button>
                <button type="button" onclick="setRange('this_year')"  class="btn btn-ghost" style="font-size:.8rem;padding:8px 12px">এই বছর</button>
            </div>
            <button type="submit" class="btn btn-primary" style="align-self:flex-end">
                <i class="fas fa-search"></i> দেখুন
            </button>
            <div style="align-self:flex-end;display:flex;gap:8px;margin-left:auto">
                <a href="{{ route('reports.export.profit-loss', request()->query()) }}" class="btn btn-export">
                    <i class="fas fa-file-csv"></i> CSV
                </a>
                <button type="button" class="btn btn-export-print" onclick="window.print()">
                    <i class="fas fa-print"></i> প্রিন্ট
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Report header (print only) ──────────────────────────────── --}}
<div class="print-header" style="display:none">
    @include('partials.store-name-arc', ['name' => \App\Models\StoreConfig::get('store_name','আমার দোকান'), 'size' => 28])
    <div style="font-size:.85rem;color:#555;margin-top:2px">লাভ-লোকসান হিসাব</div>
    <div style="font-size:.8rem;color:#777">{{ \Carbon\Carbon::parse($from)->format('d M Y') }} — {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</div>
</div>

{{-- ── KPI Cards ──────────────────────────────────────────────── --}}
<div class="pl-kpi-row">
    <div class="pl-kpi pl-kpi-blue">
        <div class="pl-kpi-label"><i class="fas fa-arrow-trend-up"></i> নিট বিক্রয় আয়</div>
        <div class="pl-kpi-value">৳ {{ number_format($netRevenue, 0) }}</div>
        @if($discounts > 0)
        <div class="pl-kpi-sub">ছাড় বাদে (ছাড়: ৳{{ number_format($discounts,0) }})</div>
        @endif
    </div>
    <div class="pl-kpi pl-kpi-orange">
        <div class="pl-kpi-label"><i class="fas fa-boxes-stacked"></i> পণ্য ক্রয় মূল্য (COGS)</div>
        <div class="pl-kpi-value">৳ {{ number_format($cogs, 0) }}</div>
    </div>
    <div class="pl-kpi {{ $grossProfit >= 0 ? 'pl-kpi-green' : 'pl-kpi-red' }}">
        <div class="pl-kpi-label"><i class="fas fa-chart-line"></i> গ্রস লাভ</div>
        <div class="pl-kpi-value">{{ $grossProfit >= 0 ? '+' : '' }}৳ {{ number_format($grossProfit, 0) }}</div>
        <div class="pl-kpi-sub">মার্জিন {{ $grossMargin }}%</div>
    </div>
    <div class="pl-kpi pl-kpi-purple">
        <div class="pl-kpi-label"><i class="fas fa-receipt"></i> পরিচালনা ব্যয়</div>
        <div class="pl-kpi-value">৳ {{ number_format($totalExpenses, 0) }}</div>
    </div>
    <div class="pl-kpi {{ $netProfit >= 0 ? 'pl-kpi-net-profit' : 'pl-kpi-net-loss' }}">
        <div class="pl-kpi-label"><i class="fas fa-scale-balanced"></i> নিট {{ $netProfit >= 0 ? 'লাভ' : 'লোকসান' }}</div>
        <div class="pl-kpi-value">{{ $netProfit >= 0 ? '+' : '' }}৳ {{ number_format($netProfit, 0) }}</div>
        <div class="pl-kpi-sub">নিট মার্জিন {{ $netMargin }}%</div>
    </div>
</div>

{{-- ── P&L Statement (formal layout) ─────────────────────────── --}}
<div class="pl-statement">

    <div class="pl-statement-title">
        লাভ-লোকসান বিবরণী
        <span style="font-size:.8rem;font-weight:500;color:#64748b;margin-left:8px">
            {{ \Carbon\Carbon::parse($from)->format('d M Y') }} — {{ \Carbon\Carbon::parse($to)->format('d M Y') }}
        </span>
    </div>

    {{-- Revenue section --}}
    <div class="pl-section-head">আয় (Revenue)</div>
    <div class="pl-row">
        <span>মোট বিক্রয় (Gross Sales)</span>
        <span>৳ {{ number_format($grossSales, 2) }}</span>
    </div>
    @if($discounts > 0)
    <div class="pl-row pl-row-sub">
        <span>বাদ: ছাড় (Discounts)</span>
        <span style="color:#dc2626">− ৳ {{ number_format($discounts, 2) }}</span>
    </div>
    @endif
    @if(($extraCost ?? 0) > 0)
    <div class="pl-row pl-row-sub">
        <span>বাদ: অতিরিক্ত খরচ (pass-through)</span>
        <span style="color:#dc2626">− ৳ {{ number_format($extraCost, 2) }}</span>
    </div>
    @endif
    @if(($laborCost ?? 0) > 0)
    <div class="pl-row pl-row-sub">
        <span>বাদ: শ্রমিক খরচ (pass-through)</span>
        <span style="color:#dc2626">− ৳ {{ number_format($laborCost, 2) }}</span>
    </div>
    @endif
    <div class="pl-row pl-row-total">
        <span>নিট বিক্রয় আয়</span>
        <span>৳ {{ number_format($netRevenue, 2) }}</span>
    </div>

    {{-- COGS --}}
    <div class="pl-section-head">বিক্রিত পণ্যের খরচ (COGS)</div>
    <div class="pl-row">
        <span>পণ্যের ক্রয় মূল্য (বিক্রিত পরিমাণ অনুযায়ী)</span>
        <span style="color:#dc2626">− ৳ {{ number_format($cogs, 2) }}</span>
    </div>
    <div class="pl-row pl-row-total {{ $grossProfit >= 0 ? 'pl-positive' : 'pl-negative' }}">
        <span>গ্রস লাভ (Gross Profit)</span>
        <span>{{ $grossProfit >= 0 ? '+' : '' }}৳ {{ number_format($grossProfit, 2) }}
            <small style="font-weight:500;font-size:.8rem;opacity:.75">({{ $grossMargin }}%)</small>
        </span>
    </div>

    {{-- Operating expenses --}}
    <div class="pl-section-head">পরিচালনা ব্যয় (Operating Expenses)</div>
    @forelse($expenseCategories as $exp)
    <div class="pl-row pl-row-sub">
        <span>{{ $exp->category }}</span>
        <span style="color:#dc2626">− ৳ {{ number_format($exp->total, 2) }}</span>
    </div>
    @empty
    <div class="pl-row pl-row-sub" style="color:#94a3b8">
        <span>এই সময়কালে কোনো খরচ নেই</span><span>৳ 0.00</span>
    </div>
    @endforelse
    <div class="pl-row pl-row-total">
        <span>মোট পরিচালনা ব্যয়</span>
        <span style="color:#dc2626">− ৳ {{ number_format($totalExpenses, 2) }}</span>
    </div>

    {{-- Net profit --}}
    <div class="pl-net {{ $netProfit >= 0 ? 'pl-net-profit' : 'pl-net-loss' }}">
        <span>নিট {{ $netProfit >= 0 ? 'লাভ' : 'লোকসান' }} (Net {{ $netProfit >= 0 ? 'Profit' : 'Loss' }})</span>
        <span>{{ $netProfit >= 0 ? '+' : '' }}৳ {{ number_format($netProfit, 2) }}
            <small style="font-size:.82rem;font-weight:500;opacity:.8">({{ $netMargin }}%)</small>
        </span>
    </div>
</div>

{{-- ── Monthly Breakdown ───────────────────────────────────────── --}}
@if(count($monthly) > 1)
<div class="card" style="margin-top:24px">
    <div class="card-header"><h3><i class="fas fa-calendar-days"></i> মাসভিত্তিক বিবরণ</h3></div>
    <div class="table-wrap">
        <table class="data-table">
            <colgroup>
                <col>                         {{-- মাস (flexible) --}}
                <col style="width:130px">     {{-- বিক্রয় আয় --}}
                <col style="width:120px">     {{-- পণ্য খরচ --}}
                <col style="width:120px">     {{-- গ্রস লাভ --}}
                <col style="width:110px">     {{-- ব্যয় --}}
                <col style="width:140px">     {{-- নিট লাভ/লোকসান --}}
                <col style="width:90px">      {{-- মার্জিন --}}
            </colgroup>
            <thead>
                <tr>
                    <th>মাস</th>
                    <th style="text-align:right">বিক্রয় আয়</th>
                    <th style="text-align:right">পণ্য খরচ</th>
                    <th style="text-align:right">গ্রস লাভ</th>
                    <th style="text-align:right">ব্যয়</th>
                    <th style="text-align:right">নিট লাভ/লোকসান</th>
                    <th style="text-align:right">মার্জিন</th>
                </tr>
            </thead>
            <tbody>
                @foreach($monthly as $m)
                @php
                    $mGross  = $m->revenue - $m->cost;
                    $mNet    = $m->profit;
                    $mMargin = $m->revenue > 0 ? round($mNet / $m->revenue * 100, 1) : 0;
                    $cls     = $mNet >= 0 ? 'profit-good' : 'profit-poor';
                @endphp
                <tr>
                    <td>{{ \Carbon\Carbon::parse($m->month.'-01')->translatedFormat('F Y') }}</td>
                    <td style="text-align:right">৳ {{ number_format($m->revenue, 0) }}</td>
                    <td style="text-align:right;color:#94a3b8">৳ {{ number_format($m->cost, 0) }}</td>
                    <td style="text-align:right" class="{{ $mGross >= 0 ? 'profit-good' : 'profit-poor' }}">
                        {{ $mGross >= 0 ? '+' : '' }}৳ {{ number_format($mGross, 0) }}
                    </td>
                    <td style="text-align:right;color:#dc2626">৳ {{ number_format($m->expenses, 0) }}</td>
                    <td style="text-align:right" class="{{ $cls }}">{{ $mNet >= 0 ? '+' : '' }}৳ {{ number_format($mNet, 0) }}</td>
                    <td style="text-align:right">
                        <span class="margin-badge {{ $mMargin >= 8 ? 'good' : ($mMargin >= 4 ? 'med' : 'poor') }}">
                            {{ $mMargin }}%
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="font-weight:700;background:var(--bg)">
                    <td>মোট</td>
                    <td style="text-align:right">৳ {{ number_format($netRevenue, 0) }}</td>
                    <td style="text-align:right;color:#94a3b8">৳ {{ number_format($cogs, 0) }}</td>
                    <td style="text-align:right" class="{{ $grossProfit >= 0 ? 'profit-good' : 'profit-poor' }}">
                        {{ $grossProfit >= 0 ? '+' : '' }}৳ {{ number_format($grossProfit, 0) }}
                    </td>
                    <td style="text-align:right;color:#dc2626">৳ {{ number_format($totalExpenses, 0) }}</td>
                    <td style="text-align:right" class="{{ $netProfit >= 0 ? 'profit-good' : 'profit-poor' }}">
                        {{ $netProfit >= 0 ? '+' : '' }}৳ {{ number_format($netProfit, 0) }}
                    </td>
                    <td style="text-align:right">
                        <span class="margin-badge {{ $netMargin >= 8 ? 'good' : ($netMargin >= 4 ? 'med' : 'poor') }}">
                            {{ $netMargin }}%
                        </span>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

{{-- ── Daily Detail Table (old-style per-row breakdown) ───────── --}}
<div class="card" style="margin-top:24px">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <h3><i class="fas fa-table-list"></i> বিক্রয় বিস্তারিত (তারিখ অনুযায়ী)</h3>
        <span style="font-size:.8rem;color:#94a3b8">মোট {{ count($dailyDetail) }} টি লাইন আইটেম</span>
    </div>
    <div class="table-wrap">
        <table class="data-table pl-item-table">
            <thead>
                <tr>
                    <th style="text-align:center;width:44px">#</th>
                    <th style="width:110px">বিক্রয় তারিখ</th>
                    <th>আইটেম নাম</th>
                    <th style="text-align:right;width:90px">বিক্রীর পরিমাণ</th>
                    <th style="text-align:right;width:120px">বিক্রয় মূল্য (৳)</th>
                    <th style="text-align:right;width:110px">লাভ (৳)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($dailyDetail as $i => $row)
                @php
                    $profit = floatval($row->profit);
                    $cls    = $profit >= 0 ? 'profit-good' : 'profit-poor';
                @endphp
                <tr>
                    <td style="text-align:center;color:#94a3b8">{{ $i + 1 }}</td>
                    <td style="color:#64748b;font-size:.82rem">{{ $row->sale_date }}</td>
                    <td>{{ $row->name }}</td>
                    <td style="text-align:right">{{ number_format($row->qty, 0) }}</td>
                    <td style="text-align:right">{{ number_format($row->unit_price, 0) }}</td>
                    <td style="text-align:right" class="{{ $cls }}">
                        {{ $profit >= 0 ? '+' : '' }}{{ number_format($profit, 0) }}
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="empty-row">এই সময়কালে কোনো বিক্রয় নেই</td></tr>
                @endforelse
            </tbody>
            @if(count($dailyDetail))
            <tfoot>
                <tr style="font-weight:700;background:var(--bg)">
                    <td colspan="3">মোট</td>
                    <td style="text-align:right">{{ number_format($dailyDetail->sum('qty'), 0) }}</td>
                    <td style="text-align:right">—</td>
                    <td style="text-align:right" class="{{ $grossProfit >= 0 ? 'profit-good' : 'profit-poor' }}">
                        {{ $grossProfit >= 0 ? '+' : '' }}{{ number_format($grossProfit, 0) }}
                    </td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

{{-- ── Item-wise Profit Breakdown ──────────────────────────────── --}}
<div class="card" style="margin-top:24px">
    <div class="card-header"><h3><i class="fas fa-boxes-stacked"></i> পণ্যভিত্তিক লাভ-লোকসান (সারসংক্ষেপ)</h3></div>
    <div class="table-wrap">
        <table class="data-table pl-item-table">
            <colgroup>
                <col style="width:44px">          {{-- # --}}
                <col>                              {{-- পণ্য (flexible) --}}
                <col style="width:110px">          {{-- বিক্রীত পরিমাণ --}}
                <col style="width:120px">          {{-- বিক্রয় আয় --}}
                <col style="width:120px">          {{-- ক্রয় মূল্য --}}
                <col style="width:120px">          {{-- লাভ --}}
                <col style="width:90px">           {{-- মার্জিন --}}
            </colgroup>
            <thead>
                <tr>
                    <th style="text-align:center">#</th>
                    <th>পণ্য</th>
                    <th style="text-align:right">বিক্রীত পরিমাণ</th>
                    <th style="text-align:right">বিক্রয় আয়</th>
                    <th style="text-align:right">ক্রয় মূল্য</th>
                    <th style="text-align:right">লাভ</th>
                    <th style="text-align:right">মার্জিন</th>
                </tr>
            </thead>
            <tbody>
                @forelse($itemBreakdown as $i => $row)
                @php
                    $pct = $row->revenue > 0 ? round($row->profit / $row->revenue * 100, 1) : 0;
                    $cls = $row->profit >= 0 ? ($pct >= 8 ? 'profit-good' : ($pct >= 4 ? 'profit-med' : 'profit-poor')) : 'profit-poor';
                @endphp
                <tr>
                    <td style="text-align:center;color:#94a3b8">{{ $i + 1 }}</td>
                    <td>{{ $row->name }}</td>
                    <td style="text-align:right">{{ number_format($row->qty, 0) }}</td>
                    <td style="text-align:right">৳ {{ number_format($row->revenue, 0) }}</td>
                    <td style="text-align:right;color:#94a3b8">৳ {{ number_format($row->cost, 0) }}</td>
                    <td style="text-align:right" class="{{ $cls }}">{{ $row->profit >= 0 ? '+' : '' }}৳ {{ number_format($row->profit, 0) }}</td>
                    <td style="text-align:right">
                        <span class="margin-badge {{ $pct >= 8 ? 'good' : ($pct >= 4 ? 'med' : 'poor') }}">
                            {{ $pct }}%
                        </span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="empty-row">এই সময়কালে কোনো বিক্রয় নেই</td></tr>
                @endforelse
            </tbody>
            @if(count($itemBreakdown))
            <tfoot>
                <tr style="font-weight:700;background:var(--bg)">
                    <td colspan="2" style="text-align:left">মোট</td>
                    <td style="text-align:right">{{ number_format($itemBreakdown->sum('qty'), 0) }}</td>
                    <td style="text-align:right">৳ {{ number_format($itemBreakdown->sum('revenue'), 0) }}</td>
                    <td style="text-align:right;color:#94a3b8">৳ {{ number_format($itemBreakdown->sum('cost'), 0) }}</td>
                    <td style="text-align:right" class="{{ $grossProfit >= 0 ? 'profit-good' : 'profit-poor' }}">
                        {{ $grossProfit >= 0 ? '+' : '' }}৳ {{ number_format($grossProfit, 0) }}
                    </td>
                    <td style="text-align:right">
                        <span class="margin-badge {{ $grossMargin >= 8 ? 'good' : ($grossMargin >= 4 ? 'med' : 'poor') }}">
                            {{ $grossMargin }}%
                        </span>
                    </td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection

@push('styles')
<style>
/* ── KPI row ──────────────────────────────────────────── */
.pl-kpi-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
    gap: 14px;
    margin-bottom: 24px;
}
.pl-kpi {
    border-radius: 12px;
    padding: 16px 18px;
    border: 1px solid transparent;
}
.pl-kpi-label { font-size: .78rem; font-weight: 600; opacity: .75; margin-bottom: 6px; display:flex;align-items:center;gap:6px; }
.pl-kpi-value { font-size: 1.35rem; font-weight: 800; line-height: 1.1; }
.pl-kpi-sub   { font-size: .75rem; opacity: .65; margin-top: 4px; }

.pl-kpi-blue   { background:#eff6ff; border-color:#bfdbfe; color:#1e40af; }
.pl-kpi-orange { background:#fff7ed; border-color:#fed7aa; color:#c2410c; }
.pl-kpi-green  { background:#f0fdf4; border-color:#bbf7d0; color:#15803d; }
.pl-kpi-red    { background:#fef2f2; border-color:#fecaca; color:#b91c1c; }
.pl-kpi-purple { background:#faf5ff; border-color:#e9d5ff; color:#7e22ce; }
.pl-kpi-net-profit { background:#ecfdf5; border-color:#6ee7b7; color:#065f46; }
.pl-kpi-net-loss   { background:#fff1f2; border-color:#fda4af; color:#9f1239; }

/* ── Formal P&L statement ─────────────────────────────── */
.pl-statement {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 0;
}
.pl-statement-title {
    padding: 14px 20px;
    font-size: 1rem;
    font-weight: 700;
    background: var(--bg);
    border-bottom: 2px solid var(--border);
}
.pl-section-head {
    padding: 8px 20px;
    font-size: .75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .07em;
    background: #f8fafc;
    color: #475569;
    border-top: 1px solid var(--border);
    border-bottom: 1px solid #e2e8f0;
}
.pl-row {
    display: flex;
    justify-content: space-between;
    padding: 9px 20px 9px 32px;
    font-size: .9rem;
    border-bottom: 1px solid #f1f5f9;
}
.pl-row-sub { padding-left: 40px; color: #475569; }
.pl-row-total {
    padding: 10px 20px;
    font-weight: 700;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    border-bottom: 1px solid #e2e8f0;
}
.pl-positive { color: #15803d; }
.pl-negative { color: #b91c1c; }
.pl-net {
    display: flex;
    justify-content: space-between;
    padding: 14px 20px;
    font-size: 1.1rem;
    font-weight: 800;
    border-top: 2px solid;
}
.pl-net-profit { background: #f0fdf4; border-color: #16a34a; color: #15803d; }
.pl-net-loss   { background: #fef2f2; border-color: #dc2626; color: #b91c1c; }

/* ── Numeric table alignment ─────────────────────────── */
.pl-item-table td,
.pl-item-table th { white-space: nowrap; }
.pl-item-table td:not(:nth-child(2)),
.pl-item-table th:not(:nth-child(2)) { font-variant-numeric: tabular-nums; }

/* ── Table utilities ──────────────────────────────────── */
.profit-good { color: #16a34a; font-weight: 600; }
.profit-med  { color: #d97706; font-weight: 600; }
.profit-poor { color: #dc2626; font-weight: 600; }
.margin-badge { display:inline-block; padding:2px 10px; border-radius:20px; font-size:.78rem; font-weight:600; }
.margin-badge.good { background:#dcfce7; color:#15803d; }
.margin-badge.med  { background:#fef9c3; color:#92400e; }
.margin-badge.poor { background:#fee2e2; color:#b91c1c; }
.tr { text-align: right; }

/* ── Print ───────────────────────────────────────────── */
@media print {
    .sidebar, .topbar, .no-print { display: none !important; }
    .main-wrapper { margin-left: 0 !important; }
    .content { padding: 8px !important; }
    .print-header { display: block !important; text-align:center; margin-bottom:16px; }
    .pl-kpi-row { grid-template-columns: repeat(5, 1fr); gap: 8px; }
    .pl-kpi { padding: 10px 12px; }
    .pl-kpi-value { font-size: 1rem; }
    .card { box-shadow: none !important; border: 1px solid #ccc !important; }
}
</style>
@endpush

@push('scripts')
<script>
function setRange(type) {
    const today = new Date();
    let from, to;
    if (type === 'this_month') {
        from = new Date(today.getFullYear(), today.getMonth(), 1);
        to   = today;
    } else if (type === 'last_month') {
        from = new Date(today.getFullYear(), today.getMonth() - 1, 1);
        to   = new Date(today.getFullYear(), today.getMonth(), 0);
    } else if (type === 'this_year') {
        from = new Date(today.getFullYear(), 0, 1);
        to   = today;
    }
    const fmt = d => d.toISOString().slice(0, 10);
    document.querySelector('input[name=from]').value = fmt(from);
    document.querySelector('input[name=to]').value   = fmt(to);
    document.querySelector('form').submit();
}
</script>
@endpush
