@extends('layouts.app')
@section('title', 'কাস্টমার')
@section('page-title', 'কাস্টমার')

@section('content')
@include('partials.page-header', ['title' => 'কাস্টমার তালিকা', 'createRoute' => route('customers.create'), 'createLabel' => 'নতুন কাস্টমার'])

<div class="card">
    <div class="card-filter">
        <form method="GET" action="{{ route('customers.index') }}" class="filter-form">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="নাম বা ফোন নম্বর...">
            </div>
            <div class="form-group-field">
                <select name="area_id" class="form-select">
                    <option value="">সকল এরিয়া</option>
                    @foreach($areas as $area)
                        <option value="{{ $area->id }}" {{ request('area_id') == $area->id ? 'selected' : '' }}>
                            {{ $area->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-secondary">খুঁজুন</button>
            @if(request('search') || request('area_id'))
                <a href="{{ route('customers.index') }}" class="btn btn-ghost">পরিষ্কার</a>
            @endif

            {{-- Toggle: hide/show zero-due customers --}}
            @if($showAll)
                <a href="{{ route('customers.index', array_merge(request()->except('show_all'), [])) }}"
                   class="btn btn-ghost" style="color:#0d9488;border-color:#0d9488">
                    <i class="fas fa-eye-slash"></i> পরিষ্কার লুকান
                </a>
            @else
                <a href="{{ route('customers.index', array_merge(request()->all(), ['show_all' => 1])) }}"
                   class="btn btn-ghost">
                    <i class="fas fa-eye"></i> সব দেখুন
                </a>
            @endif
        </form>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <colgroup>
                <col style="width:50px">    {{-- # --}}
                <col style="width:200px">   {{-- নাম --}}
                <col style="width:130px">   {{-- এরিয়া --}}
                <col style="width:130px">   {{-- ফোন --}}
                <col style="width:120px">   {{-- বকেয়া --}}
                <col style="width:90px">    {{-- অ্যাকশন --}}
            </colgroup>
            <thead>
                <tr>
                    <th>#</th>
                    <th>প্রতিষ্ঠান / নাম</th>
                    <th>এরিয়া</th>
                    <th>ফোন</th>
                    <th>বকেয়া</th>
                    <th>অ্যাকশন</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $customer)
                <tr>
                    <td class="mono">{{ $loop->iteration }}</td>
                    <td>
                        <strong>{{ $customer->name }}</strong>
                        @if($customer->proprietor)
                            <div style="font-size:.78rem;color:#64748b">{{ $customer->proprietor }}</div>
                        @endif
                    </td>
                    <td>{{ $customer->area?->name ?? '—' }}</td>
                    <td>{{ $customer->phone ?? '—' }}</td>
                    <td>
                        @if($customer->due_amount > 0)
                            <span class="badge badge-red">৳ {{ number_format($customer->due_amount, 0) }}</span>
                        @elseif($customer->due_amount < 0)
                            <span class="badge" style="background:#eff6ff;color:#1d4ed8">অগ্রিম ৳ {{ number_format(abs($customer->due_amount), 0) }}</span>
                        @else
                            <span class="badge badge-green">পরিষ্কার</span>
                        @endif
                    </td>
                    <td>
                        <div class="action-btns">
                            <a href="{{ route('customers.ledger', $customer) }}" class="btn-icon-sm" title="লেজার" style="color:#0d9488">
                                <i class="fas fa-book-open"></i>
                            </a>
                            <a href="{{ route('customers.edit', $customer) }}" class="btn-icon-sm" title="সম্পাদনা">
                                <i class="fas fa-pen"></i>
                            </a>
                            <form method="POST" action="{{ route('customers.destroy', $customer) }}"
                                  onsubmit="return confirm('এই কাস্টমার মুছে ফেলবেন?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-icon-sm btn-icon-danger" title="মুছুন">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="empty-row">কোনো কাস্টমার পাওয়া যায়নি</td></tr>
                @endforelse
            </tbody>
            @if($customers->isNotEmpty())
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
    <div class="pagination-wrap">{{ $customers->withQueryString()->links() }}</div>
</div>
@endsection
