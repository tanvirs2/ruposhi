@extends('layouts.app')
@section('title', 'দৈনিক রিসিভ রিপোর্ট')
@section('page-title', 'দৈনিক রিসিভ রিপোর্ট')

@push('styles')
<meta name="turbo-cache-control" content="no-cache">
@endpush

@section('content')

<div class="card" style="margin-bottom:20px">
    <div class="card-filter">
        <form method="GET" class="filter-form" data-date-snap>
            <div class="form-group-field">
                <label>শুরু</label>
                <input type="date" name="from" value="{{ $from }}">
            </div>
            <div class="form-group-field">
                <label>শেষ</label>
                <input type="date" name="to" value="{{ $to }}">
            </div>
            @include('partials.date-range-buttons')
            <button type="submit" class="btn btn-primary" style="align-self:flex-end">রিপোর্ট দেখুন</button>
            <div style="align-self:flex-end;display:flex;gap:8px;margin-left:auto">
                <a href="{{ route('reports.export.daily-receive', ['from'=>$from,'to'=>$to]) }}"
                   class="btn btn-export"><i class="fas fa-file-csv"></i> CSV</a>
                <button type="button" class="btn btn-export-print" onclick="window.print()">
                    <i class="fas fa-print"></i> প্রিন্ট
                </button>
            </div>
        </form>
    </div>
</div>

<div class="stats-grid" style="margin-bottom:24px">
    <div class="stat-card stat-blue">
        <div class="stat-icon"><i class="fas fa-truck-ramp-box"></i></div>
        <div class="stat-body">
            <span class="stat-label">মোট পণ্য গ্রহণ</span>
            <span class="stat-value">৳ {{ number_format($grandTotal, 0) }}</span>
        </div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
        <div class="stat-body">
            <span class="stat-label">মোট পরিশোধ</span>
            <span class="stat-value">৳ {{ number_format($grandPaid, 0) }}</span>
            @if($grandDeposit > 0)
                <span style="font-size:.78rem;font-weight:600;color:#1d4ed8">+ জমা ৳ {{ number_format($grandDeposit, 0) }}</span>
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
        <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
        <div class="stat-body">
            <span class="stat-label">মোট দিন</span>
            <span class="stat-value">{{ $daily->count() }} দিন</span>
        </div>
    </div>
</div>

@forelse($daily as $date => $dayPurchases)
<div class="card" style="margin-bottom:16px">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <h3><i class="fas fa-calendar-day"></i> {{ \Carbon\Carbon::parse($date)->format('d F Y') }}</h3>
        <span style="font-weight:700;color:#3b82f6;font-size:1rem">
            মোট: ৳ {{ number_format($dayPurchases->sum('total_amount'), 0) }}
        </span>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <colgroup>
                <col style="width:50px">    {{-- # --}}
                <col style="width:160px">   {{-- সরবরাহকারী --}}
                <col style="width:200px">   {{-- আইটেম --}}
                <col style="width:110px">   {{-- মোট --}}
                <col style="width:110px">   {{-- পরিশোধ --}}
                <col style="width:100px">   {{-- জমা --}}
                <col style="width:110px">   {{-- বাকী --}}
                <col style="width:110px">   {{-- রেকর্ডকারী --}}
                <col style="width:60px">    {{-- অ্যাকশন --}}
            </colgroup>
            <thead>
                <tr>
                    <th>#</th>
                    <th>সরবরাহকারী</th>
                    <th>আইটেম</th>
                    <th style="text-align:right">মোট</th>
                    <th style="text-align:right">পরিশোধ</th>
                    <th style="text-align:right">জমা</th>
                    <th style="text-align:right">বাকী</th>
                    <th>রেকর্ডকারী</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($dayPurchases as $p)
                <tr>
                    <td class="mono">{{ $loop->iteration }}</td>
                    <td>
                        @if($p->supplier)
                            <a href="{{ route('suppliers.ledger', $p->supplier) }}" class="link-primary">{{ $p->supplier->name }}</a>
                        @else
                            <span style="color:#94a3b8">—</span>
                        @endif
                    </td>
                    <td style="font-size:.82rem;color:#64748b">
                        {{ $p->items->map(fn($i) => $i->item->name.' ('.$i->quantity.')')->implode(', ') }}
                    </td>
                    <td style="text-align:right;font-weight:600">৳ {{ number_format($p->total_amount, 2) }}</td>
                    <td style="text-align:right;color:#16a34a">৳ {{ number_format($p->paid_amount, 2) }}</td>
                    <td style="text-align:right">
                        @if(($p->deposit_amount ?? 0) > 0)
                            <span style="color:#1d4ed8;font-weight:600">৳ {{ number_format($p->deposit_amount, 2) }}</span>
                        @else
                            <span style="color:#cbd5e1">—</span>
                        @endif
                    </td>
                    <td style="text-align:right">
                        @if($p->due_amount > 0)
                            <span style="color:#dc2626;font-weight:600">৳ {{ number_format($p->due_amount, 2) }}</span>
                        @else
                            <span style="color:#16a34a">—</span>
                        @endif
                    </td>
                    <td>{{ $p->user->name }}</td>
                    <td>
                        <a href="{{ route('purchases.show', $p) }}" class="btn-icon-sm" style="color:#0d9488" title="বিস্তারিত">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:var(--accent-light);font-weight:700">
                    <td colspan="3" style="text-align:right;padding-right:16px">দিনের সর্বমোট</td>
                    <td style="text-align:right">৳ {{ number_format($dayPurchases->sum('total_amount'), 2) }}</td>
                    <td style="text-align:right;color:#16a34a">৳ {{ number_format($dayPurchases->sum('paid_amount'), 2) }}</td>
                    <td style="text-align:right;color:#1d4ed8">৳ {{ number_format($dayPurchases->sum('deposit_amount'), 2) }}</td>
                    <td style="text-align:right;color:#dc2626">৳ {{ number_format($dayPurchases->sum('due_amount'), 2) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@empty
<div class="card"><div class="empty-row">এই সময়কালে কোনো পণ্য গ্রহণ নেই</div></div>
@endforelse
@endsection
