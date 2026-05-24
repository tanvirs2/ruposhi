@extends('layouts.app')
@section('title', 'পরিশোধ রশিদ')
@section('page-title', 'পরিশোধ রশিদ')

@section('content')
@php
    $remaining = max(0, $customerPayment->previous_due - $customerPayment->amount);
@endphp

{{-- Action buttons (no-print) --}}
<div class="form-actions no-print" style="max-width:560px;margin-bottom:16px">
    <a href="{{ route('customer-payments.index') }}" class="btn btn-ghost">
        <i class="fas fa-arrow-left"></i> ফিরে যান
    </a>
    <button onclick="window.print()" class="btn btn-primary">
        <i class="fas fa-print"></i> প্রিন্ট / PDF
    </button>
    <form method="POST" action="{{ route('customer-payments.destroy', $customerPayment) }}"
          style="margin-left:auto"
          onsubmit="return confirm('এই পরিশোধ রেকর্ড মুছলে কাস্টমারের বাকী পুনরায় যোগ হবে। নিশ্চিত?')">
        @csrf @method('DELETE')
        <button type="submit" class="btn" style="background:#fee2e2;color:#dc2626;border:1px solid #fca5a5">
            <i class="fas fa-trash"></i> মুছুন
        </button>
    </form>
</div>

<div class="cash-memo" id="paymentReceipt">

    {{-- ── RECEIPT LABEL ──────────────────────────────────────── --}}
    <div class="memo-title-top">পরিশোধ রশিদ</div>

    {{-- ── STORE HEADER ─────────────────────────────────────────── --}}
    <div class="memo-header">
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
        @if($store['phone'] || $store['phone2'])
        <div class="memo-header-right">
            @if($store['phone'])<div>{{ $store['phone'] }}</div>@endif
            @if($store['phone2'])<div>{{ $store['phone2'] }}</div>@endif
        </div>
        @endif
    </div>

    {{-- ── RECEIPT META ─────────────────────────────────────────── --}}
    <div class="memo-meta">
        <table class="memo-meta-table">
            <tr>
                <td><strong>রশিদ নং -</strong> {{ str_pad($customerPayment->id, 6, '0', STR_PAD_LEFT) }}</td>
                <td style="text-align:right">
                    <strong>তারিখঃ</strong> &nbsp;{{ $customerPayment->payment_date->format('Y-m-d') }}
                </td>
            </tr>
        </table>
        <div class="memo-meta-line">
            <span class="memo-meta-key">প্রতিষ্ঠানঃ</span> {{ $customerPayment->customer->name }}
        </div>
        @if($customerPayment->customer->proprietor)
        <div class="memo-meta-line">
            <span class="memo-meta-key">প্রোপ্রাইটরঃ</span> {{ $customerPayment->customer->proprietor }}
        </div>
        @endif
        @if($customerPayment->customer->phone)
        <div class="memo-meta-line">
            <span class="memo-meta-key">ফোনঃ</span> {{ $customerPayment->customer->phone }}
        </div>
        @endif
        @if($customerPayment->customer->address)
        <div class="memo-meta-line">
            <span class="memo-meta-key">ঠিকানাঃ</span> {{ $customerPayment->customer->address }}
        </div>
        @endif
    </div>

    {{-- ── PAYMENT SUMMARY BOX ──────────────────────────────────── --}}
    <table class="memo-table">
        <thead>
            <tr>
                <th style="text-align:left">বিবরণ</th>
                <th style="text-align:right;width:140px">পরিমাণ (৳)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>পূর্বের বাকী</td>
                <td class="tr">{{ number_format($customerPayment->previous_due, 0) }} টাকা</td>
            </tr>
            <tr style="background:#dcfce7">
                <td>
                    <strong style="color:#15803d">আদায়কৃত পরিমাণ</strong>
                    <span style="font-size:.8rem;color:#64748b;margin-left:8px">
                        ({{ $customerPayment->payment_method }})
                    </span>
                </td>
                <td class="tr" style="color:#15803d;font-weight:700">
                    {{ number_format($customerPayment->amount, 0) }} টাকা
                </td>
            </tr>
        </tbody>
        <tfoot>
            <tr class="tfoot-row tfoot-balance">
                <td class="tfoot-label" style="font-weight:700">অবশিষ্ট বাকী</td>
                <td class="tr tfoot-amount {{ $remaining > 0 ? 'tfoot-remaining' : '' }}"
                    style="{{ $remaining == 0 ? 'color:#15803d' : '' }}">
                    {{ number_format($remaining, 0) }} টাকা
                    @if($remaining == 0)
                        <span style="font-size:.78rem;font-weight:700;color:#15803d;margin-left:6px">✓ বাকীমুক্ত</span>
                    @endif
                </td>
            </tr>
        </tfoot>
    </table>

    @if($customerPayment->notes)
    <div style="margin-top:10px;font-size:.8rem;color:#555">
        <strong>মন্তব্যঃ</strong> {{ $customerPayment->notes }}
    </div>
    @endif

    {{-- Signature area --}}
    <div style="display:flex;justify-content:space-between;margin-top:36px;font-size:.83rem;color:#555">
        <div style="text-align:center">
            <div style="border-top:1px solid #aaa;padding-top:4px;min-width:120px">কাস্টমারের স্বাক্ষর</div>
        </div>
        <div style="text-align:center">
            <div style="border-top:1px solid #aaa;padding-top:4px;min-width:120px">গ্রহণকারীর স্বাক্ষর</div>
        </div>
    </div>
</div>

@push('styles')
<style>
/* ══ Cash Memo wrapper ══════════════════════════════════════════ */
.cash-memo {
    max-width: 560px;
    background: #fff;
    border: 1px solid #ccc;
    border-radius: 6px;
    padding: 24px 28px 20px;
    font-family: 'Hind Siliguri', sans-serif;
    color: #111;
    font-size: .9rem;
}
.memo-title-top {
    text-align: center;
    font-size: .85rem;
    letter-spacing: .1em;
    color: #555;
    margin-bottom: 2px;
}
.memo-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    border-bottom: 2px solid #222;
    padding-bottom: 10px;
    margin-bottom: 8px;
    gap: 12px;
}
.memo-header-left { flex: 1; text-align: center; }
.memo-store-name  { font-size: 2rem; font-weight: 800; line-height: 1.15; margin-bottom: 4px; }
.memo-owner-badge {
    display: inline-block; background: #e5e7eb; border-radius: 999px;
    padding: 1px 16px; font-size: .88rem; font-weight: 600; margin-bottom: 3px;
}
.memo-tagline { font-size: .82rem; color: #333; margin-top: 2px; }
.memo-address { font-size: .8rem; color: #555; margin-top: 1px; }
.memo-header-right {
    text-align: right; font-weight: 700; font-size: .92rem;
    line-height: 1.9; white-space: nowrap; padding-top: 6px;
}
.memo-meta { margin-bottom: 10px; font-size: .85rem; line-height: 1.7; }
.memo-meta-table { width: 100%; border-collapse: collapse; margin-bottom: 2px; }
.memo-meta-table td { padding: 0; }
.memo-meta-line { display: block; }
.memo-meta-key  { font-weight: 700; display: inline-block; min-width: 90px; }
.memo-table {
    width: 100%; border-collapse: collapse; font-size: .87rem;
}
.memo-table thead tr { background: #0d9488; color: #fff; }
.memo-table th { padding: 6px 8px; font-weight: 700; border: 1px solid #0a7a70; }
.memo-table tbody td { padding: 6px 8px; border: 1px solid #d1d5db; vertical-align: middle; }
.memo-table tbody tr:nth-child(even) { background: #fafafa; }
.tr { text-align: right; }
.tfoot-row td  { padding: 6px 8px; border: 1px solid #d1d5db; font-size: .87rem; }
.tfoot-label   { }
.tfoot-amount  { font-weight: 600; white-space: nowrap; text-align: right; }
.tfoot-balance td { background: #fff7ed; }
.tfoot-remaining  { font-weight: 800; font-size: 1rem; color: #b91c1c; }

@media print {
    .sidebar, .topbar, .no-print { display: none !important; }
    .main-wrapper { margin-left: 0 !important; }
    .content { padding: 8px !important; }
    .cash-memo {
        border: none; border-radius: 0; padding: 8px 12px;
        max-width: 100%; box-shadow: none;
    }
}
</style>
@endpush
@endsection
