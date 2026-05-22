@extends('layouts.app')
@section('title', 'ক্যাশ মেমো')
@section('page-title', 'ক্যাশ মেমো')

@section('content')
@php
    $totalQty   = $sale->items->sum('quantity');
    $grandTotal = $sale->total_amount + $sale->previous_due;
    $remaining  = $grandTotal - $sale->paid_amount;
@endphp

{{-- Action buttons (no-print) --}}
<div class="form-actions no-print" style="max-width:720px;margin-bottom:16px">
    <a href="{{ route('sales.index') }}" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> ফিরে যান</a>
    <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> প্রিন্ট / PDF</button>
    <form method="POST" action="{{ route('sales.destroy', $sale) }}" style="margin-left:auto"
        onsubmit="return confirm('এই বিক্রয় মুছলে স্টক ফেরত আসবে। নিশ্চিত?')">
        @csrf @method('DELETE')
        <button type="submit" class="btn" style="background:#fee2e2;color:#dc2626;border:1px solid #fca5a5">
            <i class="fas fa-trash"></i> মুছুন
        </button>
    </form>
</div>

<div class="cash-memo" id="cashMemo">

    {{-- ── STORE HEADER ─────────────────────────────────────────── --}}
    <div class="memo-title-top">ক্যাশ মেমো</div>

    <div class="memo-header">
        {{-- Left: store name + badge + tagline + address --}}
        <div class="memo-header-left">
            <div class="memo-store-name">{{ $store['name'] }}</div>
            @if($store['owner'])
            <div><span class="memo-owner-badge">প্রোঃ {{ $store['owner'] }}</span></div>
            @endif
            @if($store['tagline'])
            <div class="memo-tagline">{{ $store['tagline'] }}</div>
            @endif
            <div class="memo-address">{{ $store['address'] }}</div>
        </div>

        {{-- Right: phone numbers stacked --}}
        @if($store['phone'] || $store['phone2'])
        <div class="memo-header-right">
            @if($store['phone'])<div>{{ $store['phone'] }}</div>@endif
            @if($store['phone2'])<div>{{ $store['phone2'] }}</div>@endif
        </div>
        @endif
    </div>

    {{-- ── INVOICE META ─────────────────────────────────────────── --}}
    <div class="memo-meta">
        <table class="memo-meta-table">
            <tr>
                <td><strong>চালান নং -</strong> {{ str_pad($sale->id, 6, '0', STR_PAD_LEFT) }}</td>
                <td style="text-align:right"><strong>তারিখঃ</strong> &nbsp;{{ $sale->sale_date->format('Y-m-d') }} - {{ $sale->created_at->format('h:i:sa') }}</td>
            </tr>
        </table>
        @if($sale->customer)
            <div class="memo-meta-line"><span class="memo-meta-key">প্রতিষ্ঠানঃ</span> {{ $sale->customer->name }}</div>
            @if($sale->customer->proprietor)
            <div class="memo-meta-line"><span class="memo-meta-key">প্রোপ্রাইটরঃ</span> {{ $sale->customer->proprietor }}</div>
            @endif
            <div class="memo-meta-line">
                <span class="memo-meta-key">ঠিকানাঃ</span>
                {{ $sale->customer->address ?? '—' }}
                @if($sale->customer->phone) &nbsp; {{ $sale->customer->phone }} @endif
            </div>
        @else
            <div class="memo-meta-line"><span class="memo-meta-key">প্রতিষ্ঠানঃ</span> ওয়াক-ইন কাস্টমার</div>
        @endif
    </div>

    {{-- ── ITEMS TABLE ──────────────────────────────────────────── --}}
    <table class="memo-table">
        <thead>
            <tr>
                <th class="col-bosta">বস্তা</th>
                <th class="col-desc">মালপত্রের বিবরণ</th>
                <th class="col-kg">কেজি</th>
                <th class="col-rate">দর</th>
                <th class="col-taka">টাকা</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $si)
            @php
                preg_match('/(৫০|২৫|50|25)\s*(কেজি|kg)/ui', $si->item->name, $m);
                $kg = isset($m[1]) ? str_replace(['৫০','২৫'],['50','25'], $m[1]) : '';
            @endphp
            <tr>
                <td class="tc">{{ (int)$si->quantity }}</td>
                <td>{{ $si->item->name }}</td>
                <td class="tc">{{ $kg }}</td>
                <td class="tr">{{ number_format($si->price, 0) }}</td>
                <td class="tr">{{ number_format($si->subtotal, 0) }}</td>
            </tr>
            @endforeach
        </tbody>

        {{-- ── TOTALS (same table, continues border) ───────────── --}}
        <tfoot>
            <tr class="tfoot-qty">
                <td colspan="5">মোট &nbsp;<strong>{{ (int)$totalQty }}</strong></td>
            </tr>
            @if($sale->discount > 0)
            <tr class="tfoot-row">
                <td colspan="4" class="tfoot-label">ছাড়</td>
                <td class="tr tfoot-amount">{{ number_format($sale->discount, 0) }} টাকা</td>
            </tr>
            @endif
            <tr class="tfoot-row">
                <td colspan="4" class="tfoot-label">পূর্বের বাকী</td>
                <td class="tr tfoot-amount">{{ number_format($sale->previous_due, 0) }} টাকা</td>
            </tr>
            <tr class="tfoot-row">
                <td colspan="4" class="tfoot-label">সর্বমোট</td>
                <td class="tr tfoot-amount">{{ number_format($sale->total_amount, 0) }} টাকা</td>
            </tr>
            <tr class="tfoot-row">
                <td colspan="4" class="tfoot-label"></td>
                <td class="tr tfoot-amount tfoot-grand">{{ number_format($grandTotal, 0) }} টাকা</td>
            </tr>
            <tr class="tfoot-row">
                <td colspan="4" class="tfoot-label">
                    পরিশোধ
                    @if($sale->payment_method)
                        <span style="font-size:.75rem;font-weight:500;color:#64748b;margin-left:6px">({{ $sale->payment_method }})</span>
                    @endif
                </td>
                <td class="tr tfoot-amount">{{ number_format($sale->paid_amount, 0) }} টাকা</td>
            </tr>
            <tr class="tfoot-row tfoot-balance">
                <td colspan="4" class="tfoot-label">বাকী</td>
                <td class="tr tfoot-amount tfoot-remaining">{{ number_format($remaining, 0) }} টাকা</td>
            </tr>
        </tfoot>
    </table>

    @if($sale->notes)
    <div style="margin-top:8px;font-size:.8rem;color:#555">মন্তব্যঃ {{ $sale->notes }}</div>
    @endif
</div>
@endsection

@push('styles')
<style>
/* ══ Cash Memo wrapper ══════════════════════════════════════════ */
.cash-memo {
    max-width: 720px;
    background: #fff;
    border: 1px solid #ccc;
    border-radius: 6px;
    padding: 24px 28px 20px;
    font-family: 'Hind Siliguri', sans-serif;
    color: #111;
    font-size: .9rem;
}

/* ══ Title "ক্যাশ মেমো" ══════════════════════════════════════════ */
.memo-title-top {
    text-align: center;
    font-size: .85rem;
    letter-spacing: .1em;
    color: #555;
    margin-bottom: 2px;
}

/* ══ Header row (store name LEFT, phones RIGHT) ══════════════════ */
.memo-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    border-bottom: 2px solid #222;
    padding-bottom: 10px;
    margin-bottom: 8px;
    gap: 12px;
}
.memo-header-left {
    flex: 1;
    text-align: center;
}
.memo-store-name {
    font-size: 2rem;
    font-weight: 800;
    line-height: 1.15;
    margin-bottom: 4px;
}
.memo-owner-badge {
    display: inline-block;
    background: #e5e7eb;
    border-radius: 999px;
    padding: 1px 16px;
    font-size: .88rem;
    font-weight: 600;
    margin-bottom: 3px;
}
.memo-tagline {
    font-size: .82rem;
    color: #333;
    margin-top: 2px;
}
.memo-address {
    font-size: .8rem;
    color: #555;
    margin-top: 1px;
}
.memo-header-right {
    text-align: right;
    font-weight: 700;
    font-size: .92rem;
    line-height: 1.9;
    white-space: nowrap;
    padding-top: 6px;
}

/* ══ Invoice meta section ════════════════════════════════════════ */
.memo-meta {
    margin-bottom: 8px;
    font-size: .85rem;
    line-height: 1.7;
}
.memo-meta-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 2px;
}
.memo-meta-table td { padding: 0; }
.memo-meta-line { display: block; }
.memo-meta-key {
    font-weight: 700;
    display: inline-block;
    min-width: 90px;
}

/* ══ Items + Totals table ════════════════════════════════════════ */
.memo-table {
    width: 100%;
    border-collapse: collapse;
    font-size: .87rem;
}

/* Header row */
.memo-table thead tr {
    background: #c0392b;
    color: #fff;
}
.memo-table th {
    padding: 6px 6px;
    font-weight: 700;
    border: 1px solid #922b21;
}
.col-bosta { width: 52px;  text-align: center; }
.col-desc  {              text-align: center; }
.col-kg    { width: 50px;  text-align: center; }
.col-rate  { width: 70px;  text-align: center; }
.col-taka  { width: 90px;  text-align: center; }

/* Body rows */
.memo-table tbody tr { }
.memo-table tbody tr:nth-child(even) { background: #fafafa; }
.memo-table td {
    padding: 4px 6px;
    border: 1px solid #d1d5db;
    vertical-align: middle;
}
.tc { text-align: center; }
.tr { text-align: right; }

/* ══ Tfoot (totals) ══════════════════════════════════════════════ */
.tfoot-qty td {
    padding: 5px 6px;
    font-size: .85rem;
    border: 1px solid #d1d5db;
    border-top: 2px solid #922b21;
    background: #fff;
}
.tfoot-row td {
    padding: 4px 6px;
    border: 1px solid #d1d5db;
    font-size: .87rem;
}
.tfoot-label { }
.tfoot-amount {
    font-weight: 600;
    white-space: nowrap;
}
.tfoot-grand {
    font-weight: 800;
    font-size: .95rem;
    border-top: 1px solid #aaa;
    border-bottom: 1px solid #aaa;
}
.tfoot-balance td {
    background: #fff7ed;
}
.tfoot-remaining {
    font-weight: 800;
    font-size: 1rem;
    color: #b91c1c;
}

/* ══ Print ════════════════════════════════════════════════════════ */
@media print {
    .sidebar, .topbar, .no-print { display: none !important; }
    .main-wrapper { margin-left: 0 !important; }
    .content { padding: 8px !important; }
    .cash-memo {
        border: none;
        border-radius: 0;
        padding: 8px 12px;
        max-width: 100%;
        box-shadow: none;
    }
}
</style>
@endpush
