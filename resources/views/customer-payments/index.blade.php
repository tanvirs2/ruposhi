@extends('layouts.app')
@section('title', 'কাস্টমার পরিশোধ')
@section('page-title', 'কাস্টমার পরিশোধ')

@section('content')
@include('partials.page-header', ['title' => 'পরিশোধ তালিকা', 'createRoute' => route('customer-payments.create'), 'createLabel' => 'নতুন পরিশোধ'])

<div class="card" style="margin-bottom:20px">
    <div class="card-filter">
        <form method="GET" class="filter-form">
            <div class="form-group-field">
                <label>কাস্টমার</label>
                <select name="customer_id" class="form-select">
                    <option value="">সকল কাস্টমার</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" {{ request('customer_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group-field">
                <label>শুরু</label>
                <input type="date" name="from" value="{{ request('from') }}">
            </div>
            <div class="form-group-field">
                <label>শেষ</label>
                <input type="date" name="to" value="{{ request('to') }}">
            </div>
            @include('partials.date-range-buttons')
            <button type="submit" class="btn btn-secondary" style="align-self:flex-end">ফিল্টার</button>
        </form>
    </div>
</div>

<div class="stats-grid" style="margin-bottom:20px">
    <div class="stat-card stat-green">
        <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
        <div class="stat-body">
            <span class="stat-label">মোট পরিশোধ</span>
            <span class="stat-value">৳ {{ number_format($totalPaid, 0) }}</span>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ইনভয়েস</th>
                    <th>তারিখ</th>
                    <th>কাস্টমার</th>
                    <th>পরিমাণ</th>
                    <th>পদ্ধতি</th>
                    <th>মন্তব্য</th>
                    <th>রেকর্ডকারী</th>
                    <th>অ্যাকশন</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $p)
                <tr>
                    <td>
                        <a href="{{ route('sales.show', $p) }}" class="link-primary mono">
                            #INV-{{ str_pad($p->id,4,'0',STR_PAD_LEFT) }}
                        </a>
                    </td>
                    <td>{{ $p->sale_date->format('d M Y') }}</td>
                    <td>
                        <a href="{{ route('customers.ledger', $p->customer) }}" class="link-primary">
                            {{ $p->customer->name ?? '—' }}
                        </a>
                    </td>
                    <td><strong style="color:#16a34a">৳ {{ number_format($p->paid_amount, 0) }}</strong></td>
                    <td><span class="badge badge-green">{{ $p->payment_method }}</span></td>
                    <td>{{ $p->notes ?? '—' }}</td>
                    <td>{{ $p->user->name ?? '—' }}</td>
                    <td>
                        <div class="action-btns">
                            <a href="{{ route('sales.show', $p) }}" class="btn-icon-sm" title="ইনভয়েস"><i class="fas fa-eye"></i></a>
                            <form class="admin-only" method="POST" action="{{ route('sales.destroy', $p) }}"
                                  data-confirm-msg="পরিশোধ ৳{{ number_format($p->paid_amount,0) }} মুছে ফেলবেন? {{ $p->customer?->name ?? '' }}-এর বাকী ৳{{ number_format($p->paid_amount,0) }} বেড়ে যাবে।">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-icon-sm btn-icon-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="empty-row">কোনো পরিশোধ পাওয়া যায়নি</td></tr>
                @endforelse
            </tbody>
            @if($payments->isNotEmpty())
            <tfoot>
                <tr class="tfoot-summary">
                    <td colspan="3" style="text-align:right;font-weight:700;padding-right:16px">সর্বমোট (ফিল্টার)</td>
                    <td style="font-weight:800;color:#16a34a">৳ {{ number_format($totalPaid, 2) }}</td>
                    <td colspan="4"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
    <div class="pagination-wrap">{{ $payments->withQueryString()->links() }}</div>
</div>
@endsection
