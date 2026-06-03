@extends('layouts.app')
@section('title', 'সরবরাহকারী')
@section('page-title', 'সরবরাহকারী')

@section('content')
@include('partials.page-header', ['title' => 'সরবরাহকারী তালিকা', 'createRoute' => route('suppliers.create'), 'createLabel' => 'নতুন সরবরাহকারী'])

<div class="card">
    <div class="card-filter">
        <form method="GET" class="filter-form">
            <div class="search-box"><i class="fas fa-search"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="নাম বা ফোন...">
            </div>
            <button type="submit" class="btn btn-secondary">খুঁজুন</button>
            @if(request('search'))<a href="{{ route('suppliers.index') }}" class="btn btn-ghost">পরিষ্কার</a>@endif
        </form>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <colgroup>
                <col style="width:50px">    {{-- # --}}
                <col style="width:180px">   {{-- নাম --}}
                <col style="width:130px">   {{-- ফোন --}}
                <col style="width:200px">   {{-- ঠিকানা --}}
                <col style="width:120px">   {{-- বকেয়া --}}
                <col style="width:90px">    {{-- অ্যাকশন --}}
            </colgroup>
            <thead>
                <tr><th>#</th><th>নাম</th><th>ফোন</th><th>ঠিকানা</th><th>বকেয়া</th><th>অ্যাকশন</th></tr>
            </thead>
            <tbody>
                @forelse($suppliers as $supplier)
                <tr>
                    <td class="mono">{{ $loop->iteration }}</td>
                    <td><strong>{{ $supplier->name }}</strong></td>
                    <td>{{ $supplier->phone ?? '—' }}</td>
                    <td>{{ $supplier->address ? \Str::limit($supplier->address, 30) : '—' }}</td>
                    <td>
                        @if($supplier->due_amount > 0)
                            <span class="badge badge-red">৳ {{ number_format($supplier->due_amount, 0) }}</span>
                        @elseif($supplier->due_amount < 0)
                            <span class="badge" style="background:#eff6ff;color:#1d4ed8">অগ্রিম ৳ {{ number_format(abs($supplier->due_amount), 0) }}</span>
                        @else
                            <span class="badge badge-green">পরিষ্কার</span>
                        @endif
                    </td>
                    <td>
                        <div class="action-btns">
                            <a href="{{ route('suppliers.ledger', $supplier) }}" class="btn-icon-sm" title="লেজার" style="color:#0d9488">
                                <i class="fas fa-book-open"></i>
                            </a>
                            <a href="{{ route('supplier-payments.create', ['supplier_id' => $supplier->id]) }}" class="btn-icon-sm" title="পরিশোধ যোগ" style="color:#16a34a">
                                <i class="fas fa-money-bill-wave"></i>
                            </a>
                            <a href="{{ route('suppliers.edit', $supplier) }}" class="btn-icon-sm" title="সম্পাদনা">
                                <i class="fas fa-pen"></i>
                            </a>
                            <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}" onsubmit="return confirm('মুছে ফেলবেন?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-icon-sm btn-icon-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="empty-row">কোনো সরবরাহকারী পাওয়া যায়নি</td></tr>
                @endforelse
            </tbody>
            @if($suppliers->isNotEmpty())
            <tfoot>
                <tr class="tfoot-summary">
                    <td colspan="4" style="text-align:right;font-weight:700;padding-right:16px">মোট বকেয়া</td>
                    <td style="font-weight:800;color:#dc2626">৳ {{ number_format($grossDue, 0) }}</td>
                    <td></td>
                </tr>
                @if($totalCredit > 0)
                <tr class="tfoot-summary">
                    <td colspan="4" style="text-align:right;font-weight:700;padding-right:16px">মোট অগ্রিম (−)</td>
                    <td style="font-weight:800;color:#1d4ed8">৳ {{ number_format($totalCredit, 0) }}</td>
                    <td></td>
                </tr>
                <tr class="tfoot-summary">
                    <td colspan="4" style="text-align:right;font-weight:700;padding-right:16px">নিট বকেয়া</td>
                    <td style="font-weight:800;color:{{ $totalDue > 0 ? '#dc2626' : '#16a34a' }}">
                        ৳ {{ number_format(abs($totalDue), 0) }}
                    </td>
                    <td></td>
                </tr>
                @endif
            </tfoot>
            @endif
        </table>
    </div>
    <div class="pagination-wrap">{{ $suppliers->withQueryString()->links() }}</div>
</div>
@endsection
