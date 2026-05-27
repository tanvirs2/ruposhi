@extends('layouts.app')
@section('title', 'দৈনিক বিক্রয় লেজার রিপোর্ট')
@section('page-title', 'দৈনিক বিক্রয় লেজার রিপোর্ট')

@section('content')

{{-- ── Print header ────────────────────────────────────────────── --}}
<div class="dsl-print-header" style="display:none">
    @include('partials.store-name-arc', ['name' => \App\Models\StoreConfig::get('store_name','আমার দোকান'), 'size' => 26])
    <div style="font-size:.85rem;color:#555;margin-top:2px">দৈনিক বিক্রয় লেজার রিপোর্ট</div>
    <div style="font-size:.8rem;color:#777">
        লেজার রিপোর্ট — {{ \Carbon\Carbon::parse($from)->format('d M Y') }}
        @if($from !== $to) থেকে {{ \Carbon\Carbon::parse($to)->format('d M Y') }} পর্যন্ত @endif
    </div>
</div>

{{-- ── Filter ────────────────────────────────────────────────────── --}}
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
            <div style="align-self:flex-end;display:flex;gap:6px;flex-wrap:wrap">
                <button type="button" onclick="dslRange('today')"      class="btn btn-ghost" style="font-size:.8rem;padding:8px 12px">আজ</button>
                <button type="button" onclick="dslRange('yesterday')"  class="btn btn-ghost" style="font-size:.8rem;padding:8px 12px">গতকাল</button>
                <button type="button" onclick="dslRange('this_month')" class="btn btn-ghost" style="font-size:.8rem;padding:8px 12px">এই মাস</button>
                <button type="button" onclick="dslRange('last_month')" class="btn btn-ghost" style="font-size:.8rem;padding:8px 12px">গত মাস</button>
            </div>
            <button type="submit" class="btn btn-primary" style="align-self:flex-end">
                <i class="fas fa-search"></i> দেখুন
            </button>
            <div style="align-self:flex-end;display:flex;gap:8px;margin-left:auto">
                <a href="{{ route('reports.export.daily-sales-ledger', request()->query()) }}" class="btn btn-export">
                    <i class="fas fa-file-csv"></i> CSV
                </a>
                <button type="button" class="btn btn-export-print" onclick="window.print()">
                    <i class="fas fa-print"></i> প্রিন্ট
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── KPI summary ──────────────────────────────────────────────── --}}
@if($rows->count())
<div class="dsl-kpi-row">
    <div class="dsl-kpi dsl-kpi-blue">
        <div class="dsl-kpi-label"><i class="fas fa-receipt"></i> মোট বিক্রয়</div>
        <div class="dsl-kpi-value">৳ {{ number_format($grandTotal, 0) }}</div>
        <div class="dsl-kpi-sub">{{ $rows->count() }} আইটেম</div>
    </div>
    <div class="dsl-kpi dsl-kpi-green">
        <div class="dsl-kpi-label"><i class="fas fa-boxes-stacked"></i> মোট পরিমাণ</div>
        <div class="dsl-kpi-value">{{ number_format($rows->sum('qty'), 0) }}</div>
        <div class="dsl-kpi-sub">ইউনিট বিক্রয়</div>
    </div>
    <div class="dsl-kpi dsl-kpi-purple">
        <div class="dsl-kpi-label"><i class="fas fa-file-invoice"></i> চালান সংখ্যা</div>
        <div class="dsl-kpi-value">{{ $rows->pluck('sale_id')->unique()->count() }}</div>
        <div class="dsl-kpi-sub">মোট বিক্রয় এন্ট্রি</div>
    </div>
    <div class="dsl-kpi dsl-kpi-orange">
        <div class="dsl-kpi-label"><i class="fas fa-calendar-days"></i> সময়কাল</div>
        <div class="dsl-kpi-value" style="font-size:1rem">{{ \Carbon\Carbon::parse($from)->format('d M') }}@if($from !== $to) — {{ \Carbon\Carbon::parse($to)->format('d M') }}@endif</div>
        <div class="dsl-kpi-sub">{{ \Carbon\Carbon::parse($from)->format('Y') }}</div>
    </div>
</div>
@endif

{{-- ── Report period label ──────────────────────────────────────── --}}
<div class="dsl-period-label">
    <i class="fas fa-calendar-check" style="margin-right:6px;color:var(--accent)"></i>
    লেজার রিপোর্ট —
    <strong>{{ \Carbon\Carbon::parse($from)->format('d M Y') }}</strong>
    @if($from !== $to)
        থেকে <strong>{{ \Carbon\Carbon::parse($to)->format('d M Y') }}</strong> পর্যন্ত
    @endif
</div>

{{-- ── Ledger Table ─────────────────────────────────────────────── --}}
<div class="card">
    <div class="table-wrap">
        <table class="data-table dsl-table">
            <colgroup>
                <col style="width:100px">    {{-- তারিখ --}}
                <col style="width:200px">    {{-- বিবরণ --}}
                <col style="width:80px">     {{-- পরিমাণ --}}
                <col style="width:100px">    {{-- দর --}}
                <col style="width:90px">     {{-- জমা --}}
                <col style="width:110px">    {{-- খরচ --}}
                <col style="width:120px">    {{-- অবশিষ্ট --}}
            </colgroup>
            <thead>
                <tr>
                    <th>তারিখ</th>
                    <th>বিবরণ</th>
                    <th style="text-align:right">পরিমাণ</th>
                    <th style="text-align:right">দর (৳)</th>
                    <th style="text-align:right">জমা (৳)</th>
                    <th style="text-align:right">খরচ (৳)</th>
                    <th style="text-align:right">অবশিষ্ট (৳)</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $running     = 0;
                    $prevDate    = null;
                    $prevSaleId  = null;
                @endphp
                @forelse($rows as $row)
                @php
                    $running    += $row->amount;
                    $isNewDate   = $row->date !== $prevDate;
                    $isNewSale   = $row->sale_id !== $prevSaleId;
                    $prevDate    = $row->date;
                    $prevSaleId  = $row->sale_id;
                @endphp
                {{-- Date group separator --}}
                @if($isNewDate && $from !== $to)
                <tr class="dsl-date-group">
                    <td colspan="7">
                        <i class="fas fa-calendar-day" style="margin-right:5px;font-size:.8rem"></i>
                        {{ \Carbon\Carbon::parse($row->date)->translatedFormat('d F Y, l') }}
                    </td>
                </tr>
                @endif
                <tr class="{{ $isNewSale && !$loop->first ? 'dsl-new-sale' : '' }}">
                    <td style="white-space:nowrap;color:#64748b;font-size:.82rem">
                        {{ \Carbon\Carbon::parse($row->date)->format('d/m/Y') }}
                    </td>
                    <td>
                        <span class="dsl-item-name">{{ $row->item_name }}</span>
                        @if($row->customer_name)
                        <span class="dsl-customer-tag">{{ $row->customer_name }}</span>
                        @endif
                    </td>
                    <td style="text-align:right;font-variant-numeric:tabular-nums;font-weight:600">
                        {{ number_format($row->qty, 0) }}
                    </td>
                    <td style="text-align:right;font-variant-numeric:tabular-nums;color:#475569">
                        {{ number_format($row->rate, 0) }}
                    </td>
                    <td style="text-align:right;color:#94a3b8">—</td>
                    <td style="text-align:right;font-variant-numeric:tabular-nums;font-weight:600;color:#1e40af">
                        {{ number_format($row->amount, 0) }}
                    </td>
                    <td style="text-align:right;font-variant-numeric:tabular-nums;font-weight:700;color:#15803d">
                        {{ number_format($running, 0) }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="empty-row">এই সময়কালে কোনো বিক্রয় নেই</td>
                </tr>
                @endforelse
            </tbody>
            @if($rows->count())
            <tfoot>
                <tr class="dsl-tfoot">
                    <td colspan="2">সর্বমোট</td>
                    <td style="text-align:right;font-variant-numeric:tabular-nums">
                        {{ number_format($rows->sum('qty'), 0) }}
                    </td>
                    <td style="text-align:right">—</td>
                    <td style="text-align:right">—</td>
                    <td style="text-align:right;font-variant-numeric:tabular-nums;color:#1e40af">
                        {{ number_format($grandTotal, 0) }}
                    </td>
                    <td style="text-align:right;font-variant-numeric:tabular-nums;color:#15803d">
                        {{ number_format($grandTotal, 0) }}
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
/* ── KPI row ──────────────────────────────────────────────── */
.dsl-kpi-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 14px;
    margin-bottom: 16px;
}
.dsl-kpi {
    border-radius: 12px;
    padding: 14px 16px;
    border: 1px solid transparent;
}
.dsl-kpi-label { font-size: .76rem; font-weight: 600; opacity: .75; margin-bottom: 5px; display:flex; align-items:center; gap:6px; }
.dsl-kpi-value { font-size: 1.3rem; font-weight: 800; line-height: 1.1; font-variant-numeric: tabular-nums; }
.dsl-kpi-sub   { font-size: .72rem; opacity: .6; margin-top: 4px; }

.dsl-kpi-blue   { background:#eff6ff; border-color:#bfdbfe; color:#1e40af; }
.dsl-kpi-green  { background:#f0fdf4; border-color:#bbf7d0; color:#15803d; }
.dsl-kpi-purple { background:#faf5ff; border-color:#e9d5ff; color:#7e22ce; }
.dsl-kpi-orange { background:#fff7ed; border-color:#fed7aa; color:#c2410c; }

/* ── Period label ──────────────────────────────────────────── */
.dsl-period-label {
    padding: 10px 16px;
    background: #f8fafc;
    border: 1px solid var(--border);
    border-radius: 8px;
    font-size: .88rem;
    color: #475569;
    margin-bottom: 16px;
}

/* ── Table ─────────────────────────────────────────────────── */
.dsl-table td, .dsl-table th { white-space: nowrap; }

.dsl-date-group td {
    padding: 8px 12px;
    font-size: .78rem;
    font-weight: 700;
    background: #f1f5f9;
    color: #334155;
    letter-spacing: .04em;
    border-top: 2px solid #e2e8f0;
}

.dsl-new-sale td { border-top: 1px dashed #e2e8f0; }

.dsl-item-name   { font-weight: 600; font-size: .9rem; }
.dsl-customer-tag {
    display: inline-block;
    margin-left: 6px;
    padding: 1px 8px;
    background: #eff6ff;
    color: #1e40af;
    border-radius: 20px;
    font-size: .7rem;
    font-weight: 600;
    vertical-align: middle;
}

.dsl-tfoot td {
    padding: 10px 12px;
    font-weight: 700;
    background: var(--bg);
    border-top: 2px solid var(--border);
}

/* ── Print ─────────────────────────────────────────────────── */
@media print {
    .sidebar, .topbar, .no-print { display: none !important; }
    .main-wrapper { margin-left: 0 !important; }
    .content { padding: 8px !important; }
    .dsl-print-header { display: block !important; text-align: center; margin-bottom: 14px; }
    .dsl-kpi-row { grid-template-columns: repeat(4,1fr); gap: 8px; }
    .dsl-kpi { padding: 8px 10px; }
    .dsl-kpi-value { font-size: 1rem; }
    .card { box-shadow: none !important; border: 1px solid #ccc !important; }
    .dsl-date-group td { background: #f1f5f9 !important; -webkit-print-color-adjust: exact; }
}
</style>
@endpush

@push('scripts')
<script>
function dslRange(type) {
    const today = new Date();
    let from, to;
    if (type === 'today') {
        from = to = today;
    } else if (type === 'yesterday') {
        const y = new Date(today); y.setDate(y.getDate() - 1);
        from = to = y;
    } else if (type === 'this_month') {
        from = new Date(today.getFullYear(), today.getMonth(), 1);
        to   = today;
    } else if (type === 'last_month') {
        from = new Date(today.getFullYear(), today.getMonth() - 1, 1);
        to   = new Date(today.getFullYear(), today.getMonth(), 0);
    }
    const fmt = d => d.toISOString().slice(0, 10);
    document.querySelector('input[name=from]').value = fmt(from);
    document.querySelector('input[name=to]').value   = fmt(to);
    document.querySelector('form').submit();
}
</script>
@endpush
