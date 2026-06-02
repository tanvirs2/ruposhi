@extends('layouts.app')
@section('title', 'স্টক তথ্য')
@section('page-title', 'স্টক তথ্য')

@section('content')
@include('partials.page-header', ['title' => 'সকল স্টক', 'createRoute' => null])

<div class="card">
    <div class="card-filter">
        <form method="GET" class="filter-form" style="align-items:center">
            <div class="search-box"><i class="fas fa-search"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="আইটেমের নাম...">
            </div>
            <div style="display:flex;align-items:center;gap:6px">
                <i class="fas fa-calendar-day" style="color:#94a3b8"></i>
                <input type="date" name="date" value="{{ $filterDate }}"
                    style="height:38px;padding:0 10px;border:1.5px solid var(--border);border-radius:6px;font-family:inherit;font-size:.85rem">
            </div>
            {{-- Stock updated_date filter --}}
            <div style="display:flex;align-items:center;gap:6px">
                <i class="fas fa-rotate" style="color:#0d9488"></i>
                <input type="date" name="updated_date" value="{{ $updatedDate }}"
                    title="এই তারিখে স্টক আপডেট হয়েছে এমন পণ্য"
                    style="height:38px;padding:0 10px;border:1.5px solid {{ $updatedDate ? '#0d9488' : 'var(--border)' }};border-radius:6px;font-family:inherit;font-size:.85rem;{{ $updatedDate ? 'color:#0d9488;font-weight:600' : '' }}">
            </div>
            <button type="submit" class="btn btn-secondary">খুঁজুন</button>
            {{-- Quick: today's stock updates --}}
            @if($updatedDate !== now()->toDateString())
                <a href="{{ route('stock.index', array_merge(request()->all(), ['updated_date' => now()->toDateString()])) }}"
                   class="btn btn-ghost" style="color:#0d9488;border-color:#0d9488;white-space:nowrap">
                    <i class="fas fa-rotate"></i> আজ আপডেট
                </a>
            @endif
            @if(request('search') || request('date') || $updatedDate)
                <a href="{{ route('stock.index') }}" class="btn btn-ghost">পরিষ্কার</a>
            @endif
        </form>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>আইটেম</th>
                    <th>ক্যাটাগরি</th>
                    <th class="tc">সর্বশেষ আপডেট</th>
                    <th class="tc">
                        {{ $filterDate === now()->toDateString() ? 'আজকের' : \Carbon\Carbon::parse($filterDate)->format('d/m') }} বিক্রয়
                    </th>
                    <th class="tc">মোট বিক্রয়</th>
                    <th class="tc">পূর্বের স্টক</th>
                    <th class="tc" style="color:#0d9488">
                        {{ $filterDate === now()->toDateString() ? 'আজ' : \Carbon\Carbon::parse($filterDate)->format('d/m') }} গ্রহণ
                    </th>
                    <th class="tc">বর্তমান স্টক</th>
                    <th class="tr">ক্রয় মূল্য</th>
                    <th class="tr">স্টক মূল্য</th>
                    <th class="tc">অবস্থা</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stock as $s)
                @php
                    $itemId      = $s->item_id;
                    $unit        = $s->item->unit ?? '';
                    $todayQty    = $todaySales[$itemId] ?? 0;
                    $totalQty    = $totalSales[$itemId] ?? 0;
                    $stockVal     = $s->quantity * ($s->item->purchase_price ?? 0);
                    $updatedAt    = $s->updated_at;
                    $isToday      = $updatedAt && $updatedAt->isToday();
                    $isYesterday  = $updatedAt && $updatedAt->isYesterday();
                    $receiveQty   = $todayReceive[$itemId] ?? 0;
                    $prevStock    = $s->quantity - $receiveQty;
                @endphp
                <tr class="{{ $isToday ? 'stock-updated-today' : '' }}">
                    <td class="mono">{{ $loop->iteration }}</td>
                    <td>
                        <strong>{{ $s->item->name }}</strong>
                        @if($isToday)
                            <span class="badge-new-stock">নতুন</span>
                        @endif
                    </td>
                    <td>{{ $s->item->category?->name ?? '—' }}</td>
                    <td class="tc" style="font-size:.78rem;white-space:nowrap">
                        @if($updatedAt)
                            @if($isToday)
                                <span style="color:#0d9488;font-weight:600">আজ {{ $updatedAt->format('h:ia') }}</span>
                            @elseif($isYesterday)
                                <span style="color:#64748b">গতকাল {{ $updatedAt->format('h:ia') }}</span>
                            @else
                                <span style="color:#94a3b8">{{ $updatedAt->format('d M, h:ia') }}</span>
                            @endif
                        @else
                            <span style="color:#cbd5e1">—</span>
                        @endif
                    </td>
                    <td class="tc">
                        @if($todayQty > 0)
                            <span style="color:#16a34a;font-weight:600">{{ number_format($todayQty, 0) }}</span>
                            <span style="color:#94a3b8;font-size:.8rem"> {{ $unit }}</span>
                        @else
                            <span style="color:#cbd5e1">—</span>
                        @endif
                    </td>
                    <td class="tc">
                        <span style="font-weight:600">{{ number_format($totalQty, 0) }}</span>
                        <span style="color:#94a3b8;font-size:.8rem"> {{ $unit }}</span>
                    </td>
                    {{-- পূর্বের স্টক --}}
                    <td class="tc">
                        <span style="font-weight:600;color:#475569">{{ number_format($prevStock, 0) }}</span>
                        <span style="color:#94a3b8;font-size:.8rem"> {{ $unit }}</span>
                    </td>
                    {{-- আজ গ্রহণ --}}
                    <td class="tc">
                        @if($receiveQty > 0)
                            <span style="color:#0d9488;font-weight:700">+{{ number_format($receiveQty, 0) }}</span>
                            <span style="color:#94a3b8;font-size:.8rem"> {{ $unit }}</span>
                        @else
                            <span style="color:#cbd5e1">—</span>
                        @endif
                    </td>
                    <td class="tc">
                        <strong>{{ number_format($s->quantity, 0) }}</strong>
                        <span style="color:#94a3b8;font-size:.8rem"> {{ $unit }}</span>
                    </td>
                    <td class="tr">
                        @if(($s->item->purchase_price ?? 0) > 0)
                            <span style="color:#475569">৳ {{ number_format($s->item->purchase_price, 0) }}</span>
                        @else
                            <span style="color:#cbd5e1">—</span>
                        @endif
                    </td>
                    <td class="tr">
                        @if($stockVal != 0)
                            <span style="font-weight:600;color:{{ $stockVal < 0 ? '#dc2626' : 'inherit' }}">
                                {{ $stockVal < 0 ? '−' : '' }}৳ {{ number_format(abs($stockVal), 0) }}
                            </span>
                        @else
                            <span style="color:#cbd5e1">—</span>
                        @endif
                    </td>
                    <td class="tc">
                        @if($s->quantity <= 0)
                            <span class="badge badge-red"><i class="fas fa-circle-xmark"></i> শেষ</span>
                        @elseif($s->isLow())
                            <span class="badge" style="background:#fef3c7;color:#92400e"><i class="fas fa-triangle-exclamation"></i> কম</span>
                        @else
                            <span class="badge badge-green">পর্যাপ্ত</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="13" class="empty-row">কোনো স্টক পাওয়া যায়নি</td></tr>
                @endforelse
            </tbody>
            @if($stock->total() > 0)
            <tfoot>
                <tr class="tfoot-summary">
                    <td colspan="5" style="text-align:right;font-weight:700;padding-right:16px">সর্বমোট স্টক</td>
                    <td class="tc" style="font-weight:800;color:#16a34a">{{ number_format($grandTodaySales, 0) }}</td>
                    <td class="tc" style="font-weight:800">{{ number_format($grandTotalSales, 0) }}</td>
                    <td class="tc" style="font-weight:800;color:#475569">{{ number_format($grandStockQty - $grandTodayReceive, 0) }}</td>
                    <td class="tc" style="font-weight:800;color:#0d9488">
                        @if($grandTodayReceive > 0)+{{ number_format($grandTodayReceive, 0) }}@else —@endif
                    </td>
                    <td class="tc" style="font-weight:800">{{ number_format($grandStockQty, 0) }}</td>
                    <td></td>
                    <td class="tr" style="font-weight:800;color:{{ $grandStockValue < 0 ? '#dc2626' : 'inherit' }}">
                        {{ $grandStockValue < 0 ? '−' : '' }}৳ {{ number_format(abs($grandStockValue), 0) }}
                    </td>
                    <td></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
    <div class="pagination-wrap">{{ $stock->withQueryString()->links() }}</div>
</div>
@endsection

@push('styles')
<style>
.stock-updated-today { background: #f0fdfa !important; }
.badge-new-stock {
    display: inline-block;
    margin-left: 6px;
    background: #0d9488;
    color: #fff;
    font-size: .65rem;
    font-weight: 700;
    padding: 1px 7px;
    border-radius: 999px;
    letter-spacing: .04em;
    vertical-align: middle;
}
</style>
@endpush
