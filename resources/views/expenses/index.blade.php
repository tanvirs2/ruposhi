@extends('layouts.app')
@section('title', 'খরচ ও জমা')
@section('page-title', 'খরচ ও জমা')

@section('content')

<div class="page-header-bar">
    <h2 style="font-size:1.1rem;font-weight:700">খরচ ও জমার তালিকা</h2>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="{{ route('expenses.create', ['type'=>'deposit']) }}" class="btn btn-primary">
            <i class="fas fa-arrow-down-to-line"></i> নতুন জমা
        </a>
        <a href="{{ route('expenses.create', ['type'=>'expense']) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-up-from-line"></i> নতুন খরচ
        </a>
    </div>
</div>

{{-- KPI --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px;margin-bottom:20px">
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:16px 18px;color:#15803d">
        <div style="font-size:.78rem;font-weight:600;opacity:.8;margin-bottom:4px"><i class="fas fa-arrow-down-to-line"></i> মোট জমা</div>
        <div style="font-size:1.4rem;font-weight:800;font-variant-numeric:tabular-nums">৳ {{ number_format($totalDeposit, 0) }}</div>
    </div>
    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:16px 18px;color:#b91c1c">
        <div style="font-size:.78rem;font-weight:600;opacity:.8;margin-bottom:4px"><i class="fas fa-arrow-up-from-line"></i> মোট খরচ</div>
        <div style="font-size:1.4rem;font-weight:800;font-variant-numeric:tabular-nums">৳ {{ number_format($totalExpense, 0) }}</div>
    </div>
    <div style="background:{{ $totalDeposit - $totalExpense >= 0 ? '#f0fdf4' : '#fef2f2' }};border:1px solid {{ $totalDeposit - $totalExpense >= 0 ? '#bbf7d0' : '#fecaca' }};border-radius:12px;padding:16px 18px;color:{{ $totalDeposit - $totalExpense >= 0 ? '#15803d' : '#b91c1c' }}">
        <div style="font-size:.78rem;font-weight:600;opacity:.8;margin-bottom:4px"><i class="fas fa-scale-balanced"></i> নিট</div>
        <div style="font-size:1.4rem;font-weight:800;font-variant-numeric:tabular-nums">
            {{ $totalDeposit - $totalExpense >= 0 ? '+' : '' }}৳ {{ number_format($totalDeposit - $totalExpense, 0) }}
        </div>
    </div>
</div>

<div class="card">
    <div class="card-filter">
        <form method="GET" class="filter-form">
            <div class="search-box"><i class="fas fa-search"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="শিরোনাম...">
            </div>
            <select name="type" class="form-select">
                <option value="">সব ধরন</option>
                <option value="deposit"  @selected(request('type')==='deposit')>💚 জমা</option>
                <option value="expense"  @selected(request('type')==='expense')>🔴 খরচ</option>
            </select>
            <select name="category" class="form-select">
                <option value="">সব ক্যাটাগরি</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat }}" @selected(request('category')===$cat)>{{ $cat }}</option>
                @endforeach
            </select>
            <input type="date" name="from" value="{{ request('from') }}" title="শুরুর তারিখ">
            <input type="date" name="to"   value="{{ request('to') }}"   title="শেষ তারিখ">
            @include('partials.date-range-buttons')
            <button type="submit" class="btn btn-secondary">ফিল্টার</button>
            @if(request()->hasAny(['search','type','category','from','to']))
                <a href="{{ route('expenses.index') }}" class="btn btn-ghost">রিসেট</a>
            @endif
        </form>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>ধরন</th>
                    <th>শিরোনাম</th>
                    <th>ক্যাটাগরি</th>
                    <th style="text-align:right">পরিমাণ</th>
                    <th>তারিখ</th>
                    <th>মন্তব্য</th>
                    <th>অ্যাকশন</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expenses as $exp)
                <tr style="{{ $exp->type === 'deposit' ? 'background:#f0fdf4' : '' }}">
                    <td>{{ $expenses->firstItem() + $loop->index }}</td>
                    <td>
                        @if($exp->type === 'deposit')
                            <span style="display:inline-flex;align-items:center;gap:4px;font-size:.8rem;font-weight:700;color:#15803d;background:#dcfce7;padding:2px 10px;border-radius:20px">
                                <i class="fas fa-arrow-down-to-line" style="font-size:.7rem"></i> জমা
                            </span>
                        @else
                            <span style="display:inline-flex;align-items:center;gap:4px;font-size:.8rem;font-weight:700;color:#b91c1c;background:#fee2e2;padding:2px 10px;border-radius:20px">
                                <i class="fas fa-arrow-up-from-line" style="font-size:.7rem"></i> খরচ
                            </span>
                        @endif
                    </td>
                    <td><strong>{{ $exp->title }}</strong></td>
                    <td>{{ $exp->category ?? '—' }}</td>
                    <td style="text-align:right;font-variant-numeric:tabular-nums">
                        <span style="font-weight:700;color:{{ $exp->type==='deposit' ? '#15803d' : '#dc2626' }}">
                            {{ $exp->type==='deposit' ? '+' : '-' }}৳ {{ number_format($exp->amount, 0) }}
                        </span>
                    </td>
                    <td style="white-space:nowrap">{{ $exp->expense_date->format('d M Y') }}</td>
                    <td style="font-size:.82rem;color:#64748b;max-width:180px;white-space:normal">{{ $exp->notes ?? '—' }}</td>
                    <td>
                        <div class="action-btns">
                            <a href="{{ route('expenses.edit', $exp) }}" class="btn-icon-sm"><i class="fas fa-pen"></i></a>
                            <form class="admin-only" method="POST" action="{{ route('expenses.destroy', $exp) }}" onsubmit="return confirm('মুছে ফেলবেন?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-icon-sm btn-icon-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="empty-row">কোনো রেকর্ড পাওয়া যায়নি</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">{{ $expenses->links() }}</div>
</div>

@endsection
