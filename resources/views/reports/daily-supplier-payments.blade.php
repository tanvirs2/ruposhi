@extends('layouts.app')
@section('title', 'দৈনিক সরবরাহকারী পরিশোধ')
@section('page-title', 'দৈনিক সরবরাহকারী পরিশোধ')

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
                <a href="{{ route('reports.export.daily-supplier-payments', ['from'=>$from,'to'=>$to]) }}"
                   class="btn btn-export"><i class="fas fa-file-csv"></i> CSV</a>
                <button type="button" class="btn btn-export-print" onclick="window.print()">
                    <i class="fas fa-print"></i> প্রিন্ট
                </button>
            </div>
        </form>
    </div>
</div>

<div class="stats-grid" style="margin-bottom:24px">
    <div class="stat-card stat-green">
        <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
        <div class="stat-body">
            <span class="stat-label">মোট পরিশোধ</span>
            <span class="stat-value">৳ {{ number_format($grandTotal, 0) }}</span>
        </div>
    </div>
    <div class="stat-card stat-blue">
        <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
        <div class="stat-body">
            <span class="stat-label">মোট দিন</span>
            <span class="stat-value">{{ $daily->count() }} দিন</span>
        </div>
    </div>
</div>

@forelse($daily as $date => $dayPayments)
<div class="card" style="margin-bottom:16px">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <h3><i class="fas fa-calendar-day"></i> {{ \Carbon\Carbon::parse($date)->format('d F Y') }}</h3>
        <span style="font-weight:700;color:#16a34a;font-size:1rem">
            মোট: ৳ {{ number_format($dayPayments->sum('paid_amount'), 0) }}
        </span>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr><th>সরবরাহকারী</th><th>পরিমাণ</th><th>পদ্ধতি</th><th>মন্তব্য</th><th>রেকর্ডকারী</th></tr>
            </thead>
            <tbody>
                @foreach($dayPayments as $p)
                <tr>
                    <td>
                        <a href="{{ route('suppliers.ledger', $p->supplier) }}" class="link-primary">
                            {{ $p->supplier->name }}
                        </a>
                        @if($p->supplier->proprietor)
                        <div style="font-size:.78rem;color:#64748b">{{ $p->supplier->proprietor }}</div>
                        @endif
                    </td>
                    <td><strong style="color:#16a34a">৳ {{ number_format($p->paid_amount, 0) }}</strong></td>
                    <td><span class="badge badge-green">{{ $p->payment_method }}</span></td>
                    <td>{{ $p->notes ?? '—' }}</td>
                    <td>{{ $p->user->name ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@empty
<div class="card"><div class="empty-row">এই সময়কালে কোনো পরিশোধ নেই</div></div>
@endforelse
@endsection
