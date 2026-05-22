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
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>আইটেমের নাম</th>
                    <th>ব্র্যান্ড</th>
                    <th>ক্যাটাগরি</th>
                    <th>বর্তমান স্টক</th>
                    <th>সর্বনিম্ন</th>
                    <th>ঘাটতি</th>
                    <th>অবস্থা</th>
                    <th>সমন্বয়</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stock as $s)
                <tr style="{{ $s->quantity <= 0 ? 'background:#fff5f5' : 'background:#fffbeb' }}">
                    <td class="mono">{{ $loop->iteration }}</td>
                    <td><strong>{{ $s->item->name }}</strong></td>
                    <td>{{ $s->item->itemBrand?->name ?? '—' }}</td>
                    <td>{{ $s->item->category?->name ?? '—' }}</td>
                    <td>
                        <strong style="color:{{ $s->quantity <= 0 ? '#dc2626' : '#d97706' }}">
                            {{ number_format($s->quantity, 0) }}
                        </strong>
                        <span style="color:#94a3b8;font-size:.8rem"> {{ $s->item->unitType?->short ?? $s->item->unit }}</span>
                    </td>
                    <td>{{ $s->min_quantity }}</td>
                    <td>
                        @php $deficit = max(0, $s->min_quantity - $s->quantity); @endphp
                        @if($deficit > 0)
                            <span style="color:#dc2626;font-weight:700">− {{ number_format($deficit, 0) }}</span>
                        @else
                            <span style="color:#94a3b8">—</span>
                        @endif
                    </td>
                    <td>
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
                    <td colspan="9" class="empty-row">
                        <i class="fas fa-circle-check" style="color:#22c55e;font-size:1.5rem;display:block;margin-bottom:6px"></i>
                        সকল আইটেমের স্টক পর্যাপ্ত আছে
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">{{ $stock->links() }}</div>
</div>
@endsection
