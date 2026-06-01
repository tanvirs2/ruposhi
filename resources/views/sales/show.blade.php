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
    <a href="{{ route('sales.index') }}" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> বিক্রয় তালিকা</a>
    <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> প্রিন্ট / PDF</button>
    <a href="{{ route('sales.edit', $sale) }}" class="btn" style="background:#fef9c3;color:#92400e;border:1px solid #fde68a">
        <i class="fas fa-pen-to-square"></i> সংশোধন করুন
    </a>
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
    <div class="memo-title-top">
        ক্যাশ মেমো
        @if($sale->is_edited)
        <span style="display:inline-block;margin-left:8px;background:#fef9c3;color:#92400e;
                     border:1px solid #fde68a;border-radius:20px;padding:1px 10px;
                     font-size:.72rem;font-weight:700;vertical-align:middle;letter-spacing:.04em">
            ✎ সংশোধিত
        </span>
        @endif
    </div>

    <div class="memo-header">
        <div class="memo-store-name">
            @include('partials.store-name-arc', ['name' => $store['name'], 'size' => 36])
        </div>
        <div class="memo-under-arch">
            @if($store['owner'])
            <div><span class="memo-owner-badge">প্রোঃ {{ $store['owner'] }}</span></div>
            @endif
            @if($store['tagline'])
            <div class="memo-tagline">{{ $store['tagline'] }}</div>
            @endif
            @if($store['address'])
            <div class="memo-address">{{ $store['address'] }}</div>
            @endif
            @if($store['phone'] || $store['phone2'])
            <div class="memo-phones">
                @if($store['phone']){{ $store['phone'] }}@endif
                @if($store['phone'] && $store['phone2']) &nbsp;|&nbsp; @endif
                @if($store['phone2']){{ $store['phone2'] }}@endif
            </div>
            @endif
        </div>
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
            @forelse($sale->items as $si)
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
            @empty
            <tr>
                <td colspan="5" style="text-align:center;padding:14px 8px;color:#64748b;font-style:italic;font-size:.85rem">
                    — শুধু বাকী পরিশোধ (কোনো পণ্য নেই) —
                </td>
            </tr>
            @endforelse
        </tbody>

        {{-- ── TOTALS (same table, continues border) ───────────── --}}
        <tfoot>
            @if($sale->items->count() > 0)
            <tr class="tfoot-qty">
                <td colspan="5">মোট &nbsp;<strong>{{ (int)$totalQty }}</strong></td>
            </tr>
            @endif
            {{-- Current invoice adjustments --}}
            @if($sale->discount > 0)
            <tr class="tfoot-row">
                <td colspan="4" class="tfoot-label">ছাড়</td>
                <td class="tr tfoot-amount" style="color:#15803d">− {{ number_format($sale->discount, 0) }} টাকা</td>
            </tr>
            @endif
            @if($sale->extra_cost > 0)
            <tr class="tfoot-row">
                <td colspan="4" class="tfoot-label">অতিরিক্ত খরচ</td>
                <td class="tr tfoot-amount">+ {{ number_format($sale->extra_cost, 0) }} টাকা</td>
            </tr>
            @endif
            @if($sale->labor_cost > 0)
            <tr class="tfoot-row">
                <td colspan="4" class="tfoot-label">শ্রমিক খরচ</td>
                <td class="tr tfoot-amount">+ {{ number_format($sale->labor_cost, 0) }} টাকা</td>
            </tr>
            @endif

            {{-- Previous due + grand total --}}
            @if($sale->previous_due != 0)
            <tr class="tfoot-row">
                <td colspan="4" class="tfoot-label">বিক্রয় মোট</td>
                <td class="tr tfoot-amount">{{ number_format($sale->total_amount, 0) }} টাকা</td>
            </tr>
            @endif
            <tr class="tfoot-row">
                <td colspan="4" class="tfoot-label">পূর্বের বাকী</td>
                <td class="tr tfoot-amount">{{ number_format($sale->previous_due, 0) }} টাকা</td>
            </tr>
            <tr class="tfoot-row">
                <td colspan="4" class="tfoot-label">সর্বমোট</td>
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
    @if($sale->is_edited)
    <div style="margin-top:8px;padding:7px 12px;background:#fef9c3;border:1px dashed #fde68a;
                border-radius:6px;font-size:.8rem;color:#92400e">
        <strong>✎ সংশোধনের কারণঃ</strong>
        {{ $sale->edit_note ?: 'উল্লেখ করা হয়নি' }}
    </div>
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

/* ══ Header (centered, store name arches over the info block) ══════ */
.memo-header {
    text-align: center;
    border-bottom: 2px solid #222;
    padding-bottom: 10px;
    margin-bottom: 8px;
}
.memo-store-name {
    margin: 0 auto;
    max-width: 540px;
}
.memo-under-arch {
    margin-top: -58px;
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
}
.memo-owner-badge {
    display: inline-block;
    background: #e5e7eb;
    border-radius: 999px;
    padding: 2px 16px;
    font-size: .88rem;
    font-weight: 600;
}
.memo-tagline { font-size: .82rem; color: #333; }
.memo-address { font-size: .8rem;  color: #555; }
.memo-phones {
    font-size: .92rem;
    font-weight: 700;
    margin-top: 3px;
    letter-spacing: .02em;
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
