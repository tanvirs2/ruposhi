@extends('layouts.app')
@section('title', 'রিসিভ বিবরণ')
@section('page-title', 'রিসিভ বিবরণ')

@section('content')
@php
    $store = [
        'name'    => \App\Models\StoreConfig::get('store_name', 'আমার দোকান'),
        'owner'   => \App\Models\StoreConfig::get('store_owner', ''),
        'tagline' => \App\Models\StoreConfig::get('store_tagline', ''),
        'phone'   => \App\Models\StoreConfig::get('store_phone', ''),
        'phone2'  => \App\Models\StoreConfig::get('store_phone2', ''),
        'address' => \App\Models\StoreConfig::get('store_address', ''),
    ];
    $itemsSubtotal = $purchase->items->sum('subtotal');
@endphp

{{-- Action buttons --}}
<div class="form-actions no-print" style="max-width:720px;margin-bottom:16px">
    <a href="{{ route('purchases.index') }}" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> রিসিভ তালিকা</a>
    <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> প্রিন্ট / PDF</button>
    <a href="{{ route('purchases.edit', $purchase) }}" class="btn" style="background:#fef9c3;color:#92400e;border:1px solid #fde68a">
        <i class="fas fa-pen-to-square"></i> সংশোধন
    </a>
    <form method="POST" action="{{ route('purchases.destroy',$purchase) }}" style="margin-left:auto"
        onsubmit="return confirm('মুছলে স্টক কমে যাবে। নিশ্চিত?')">
        @csrf @method('DELETE')
        <button type="submit" class="btn" style="background:#fee2e2;color:#dc2626;border:1px solid #fca5a5">
            <i class="fas fa-trash"></i> মুছুন
        </button>
    </form>
</div>

<div class="cash-memo" id="cashMemo">

    {{-- ── STORE HEADER ─────────────────────────────────────────── --}}
    <div class="memo-title-top">মাল রিসিভ রসিদ</div>

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
                <td><strong>রিসিভ নং -</strong> #RCV-{{ str_pad($purchase->id, 4, '0', STR_PAD_LEFT) }}</td>
                <td style="text-align:right"><strong>তারিখঃ</strong> &nbsp;{{ $purchase->purchase_date->format('Y-m-d') }} - {{ $purchase->created_at->format('h:i:sa') }}</td>
            </tr>
        </table>
        @if($purchase->supplier)
            <div class="memo-meta-line"><span class="memo-meta-key">সরবরাহকারীঃ</span> {{ $purchase->supplier->name }}</div>
            @if($purchase->supplier->phone)
            <div class="memo-meta-line"><span class="memo-meta-key">ফোনঃ</span> {{ $purchase->supplier->phone }}</div>
            @endif
            @if($purchase->supplier->address)
            <div class="memo-meta-line"><span class="memo-meta-key">ঠিকানাঃ</span> {{ $purchase->supplier->address }}</div>
            @endif
        @endif
        <div class="memo-meta-line"><span class="memo-meta-key">রেকর্ডকারীঃ</span> {{ $purchase->user->name }}</div>
    </div>

    {{-- ── ITEMS TABLE ──────────────────────────────────────────── --}}
    <table class="memo-table">
        <thead>
            <tr>
                <th class="col-bosta">বস্তা</th>
                <th class="col-desc">মালপত্রের বিবরণ</th>
                <th class="col-rate">দর</th>
                <th class="col-taka">টাকা</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchase->items as $pi)
            <tr>
                <td class="tc">{{ (int)$pi->quantity }}</td>
                <td>{{ $pi->item->name }}</td>
                <td class="tr">{{ number_format($pi->price, 0) }}</td>
                <td class="tr">{{ number_format($pi->subtotal, 0) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="tfoot-qty">
                <td colspan="4">মোট &nbsp;<strong>{{ (int)$purchase->items->sum('quantity') }}</strong> বস্তা</td>
            </tr>
            @if(($purchase->extra_cost ?? 0) > 0)
            <tr class="tfoot-row">
                <td colspan="3" class="tfoot-label">অতিরিক্ত খরচ</td>
                <td class="tr tfoot-amount">+ {{ number_format($purchase->extra_cost, 0) }} টাকা</td>
            </tr>
            @endif
            @if(($purchase->labor_cost ?? 0) > 0)
            <tr class="tfoot-row">
                <td colspan="3" class="tfoot-label">শ্রমিক খরচ</td>
                <td class="tr tfoot-amount">+ {{ number_format($purchase->labor_cost, 0) }} টাকা</td>
            </tr>
            @endif
            <tr class="tfoot-row">
                <td colspan="3" class="tfoot-label">মোট মূল্য</td>
                <td class="tr tfoot-amount tfoot-grand">{{ number_format($purchase->total_amount, 0) }} টাকা</td>
            </tr>
            <tr class="tfoot-row">
                <td colspan="3" class="tfoot-label">
                    পরিশোধ
                    @if($purchase->payment_method)
                        <span style="font-size:.75rem;font-weight:500;color:#64748b;margin-left:6px">({{ $purchase->payment_method }})</span>
                    @endif
                </td>
                <td class="tr tfoot-amount">{{ number_format($purchase->paid_amount, 0) }} টাকা</td>
            </tr>
            <tr class="tfoot-row tfoot-balance">
                <td colspan="3" class="tfoot-label">বকেয়া</td>
                <td class="tr tfoot-amount tfoot-remaining">
                    @if($purchase->due_amount > 0)
                        {{ number_format($purchase->due_amount, 0) }} টাকা
                    @elseif($purchase->due_amount < 0)
                        <span style="color:#1d4ed8">অগ্রিম {{ number_format(abs($purchase->due_amount), 0) }} টাকা</span>
                    @else
                        <span style="color:#16a34a;font-size:.9rem">সম্পূর্ণ পরিশোধিত ✓</span>
                    @endif
                </td>
            </tr>
        </tfoot>
    </table>

    @if($purchase->notes)
    <div style="margin-top:8px;font-size:.8rem;color:#555">মন্তব্যঃ {{ $purchase->notes }}</div>
    @endif
</div>
@endsection

@push('styles')
<style>
/* ══ Memo wrapper ══ (reuse sale invoice styles) */
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
.memo-title-top { text-align:center;font-size:.85rem;letter-spacing:.1em;color:#555;margin-bottom:2px; }
.memo-header { text-align:center;border-bottom:2px solid #222;padding-bottom:10px;margin-bottom:8px; }
.memo-store-name { margin:0 auto;max-width:540px; }
.memo-under-arch { margin-top:-58px;position:relative;z-index:1;display:flex;flex-direction:column;align-items:center;gap:4px; }
.memo-owner-badge { display:inline-block;background:#e5e7eb;border-radius:999px;padding:2px 16px;font-size:.88rem;font-weight:600; }
.memo-tagline { font-size:.82rem;color:#333; }
.memo-address  { font-size:.8rem;color:#555; }
.memo-phones   { font-size:.92rem;font-weight:700;margin-top:3px;letter-spacing:.02em; }
.memo-meta     { margin-bottom:8px;font-size:.85rem;line-height:1.7; }
.memo-meta-table { width:100%;border-collapse:collapse;margin-bottom:2px; }
.memo-meta-table td { padding:0; }
.memo-meta-line { display:block; }
.memo-meta-key  { font-weight:700;display:inline-block;min-width:100px; }

.memo-table { width:100%;border-collapse:collapse;font-size:.87rem; }
.memo-table thead tr { background:#c0392b;color:#fff; }
.memo-table th { padding:6px;font-weight:700;border:1px solid #922b21; }
.col-bosta { width:52px;text-align:center; }
.col-desc  { text-align:center; }
.col-rate  { width:70px;text-align:center; }
.col-taka  { width:90px;text-align:center; }
.memo-table tbody tr:nth-child(even) { background:#fafafa; }
.memo-table td { padding:4px 6px;border:1px solid #d1d5db;vertical-align:middle; }
.tc { text-align:center; } .tr { text-align:right; }
.tfoot-qty td  { padding:5px 6px;font-size:.85rem;border:1px solid #d1d5db;border-top:2px solid #922b21;background:#fff; }
.tfoot-row td  { padding:4px 6px;border:1px solid #d1d5db;font-size:.87rem; }
.tfoot-grand   { font-weight:800;font-size:.95rem;border-top:1px solid #aaa;border-bottom:1px solid #aaa; }
.tfoot-balance td { background:#fff7ed; }
.tfoot-remaining { font-weight:800;font-size:1rem;color:#b91c1c; }
.tfoot-amount  { font-weight:600;white-space:nowrap; }

@media print {
    .sidebar,.topbar,.no-print { display:none !important; }
    .main-wrapper { margin-left:0 !important; }
    .content { padding:8px !important; }
    .cash-memo { border:none;border-radius:0;padding:8px 12px;max-width:100%;box-shadow:none; }
}
</style>
@endpush
