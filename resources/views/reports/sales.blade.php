@extends('layouts.app')
@section('title', 'বিক্রয় রিপোর্ট')
@section('page-title', 'বিক্রয় রিপোর্ট')

@section('content')

{{-- Filter --}}
<div class="card" style="margin-bottom:20px">
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
                <a href="{{ route('reports.sales', ['from'=>$from,'to'=>$to]) }}" class="btn btn-ghost" style="align-self:flex-end">পরিষ্কার</a>
            @endif
            <div style="align-self:flex-end;display:flex;gap:8px;margin-left:auto">
                <a href="{{ route('reports.export.sales', array_filter(['from'=>$from,'to'=>$to,'customer_id'=>request('customer_id')])) }}"
                   class="btn btn-export" title="CSV ডাউনলোড (Excel-এ খুলুন)">
                    <i class="fas fa-file-csv"></i> CSV
                </a>
                <button type="button" class="btn btn-export-print" onclick="window.print()" title="প্রিন্ট / PDF">
                    <i class="fas fa-print"></i> প্রিন্ট
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Summary cards --}}
<div class="stats-grid" style="margin-bottom:20px">
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

{{-- Sales table --}}
<div class="card">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>তারিখ</th>
                    <th>কাস্টমার</th>
                    <th>আইটেম</th>
                    <th style="text-align:right">মোট</th>
                    <th style="text-align:right">পরিশোধ</th>
                    <th style="text-align:right">বাকী</th>
                    <th>চালান</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sales as $sale)
                <tr>
                    <td class="mono">{{ $loop->iteration }}</td>
                    <td>{{ $sale->sale_date->format('d/m/Y') }}</td>
                    <td>
                        @if($sale->customer)
                            <a href="{{ route('customers.ledger', $sale->customer) }}" class="link-primary">
                                {{ $sale->customer->name }}
                            </a>
                        @else
                            <span style="color:#94a3b8">নগদ</span>
                        @endif
                    </td>
                    <td style="font-size:.82rem;color:#64748b">
                        {{ $sale->items->map(fn($i) => $i->item->name)->implode(', ') }}
                    </td>
                    <td style="text-align:right;font-weight:600">৳ {{ number_format($sale->total_amount, 2) }}</td>
                    <td style="text-align:right;color:#16a34a">৳ {{ number_format($sale->paid_amount, 2) }}</td>
                    <td style="text-align:right">
                        @if($sale->due_amount > 0)
                            <span style="color:#dc2626;font-weight:600">৳ {{ number_format($sale->due_amount, 2) }}</span>
                        @else
                            <span style="color:#16a34a">—</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('sales.show', $sale) }}" class="btn-icon-sm" title="চালান দেখুন" style="color:#0d9488">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="empty-row">এই সময়কালে কোনো বিক্রয় নেই</td></tr>
                @endforelse
            </tbody>
            @if($sales->isNotEmpty())
            <tfoot>
                <tr style="background:var(--accent-light);font-weight:700">
                    <td colspan="4" style="text-align:right;padding-right:16px">সর্বমোট</td>
                    <td style="text-align:right">৳ {{ number_format($grandTotal, 2) }}</td>
                    <td style="text-align:right;color:#16a34a">৳ {{ number_format($grandPaid, 2) }}</td>
                    <td style="text-align:right;color:#dc2626">৳ {{ number_format($grandDue, 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection
