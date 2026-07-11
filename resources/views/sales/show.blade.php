@extends('layouts.app')
@section('title', 'ক্যাশ মেমো')
@section('page-title', 'ক্যাশ মেমো')
@section('no-print-header', '1')

@section('content')
{{-- Hind Siliguri faces must exist on this page even when the shop uses another
     font: the print CSS forces the memo's DATA sections (meta/table/footer) to
     Hind Siliguri so the one-page fit calibration holds for every shop font. --}}
@include('partials.base-fonts')
{{-- Shobuj Bhulua — self-hosted, used ONLY for the store-name heading on this memo --}}
<link rel="preload" href="{{ asset('fonts/shobuj_bhulua.woff2') }}" as="font" type="font/woff2" crossorigin>
<style>
@font-face { font-family:'Shobuj Bhulua'; font-style:normal; font-weight:400 900; font-display:block;
    src:url('{{ asset('fonts/shobuj_bhulua.woff2') }}') format('woff2'); }
</style>
<script>
// Force-download the print faces NOW. @font-face fetches lazily on first USE —
// when the shop font isn't Hind Siliguri, print is the first use, so the first
// Ctrl+P rendered invisible text (font-display:block) until the dialog was
// reopened. The load() text has Bengali + digits so both unicode-range subsets
// (bengali + latin) fetch. Cached after first visit; no-op when already loaded.
(function () {
    if (!document.fonts || !document.fonts.load) return;
    ['400', '600', '700'].forEach(function (w) {
        document.fonts.load(w + ' 1rem "Hind Siliguri"', 'বাকী মোট ০১২৩ 0123 ৳');
    });
    document.fonts.load('700 1rem "Shobuj Bhulua"', 'মেসার্স রূপসী বাংলা ট্রেডার্স');
})();
</script>
@php
    $totalQty      = $sale->items->sum('quantity');
    $itemsSubtotal = $sale->items->sum('subtotal');
    $grandTotal    = $sale->total_amount + $sale->previous_due;
    $remaining     = $grandTotal - $sale->paid_amount;

    /* ── Print density — ONE fixed size, owner's directive ─────────────
       No dynamic scaling: staff read the same size every day. Owner capped
       one-page memos at 20 rows (was 25 — 25 printed too small/tight). With
       only 20 rows the fixed font is 0.90rem (bigger, easier to read; ~5mm
       rows × 20 ≈ 100mm, well inside the ~121mm row budget with a full 7-row
       tfoot). >20 rows flows to 2 pages (same layout, still readable font).
       CSS vars consumed by the @media print rules. */
    $itemCount = $sale->items->count();
    $memoMulti = $itemCount > 20;
    if ($memoMulti) { $mFont = '0.92'; $mPad = '2'; $mLh = '1.2'; }
    else            { $mFont = '0.92'; $mPad = '2'; $mLh = '1.2'; }
@endphp

{{-- Action buttons (no-print) --}}
<div class="form-actions no-print" style="max-width:720px;margin-bottom:16px">
    <a href="{{ route('sales.index') }}" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> বিক্রয় তালিকা</a>
    <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> প্রিন্ট / PDF</button>
    @if($sale->customer && $sale->customer->phone)
    @php
        $waMsg = $store['name'] . "\n"
            . "ক্যাশ মেমো #" . str_pad($sale->id, 6, '0', STR_PAD_LEFT) . " — " . $sale->sale_date->format('d/m/Y') . "\n"
            . ($sale->previous_due != 0 ? "বিক্রয়: ৳" . number_format($sale->total_amount, 0) . "\nপূর্বের বাকী: ৳" . number_format($sale->previous_due, 0) . "\n" : '')
            . "সর্বমোট: ৳" . number_format($grandTotal, 0) . "\n"
            . "পরিশোধ: ৳" . number_format($sale->paid_amount, 0) . "\n"
            . "বাকী: ৳" . number_format($remaining, 0)
            . ($store['phone'] ? "\nযোগাযোগ: " . $store['phone'] : '');
    @endphp
    <button type="button" class="btn" style="background:#dcfce7;color:#15803d;border:1px solid #86efac"
            onclick="openWhatsApp(@js($sale->customer->phone), @js($waMsg))">
        <i class="fab fa-whatsapp"></i> WhatsApp
    </button>
    @endif
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

<div class="cash-memo{{ $memoMulti ? ' memo-multi' : '' }}" id="cashMemo" style="--m-font:{{ $mFont }}rem; --m-pad:{{ $mPad }}px; --m-lh:{{ $mLh }};">

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
            @if($store['tagline'] || $store['address'])
            {{-- tagline + address on ONE line — saves a full header row on the small memo paper --}}
            <div class="memo-tagline">{{ $store['tagline'] }}@if($store['tagline'] && $store['address'])<span class="memo-tagline-sep">, </span>@endif{{ $store['address'] }}</div>
            @endif
        </div>

    </div>{{-- /.memo-header --}}

    {{-- ── INVOICE META ─────────────────────────────────────────── --}}
    <div class="memo-meta">
        <div class="memo-meta-row-top">
            <span>
                <strong>চালান নং -</strong> {{ str_pad($sale->id, 6, '0', STR_PAD_LEFT) }}
                <span class="memo-operator"><strong>বিক্রেতাঃ</strong> {{ $sale->user?->name ?? '—' }}</span>
            </span>
            <span><strong>তারিখঃ</strong> {{ $sale->sale_date->format('Y-m-d') }} - {{ $sale->created_at->format('h:i:sa') }}</span>
        </div>
        @if($sale->customer)
            <div class="memo-customer-row">
                <div class="memo-customer-left">
                    {{-- প্রতিষ্ঠান + প্রোপ্রাইটর on one line — 3 lines → 2 on the small memo paper --}}
                    <div class="memo-meta-line">
                        <span class="memo-meta-key">প্রতিষ্ঠানঃ</span> {{ $sale->customer->name }}
                        @if($sale->customer->proprietor)
                        <span class="memo-meta-key" style="margin-left:12px;min-width:0">প্রোপ্রাইটরঃ</span> {{ $sale->customer->proprietor }}
                        @endif
                    </div>
                    <div class="memo-meta-line"><span class="memo-meta-key">ঠিকানাঃ</span> {{ $sale->customer->address ?? '' }}</div>
                </div>
                @if($sale->customer->phone)
                <div class="memo-customer-phone">
                    <i class="fas fa-phone-alt" style="font-size:.7rem;margin-right:3px"></i>{{ $sale->customer->phone }}
                </div>
                @endif
            </div>
        @else
            <div class="memo-meta-line">
                <span class="memo-meta-key">প্রতিষ্ঠানঃ</span> ওয়াক-ইন কাস্টমার
                <span class="memo-meta-key" style="margin-left:12px;min-width:0">প্রোপ্রাইটরঃ</span>
            </div>
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
                <td class="tr">{{ number_format($si->subtotal, 0) }}</td>
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

    @if(\App\Support\BanglaWords::taka($sale->paid_amount) !== '')
    <div class="memo-words" style="margin-top:6px;font-size:.85rem;color:#333">
        <strong>কথায় (পরিশোধ):</strong> {{ \App\Support\BanglaWords::taka($sale->paid_amount) }} মাত্র
    </div>
    @endif

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
        <div class="memo-footer-note">বিঃ দ্রঃ ক্রটিপূর্ণ পণ্য ফেরৎযোগ্য।</div>

        {{-- Tear-off cut line --}}
        <div class="memo-cut-line"><span>✂ ছিঁড়ে নিন</span></div>

        {{-- Store name repeated, big & bold — BELOW the cut line, on the kept stub --}}
        <div class="memo-footer-name">{{ $store['name'] }}</div>

        {{-- Counterfoil stub — challan no + customer/date on ONE line (saves a line vs the old 2-row table) --}}
        <div class="memo-stub">
            <div class="memo-stub-line">
                <span><strong>চালান নং -</strong> {{ str_pad($sale->id, 6, '0', STR_PAD_LEFT) }}</span>
                <span><strong>কাস্টমারঃ</strong> {{ $sale->customer->name ?? 'ওয়াক-ইন কাস্টমার' }}</span>
                <span><strong>তারিখঃ</strong> {{ $sale->sale_date->format('Y-m-d') }} - {{ $sale->created_at->format('h:i:sa') }}</span>
            </div>
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
    font-family: var(--bn-font, 'Hind Siliguri'), sans-serif;
    color: #111;
    font-size: .88rem;
    line-height: 1.45;
}

/* ══ Header ══════════════════════════════════════════════════════ */
.memo-header {
    position: relative;
    text-align: center;
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
    font-family: 'Shobuj Bhulua', var(--bn-font, 'Hind Siliguri'), sans-serif;   /* dedicated heading font (owner) */
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
.memo-operator { margin-left: 16px; }   /* operator (বিক্রেতা) inline after চালান নং — no extra row */
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
    margin-bottom: 10px;   /* clear the "✂ ছিঁড়ে নিন" line — Bengali descenders were touching it */
}

/* Tear-off cut line */
.memo-cut-line {
    position: relative;
    text-align: center;
    border-top: 1.5px dashed #888;
    margin: 8px 0 5px;
    top: 6px;   /* nudge the dashed line down (owner request) — position shift only, doesn't change spacing */
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
/* Challan + customer + date on ONE line (tear-off stub) — bordered box */
.memo-stub-line {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 4px 14px;
    font-size: .8rem;
    border: 1px solid #ccc;
    padding: 4px 8px;
    border-radius: 4px;
}

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
    /* NO fixed @page size — a hardcoded size that differs from the paper chosen in
       the print dialog makes Chrome center the smaller page on the larger sheet,
       creating a huge top gap. Like the old phppos memo, let the dialog's paper
       size win; the container padding below keeps content off the red border. */
    @page {
        margin: 0;
    }

    /* ── Step 1: hide the entire page ── */
    body * { visibility: hidden; }

    /* ── Step 2: show ONLY the invoice ── */
    #cashMemo,
    #cashMemo * { visibility: visible; }

    /* ── Step 3: pin invoice to the top, centered horizontally ──
       left:0 + right:0 + margin:auto centers the fixed-width memo on
       whatever paper the dialog picked (matches the old phppos memo). */
    #cashMemo {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        width: 165mm !important;
        /* EXPLICIT height (not min-height): a position:fixed box collapses to its
           content height in real print, so with min-height the footer floated up
           for short memos (few items) but sat at the bottom for full ones (20+).
           A fixed height forces the box to always be 230mm → the absolute-pinned
           tear-off lands at the paper's bottom for ANY item count. Content is
           always ≤20 items (< 230mm) so nothing is clipped. Tune this single
           value to move the whole tear-off up/down. */
        height: 230mm !important;
        min-height: 230mm !important;
        box-sizing: border-box !important;
        padding: 8mm 15mm 15mm !important;  /* top trimmed to 8mm (owner's print test — minimal gap above "ক্যাশ মেমো"); sides/bottom stay 15mm to clear the red border */
        display: flex !important;
        flex-direction: column !important;
        margin: 0 auto !important;
        max-width: 100% !important;
        box-shadow: none !important;
        font-size: .78rem !important;
        line-height: 1.3 !important;
        font-family: var(--bn-font, 'Hind Siliguri'), sans-serif !important;
        color: #111 !important;
        background: #fff !important;
    }

    /* ── Multi-page mode (>25 items) — a position:fixed box clips everything
       past one sheet, so switch to normal block flow: rows continue onto
       page 2 (block layout fragments reliably; flex doesn't always). The memo
       is exactly 2 sheets tall (444mm) and the tear-off footer is absolutely
       pinned to the BOTTOM of page 2, matching the single-page look.
       thead repeats per page; rows never split mid-line. ── */
    #cashMemo.memo-multi {
        position: relative !important;      /* anchor for the abs-pos footer */
        min-height: 444mm !important;       /* 2 × 222mm sheets */
        display: block !important;
        padding: 8mm 15mm 15mm !important;
    }
    .memo-multi .memo-footer {
        position: absolute !important;
        left: 15mm !important; right: 15mm !important;
        bottom: 15mm !important;            /* same clearance as the sheet padding */
    }
    /* Collapse hidden ancestors' ghost space so the memo starts at the sheet top
       and no blank trailing pages print */
    body:has(#cashMemo.memo-multi) { height: auto !important; }
    body:has(#cashMemo.memo-multi) .main-wrapper,
    body:has(#cashMemo.memo-multi) .content {
        min-height: 0 !important; height: auto !important;
        padding: 0 !important; margin: 0 !important;
    }
    .memo-multi .memo-table tr { page-break-inside: avoid; }

    /* ── Data sections always print in Hind Siliguri ──
       Decorative shop fonts (Sonali Borno etc.) have taller vertical metrics —
       rows grew ~50% and broke the one-page calibration. The HEADER keeps the
       shop's chosen font (branding); the data below it prints in the stable
       font the tiers were calibrated for. ── */
    #cashMemo .memo-meta,
    #cashMemo .memo-table,
    #cashMemo .memo-words,
    #cashMemo .memo-footer { font-family: 'Hind Siliguri', sans-serif !important; }

    /* ── Typography scale-down + tight header.
       Bengali fonts carry extra internal leading, so the store name / label boxes
       print taller than the glyphs need. Pulling line-height to ~1.0 collapses that
       top/bottom gap around "ক্যাশ মেমো" and the store name. ── */
    .memo-header       { padding-bottom: 2px !important; margin-bottom: 2px !important; }
    .memo-store-name   { font-family: 'Shobuj Bhulua', 'Hind Siliguri', sans-serif !important; font-size: 1.85rem !important; line-height: 1.05 !important; margin-top: 20px !important; }
    .memo-owner        { font-size: .78rem !important; font-weight: 700 !important; margin-top: 0 !important; line-height: 1.05 !important; }
    .memo-tagline      { font-size: .66rem !important; line-height: 1.05 !important; }
    .memo-phones-right { font-size: .70rem !important; line-height: 1.25 !important; }
    /* "ক্যাশ মেমো" label moves to the top-LEFT corner (mirror of the phones on the
       right) — frees a full line above the store name */
    .memo-title-label  { position: absolute !important; top: 0 !important; left: 0 !important;
                         font-size: .62rem !important; margin-bottom: 0 !important; line-height: 1.0 !important; }

    .memo-meta         { font-size: .72rem !important; line-height: 1.3 !important;
                         padding-bottom: 2px !important; margin-bottom: 2px !important; }
    .memo-meta-row-top { font-size: .72rem !important; margin-bottom: 0 !important; }
    .memo-meta-key     { min-width: 0 !important; }
    .memo-customer-phone { font-size: .72rem !important; padding-top: 0 !important; }

    /* ── Table — font/row-height driven by the dynamic --m-* vars set on
       #cashMemo (PHP picks them from the item count). Fewer items → bigger;
       more items → smaller. Left/right padding stays 4px so text keeps off
       the borders; only the vertical padding scales. Fallbacks = tight. ── */
    .memo-table        { font-size: var(--m-font, .70rem) !important; line-height: var(--m-lh, 1.0) !important; }
    .memo-table th     { padding: var(--m-pad, 1px) 4px !important; line-height: var(--m-lh, 1.0) !important; }
    .memo-table tbody td { padding: var(--m-pad, 0px) 4px !important; line-height: var(--m-lh, 1.0) !important; border-color: #bbb !important; }
    .memo-table tbody tr:nth-child(even) { background: #fff !important; }

    /* ── Tfoot — same dynamic scale so the summary matches the items ── */
    .tfoot-qty td  { padding: var(--m-pad, 0px) 4px !important; line-height: var(--m-lh, 1.0) !important; font-size: var(--m-font, .72rem) !important; border-color: #ddd !important; }
    .tfoot-row td  { padding: var(--m-pad, 0px) 4px !important; line-height: var(--m-lh, 1.0) !important; font-size: var(--m-font, .72rem) !important; border-color: #ddd !important; }
    .tfoot-grand   { font-size: calc(var(--m-font, .72rem) + .08rem) !important; }
    .tfoot-remaining { font-size: calc(var(--m-font, .72rem) + .12rem) !important; }
    /* Ink-saving: white background, dark text — the highlighted rows keep
       their emphasis with borders/weight instead of tinted fills */
    .tfoot-balance-row td { background: #fff !important; }
    .tfoot-grand-row td { border-top: 1.5px solid #555 !important; border-bottom: 1.5px solid #555 !important; }

    .memo-table thead tr { background: #fff !important; color: #000 !important; }
    .memo-table th        { border-color: #999 !important; border-bottom: 1.5px solid #000 !important; }

    tfoot { page-break-inside: avoid; }

    /* ── কথায় / মন্তব্য lines ── */
    /* কথায় (পরিশোধ) line hidden in print — owner: the numeric amount is enough, saves a line */
    .memo-words        { display: none !important; }

    /* ── Footer — pinned at a FIXED DISTANCE FROM THE SHEET TOP, not from the
       page-box bottom. The owner prints with A4 selected in the dialog while
       the real paper is 1/4 Demy (165×222mm): pinning to the A4 bottom (297mm)
       pushed the tear-off past the real paper entirely. Instead the memo box
       is 240mm tall (owner-calibrated) and the footer sits at its bottom —
       tune #cashMemo's min-height below if the stub needs to move up/down.
       Single page only; the multi-page rule above overrides for >25 items. ── */
    .memo-footer       {
        position: absolute !important;   /* anchored inside the fixed memo box */
        bottom: 2mm !important;
        left: 15mm !important; right: 15mm !important;
        padding-top: 4px !important;
        page-break-inside: avoid;
    }
    .memo-footer-note  { font-size: .64rem !important; margin-bottom: 0 !important; line-height: 1.2 !important; }
    .memo-footer-name  { font-size: .85rem !important; margin-bottom: 6px !important; }
    .memo-cut-line     { margin: 2px 0 1px !important; border-top-color: #999 !important; top: 6px !important; }   /* nudge dashed line down */
    .memo-cut-line span { font-size: .56rem !important; top: -5px !important; }   /* smaller pull than screen's -9px — keeps the label off the store name's descenders */
    .memo-stub-line    { font-size: .66rem !important; padding: 2px 6px !important; border-color: #999 !important; gap: 2px 10px !important; }
    .memo-credit       { font-size: .58rem !important; border-top-color: #ddd !important; margin-top: 2px !important; padding-top: 1px !important; }
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
