@extends('layouts.app')
@section('title', 'স্টক রিপোর্ট')
@section('page-title', 'স্টক রিপোর্ট')

@section('content')

{{-- Search bar --}}
<div class="card" style="margin-bottom:20px">
    <div class="card-filter">
        <form method="GET" class="filter-form">
            <div class="search-box" style="flex:1;max-width:420px">
                <i class="fas fa-search"></i>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="প্রোডাক্ট নাম বা কোড লিখুন..." autofocus>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-magnifying-glass"></i> অনুসন্ধান
            </button>
            @if(request('search'))
                <a href="{{ route('stock.report') }}" class="btn btn-ghost">পরিষ্কার</a>
            @endif
        </form>
    </div>
</div>

@if(request()->filled('search'))
    @if($results->isEmpty())
        <div class="card">
            <div class="empty-row">
                <i class="fas fa-box-open" style="font-size:2rem;color:#cbd5e1;display:block;margin-bottom:8px"></i>
                "{{ request('search') }}" — কোনো আইটেম পাওয়া যায়নি
            </div>
        </div>
    @else
        <div class="stats-grid" style="margin-bottom:20px">
            <div class="stat-card stat-blue">
                <div class="stat-icon"><i class="fas fa-boxes-stacked"></i></div>
                <div class="stat-body">
                    <span class="stat-label">মোট আইটেম পাওয়া</span>
                    <span class="stat-value">{{ $results->count() }}</span>
                </div>
            </div>
            <div class="stat-card stat-green">
                <div class="stat-icon"><i class="fas fa-warehouse"></i></div>
                <div class="stat-body">
                    <span class="stat-label">মোট স্টক</span>
                    <span class="stat-value">{{ number_format($results->sum('quantity'), 0) }}</span>
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
                            <th>টাইপ</th>
                            <th>ক্যাটাগরি</th>
                            <th>ক্রয় মূল্য</th>
                            <th>বিক্রয় মূল্য</th>
                            <th>স্টক</th>
                            <th>অবস্থা</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results as $s)
                        <tr>
                            <td class="mono">{{ $loop->iteration }}</td>
                            <td>
                                <strong>{{ $s->item->name }}</strong>
                                @if($s->item->code)
                                    <div class="mono" style="font-size:.75rem;color:#94a3b8">{{ $s->item->code }}</div>
                                @endif
                            </td>
                            <td>{{ $s->item->itemBrand?->name ?? '—' }}</td>
                            <td>{{ $s->item->itemType?->name ?? '—' }}</td>
                            <td>{{ $s->item->category?->name ?? '—' }}</td>
                            <td>৳ {{ number_format($s->item->purchase_price, 2) }}</td>
                            <td>৳ {{ number_format($s->item->sale_price, 2) }}</td>
                            <td>
                                <strong style="font-size:1rem">{{ number_format($s->quantity, 0) }}</strong>
                                <span style="color:#64748b;font-size:.8rem"> {{ $s->item->unitType?->short ?? $s->item->unit }}</span>
                            </td>
                            <td>
                                @if($s->quantity <= 0)
                                    <span class="badge badge-red"><i class="fas fa-circle-xmark"></i> শেষ</span>
                                @elseif($s->isLow())
                                    <span class="badge" style="background:#fef3c7;color:#92400e"><i class="fas fa-triangle-exclamation"></i> কম</span>
                                @else
                                    <span class="badge badge-green"><i class="fas fa-circle-check"></i> পর্যাপ্ত</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@else
    <div class="card">
        <div class="empty-row" style="padding:48px">
            <i class="fas fa-magnifying-glass" style="font-size:2.5rem;color:#c7d2fe;display:block;margin-bottom:12px"></i>
            <div style="font-size:1rem;color:#94a3b8">প্রোডাক্টের নাম বা কোড লিখে অনুসন্ধান করুন</div>
        </div>
    </div>
@endif

@endsection
