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
            <button type="submit" class="btn btn-secondary">খুঁজুন</button>
            @if(request('search') || request('date'))
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
                    <th>ব্র্যান্ড</th>
                    <th>ক্যাটাগরি</th>
                    <th class="tc">
                        {{ $filterDate === now()->toDateString() ? 'আজকের' : \Carbon\Carbon::parse($filterDate)->format('d/m') }} বিক্রয়
                    </th>
                    <th class="tc">মোট বিক্রয়</th>
                    <th class="tc">বর্তমান স্টক</th>
                    <th class="tr">স্টক মূল্য</th>
                    <th class="tc">অবস্থা</th>
                    <th>সমন্বয়</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stock as $s)
                @php
                    $itemId   = $s->item_id;
                    $unit     = $s->item->unitType?->short ?? $s->item->unit ?? '';
                    $todayQty = $todaySales[$itemId] ?? 0;
                    $totalQty = $totalSales[$itemId] ?? 0;
                    $stockVal = $s->quantity * ($s->item->purchase_price ?? 0);
                @endphp
                <tr>
                    <td class="mono">{{ $loop->iteration }}</td>
                    <td><strong>{{ $s->item->name }}</strong></td>
                    <td>{{ $s->item->itemBrand?->name ?? '—' }}</td>
                    <td>{{ $s->item->category?->name ?? '—' }}</td>
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
                    <td class="tc">
                        <strong>{{ number_format($s->quantity, 0) }}</strong>
                        <span style="color:#94a3b8;font-size:.8rem"> {{ $unit }}</span>
                    </td>
                    <td class="tr">
                        @if($stockVal > 0)
                            <span style="font-weight:600">৳ {{ number_format($stockVal, 0) }}</span>
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
                    <td>
                        <form method="POST" action="{{ route('stock.adjust', $s) }}" class="inline-form">
                            @csrf @method('PATCH')
                            <input type="text" inputmode="decimal" name="quantity" value="{{ $s->quantity }}" class="inline-input" style="width:90px">
                            <button type="submit" class="btn btn-secondary btn-sm">আপডেট</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="10" class="empty-row">কোনো স্টক পাওয়া যায়নি</td></tr>
                @endforelse
            </tbody>
            @if($stock->total() > 0)
            <tfoot>
                <tr class="tfoot-summary">
                    <td colspan="4" style="text-align:right;font-weight:700;padding-right:16px">সর্বমোট স্টক</td>
                    <td class="tc" style="font-weight:800;color:#16a34a">{{ number_format($grandTodaySales, 0) }}</td>
                    <td class="tc" style="font-weight:800">{{ number_format($grandTotalSales, 0) }}</td>
                    <td class="tc" style="font-weight:800">{{ number_format($grandStockQty, 0) }}</td>
                    <td class="tr" style="font-weight:800">৳ {{ number_format($grandStockValue, 0) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
    <div class="pagination-wrap">{{ $stock->withQueryString()->links() }}</div>
</div>
@endsection
