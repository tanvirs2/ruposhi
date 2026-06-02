@extends('layouts.app')
@section('title', 'বিক্রয় রিপোর্ট')
@section('page-title', 'দৈনিক বিক্রয় রিপোর্ট')

@section('content')

{{-- Filter ────────────────────────────────────────────────── --}}
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
            <div class="form-group-field">
                <label>কাস্টমার</label>
                <select name="customer_id" class="form-select" style="min-width:180px">
                    <option value="">সব কাস্টমার</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" @selected(request('customer_id') == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="align-self:flex-end">
                <i class="fas fa-search"></i> রিপোর্ট দেখুন
            </button>
            @if(request()->hasAny(['customer_id']))
                <a href="{{ route('reports.sales', ['from'=>$from,'to'=>$to]) }}"
                   class="btn btn-ghost" style="align-self:flex-end">পরিষ্কার</a>
            @endif
            <div style="align-self:flex-end;display:flex;gap:8px;margin-left:auto">
                <a href="{{ route('reports.export.sales', array_filter(['from'=>$from,'to'=>$to,'customer_id'=>request('customer_id')])) }}"
                   class="btn btn-export" title="CSV ডাউনলোড">
                    <i class="fas fa-file-csv"></i> CSV
                </a>
                <button type="button" class="btn btn-export-print" onclick="window.print()">
                    <i class="fas fa-print"></i> প্রিন্ট
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Print header ────────────────────────────────────────────── --}}
<div class="print-header" style="display:none;text-align:center;margin-bottom:14px">
    @include('partials.store-name-arc', ['name' => \App\Models\StoreConfig::get('store_name','আমার দোকান'), 'size' => 26])
    <div style="font-size:.85rem;color:#555">দৈনিক বিক্রয় রিপোর্ট</div>
    <div style="font-size:.8rem;color:#777">
        তারিখ: {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }}
        @if($from !== $to) — {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }} @endif
    </div>
</div>

{{-- Summary cards ───────────────────────────────────────────── --}}
<div class="sales-stats-grid no-print">
    <div class="stat-card stat-green">
        <div class="stat-icon"><i class="fas fa-receipt"></i></div>
        <div class="stat-body">
            <span class="stat-label">মোট বিক্রয়</span>
            <span class="stat-value">৳ {{ number_format($grandTotal, 0) }}</span>
        </div>
    </div>
    <div class="stat-card stat-blue">
        <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
        <div class="stat-body">
            <span class="stat-label">বিক্রয়ে পরিশোধ</span>
            <span class="stat-value">৳ {{ number_format($grandItemPaid, 0) }}</span>
            @php $extraPaid = $noItemSales->sum('paid_amount') + $grandStandalone; @endphp
            @if($extraPaid > 0)
            <span style="font-size:.72rem;color:#2563eb;font-weight:600;margin-top:2px;display:block">
                + ৳ {{ number_format($extraPaid, 0) }} বাকী পরিশোধ
            </span>
            @endif
        </div>
    </div>
    <div class="stat-card" style="border-left:4px solid #16a34a;background:linear-gradient(135deg,#f0fdf4,#dcfce7)">
        <div class="stat-icon" style="background:#bbf7d0;color:#15803d"><i class="fas fa-hand-holding-dollar"></i></div>
        <div class="stat-body">
            <span class="stat-label" style="color:#15803d;font-weight:700">মোট প্রাপ্তি</span>
            <span class="stat-value" style="color:#15803d">৳ {{ number_format($grandPaid, 0) }}</span>
            @if($extraPaid > 0)
            <span style="font-size:.70rem;color:#64748b;font-weight:500;margin-top:2px;display:block">
                {{ number_format($grandItemPaid,0) }} + {{ number_format($extraPaid,0) }}
            </span>
            @endif
        </div>
    </div>
    <div class="stat-card" style="border-left:4px solid #ef4444">
        <div class="stat-icon" style="background:#fee2e2;color:#dc2626"><i class="fas fa-triangle-exclamation"></i></div>
        <div class="stat-body">
            <span class="stat-label">মোট বাকী</span>
            <span class="stat-value" style="color:#dc2626">৳ {{ number_format($grandDue, 0) }}</span>
        </div>
    </div>
    <div class="stat-card stat-purple">
        <div class="stat-icon"><i class="fas fa-hashtag"></i></div>
        <div class="stat-body">
            <span class="stat-label">মোট চালান</span>
            <span class="stat-value">{{ $sales->count() }}</span>
        </div>
    </div>
    @if($grandExtraCost > 0 || $grandLaborCost > 0 || $grandDiscount > 0)
    <div class="stat-card" style="border-left:4px solid #8b5cf6;background:linear-gradient(135deg,#faf5ff,#ede9fe)">
        <div class="stat-icon" style="background:#ddd6fe;color:#7c3aed"><i class="fas fa-coins"></i></div>
        <div class="stat-body">
            <span class="stat-label" style="color:#6d28d9;font-weight:700">অন্যান্য খরচ / ছাড়</span>
            @if($grandExtraCost > 0)
            <span style="font-size:.72rem;color:#7c3aed;display:block;margin-top:2px">
                অতিরিক্ত: ৳ {{ number_format($grandExtraCost, 0) }}
            </span>
            @endif
            @if($grandLaborCost > 0)
            <span style="font-size:.72rem;color:#7c3aed;display:block">
                শ্রমিক: ৳ {{ number_format($grandLaborCost, 0) }}
            </span>
            @endif
            @if($grandDiscount > 0)
            <span style="font-size:.72rem;color:#15803d;display:block">
                ছাড়: − ৳ {{ number_format($grandDiscount, 0) }}
            </span>
            @endif
        </div>
    </div>
    @endif
</div>

{{-- Per-item detail table (old-system style) ───────────────── --}}
<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <h3><i class="fas fa-table-list"></i>
            তারিখ : {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }}
            @if($from !== $to) — {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }} @endif
        </h3>
        <span style="font-size:.8rem;color:#94a3b8">{{ $saleItems->count() }} টি আইটেম</span>
    </div>
    <div class="table-wrap">
        <table class="data-table sale-detail-table">
            <thead>
                <tr>
                    <th class="tc">চালান নং</th>
                    <th>কাস্টমার নাম</th>
                    <th>আইটেম নাম</th>
                    <th class="tc">পরিমাণ</th>
                    <th class="tc col-hide-tablet">পরিমাণ কেজি</th>
                    <th class="tr col-hide-tablet">বিক্রয় মূল্য</th>
                    <th class="tr">মোট মূল্য</th>
                    <th class="tr col-hide-tablet" style="color:#15803d">ছাড়</th>
                    <th class="tr col-hide-tablet" style="color:#7c3aed">অতি. খরচ</th>
                    <th class="tr col-hide-tablet" style="color:#7c3aed">শ্রমিক খরচ</th>
                    <th class="tr">জমা</th>
                    <th class="tr">বাকী</th>
                    <th class="tc col-hide-tablet">ইউজার</th>
                    <th class="tc col-hide-tablet">সময়</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $running = 0; $lastSaleId = null;
                    $totalQty = 0; $totalKgSum = 0;
                    $shownSaleIds = [];
                @endphp
                @forelse($saleItems as $row)
                @php
                    $running += $row->amount;
                    $isNewSale = ($row->sale_id !== $lastSaleId);
                    $lastSaleId = $row->sale_id;
                    $showCosts = $isNewSale; // show extra/labor/discount only once per sale
                    // Extract KG from item name (50kg, 25kg, ৫০কেজি, etc.)
                    preg_match('/(৫০|২৫|50|25)\s*(কেজি|kg)/ui', $row->item_name, $m);
                    $kgPer = isset($m[1]) ? (int) str_replace(['৫০','২৫'],['50','25'], $m[1]) : null;
                    $totalKg = $kgPer ? (int)$row->qty * $kgPer : null;
                    $totalQty += (int)$row->qty;
                    if ($totalKg) $totalKgSum += $totalKg;
                @endphp
                <tr class="{{ $isNewSale ? 'new-sale-row' : '' }}">
                    <td class="tc mono">
                        <a href="{{ route('sales.show', $row->sale_id) }}" class="link-primary">
                            {{ str_pad($row->sale_id, 6, '0', STR_PAD_LEFT) }}
                        </a>
                    </td>
                    <td>
                        @if($row->customer_name)
                            {{ $row->customer_name }}
                        @else
                            <span style="color:#94a3b8">ওয়াক-ইন</span>
                        @endif
                    </td>
                    <td>{{ $row->item_name }}</td>
                    <td class="tc">{{ (int)$row->qty }}</td>
                    <td class="tc col-hide-tablet">{{ $totalKg ?? '—' }}</td>
                    <td class="tr col-hide-tablet">{{ number_format($row->rate, 0) }}</td>
                    <td class="tr" style="font-weight:600">{{ number_format($running, 0) }}</td>
                    {{-- ছাড় --}}
                    <td class="tr col-hide-tablet" style="color:#15803d;font-size:.8rem">
                        @if($showCosts && ($row->discount ?? 0) > 0)
                            − {{ number_format($row->discount, 0) }}
                        @else —
                        @endif
                    </td>
                    {{-- অতিরিক্ত খরচ --}}
                    <td class="tr col-hide-tablet" style="color:#7c3aed;font-size:.8rem">
                        @if($showCosts && ($row->extra_cost ?? 0) > 0)
                            + {{ number_format($row->extra_cost, 0) }}
                        @else —
                        @endif
                    </td>
                    {{-- শ্রমিক খরচ --}}
                    <td class="tr col-hide-tablet" style="color:#7c3aed;font-size:.8rem">
                        @if($showCosts && ($row->labor_cost ?? 0) > 0)
                            + {{ number_format($row->labor_cost, 0) }}
                        @else —
                        @endif
                    </td>
                    <td class="tr" style="color:#16a34a">
                        @if($isNewSale) {{ number_format($row->paid_amount, 0) }} @else — @endif
                    </td>
                    <td class="tr">
                        @if($isNewSale)
                            @if($row->due_amount > 0)
                                <span style="color:#dc2626;font-weight:600">{{ number_format($row->due_amount, 0) }}</span>
                            @else
                                <span style="color:#16a34a">—</span>
                            @endif
                        @else —
                        @endif
                    </td>
                    <td class="tc col-hide-tablet" style="font-size:.78rem;color:#64748b">{{ $row->user_name ?? '—' }}</td>
                    <td class="tc col-hide-tablet" style="font-size:.78rem;color:#64748b;white-space:nowrap">
                        {{ \Carbon\Carbon::parse($row->sale_time)->format('h:i:s a') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="14" class="empty-row">এই সময়কালে কোনো বিক্রয় নেই</td>
                </tr>
                @endforelse
            </tbody>
            @if($saleItems->isNotEmpty())
            <tfoot>
                <tr class="tfoot-summary">
                    <td colspan="3" style="text-align:right;font-weight:700;padding-right:16px">সর্বমোট</td>
                    <td class="tc" style="font-weight:800">{{ $totalQty }}</td>
                    <td class="tc col-hide-tablet" style="font-weight:800">{{ $totalKgSum ?: '—' }}</td>
                    <td></td>
                    <td class="tr" style="font-weight:800">{{ number_format($saleItems->sum('amount'), 0) }}</td>
                    <td class="tr col-hide-tablet" style="color:#15803d;font-weight:700">
                        {{ $grandDiscount > 0 ? '− '.number_format($grandDiscount, 0) : '—' }}
                    </td>
                    <td class="tr col-hide-tablet" style="color:#7c3aed;font-weight:700">
                        {{ $grandExtraCost > 0 ? '+ '.number_format($grandExtraCost, 0) : '—' }}
                    </td>
                    <td class="tr col-hide-tablet" style="color:#7c3aed;font-weight:700">
                        {{ $grandLaborCost > 0 ? '+ '.number_format($grandLaborCost, 0) : '—' }}
                    </td>
                    <td class="tr" style="color:#16a34a;font-weight:700">{{ number_format($grandItemPaid, 0) }}</td>
                    <td class="tr" style="color:#dc2626;font-weight:700">{{ number_format($grandDue, 0) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

{{-- ── No-item Sales (paying off previous due via sale form, no products) ─── --}}
@if($noItemSales->isNotEmpty())
<div class="card" style="margin-top:18px">
    <div class="card-header" style="padding:12px 16px;background:#fffbeb;border-bottom:1px solid #fde68a">
        <h3 style="font-size:.95rem;color:#92400e;margin:0">
            <i class="fas fa-hand-holding-dollar"></i>
            বাকী পরিশোধ (পণ্য ছাড়া বিক্রয়) — পূর্বের বাকীর বিপরীতে
        </h3>
    </div>
    <div class="table-wrap">
        <table class="data-table sale-detail-table">
            <thead>
                <tr>
                    <th class="tc">চালান নং</th>
                    <th>কাস্টমার</th>
                    <th class="tc">তারিখ</th>
                    <th class="tc">পরিশোধ মোড</th>
                    <th class="tr">পরিশোধ (৳)</th>
                    <th class="tc">ইউজার</th>
                    <th class="tc">সময়</th>
                </tr>
            </thead>
            <tbody>
                @foreach($noItemSales as $s)
                <tr>
                    <td class="tc mono">
                        <a href="{{ route('sales.show', $s->id) }}" class="link-primary">
                            {{ str_pad($s->id, 6, '0', STR_PAD_LEFT) }}
                        </a>
                    </td>
                    <td>{{ $s->customer?->name ?? '<span style="color:#94a3b8">ওয়াক-ইন</span>' }}</td>
                    <td class="tc">{{ \Carbon\Carbon::parse($s->sale_date)->format('d/m/Y') }}</td>
                    <td class="tc">{{ $s->payment_method ?: '—' }}</td>
                    <td class="tr" style="color:#16a34a;font-weight:600">
                        {{ number_format($s->paid_amount, 0) }}
                    </td>
                    <td class="tc" style="font-size:.78rem;color:#64748b">{{ $s->user?->name ?? '—' }}</td>
                    <td class="tc" style="font-size:.78rem;color:#64748b;white-space:nowrap">
                        {{ \Carbon\Carbon::parse($s->created_at)->format('h:i:s a') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="tfoot-summary">
                    <td colspan="4" style="text-align:right;font-weight:700;padding-right:16px">সর্বমোট</td>
                    <td class="tr" style="color:#16a34a;font-weight:800">{{ number_format($noItemSales->sum('paid_amount'), 0) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

{{-- ── Standalone Customer Payments (not tied to any sale) ─────── --}}
@if($standalonePayments->isNotEmpty())
<div class="card" style="margin-top:18px">
    <div class="card-header" style="padding:12px 16px;background:#f0fdf4;border-bottom:1px solid #bbf7d0">
        <h3 style="font-size:.95rem;color:#15803d;margin:0">
            <i class="fas fa-money-bill-wave"></i>
            কাস্টমার পরিশোধ (বিক্রয় ছাড়া) — পূর্বের বাকীর বিপরীতে
        </h3>
    </div>
    <div class="table-wrap">
        <table class="data-table sale-detail-table">
            <thead>
                <tr>
                    <th class="tc">#</th>
                    <th>কাস্টমার</th>
                    <th class="tc">তারিখ</th>
                    <th class="tc">পদ্ধতি</th>
                    <th>মন্তব্য</th>
                    <th class="tr">পরিশোধ (৳)</th>
                    <th class="tc">সময়</th>
                </tr>
            </thead>
            <tbody>
                @foreach($standalonePayments as $p)
                <tr>
                    <td class="tc mono">{{ str_pad($p->id, 6, '0', STR_PAD_LEFT) }}</td>
                    <td>{{ $p->customer?->name ?? '—' }}</td>
                    <td class="tc">{{ \Carbon\Carbon::parse($p->payment_date)->format('Y-m-d') }}</td>
                    <td class="tc">{{ $p->payment_method ?: '—' }}</td>
                    <td style="color:#64748b;font-size:.82rem">{{ $p->notes ?: '—' }}</td>
                    <td class="tr" style="color:#16a34a;font-weight:600">
                        {{ number_format($p->amount, 0) }}
                    </td>
                    <td class="tc" style="font-size:.78rem;color:#64748b;white-space:nowrap">
                        {{ \Carbon\Carbon::parse($p->created_at)->format('h:i:s a') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="tfoot-summary">
                    <td colspan="5" style="text-align:right;font-weight:700;padding-right:16px">সর্বমোট</td>
                    <td class="tr" style="color:#16a34a;font-weight:800">{{ number_format($grandStandalone, 0) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

{{-- ── Grand Combined Total ─────────────────────────────────────── --}}
@if($noItemSales->isNotEmpty() || $standalonePayments->isNotEmpty())
<div style="margin-top:14px;padding:12px 20px;background:#1e293b;border-radius:8px;display:flex;align-items:center;gap:24px;flex-wrap:wrap">
    <span style="color:#94a3b8;font-size:.82rem">সর্বমোট পরিশোধ =</span>
    <span style="color:#fff;font-size:.9rem">
        ৳ {{ number_format($grandItemPaid, 0) }}
        <span style="color:#94a3b8;font-size:.78rem">(বিক্রয়)</span>
    </span>
    @if($noItemSales->isNotEmpty())
    <span style="color:#94a3b8">+</span>
    <span style="color:#fff;font-size:.9rem">
        ৳ {{ number_format($noItemSales->sum('paid_amount'), 0) }}
        <span style="color:#94a3b8;font-size:.78rem">(পণ্য ছাড়া)</span>
    </span>
    @endif
    @if($standalonePayments->isNotEmpty())
    <span style="color:#94a3b8">+</span>
    <span style="color:#fff;font-size:.9rem">
        ৳ {{ number_format($grandStandalone, 0) }}
        <span style="color:#94a3b8;font-size:.78rem">(কাস্টমার পরিশোধ)</span>
    </span>
    @endif
    <span style="color:#94a3b8">=</span>
    <span style="color:#4ade80;font-size:1.05rem;font-weight:800">৳ {{ number_format($grandPaid, 0) }}</span>
    <span style="margin-left:auto;color:#94a3b8;font-size:.82rem">মোট বাকী:</span>
    <span style="color:#f87171;font-size:1rem;font-weight:800">৳ {{ number_format($grandDue, 0) }}</span>
</div>
@endif

@endsection

@push('styles')
<style>
.tc { text-align: center; }
.tr { text-align: right; }
.mono { font-family: 'Inter', monospace; font-size: .8rem; }

.sale-detail-table th,
.sale-detail-table td { padding: 6px 10px; font-size: .82rem; }

/* Highlight first item of each new invoice */
.new-sale-row td { border-top: 2px solid #e2e8f0 !important; }
.new-sale-row:first-child td { border-top: 1px solid var(--border) !important; }

.tfoot-summary td {
    background: var(--surface-2);
    padding: 8px 10px;
    font-size: .85rem;
    border-top: 2px solid var(--border);
}

/* ── 5-card grid for sales report ─────────────────────────── */
.sales-stats-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 16px;
    margin-bottom: 20px;
}
@media (max-width: 1200px) {
    .sales-stats-grid { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 768px) {
    .sales-stats-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
}
@media (max-width: 480px) {
    .sales-stats-grid { grid-template-columns: 1fr 1fr; gap: 8px; }
}

/* ── Filter form tablet wrap ───────────────────────────────── */
@media (max-width: 900px) {
    .filter-form { flex-wrap: wrap; gap: 10px; }
    .filter-form .form-group-field { min-width: 140px; flex: 1 1 140px; }
    .filter-form .btn { align-self: flex-end; }
}

/* ── Table: hide less-important columns on tablet ─────────── */
@media (max-width: 900px) {
    .sale-detail-table th,
    .sale-detail-table td { padding: 5px 7px; font-size: .78rem; }
    .col-hide-tablet { display: none; }
}
@media (max-width: 640px) {
    .sale-detail-table th,
    .sale-detail-table td { padding: 4px 5px; font-size: .74rem; }
}

@media print {
    .sidebar, .topbar, .no-print { display: none !important; }
    .main-wrapper { margin-left: 0 !important; }
    .content { padding: 6px !important; }
    .print-header { display: block !important; }
    .card { box-shadow: none !important; border: 1px solid #ccc !important; }
    .sale-detail-table th,
    .sale-detail-table td { font-size: .75rem; padding: 4px 6px; }
}
</style>
@endpush
