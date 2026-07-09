@extends('layouts.app')
@section('title', 'স্টক শেষ')
@section('page-title', 'স্টক শেষ / কম স্টক')

@section('content')

<div class="stats-grid" style="margin-bottom:20px">
    <div class="stat-card" style="border-left:4px solid #ef4444">
        <div class="stat-icon" style="background:#fee2e2;color:#dc2626"><i class="fas fa-circle-xmark"></i></div>
        <div class="stat-body">
            <span class="stat-label">স্টক শেষ / কম</span>
            <span class="stat-value">{{ $stock->total() }}</span>
        </div>
    </div>
    <div class="stat-card" style="border-left:4px solid #f59e0b">
        <div class="stat-icon" style="background:#fef3c7;color:#d97706"><i class="fas fa-triangle-exclamation"></i></div>
        <div class="stat-body">
            <span class="stat-label">সতর্কবার্তা</span>
            <span class="stat-value" style="font-size:0.88rem;color:#92400e">স্টক পুনরায় পূরণ করুন</span>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-filter">
        <form method="GET" class="filter-form" style="align-items:center">
            <div style="display:flex;align-items:center;gap:6px">
                <i class="fas fa-calendar-day" style="color:#94a3b8"></i>
                <input type="date" name="date" value="{{ $filterDate }}"
                    style="height:38px;padding:0 10px;border:1.5px solid var(--border);border-radius:6px;font-family:inherit;font-size:.85rem">
            </div>
            <button type="submit" class="btn btn-secondary">খুঁজুন</button>
            @if(request('date'))
                <a href="{{ route('stock.low') }}" class="btn btn-ghost">পরিষ্কার</a>
            @endif
            <button type="button" class="btn-export-print no-print" onclick="window.print()">
                <i class="fas fa-print"></i> প্রিন্ট
            </button>
        </form>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>আইটেমের নাম</th>
                    <th>ক্যাটাগরি</th>
                    <th class="tc">মোট বিক্রয়</th>
                    <th class="tc">
                        {{ $filterDate === now()->toDateString() ? 'আজকের' : \Carbon\Carbon::parse($filterDate)->format('d/m') }} বিক্রয়
                    </th>
                    <th class="tc">বর্তমান স্টক</th>
                    <th class="tr">ক্রয় মূল্য</th>
                    <th class="tr">স্টক মূল্য</th>
                    <th class="tc">ঘাটতি</th>
                    <th class="tc">অবস্থা</th>
                    <th>সমন্বয়</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stock as $s)
                @php
                    $itemId   = $s->item_id;
                    $unit     = $s->item->unit ?? '';
                    $todayQty = $todaySales[$itemId] ?? 0;
                    $totalQty = $totalSales[$itemId] ?? 0;
                    $stockVal = $s->quantity * ($s->item->purchase_price ?? 0);
                    $deficit  = max(0, $s->min_quantity - $s->quantity);
                @endphp
                <tr style="{{ $s->quantity <= 0 ? 'background:#fff5f5' : 'background:#fffbeb' }}">
                    <td class="mono">{{ $loop->iteration }}</td>
                    <td><strong>{{ $s->item->name }}</strong></td>
                    <td>{{ $s->item->category?->name ?? '—' }}</td>
                    <td class="tc">
                        <span style="font-weight:600">{{ number_format($totalQty, 0) }}</span>
                        <span style="color:#94a3b8;font-size:.8rem"> {{ $unit }}</span>
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
                        <strong style="color:{{ $s->quantity <= 0 ? '#dc2626' : '#d97706' }}">
                            {{ number_format($s->quantity, 0) }}
                        </strong>
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
                        @if($stockVal > 0)
                            <span style="font-weight:600">৳ {{ number_format($stockVal, 0) }}</span>
                        @else
                            <span style="color:#cbd5e1">—</span>
                        @endif
                    </td>
                    <td class="tc">
                        @if($deficit > 0)
                            <span style="color:#dc2626;font-weight:700">− {{ number_format($deficit, 0) }}</span>
                        @else
                            <span style="color:#94a3b8">—</span>
                        @endif
                    </td>
                    <td class="tc">
                        @if($s->quantity <= 0)
                            <span class="badge badge-red"><i class="fas fa-circle-xmark"></i> শেষ</span>
                        @else
                            <span class="badge" style="background:#fef3c7;color:#92400e">
                                <i class="fas fa-triangle-exclamation"></i> কম
                            </span>
                        @endif
                    </td>
                    <td>
                        <form method="POST" action="{{ route('stock.adjust', $s) }}" class="inline-form">
                            @csrf @method('PATCH')
                            <input type="text" inputmode="decimal" name="quantity" value="{{ $s->quantity }}"
                                   class="inline-input" style="width:80px">
                            <button type="submit" class="btn btn-secondary btn-sm">আপডেট</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="12" class="empty-row">
                        <i class="fas fa-circle-check" style="color:#22c55e;font-size:1.5rem;display:block;margin-bottom:6px"></i>
                        সকল আইটেমের স্টক পর্যাপ্ত আছে
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if($stock->total() > 0)
            <tfoot>
                <tr class="tfoot-summary">
                    <td colspan="3" style="text-align:right;font-weight:700;padding-right:16px">সর্বমোট স্টক</td>
                    <td class="tc" style="font-weight:800">{{ number_format($grandTotalSales, 0) }}</td>
                    <td class="tc" style="font-weight:800;color:#16a34a">{{ number_format($grandTodaySales, 0) }}</td>
                    <td class="tc" style="font-weight:800">{{ number_format($grandStockQty, 0) }}</td>
                    <td></td>
                    <td class="tr" style="font-weight:800">৳ {{ number_format($grandStockValue, 0) }}</td>
                    <td class="tc" style="font-weight:800;color:#dc2626">− {{ number_format($grandDeficit, 0) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
    <div class="pagination-wrap">{{ $stock->withQueryString()->links() }}</div>
</div>
@endsection
