@extends('layouts.app')
@section('title', 'ক্যাশ মেমো')
@section('page-title', 'ক্যাশ মেমো')

@section('content')
@php
    $totalQty      = $sale->items->sum('quantity');
    $itemsSubtotal = $sale->items->sum('subtotal');
    $grandTotal    = $sale->total_amount + $sale->previous_due;
    $remaining     = $grandTotal - $sale->paid_amount;
@endphp

{{-- Action buttons (no-print) --}}
<div class="form-actions no-print" style="max-width:720px;margin-bottom:16px">
    <a href="{{ route('sales.index') }}" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> বিক্রয় তালিকা</a>
    <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> প্রিন্ট / PDF</button>
    <a href="{{ route('sales.edit', $sale) }}" class="btn" style="background:#fef9c3;color:#92400e;border:1px solid #fde68a">
        <i class="fas fa-pen-to-square"></i> সংশোধন করুন
    </a>
    <form class="admin-only" method="POST" action="{{ route('sales.destroy', $sale) }}" style="margin-left:auto"
        onsubmit="return confirm('এই বিক্রয় মুছলে স্টক ফেরত আসবে। নিশ্চিত?')">
        @csrf @method('DELETE')
        <button type="submit" class="btn" style="background:#fee2e2;color:#dc2626;border:1px solid #fca5a5">
            <i class="fas fa-trash"></i> মুছুন
        </button>
    </form>
</div>

<div class="cash-memo" id="cashMemo">

    {{-- ── STORE HEADER ─────────────────────────────────────────── --}}
    <div class="memo-header">

        {{-- Phone numbers float to top-right --}}
        @if($store['phone'] || $store['phone2'])
        <div class="memo-phones-right">
            @if($store['phone'])<span>{{ $store['phone'] }}</span>@endif
            @if($store['phone2'])<span>{{ $store['phone2'] }}</span>@endif
        </div>
        @endif

        {{-- Centered store info --}}
        <div class="memo-center">
            <div class="memo-title-label">
                ক্যাশ মেমো
                @if($sale->is_edited)
                <span class="memo-edited-badge">✎ সংশোধিত</span>
                @endif
            </div>
            <div class="memo-store-name">{{ $store['name'] }}</div>
            @if($store['owner'])
            <div class="memo-owner">প্রোঃ {{ $store['owner'] }}</div>
            @endif
            @if($store['tagline'])
            <div class="memo-tagline">{{ $store['tagline'] }}</div>
            @endif
            @if($store['address'])
            <div class="memo-address">{{ $store['address'] }}</div>
            @endif
        </div>

    </div>{{-- /.memo-header --}}

    {{-- ── INVOICE META ─────────────────────────────────────────── --}}
    <div class="memo-meta">
        <div class="memo-meta-row-top">
            <span><strong>চালান নং -</strong> {{ str_pad($sale->id, 6, '0', STR_PAD_LEFT) }}</span>
            <span><strong>তারিখঃ</strong> {{ $sale->sale_date->format('Y-m-d') }} - {{ $sale->created_at->format('h:i:sa') }}</span>
        </div>
        @if($sale->customer)
            <div class="memo-customer-row">
                <div class="memo-customer-left">
                    <div class="memo-meta-line"><span class="memo-meta-key">প্রতিষ্ঠানঃ</span> {{ $sale->customer->name }}</div>
                    @if($sale->customer->proprietor)
                    <div class="memo-meta-line"><span class="memo-meta-key">প্রোপ্রাইটরঃ</span> {{ $sale->customer->proprietor }}</div>
                    @endif
                    <div class="memo-meta-line"><span class="memo-meta-key">ঠিকানাঃ</span> {{ $sale->customer->address ?? '' }}</div>
                </div>
                @if($sale->customer->phone)
                <div class="memo-customer-phone">
                    <i class="fas fa-phone-alt" style="font-size:.7rem;margin-right:3px"></i>{{ $sale->customer->phone }}
                </div>
                @endif
            </div>
        @else
            <div class="memo-meta-line"><span class="memo-meta-key">প্রতিষ্ঠানঃ</span> ওয়াক-ইন কাস্টমার</div>
            <div class="memo-meta-line"><span class="memo-meta-key">প্রোপ্রাইটরঃ</span></div>
            <div class="memo-meta-line"><span class="memo-meta-key">ঠিকানাঃ</span></div>
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
                <td class="tr">{{ number_format($si->subtotal, 0) }} টাকা</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align:center;padding:14px 8px;color:#555;font-style:italic">
                    — শুধু বাকী পরিশোধ (কোনো পণ্য নেই) —
                </td>
            </tr>
            @endforelse
        </tbody>

        <tfoot>
            @if($sale->items->count() > 0)
            <tr class="tfoot-qty">
                <td colspan="4" class="tfoot-label">মোট {{ (int)$totalQty }} বস্তা</td>
                <td class="tr">{{ number_format($itemsSubtotal, 0) }} টাকা</td>
            </tr>
            @endif

            @if($sale->discount > 0)
            <tr class="tfoot-row">
                <td colspan="4" class="tfoot-label">ছাড়</td>
                <td class="tr tfoot-amount" style="color:#15803d">− {{ number_format($sale->discount, 0) }} টাকা</td>
            </tr>
            @endif
            @foreach($sale->extraCosts as $ec)
            <tr class="tfoot-row">
                <td colspan="4" class="tfoot-label">{{ $ec->category_name }}</td>
                <td class="tr tfoot-amount">+ {{ number_format($ec->amount, 0) }} টাকা</td>
            </tr>
            @endforeach
            @if($sale->extra_cost > 0 && $sale->extraCosts->isEmpty())
            {{-- Legacy single extra_cost (old records before categorized system) --}}
            <tr class="tfoot-row">
                <td colspan="4" class="tfoot-label">অতিরিক্ত খরচ</td>
                <td class="tr tfoot-amount">+ {{ number_format($sale->extra_cost, 0) }} টাকা</td>
            </tr>
            @endif
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
            <tr class="tfoot-row tfoot-grand-row">
                <td colspan="4" class="tfoot-label">সর্বমোট</td>
                <td class="tr tfoot-amount tfoot-grand">{{ number_format($grandTotal, 0) }} টাকা</td>
            </tr>
            <tr class="tfoot-row">
                <td colspan="4" class="tfoot-label">
                    পরিশোধ
                    @if($sale->payment_method)
                        <span style="font-size:.75rem;font-weight:500;color:#666;margin-left:6px">({{ $sale->payment_method }})</span>
                    @endif
                </td>
                <td class="tr tfoot-amount">{{ number_format($sale->paid_amount, 0) }} টাকা</td>
            </tr>
            <tr class="tfoot-row tfoot-balance-row">
                <td colspan="4" class="tfoot-label">বাকী</td>
                <td class="tr tfoot-amount tfoot-remaining">{{ number_format($remaining, 0) }} টাকা</td>
            </tr>
        </tfoot>
    </table>

    @if($sale->notes)
    <div style="margin-top:6px;font-size:.8rem;color:#555">মন্তব্যঃ {{ $sale->notes }}</div>
    @endif
    @if($sale->is_edited)
    <div style="margin-top:8px;padding:6px 10px;background:#fef9c3;border:1px dashed #fde68a;font-size:.8rem;color:#92400e">
        <strong>✎ সংশোধনের কারণঃ</strong> {{ $sale->edit_note ?: 'উল্লেখ করা হয়নি' }}
    </div>
    @endif

    {{-- ── FOOTER (like old system) ─────────────────────────────── --}}
    <div class="memo-footer">

        {{-- N.B. note --}}
        <div class="memo-footer-note">বিঃ দ্রঃ ক্রটিপূর্ণ মাল ফেরৎযোগ্য।</div>

        {{-- Store name repeated, big & bold --}}
        <div class="memo-footer-name">{{ $store['name'] }}</div>

        {{-- Tear-off cut line --}}
        <div class="memo-cut-line"><span>✂ ছিঁড়ে নিন</span></div>

        {{-- Blank counterfoil stub (handwritten) --}}
        <div class="memo-stub">
            <div class="memo-stub-challan"><strong>চালান নং -</strong> {{ str_pad($sale->id, 6, '0', STR_PAD_LEFT) }}</div>
            <table class="memo-stub-table">
                <thead>
                    <tr>
                        <th class="stub-qty">পরিমাণ</th>
                        <th class="stub-desc">আইটেম বিবরণ</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td></td><td></td></tr>
                    <tr><td></td><td></td></tr>
                </tbody>
            </table>
        </div>

        {{-- Our credit line --}}
        <div class="memo-credit">
            @php
                $swName  = \App\Models\SystemConfig::get('software_name', 'Ruposhi POS');
                $swPhone = \App\Models\SystemConfig::get('support_phone');
            @endphp
            Developed by {{ $swName }}@if($swPhone) &nbsp;•&nbsp; যোগাযোগঃ {{ $swPhone }}@endif
        </div>

    </div>{{-- /.memo-footer --}}

</div>{{-- /.cash-memo --}}
@endsection

@push('styles')
<style>
/* ══ Wrapper ════════════════════════════════════════════════════ */
.cash-memo {
    max-width: 700px;
    min-height: 1040px;          /* ~A4 height — fills the page on screen */
    display: flex;
    flex-direction: column;
    background: #fff;
    border: none;
    border-radius: 0;
    padding: 18px 22px 16px;
    font-family: 'Hind Siliguri', sans-serif;
    color: #111;
    font-size: .88rem;
    line-height: 1.45;
}

/* ══ Header ══════════════════════════════════════════════════════ */
.memo-header {
    position: relative;
    text-align: center;
    border-bottom: 2px solid #111;
    padding-bottom: 8px;
    margin-bottom: 7px;
}

/* Phone numbers — absolutely positioned top-right, out of flow */
.memo-phones-right {
    position: absolute;
    top: 0;
    right: 0;
    text-align: right;
    font-size: .82rem;
    font-weight: 700;
    line-height: 1.8;
    color: #111;
    display: flex;
    flex-direction: column;
}

/* Centered block — full width, unaffected by phone position */
.memo-center {
    text-align: center;
    width: 100%;
}
.memo-title-label {
    font-size: .78rem;
    letter-spacing: .12em;
    color: #555;
    margin-bottom: 1px;
}
.memo-edited-badge {
    display: inline-block;
    margin-left: 8px;
    background: #fef9c3;
    color: #92400e;
    border: 1px solid #fde68a;
    border-radius: 20px;
    padding: 0px 8px;
    font-size: .68rem;
    font-weight: 700;
    vertical-align: middle;
}
.memo-store-name {
    font-size: 1.65rem;
    font-weight: 900;
    color: #111;
    line-height: 1.15;
    letter-spacing: .01em;
}
.memo-owner   { font-size: .84rem; font-weight: 600; color: #222; margin-top: 2px; }
.memo-tagline { font-size: .80rem; color: #333; }
.memo-address { font-size: .78rem; color: #444; }

/* ══ Meta section ════════════════════════════════════════════════ */
.memo-meta {
    font-size: .83rem;
    line-height: 1.7;
    border-bottom: 1px solid #bbb;
    padding-bottom: 5px;
    margin-bottom: 6px;
}
.memo-meta-row-top {
    display: flex;
    justify-content: space-between;
    font-size: .83rem;
    margin-bottom: 1px;
}
.memo-meta-line { display: block; }
.memo-meta-key {
    font-weight: 700;
    display: inline-block;
    min-width: 94px;
}
.memo-customer-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 10px;
}
.memo-customer-left { flex: 1; }
.memo-customer-phone {
    font-size: .84rem;
    font-weight: 700;
    color: #111;
    text-align: right;
    white-space: nowrap;
    padding-top: 2px;
}

/* ══ Items table ═════════════════════════════════════════════════ */
.memo-table {
    width: 100%;
    border-collapse: collapse;
    font-size: .86rem;
}
.memo-table thead tr {
    background: #475569;
    color: #fff;
}
.memo-table th {
    padding: 5px 6px;
    font-weight: 700;
    border: 1px solid #334155;
    text-align: center;
}
.col-bosta { width: 50px; }
.col-desc  { }
.col-kg    { width: 48px; }
.col-rate  { width: 68px; }
.col-taka  { width: 100px; }

.memo-table tbody td {
    padding: 3px 6px;
    border: 1px solid #e0e0e0;
    vertical-align: middle;
}
.memo-table tbody tr:nth-child(even) { background: #fafafa; }
.tc { text-align: center; }
.tr { text-align: right;  }

/* ══ Tfoot ═══════════════════════════════════════════════════════ */
.tfoot-qty td {
    padding: 4px 6px;
    border: 1px solid #e0e0e0;
    border-top: 2px solid #334155;
    font-size: .84rem;
    font-weight: 700;
    background: #fff;
}
.tfoot-row td {
    padding: 3px 6px;
    border: 1px solid #e0e0e0;
    font-size: .85rem;
}
.tfoot-label  { font-weight: 600; }
.tfoot-amount { font-weight: 600; white-space: nowrap; }
.tfoot-grand-row td { border-top: 1.5px solid #aaa; border-bottom: 1.5px solid #aaa; }
.tfoot-grand  { font-weight: 800; font-size: .94rem; }
.tfoot-balance-row td { background: #fff7ed; }
.tfoot-remaining { font-weight: 800; font-size: .98rem; color: #b91c1c; }

/* ══ Footer (old-system style) ═══════════════════════════════════ */
.memo-footer { margin-top: auto; padding-top: 12px; text-align: center; }  /* pinned to bottom */
.memo-footer-note {
    font-size: .76rem;
    font-style: italic;
    color: #333;
    margin-bottom: 1px;
}
.memo-footer-name {
    font-size: 1.1rem;
    font-weight: 900;
    color: #111;
    line-height: 1.15;
    letter-spacing: .01em;
}

/* Tear-off cut line */
.memo-cut-line {
    position: relative;
    text-align: center;
    border-top: 1.5px dashed #888;
    margin: 8px 0 5px;
}
.memo-cut-line span {
    position: relative;
    top: -9px;
    background: #fff;
    padding: 0 8px;
    font-size: .66rem;
    color: #888;
    letter-spacing: .04em;
}

/* Blank counterfoil stub */
.memo-stub { text-align: left; }
.memo-stub-challan { font-size: .76rem; margin-bottom: 3px; }
.memo-stub-table {
    width: 100%;
    border-collapse: collapse;
    font-size: .8rem;
}
.memo-stub-table th {
    padding: 2px 8px;
    font-weight: 700;
    text-align: center;
    border: 1px solid #bbb;
    color: #333;
}
.memo-stub-table td {
    height: 20px;
    border: 1px solid #ccc;
    border-style: dotted;
}
.stub-qty  { width: 90px; }
.stub-desc { }

/* Our credit line */
.memo-credit {
    margin-top: 5px;
    text-align: center;
    font-size: .68rem;
    color: #888;
    border-top: 1px solid #e0e0e0;
    padding-top: 3px;
}

/* ══ Print ════════════════════════════════════════════════════════ */
@media print {
    @page {
        size: A4 portrait;
        margin: 14mm 14mm 14mm 14mm;
    }

    /* ── Step 1: hide the entire page ── */
    body * { visibility: hidden; }

    /* ── Step 2: show ONLY the invoice ── */
    #cashMemo,
    #cashMemo * { visibility: visible; }

    /* ── Step 3: pin invoice to top-left corner ── */
    #cashMemo {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100% !important;
        min-height: 268mm !important;   /* fill A4 printable area (297 − 14×2 margins) */
        display: flex !important;
        flex-direction: column !important;
        margin: 0 !important;
        padding: 0 !important;
        max-width: 100% !important;
        box-shadow: none !important;
        font-size: .82rem !important;
        font-family: 'Hind Siliguri', sans-serif !important;
        color: #111 !important;
        background: #fff !important;
    }

    /* ── Typography scale-down ── */
    .memo-store-name   { font-size: 1.3rem !important; }
    .memo-owner        { font-size: .76rem !important; }
    .memo-tagline      { font-size: .72rem !important; }
    .memo-address      { font-size: .70rem !important; }
    .memo-phones-right { font-size: .74rem !important; line-height: 1.6 !important; }
    .memo-title-label  { font-size: .70rem !important; }

    .memo-meta         { font-size: .78rem !important; line-height: 1.55 !important; }
    .memo-meta-row-top { font-size: .78rem !important; }
    .memo-meta-key     { min-width: 80px !important; }

    /* ── Table ── */
    .memo-table        { font-size: .78rem !important; }
    .memo-table th     { padding: 3px 5px !important; }
    .memo-table tbody td { padding: 3px 5px !important; border-color: #ddd !important; }
    .memo-table tbody tr:nth-child(even) { background: #f8f8f8 !important; }

    /* ── Tfoot ── */
    .tfoot-qty td  { padding: 3px 5px !important; font-size: .78rem !important; border-color: #ddd !important; }
    .tfoot-row td  { padding: 3px 5px !important; font-size: .78rem !important; border-color: #ddd !important; }
    .tfoot-grand   { font-size: .86rem !important; }
    .tfoot-remaining { font-size: .90rem !important; }
    .tfoot-balance-row td { background: #fff7ed !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .tfoot-grand-row td { border-top: 1.5px solid #888 !important; border-bottom: 1.5px solid #888 !important; }

    /* ── Red header: force color print ── */
    .memo-table thead tr { background: #475569 !important; color: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .memo-table th        { border-color: #334155 !important; }

    tfoot { page-break-inside: avoid; }

    /* ── Footer ── */
    .memo-footer       { margin-top: auto !important; padding-top: 10px !important; page-break-inside: avoid; }
    .memo-footer-note  { font-size: .7rem !important; margin-bottom: 0 !important; }
    .memo-footer-name  { font-size: 1rem !important; }
    .memo-cut-line     { margin: 7px 0 4px !important; border-top-color: #999 !important; }
    .memo-cut-line span { font-size: .6rem !important; }
    .memo-stub-challan { font-size: .7rem !important; margin-bottom: 2px !important; }
    .memo-stub-table   { font-size: .72rem !important; }
    .memo-stub-table th { padding: 2px 6px !important; border-color: #999 !important; }
    .memo-stub-table td { height: 18px !important; border-color: #aaa !important; }
    .memo-credit       { font-size: .62rem !important; border-top-color: #ddd !important; margin-top: 4px !important; padding-top: 2px !important; }
}

/* ══ Mobile view (screen only) ══════════════════════════════════ */
@media screen and (max-width: 480px) {
    .cash-memo {
        padding: 12px 12px 12px;
        font-size: .84rem;
    }
    /* Phone numbers: remove absolute, stack under store name */
    .memo-header { padding-bottom: 6px; }
    .memo-phones-right {
        position: static;
        text-align: center;
        flex-direction: row;
        justify-content: center;
        gap: 14px;
        margin-top: 4px;
        font-size: .8rem;
    }
    .memo-store-name { font-size: 1.3rem; }
    .memo-meta-row-top { flex-direction: column; gap: 0; }
    .memo-table th, .memo-table tbody td { padding: 3px 4px; font-size: .78rem; }
    .col-rate { width: 52px; }
    .col-taka { width: 80px; }
}
</style>
@endpush
