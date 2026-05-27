@extends('layouts.app')
@section('title', 'কাস্টমার লেজার — '.$customer->name)
@section('page-title', 'কাস্টমার লেজার রিপোর্ট')

@section('content')

{{-- ── Print header ──────────────────────────────────────────── --}}
<div class="ledger-print-header" style="display:none">
    <div style="font-size:1.2rem;font-weight:800">
        {{ \App\Models\StoreConfig::get('store_name','আমার দোকান') }}
    </div>
    <div style="font-size:.85rem;color:#555;margin-top:2px">কাস্টমার লেজার রিপোর্ট</div>
    <div style="font-size:.8rem;color:#777">
        {{ \Carbon\Carbon::parse($from)->format('d M Y') }} — {{ \Carbon\Carbon::parse($to)->format('d M Y') }}
    </div>
</div>

{{-- ── Top action bar ────────────────────────────────────────── --}}
<div class="page-header-bar no-print">
    <div>
        <h2 style="font-size:1.1rem;font-weight:700">{{ $customer->name }}</h2>
        <p style="font-size:.82rem;color:#64748b;margin-top:2px">
            @if($customer->phone) {{ $customer->phone }} &nbsp;·&nbsp; @endif
            @if($customer->proprietor) প্রোঃ {{ $customer->proprietor }} &nbsp;·&nbsp; @endif
            @if($customer->area) {{ $customer->area->name }} @endif
        </p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
        <a href="{{ route('customer-payments.create', ['customer_id' => $customer->id]) }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> পরিশোধ যোগ করুন
        </a>
        <button type="button" class="btn btn-export-print" onclick="window.print()">
            <i class="fas fa-print"></i> প্রিন্ট
        </button>
        <a href="{{ route('customers.ledger', $customer->id) }}?from={{ $from }}&to={{ $to }}&export=csv"
           class="btn btn-export"><i class="fas fa-file-csv"></i> CSV</a>
        <a href="{{ route('customers.ledger-select') }}" class="btn btn-ghost">
            <i class="fas fa-arrow-left"></i> ফিরে যান
        </a>
    </div>
</div>

{{-- ── Filter ─────────────────────────────────────────────────── --}}
<div class="card no-print" style="margin-bottom:18px">
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
                <button type="button" onclick="ledgerRange('this_month')" class="btn btn-ghost" style="font-size:.8rem;padding:8px 12px">এই মাস</button>
                <button type="button" onclick="ledgerRange('last_month')" class="btn btn-ghost" style="font-size:.8rem;padding:8px 12px">গত মাস</button>
                <button type="button" onclick="ledgerRange('this_year')"  class="btn btn-ghost" style="font-size:.8rem;padding:8px 12px">এই বছর</button>
                <button type="button" onclick="ledgerRange('all')"        class="btn btn-ghost" style="font-size:.8rem;padding:8px 12px">সব</button>
            </div>
            <button type="submit" class="btn btn-primary" style="align-self:flex-end">
                <i class="fas fa-search"></i> দেখুন
            </button>
        </form>
    </div>
</div>

{{-- ── Customer info (print only) ────────────────────────────── --}}
<div class="ledger-customer-card" style="display:none">
    <table class="ledger-info-table">
        <tr>
            <td><strong>কাস্টমার নামঃ</strong> {{ $customer->name }}</td>
            <td><strong>প্রতিষ্ঠানের নামঃ</strong> {{ $customer->proprietor ?? '—' }}</td>
        </tr>
        <tr>
            <td><strong>ঠিকানাঃ</strong> {{ $customer->address ?? '—' }}</td>
            <td><strong>লেজার রিপোর্ট -</strong> {{ \Carbon\Carbon::parse($from)->format('Y-m-d') }} থেকে {{ \Carbon\Carbon::parse($to)->format('Y-m-d') }} পর্যন্ত</td>
        </tr>
    </table>
</div>

{{-- ── KPI Cards ──────────────────────────────────────────────── --}}
<div class="ledger-kpi-row">
    <div class="ledger-kpi ledger-kpi-blue">
        <div class="ledger-kpi-label"><i class="fas fa-receipt"></i> মোট বিক্রয়</div>
        <div class="ledger-kpi-value">৳ {{ number_format($totalSales, 0) }}</div>
        <div class="ledger-kpi-sub">{{ $ledger->where('type','item')->pluck('sale_id')->unique()->count() }}টি চালান</div>
    </div>
    <div class="ledger-kpi ledger-kpi-green">
        <div class="ledger-kpi-label"><i class="fas fa-money-bill-wave"></i> মোট পরিশোধ</div>
        <div class="ledger-kpi-value">৳ {{ number_format($totalCredits, 0) }}</div>
        <div class="ledger-kpi-sub">{{ $ledger->where('type','payment')->count() }}টি পরিশোধ</div>
    </div>
    <div class="ledger-kpi {{ $periodBalance > 0 ? 'ledger-kpi-red' : 'ledger-kpi-green' }}">
        <div class="ledger-kpi-label"><i class="fas fa-calculator"></i> এই সময়ের বাকী</div>
        <div class="ledger-kpi-value">৳ {{ number_format(max(0,$periodBalance), 0) }}</div>
        <div class="ledger-kpi-sub">নির্বাচিত পিরিয়ড অনুযায়ী</div>
    </div>
    <div class="ledger-kpi {{ $realTotalDue > 0 ? 'ledger-kpi-orange' : 'ledger-kpi-green' }}">
        <div class="ledger-kpi-label"><i class="fas fa-scale-balanced"></i> সর্বমোট বাকী</div>
        <div class="ledger-kpi-value">৳ {{ number_format(max(0,$realTotalDue), 0) }}</div>
        <div class="ledger-kpi-sub">সকল লেনদেন হিসেবে</div>
    </div>
</div>

{{-- ── Ledger Table ────────────────────────────────────────────── --}}
<div class="card">
    <div class="table-wrap">
        <table class="data-table cl-table">
            <colgroup>
                <col style="width:90px">    {{-- চালান নং --}}
                <col style="width:120px">   {{-- তারিখ --}}
                <col style="width:220px">   {{-- বিবরণ --}}
                <col style="width:70px">    {{-- পরিমাণ --}}
                <col style="width:70px">    {{-- দর --}}
                <col style="width:90px">    {{-- জমা --}}
                <col style="width:90px">    {{-- বাকি --}}
                <col style="width:110px">   {{-- অবশিষ্ট --}}
            </colgroup>
            <thead>
                <tr>
                    <th class="tc">চালান নং</th>
                    <th>তারিখ</th>
                    <th>বিবরণ</th>
                    <th style="text-align:right">পরিমাণ</th>
                    <th style="text-align:right">দর</th>
                    <th style="text-align:right">জমা (৳)</th>
                    <th style="text-align:right">বাকি (৳)</th>
                    <th style="text-align:right">অবশিষ্ট (৳)</th>
                </tr>
            </thead>
            <tbody>
                @php $running = $openingBalance; $prevSaleId = null; @endphp

                {{-- Opening balance row --}}
                @if($openingBalance != 0)
                <tr class="cl-opening-row">
                    <td colspan="7" style="font-weight:700;color:#475569">পূর্বের অবশিষ্ট</td>
                    <td style="text-align:right;font-weight:800;color:{{ $openingBalance > 0 ? '#b45309' : '#15803d' }}">
                        {{ number_format(abs($openingBalance), 0) }}
                    </td>
                </tr>
                @endif

                @forelse($ledger as $row)
                @php
                    $running    += $row['debit'] - $row['credit'];
                    $isNewSale   = $row['sale_id'] && $row['sale_id'] !== $prevSaleId && $row['type'] === 'item';
                    if ($row['sale_id']) $prevSaleId = $row['sale_id'];
                @endphp
                <tr class="{{ $row['type'] === 'payment' ? 'cl-payment-row' : ($row['type'] === 'discount' ? 'cl-discount-row' : '') }} {{ $isNewSale && !$loop->first ? 'cl-new-sale' : '' }}">
                    <td class="tc mono" style="font-size:.82rem">
                        @if($row['sale_id'])
                            <a href="{{ route('sales.show', $row['sale_id']) }}" class="link-primary">
                                {{ str_pad($row['sale_id'], 6, '0', STR_PAD_LEFT) }}
                            </a>
                        @else
                            <span class="cl-dash">—</span>
                        @endif
                    </td>
                    <td class="cl-date" style="white-space:nowrap">
                        @php
                            $dt = \Carbon\Carbon::parse($row['datetime']);
                        @endphp
                        {{ $dt->format('Y-m-d') }} <span class="cl-time">{{ $dt->format('h:i:s a') }}</span>
                    </td>
                    <td>
                        @if($row['type'] === 'payment')
                            <span class="cl-payment-label">
                                <i class="fas fa-circle-check" style="font-size:.72rem;margin-right:3px"></i>
                                {{ $row['label'] }}
                            </span>
                        @elseif($row['type'] === 'discount')
                            <span class="cl-discount-label">
                                <i class="fas fa-tag" style="font-size:.72rem;margin-right:3px"></i>
                                {{ $row['label'] }}
                            </span>
                        @else
                            <span class="cl-item-name">{{ $row['label'] }}</span>
                        @endif
                    </td>
                    <td style="text-align:right;font-variant-numeric:tabular-nums">
                        @if($row['qty'] > 0)
                            <strong>{{ number_format($row['qty'], 0) }}</strong>
                        @else
                            <span class="cl-dash">-</span>
                        @endif
                    </td>
                    <td style="text-align:right;font-variant-numeric:tabular-nums;color:#475569">
                        @if($row['rate'] > 0)
                            {{ number_format($row['rate'], 0) }}
                        @else
                            <span class="cl-dash">-</span>
                        @endif
                    </td>
                    <td style="text-align:right;font-variant-numeric:tabular-nums">
                        @if($row['credit'] > 0)
                            <span class="cl-credit">{{ number_format($row['credit'], 0) }}</span>
                        @else
                            <span class="cl-dash">-</span>
                        @endif
                    </td>
                    <td style="text-align:right;font-variant-numeric:tabular-nums">
                        @if($row['debit'] > 0)
                            <span class="cl-debit">{{ number_format($row['debit'], 0) }}</span>
                        @else
                            <span class="cl-dash">-</span>
                        @endif
                    </td>
                    <td style="text-align:right;font-variant-numeric:tabular-nums;font-weight:700;
                        color:{{ $running > 0 ? '#b45309' : ($running < 0 ? '#15803d' : '#64748b') }}">
                        {{ number_format(abs($running), 0) }}
                        @if($running < 0)
                            <div style="font-size:.68rem;font-weight:600;color:#15803d;margin-top:1px">(অতিরিক্ত)</div>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="empty-row">এই সময়কালে কোনো লেনদেন নেই</td></tr>
                @endforelse
            </tbody>
            @if($ledger->count())
            <tfoot>
                <tr class="cl-tfoot">
                    <td colspan="3" style="font-weight:700">সর্বমোট</td>
                    <td style="text-align:right">{{ number_format($ledger->sum('qty'), 0) }}</td>
                    <td></td>
                    <td style="text-align:right;font-weight:700;color:#15803d">
                        {{ number_format($totalCredits + $totalDiscount, 0) }}
                    </td>
                    <td style="text-align:right;font-weight:700;color:#dc2626">
                        {{ number_format($totalSales + $totalDiscount, 0) }}
                    </td>
                    <td style="text-align:right;font-weight:800;color:{{ $running > 0 ? '#b45309' : ($running < 0 ? '#15803d' : '#64748b') }}">
                        {{ number_format(abs($running), 0) }}
                        @if($running < 0) <div style="font-size:.7rem;color:#15803d">(অতিরিক্ত)</div> @endif
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
/* ── KPI ──────────────────────────────────────────────────── */
.ledger-kpi-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 14px;
    margin-bottom: 20px;
}
.ledger-kpi { border-radius:12px; padding:16px 18px; border:1px solid transparent; }
.ledger-kpi-label { font-size:.78rem; font-weight:600; opacity:.75; margin-bottom:6px; display:flex; align-items:center; gap:6px; }
.ledger-kpi-value { font-size:1.3rem; font-weight:800; font-variant-numeric:tabular-nums; }
.ledger-kpi-sub   { font-size:.72rem; opacity:.6; margin-top:4px; }
.ledger-kpi-blue   { background:#eff6ff; border-color:#bfdbfe; color:#1e40af; }
.ledger-kpi-green  { background:#f0fdf4; border-color:#bbf7d0; color:#15803d; }
.ledger-kpi-orange { background:#fff7ed; border-color:#fed7aa; color:#c2410c; }
.ledger-kpi-red    { background:#fef2f2; border-color:#fecaca; color:#b91c1c; }

/* ── Table ───────────────────────────────────────────────── */
.cl-table td, .cl-table th { white-space: nowrap; }

.cl-opening-row td {
    background: #f8fafc;
    border-top: 2px solid #e2e8f0;
    border-bottom: 2px solid #e2e8f0;
    padding: 8px 12px;
}

.cl-new-sale td { border-top: 1px dashed #e2e8f0; }

.cl-payment-row td { background: #f0fdf4; }

.cl-date  { color: #475569; font-size: .78rem; white-space: nowrap; }
.cl-time  { font-size: .72rem; color: #94a3b8; margin-top:1px; }

.cl-item-name    { font-weight: 600; font-size: .85rem; word-break: break-word; }
.cl-payment-label { color: #15803d; font-weight: 600; font-size: .88rem; }

.cl-debit  { color: #dc2626; font-weight: 600; }
.cl-credit { color: #15803d; font-weight: 600; }
.cl-dash   { color: #cbd5e1; }

.cl-discount-row td { background: #fefce8; }
.cl-discount-label  { color: #92400e; font-weight: 600; font-size: .88rem; }

.cl-tfoot td {
    padding: 10px 12px;
    font-weight: 700;
    background: var(--bg);
    border-top: 2px solid var(--border);
}

/* ── Print ───────────────────────────────────────────────── */
@media print {
    .sidebar, .topbar, .no-print { display: none !important; }
    .main-wrapper { margin-left: 0 !important; }
    .content { padding: 8px !important; }
    .ledger-print-header  { display: block !important; text-align: center; margin-bottom: 12px; }
    .ledger-customer-card { display: block !important; margin-bottom: 12px; }
    .ledger-kpi-row { grid-template-columns: repeat(4,1fr); gap:8px; }
    .ledger-kpi { padding: 8px 10px; }
    .ledger-kpi-value { font-size: 1rem; }
    .card { box-shadow: none !important; border: 1px solid #ccc !important; }
    .cl-payment-row td { background: #f6fff8 !important; -webkit-print-color-adjust: exact; }
    .cl-opening-row td { background: #f1f5f9 !important; -webkit-print-color-adjust: exact; }
}

/* ── Print info table ────────────────────────────────────── */
.ledger-customer-card { margin-bottom: 12px; }
.ledger-info-table {
    width: 100%;
    border-collapse: collapse;
    font-size: .85rem;
    border: 1px solid #ccc;
}
.ledger-info-table td {
    padding: 5px 10px;
    border: 1px solid #ddd;
}
</style>
@endpush

@push('scripts')
<script>
function ledgerRange(type) {
    const today = new Date();
    let from, to;
    if (type === 'this_month')      { from = new Date(today.getFullYear(), today.getMonth(), 1); to = today; }
    else if (type === 'last_month') { from = new Date(today.getFullYear(), today.getMonth()-1, 1); to = new Date(today.getFullYear(), today.getMonth(), 0); }
    else if (type === 'this_year')  { from = new Date(today.getFullYear(), 0, 1); to = today; }
    else                            { from = new Date('2000-01-01'); to = today; }
    const fmt = d => d.toISOString().slice(0,10);
    document.querySelector('input[name=from]').value = fmt(from);
    document.querySelector('input[name=to]').value   = fmt(to);
    document.querySelector('form').submit();
}
</script>
@endpush
