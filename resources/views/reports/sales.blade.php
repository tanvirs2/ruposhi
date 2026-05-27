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
<div class="stats-grid no-print" style="margin-bottom:20px">
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
            <span class="stat-label">মোট পরিশোধ</span>
            <span class="stat-value">৳ {{ number_format($grandPaid, 0) }}</span>
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
                    <th class="tc">পরিমাণ কেজি</th>
                    <th class="tr">বিক্রয় মূল্য</th>
                    <th class="tr">মোট মূল্য</th>
                    <th class="tr">জমা</th>
                    <th class="tr">বাকী</th>
                    <th class="tc">ইউজার</th>
                    <th class="tc">সময়</th>
                </tr>
            </thead>
            <tbody>
                @php $running = 0; $lastSaleId = null; $totalQty = 0; $totalKgSum = 0; @endphp
                @forelse($saleItems as $row)
                @php
                    $running += $row->amount;
                    $isNewSale = ($row->sale_id !== $lastSaleId);
                    $lastSaleId = $row->sale_id;
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
                    <td class="tc">{{ $totalKg ?? '—' }}</td>
                    <td class="tr">{{ number_format($row->rate, 0) }}</td>
                    <td class="tr" style="font-weight:600">{{ number_format($running, 0) }}</td>
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
                    <td class="tc" style="font-size:.78rem;color:#64748b">{{ $row->user_name ?? '—' }}</td>
                    <td class="tc" style="font-size:.78rem;color:#64748b;white-space:nowrap">
                        {{ \Carbon\Carbon::parse($row->sale_time)->format('h:i:s a') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" class="empty-row">এই সময়কালে কোনো বিক্রয় নেই</td>
                </tr>
                @endforelse
            </tbody>
            @if($saleItems->isNotEmpty())
            <tfoot>
                <tr class="tfoot-summary">
                    <td colspan="3" style="text-align:right;font-weight:700;padding-right:16px">সর্বমোট</td>
                    <td class="tc" style="font-weight:800">{{ $totalQty }}</td>
                    <td class="tc" style="font-weight:800">{{ $totalKgSum ?: '—' }}</td>
                    <td></td>
                    <td class="tr" style="font-weight:800">{{ number_format($saleItems->sum('amount'), 0) }}</td>
                    <td class="tr" style="color:#16a34a;font-weight:700">{{ number_format($grandPaid, 0) }}</td>
                    <td class="tr" style="color:#dc2626;font-weight:700">{{ number_format($grandDue, 0) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

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
