@extends('layouts.app')
@section('title', 'স্টক তথ্য')
@section('page-title', 'স্টক তথ্য')

@section('content')
@include('partials.page-header', ['title' => 'সকল স্টক', 'createRoute' => null])

<div class="card">
    <div class="card-filter">
        <form method="GET" class="filter-form">
            <div class="search-box"><i class="fas fa-search"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="আইটেমের নাম...">
            </div>
            <button type="submit" class="btn btn-secondary">খুঁজুন</button>
            @if(request('search'))
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
                    <th>বর্তমান স্টক</th>
                    <th>সর্বনিম্ন</th>
                    <th>অবস্থা</th>
                    <th>সমন্বয়</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stock as $s)
                <tr>
                    <td class="mono">{{ $loop->iteration }}</td>
                    <td><strong>{{ $s->item->name }}</strong></td>
                    <td>{{ $s->item->itemBrand?->name ?? '—' }}</td>
                    <td>{{ $s->item->category?->name ?? '—' }}</td>
                    <td>
                        <strong>{{ number_format($s->quantity, 0) }}</strong>
                        <span style="color:#94a3b8;font-size:.8rem"> {{ $s->item->unitType?->short ?? $s->item->unit }}</span>
                    </td>
                    <td>{{ $s->min_quantity }}</td>
                    <td>
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
                <tr><td colspan="8" class="empty-row">কোনো স্টক পাওয়া যায়নি</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">{{ $stock->withQueryString()->links() }}</div>
</div>
@endsection
