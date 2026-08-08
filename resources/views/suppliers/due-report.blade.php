@extends('layouts.app')
@section('title', 'সরবরাহকারী বাকী রিপোর্ট')
@section('page-title', 'সরবরাহকারী বাকী রিপোর্ট')

@section('content')

<div class="stats-grid" style="margin-bottom:20px">
    <div class="stat-card" style="border-left:4px solid #ef4444">
        <div class="stat-icon" style="background:#fee2e2;color:#dc2626"><i class="fas fa-triangle-exclamation"></i></div>
        <div class="stat-body">
            <span class="stat-label">মোট বকেয়া</span>
            <span class="stat-value">৳ {{ number_format($totalDue, 0) }}</span>
        </div>
    </div>
    <div class="stat-card stat-blue">
        <div class="stat-icon"><i class="fas fa-truck"></i></div>
        <div class="stat-body">
            <span class="stat-label">বকেয়া সরবরাহকারী</span>
            <span class="stat-value">{{ $suppliers->count() }}</span>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>সরবরাহকারী</th>
                    <th>ফোন</th>
                    <th>ঠিকানা</th>
                    <th style="text-align:right">বকেয়া পরিমাণ</th>
                    <th>অ্যাকশন</th>
                </tr>
            </thead>
            <tbody>
                @forelse($suppliers as $s)
                <tr>
                    <td class="mono">{{ $loop->iteration }}</td>
                    <td><strong>{{ $s->name }}</strong></td>
                    <td>{{ $s->phone ?? '—' }}</td>
                    <td>{{ $s->address ? \Str::limit($s->address, 25) : '—' }}</td>
                    <td style="text-align:right">
                        <span class="badge badge-red" style="font-size:.9rem;padding:4px 12px">
                            ৳ {{ number_format($s->due_amount) }}
                        </span>
                    </td>
                    <td>
                        <div class="action-btns">
                            <a href="{{ route('suppliers.ledger', $s) }}" class="btn-icon-sm" title="লেজার" style="color:#0d9488">
                                <i class="fas fa-book-open"></i>
                            </a>
                            <a href="{{ route('supplier-payments.create', ['supplier_id' => $s->id]) }}" class="btn-icon-sm" title="পরিশোধ" style="color:#16a34a">
                                <i class="fas fa-money-bill-wave"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="empty-row">
                        <i class="fas fa-circle-check" style="color:#22c55e;font-size:1.5rem;display:block;margin-bottom:6px"></i>
                        সকল সরবরাহকারীর বকেয়া পরিষ্কার
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if($suppliers->isNotEmpty())
            <tfoot>
                <tr style="background:var(--accent-light);font-weight:700">
                    <td colspan="4" style="text-align:right;padding-right:16px">সর্বমোট বকেয়া</td>
                    <td style="text-align:right;color:#dc2626">৳ {{ number_format($totalDue) }}</td>
                    <td></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection
