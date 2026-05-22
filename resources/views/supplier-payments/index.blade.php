@extends('layouts.app')
@section('title', 'সরবরাহকারী পরিশোধ তালিকা')
@section('page-title', 'সরবরাহকারী পরিশোধ তালিকা')

@section('content')
@include('partials.page-header', ['title' => 'পরিশোধ তালিকা', 'createRoute' => route('supplier-payments.create'), 'createLabel' => 'নতুন পরিশোধ'])

<div class="stats-grid" style="margin-bottom:20px">
    <div class="stat-card stat-green">
        <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
        <div class="stat-body">
            <span class="stat-label">মোট পরিশোধ (ফিল্টার)</span>
            <span class="stat-value">৳ {{ number_format($totalPaid, 0) }}</span>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-filter">
        <form method="GET" class="filter-form">
            <div class="form-group-field">
                <select name="supplier_id" class="form-select">
                    <option value="">সব সরবরাহকারী</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" @selected(request('supplier_id') == $s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group-field">
                <input type="date" name="from" value="{{ request('from') }}" class="form-select">
            </div>
            <div class="form-group-field">
                <input type="date" name="to" value="{{ request('to') }}" class="form-select">
            </div>
            <button type="submit" class="btn btn-secondary">ফিল্টার</button>
            @if(request()->hasAny(['supplier_id','from','to']))
                <a href="{{ route('supplier-payments.index') }}" class="btn btn-ghost">পরিষ্কার</a>
            @endif
        </form>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr><th>#</th><th>সরবরাহকারী</th><th>পরিমাণ</th><th>তারিখ</th><th>পদ্ধতি</th><th>মন্তব্য</th><th>রেকর্ডকারী</th><th>অ্যাকশন</th></tr>
            </thead>
            <tbody>
                @forelse($payments as $p)
                <tr>
                    <td class="mono">{{ $loop->iteration }}</td>
                    <td>
                        <a href="{{ route('suppliers.ledger', $p->supplier) }}" class="link-primary">
                            {{ $p->supplier->name }}
                        </a>
                    </td>
                    <td><strong style="color:#16a34a">৳ {{ number_format($p->amount, 2) }}</strong></td>
                    <td>{{ $p->payment_date->format('d/m/Y') }}</td>
                    <td><span class="badge badge-green">{{ $p->payment_method }}</span></td>
                    <td>{{ $p->notes ?? '—' }}</td>
                    <td>{{ $p->user->name }}</td>
                    <td>
                        <form method="POST" action="{{ route('supplier-payments.destroy', $p) }}"
                              onsubmit="return confirm('এই পরিশোধ মুছে ফেলবেন? বকেয়া পুনরুদ্ধার হবে।')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-icon-sm btn-icon-danger" title="মুছুন">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="empty-row">কোনো পরিশোধ পাওয়া যায়নি</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">{{ $payments->withQueryString()->links() }}</div>
</div>
@endsection
