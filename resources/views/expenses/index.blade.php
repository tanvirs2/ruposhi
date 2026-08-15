@extends('layouts.app')
@section('title', 'খরচ ও জমা')
@section('page-title', 'খরচ ও জমা')

@push('styles')
<meta name="turbo-cache-control" content="no-cache">
@endpush

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
        <form method="GET" class="filter-form" data-date-snap>
            <div class="search-box"><i class="fas fa-search"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="শিরোনাম...">
            </div>
            <select name="category" class="form-select">
                <option value="">সব ক্যাটাগরি</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat }}" @selected(request('category')===$cat)>{{ $cat }}</option>
                @endforeach
            </select>
            <input type="date" name="from" class="form-select" value="{{ $from }}" title="শুরুর তারিখ" style="width:145px">
            <input type="date" name="to"   class="form-select" value="{{ $to }}"   title="শেষ তারিখ"  style="width:145px">
            @include('partials.date-range-buttons')
            <button type="submit" class="btn btn-secondary">ফিল্টার</button>
            @if(request()->hasAny(['search','type','category','from','to']))
                <a href="{{ route('expenses.index') }}" class="btn btn-ghost">রিসেট</a>
            @endif
        </form>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;align-items:start" class="expense-split-grid">
    {{-- জমা (left) --}}
    <div class="card" style="margin:0">
        <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:8px">
            <span style="display:inline-flex;align-items:center;gap:6px;font-size:.95rem;font-weight:700;color:#15803d">
                <i class="fas fa-arrow-down-to-line"></i> জমা
            </span>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>শিরোনাম</th>
                        <th style="text-align:right">পরিমাণ</th>
                        <th>তারিখ</th>
                        <th>অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deposits as $dep)
                    <tr style="background:#f0fdf4">
                        <td>{{ $deposits->firstItem() + $loop->index }}</td>
                        <td><strong>{{ $dep->title }}</strong></td>
                        <td style="text-align:right;font-variant-numeric:tabular-nums">
                            <span style="font-weight:700;color:#15803d">+৳ {{ number_format($dep->amount, 0) }}</span>
                        </td>
                        <td style="white-space:nowrap">{{ $dep->expense_date->format('d M Y') }}</td>
                        <td>
                            <div class="action-btns">
                                <a href="{{ route('expenses.edit', $dep) }}" class="btn-icon-sm"><i class="fas fa-pen"></i></a>
                                <form class="admin-only" method="POST" action="{{ route('expenses.destroy', $dep) }}" onsubmit="return confirm('মুছে ফেলবেন?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon-sm btn-icon-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="empty-row">কোনো জমা পাওয়া যায়নি</td></tr>
                    @endforelse
                </tbody>
                @if($deposits->count())
                <tfoot>
                    <tr class="tfoot-summary">
                        <td colspan="2" style="font-weight:700">মোট</td>
                        <td style="text-align:right;vertical-align:top;padding-top:10px">
                            <span style="display:block;font-weight:800;color:#15803d;font-variant-numeric:tabular-nums">+৳ {{ number_format($totalDeposit, 0) }}</span>
                            @if(\App\Support\BanglaWords::taka($totalDeposit) !== '')
                            <small style="display:block;font-size:.72rem;color:#15803d;font-weight:600;margin-top:2px;white-space:normal">{{ \App\Support\BanglaWords::taka($totalDeposit) }} মাত্র</small>
                            @endif
                        </td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        <div class="pagination-wrap">{{ $deposits->links() }}</div>
    </div>

    {{-- খরচ (right) --}}
    <div class="card" style="margin:0">
        <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:8px">
            <span style="display:inline-flex;align-items:center;gap:6px;font-size:.95rem;font-weight:700;color:#b91c1c">
                <i class="fas fa-arrow-up-from-line"></i> খরচ
            </span>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>শিরোনাম</th>
                        <th style="text-align:right">পরিমাণ</th>
                        <th>তারিখ</th>
                        <th>অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $exp)
                    <tr>
                        <td>{{ $expenses->firstItem() + $loop->index }}</td>
                        <td><strong>{{ $exp->title }}</strong></td>
                        <td style="text-align:right;font-variant-numeric:tabular-nums">
                            <span style="font-weight:700;color:#dc2626">-৳ {{ number_format($exp->amount, 0) }}</span>
                        </td>
                        <td style="white-space:nowrap">{{ $exp->expense_date->format('d M Y') }}</td>
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
                    <tr><td colspan="5" class="empty-row">কোনো খরচ পাওয়া যায়নি</td></tr>
                    @endforelse
                </tbody>
                @if($expenses->count())
                <tfoot>
                    <tr class="tfoot-summary">
                        <td colspan="2" style="font-weight:700">মোট</td>
                        <td style="text-align:right;vertical-align:top;padding-top:10px">
                            <span style="display:block;font-weight:800;color:#dc2626;font-variant-numeric:tabular-nums">-৳ {{ number_format($totalExpense, 0) }}</span>
                            @if(\App\Support\BanglaWords::taka($totalExpense) !== '')
                            <small style="display:block;font-size:.72rem;color:#dc2626;font-weight:600;margin-top:2px;white-space:normal">{{ \App\Support\BanglaWords::taka($totalExpense) }} মাত্র</small>
                            @endif
                        </td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        <div class="pagination-wrap">{{ $expenses->links() }}</div>
    </div>
</div>

<style>
@media (max-width: 900px) {
    .expense-split-grid { grid-template-columns: 1fr !important; }
}
</style>

@endsection
