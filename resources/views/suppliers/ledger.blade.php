@extends('layouts.app')
@section('title', 'সরবরাহকারী লেজার — '.$supplier->name)
@section('page-title', 'সরবরাহকারী লেজার রিপোর্ট')
@section('no-print-header', '1')

@push('styles')
<meta name="turbo-cache-control" content="no-cache">
@endpush

@section('content')

{{-- ── Print header ────────────────────────────────────────────── --}}
<div class="sl-print-header" style="display:none">
    <div style="font-size:1.1rem;font-weight:800;color:#000">{{ \App\Models\StoreConfig::get('store_name','আমার দোকান') }}</div>
    <div style="font-size:.85rem;color:#555;margin-top:2px">সরবরাহকারী লেজার রিপোর্ট</div>
    <div style="font-size:.8rem;color:#777">
        {{ \Carbon\Carbon::parse($from)->format('d M Y') }} — {{ \Carbon\Carbon::parse($to)->format('d M Y') }}
    </div>
</div>

{{-- ── Top bar ─────────────────────────────────────────────────── --}}
<div class="page-header-bar no-print">
    <div>
        <h2 style="font-size:1.1rem;font-weight:700">{{ $supplier->name }}</h2>
        <p style="font-size:.82rem;color:#64748b;margin-top:2px">
            @if($supplier->phone)<i class="fas fa-phone" style="width:14px;font-size:.8rem"></i> {{ $supplier->phone }} @endif
            @if($supplier->address) &nbsp;·&nbsp; <i class="fas fa-location-dot" style="width:14px;font-size:.8rem"></i> {{ $supplier->address }} @endif
        </p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
        <a href="{{ route('supplier-payments.create', ['supplier_id' => $supplier->id]) }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> পরিশোধ যোগ করুন
        </a>
        <button type="button" class="btn btn-export-print" onclick="window.print()">
            <i class="fas fa-print"></i> প্রিন্ট
        </button>
        <a href="{{ route('suppliers.ledger', $supplier->id) }}?from={{ $from }}&to={{ $to }}&export=csv"
           class="btn btn-export">
            <i class="fas fa-file-csv"></i> CSV
        </a>
        <a href="{{ route('suppliers.index') }}" class="btn btn-ghost">
            <i class="fas fa-arrow-left"></i> ফিরে যান
        </a>
    </div>
</div>

{{-- ── Filter ────────────────────────────────────────────────────── --}}
<div class="card no-print" style="margin-bottom:20px">
    <div class="card-filter">
        <form method="GET" class="filter-form" id="ledgerFilterForm" data-date-snap>
            <div class="form-group-field">
                <label>শুরুর তারিখ</label>
                <input type="date" name="from" value="{{ $from }}">
            </div>
            <div class="form-group-field">
                <label>শেষ তারিখ</label>
                <input type="date" name="to" value="{{ $to }}">
            </div>
            <div style="align-self:flex-end;display:flex;gap:6px;flex-wrap:wrap">
                <button type="button" onclick="slRange('this_month')" class="btn btn-ghost" style="font-size:.8rem;padding:8px 12px">এই মাস</button>
                <button type="button" onclick="slRange('last_month')" class="btn btn-ghost" style="font-size:.8rem;padding:8px 12px">গত মাস</button>
                <button type="button" onclick="slRange('this_year')"  class="btn btn-ghost" style="font-size:.8rem;padding:8px 12px">এই বছর</button>
                <button type="button" onclick="slRange('all')"        class="btn btn-ghost" style="font-size:.8rem;padding:8px 12px">সব</button>
            </div>
            <button type="submit" class="btn btn-primary" style="align-self:flex-end">
                <i class="fas fa-search"></i> দেখুন
            </button>
        </form>
    </div>
</div>

{{-- ── Supplier info (print only) ─────────────────────────────── --}}
<div class="sl-info-card" style="display:none">
    <table class="sl-info-table">
        <tr>
            <td><strong>প্রতিষ্ঠান</strong></td>
            <td>{{ $supplier->name }}</td>
            <td><strong>ফোন</strong></td>
            <td>{{ $supplier->phone ?? '—' }}</td>
        </tr>
        @if($supplier->address)
        <tr>
            <td><strong>ঠিকানা</strong></td>
            <td colspan="3">{{ $supplier->address }}</td>
        </tr>
        @endif
        <tr>
            <td><strong>সময়কাল</strong></td>
            <td colspan="3">{{ \Carbon\Carbon::parse($from)->format('d M Y') }} — {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</td>
        </tr>
    </table>
</div>

{{-- ── KPI Cards ─────────────────────────────────────────────────── --}}
<div class="sl-kpi-row">
    <div class="sl-kpi sl-kpi-blue">
        <div class="sl-kpi-label"><i class="fas fa-truck-ramp-box"></i> মোট ক্রয়</div>
        <div class="sl-kpi-value">৳ {{ number_format($totalDebit, 0) }}</div>
        <div class="sl-kpi-sub">{{ $receiptCount }}টি রিসিভ</div>
    </div>
    <div class="sl-kpi sl-kpi-green">
        <div class="sl-kpi-label"><i class="fas fa-money-bill-wave"></i> মোট পরিশোধ</div>
        <div class="sl-kpi-value">৳ {{ number_format($totalCredit, 0) }}</div>
        <div class="sl-kpi-sub">{{ $ledger->where('credit','>',0)->count() }}টি পরিশোধ</div>
    </div>
    @if($openingBalance != 0)
    <div class="sl-kpi {{ $openingBalance > 0 ? 'sl-kpi-orange' : 'sl-kpi-green' }}">
        <div class="sl-kpi-label"><i class="fas fa-history"></i> পূর্বের অবশিষ্ট</div>
        <div class="sl-kpi-value">৳ {{ number_format(abs($openingBalance), 0) }}</div>
        <div class="sl-kpi-sub">{{ $from }} এর আগের {{ $openingBalance > 0 ? 'দেনা' : 'পাওনা' }}</div>
    </div>
    @endif
    <div class="sl-kpi {{ $realTotalDue > 0 ? 'sl-kpi-red' : 'sl-kpi-green' }}">
        <div class="sl-kpi-label"><i class="fas fa-scale-balanced"></i> সর্বমোট বকেয়া</div>
        <div class="sl-kpi-value">৳ {{ number_format($realTotalDue, 0) }}</div>
        <div class="sl-kpi-sub">সকল লেনদেন হিসেবে</div>
    </div>
</div>

{{-- ── Ledger Table ──────────────────────────────────────────────── --}}
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-book-open"></i> লেনদেনের বিবরণ</h3>
    </div>
    <div class="table-wrap">
        <table class="data-table sl-ledger-table">
            <colgroup>
                <col style="width:105px">  {{-- তারিখ --}}
                <col>                      {{-- বিবরণ --}}
                <col style="width:80px">   {{-- পরিমাণ --}}
                <col style="width:100px">  {{-- দর --}}
                <col style="width:130px">  {{-- বাকি/Dr --}}
                <col style="width:130px">  {{-- জমা/Cr --}}
                <col style="width:150px">  {{-- অবশিষ্ট --}}
            </colgroup>
            <thead>
                <tr>
                    <th>তারিখ</th>
                    <th>বিবরণ</th>
                    <th style="text-align:right">পরিমাণ</th>
                    <th style="text-align:right">দর</th>
                    <th style="text-align:right">বাকি (৳)</th>
                    <th style="text-align:right">জমা (৳)</th>
                    <th style="text-align:right">অবশিষ্ট (৳)</th>
                </tr>
            </thead>
            <tbody>

                {{-- Opening balance row --}}
                <tr class="sl-opening-row">
                    <td style="color:#334155;font-size:.82rem">
                        {{ \Carbon\Carbon::parse($from)->format('d M Y') }}
                    </td>
                    <td colspan="5">
                        <span style="font-style:italic;color:#64748b;font-size:.88rem">
                            <i class="fas fa-clock-rotate-left" style="font-size:.75rem;margin-right:4px"></i>
                            পূর্বের অবশিষ্ট
                        </span>
                    </td>
                    <td style="text-align:right;font-variant-numeric:tabular-nums">
                        @if($openingBalance == 0)
                            <span style="color:#64748b">৳ 0</span>
                        @else
                            <span class="{{ $openingBalance > 0 ? 'sl-debit' : 'sl-credit' }}">
                                ৳ {{ number_format(abs($openingBalance), 0) }}
                                <br><small class="sl-bal-tag">{{ $openingBalance > 0 ? 'দেনা' : 'পাওনা' }}</small>
                            </span>
                        @endif
                    </td>
                </tr>

                @php $prevPurchaseId = null; @endphp
                @forelse($ledger as $row)
                @php
                    $isItem      = $row->type === 'item';
                    $isPay       = $row->type === 'payment' || $row->type === 'purchase_payment';
                    $isDeposit   = $row->type === 'deposit';
                    $isExtraCost = $row->type === 'extra_cost';
                    $isNewGroup  = $row->purchase_id && $row->purchase_id !== $prevPurchaseId;
                    if ($row->purchase_id) $prevPurchaseId = $row->purchase_id;
                    elseif ($row->type === 'payment') $prevPurchaseId = null;
                    $rowClass = $isPay      ? 'sl-payment-row'
                              : ($isDeposit   ? 'sl-deposit-row'
                              : ($isExtraCost ? 'sl-extracost-row'
                              : 'sl-item-row'));
                @endphp
                <tr class="{{ $rowClass }} {{ $isNewGroup ? 'sl-new-group' : '' }}">

                    {{-- Date --}}
                    <td style="white-space:nowrap;font-size:.83rem;color:#334155">
                        {{ \Carbon\Carbon::parse($row->date)->format('d M Y') }}
                    </td>

                    {{-- Description --}}
                    <td>
                        @if($isItem)
                            <span style="font-weight:600;color:var(--text)">{{ $row->label }}</span>
                            @if($row->link)
                                <a href="{{ $row->link }}" class="sl-ref-link" title="রিসিভ দেখুন">{{ $row->ref }}</a>
                            @endif
                        @elseif($isExtraCost)
                            <span class="sl-extracost-label">
                                <i class="fas fa-plus-circle" style="font-size:.72rem;margin-right:3px"></i>
                                {{ $row->label }}
                            </span>
                            @if($row->link)
                                <a href="{{ $row->link }}" class="sl-ref-link">{{ $row->ref }}</a>
                            @endif
                        @elseif($isDeposit)
                            <span class="sl-deposit-label">
                                <i class="fas fa-piggy-bank" style="font-size:.72rem;margin-right:3px"></i>
                                {{ $row->label }}
                            </span>
                            @if($row->link)
                                <a href="{{ $row->link }}" class="sl-ref-link" title="রিসিভ দেখুন">{{ $row->ref }}</a>
                            @elseif($row->ref)
                                <span class="sl-ref-tag">{{ $row->ref }}</span>
                            @endif
                        @else
                            <span class="sl-pay-label">
                                <i class="fas fa-circle-check" style="font-size:.72rem"></i>
                                {{ $row->label }}
                            </span>
                            @if($row->ref)
                                <span class="sl-ref-tag">{{ $row->ref }}</span>
                            @endif
                        @endif
                    </td>

                    {{-- পরিমাণ --}}
                    <td style="text-align:right;font-size:.88rem">
                        @if($row->qty > 0)
                            <span style="font-weight:600">{{ number_format($row->qty, 0) }}</span>
                        @else
                            <span style="color:#cbd5e1">—</span>
                        @endif
                    </td>

                    {{-- দর --}}
                    <td style="text-align:right;font-size:.88rem">
                        @if($row->rate > 0)
                            <span>{{ number_format($row->rate, 0) }}</span>
                        @else
                            <span style="color:#cbd5e1">—</span>
                        @endif
                    </td>

                    {{-- বাকি (debit — goods received) --}}
                    <td style="text-align:right;font-variant-numeric:tabular-nums">
                        @if($row->debit > 0)
                            <span class="sl-debit">৳ {{ number_format($row->debit, 0) }}</span>
                        @else
                            <span style="color:#cbd5e1">—</span>
                        @endif
                    </td>

                    {{-- জমা (credit — payment made) --}}
                    <td style="text-align:right;font-variant-numeric:tabular-nums">
                        @if($row->credit > 0)
                            <span class="sl-credit">৳ {{ number_format($row->credit, 0) }}</span>
                        @else
                            <span style="color:#cbd5e1">—</span>
                        @endif
                    </td>

                    {{-- অবশিষ্ট --}}
                    <td style="text-align:right;font-variant-numeric:tabular-nums">
                        @if($row->balance == 0)
                            <span style="color:#64748b;font-weight:600">৳ 0</span>
                            <br><small class="sl-bal-tag">নিষ্পত্তি</small>
                        @else
                            <span class="{{ $row->balance > 0 ? 'sl-debit' : 'sl-credit' }}">
                                ৳ {{ number_format(abs($row->balance), 0) }}
                            </span>
                            <br><small class="sl-bal-tag {{ $row->balance > 0 ? 'sl-tag-due' : 'sl-tag-credit' }}">
                                {{ $row->balance > 0 ? 'দেনা' : 'পাওনা' }}
                            </small>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="empty-row">এই সময়কালে কোনো লেনদেন নেই</td></tr>
                @endforelse

            </tbody>

            @if($ledger->isNotEmpty())
            <tfoot>
                <tr class="sl-tfoot">
                    <td colspan="4">সর্বমোট</td>
                    <td style="text-align:right;font-variant-numeric:tabular-nums">
                        <span class="sl-debit">৳ {{ number_format($totalDebit, 0) }}</span>
                    </td>
                    <td style="text-align:right;font-variant-numeric:tabular-nums">
                        <span class="sl-credit">৳ {{ number_format($totalCredit, 0) }}</span>
                    </td>
                    <td style="text-align:right;font-variant-numeric:tabular-nums">
                        @php $finalBal = $ledger->last()->balance ?? $openingBalance; @endphp
                        @if($finalBal == 0)
                            <span style="color:#64748b;font-weight:700">৳ 0</span>
                        @else
                            <span class="{{ $finalBal > 0 ? 'sl-debit' : 'sl-credit' }}">
                                ৳ {{ number_format(abs($finalBal), 0) }}
                            </span>
                            <br><small class="sl-bal-tag {{ $finalBal > 0 ? 'sl-tag-due' : 'sl-tag-credit' }}">
                                {{ $finalBal > 0 ? 'দেনা' : 'পাওনা' }}
                            </small>
                        @endif
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
.sl-kpi-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 14px;
    margin-bottom: 24px;
}
.sl-kpi {
    border-radius: 12px;
    padding: 16px 18px;
    border: 1px solid transparent;
}
.sl-kpi-label { font-size: .78rem; font-weight: 600; opacity: .75; margin-bottom: 6px; display:flex; align-items:center; gap:6px; }
.sl-kpi-value { font-size: 1.35rem; font-weight: 800; line-height: 1.1; font-variant-numeric: tabular-nums; }
.sl-kpi-sub   { font-size: .72rem; opacity: .6; margin-top: 4px; }
.sl-kpi-blue   { background:#eff6ff; border-color:#bfdbfe; color:#1e40af; }
.sl-kpi-green  { background:#f0fdf4; border-color:#bbf7d0; color:#15803d; }
.sl-kpi-orange { background:#fff7ed; border-color:#fed7aa; color:#c2410c; }
.sl-kpi-red    { background:#fef2f2; border-color:#fecaca; color:#b91c1c; }

/* ── Ledger table ────────────────────────────────────────── */
.sl-ledger-table td, .sl-ledger-table th { white-space: nowrap; }

/* Group separator — dashed top border before first item of each receipt */
.sl-new-group td { border-top: 2px dashed #e2e8f0 !important; }

.sl-opening-row { background: #f8fafc; }
.sl-payment-row  { background: #f0fdf4; }
.sl-deposit-row  { background: #eff6ff; }
.sl-item-row     { background: var(--card-bg); }
.sl-extracost-row { background: #fdf4ff; }
.sl-laborcost-row { background: #fff1f2; }
.sl-extracost-label { color: #7e22ce; font-weight: 600; font-size: .88rem; }
.sl-laborcost-label { color: #be123c; font-weight: 600; font-size: .88rem; }
.sl-deposit-label   { color: #1d4ed8; font-weight: 600; font-size: .88rem; }

.sl-debit  { color: #dc2626; font-weight: 700; }
.sl-credit { color: #16a34a; font-weight: 700; }

.sl-bal-tag     { font-size: .7rem; font-weight: 500; }
.sl-tag-due     { color: #b45309; }
.sl-tag-credit  { color: #15803d; }

/* Reference link/tag */
.sl-ref-link {
    display: inline-block;
    margin-left: 6px;
    font-size: .72rem;
    color: #0d9488;
    font-weight: 600;
    text-decoration: none;
    background: #f0fdfa;
    border: 1px solid #99f6e4;
    border-radius: 4px;
    padding: 0 5px;
    vertical-align: middle;
}
.sl-ref-link:hover { text-decoration: underline; }

.sl-ref-tag {
    display: inline-block;
    margin-left: 6px;
    font-size: .72rem;
    color: #64748b;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 4px;
    padding: 0 5px;
    vertical-align: middle;
}

.sl-pay-label {
    color: #16a34a;
    font-weight: 600;
    font-size: .9rem;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

/* Footer */
.sl-tfoot td {
    padding: 10px 12px;
    font-weight: 700;
    background: var(--bg);
    border-top: 2px solid var(--border);
}

/* ── Print info table ─────────────────────────────────────── */
.sl-info-table {
    width: 100%;
    border-collapse: collapse;
    font-size: .85rem;
    margin-bottom: 12px;
    border: 1px solid #ccc;
}
.sl-info-table td { padding: 5px 10px; border: 1px solid #ddd; }
.sl-info-table td:nth-child(odd) { background: #f8fafc; font-weight: 600; width: 15%; }

/* ── Print — ink-saving: white backgrounds, dark text, tight header ── */
@media print {
    .sidebar, .topbar, .no-print { display: none !important; }
    .main-wrapper { margin-left: 0 !important; }
    .content { padding: 0 !important; }
    .sl-print-header  { display: block !important; text-align: center; margin-bottom: 4px; }
    .sl-print-header > div { margin-top: 0 !important; line-height: 1.3; }
    .sl-info-card     { display: block !important; margin-bottom: 6px; }
    .sl-kpi-row       { grid-template-columns: repeat(4, 1fr); gap: 6px; margin-bottom: 8px; }
    .sl-kpi           { padding: 4px 8px; border: 0.5px solid #999 !important; }
    .sl-kpi-value     { font-size: .95rem; }
    .card             { box-shadow: none !important; border: none !important; }
    .sl-payment-row, .sl-opening-row, .sl-tfoot td { background: #fff !important; }
    .sl-opening-row td { border-top: 1.5px solid #000; border-bottom: 1.5px solid #000; }
    .sl-new-group td  { border-top: 1px dashed #999 !important; }

    /* Compact rows: inline rem font sizes on cells beat the global 10px
       table rule, and the two-line অবশিষ্ট cell (৳ + দেনা tag) doubled
       every row's height on paper. Stylesheet !important beats inline. */
    .sl-ledger-table td, .sl-ledger-table td span, .sl-ledger-table td small,
    .sl-ledger-table td a, .sl-ledger-table td strong { font-size: 9.5px !important; }
    .sl-ledger-table td { padding: 1px 5px !important; line-height: 1.3 !important; }
    .sl-ledger-table td br { display: none; }
    .sl-bal-tag { font-size: 7.5px !important; margin-left: 2px; }
    .sl-ref-tag, .sl-ref-link { font-size: 8px !important; padding: 0 3px !important; }
    .sl-ledger-table td i { display: none; }
}
</style>
@endpush

@push('scripts')
<script>
function slRange(type) {
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
    } else {
        from = new Date('2000-01-01');
        to   = today;
    }
    // Local date format — avoids UTC timezone shift for UTC+6
    const fmt = d => {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    };
    document.querySelector('input[name=from]').value = fmt(from);
    document.querySelector('input[name=to]').value   = fmt(to);
    // Use ID to avoid submitting the logout form in the topbar
    document.getElementById('ledgerFilterForm').submit();
}
</script>
@endpush
