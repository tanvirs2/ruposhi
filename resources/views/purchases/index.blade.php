@extends('layouts.app')
@section('title', 'মাল রিসিভ তালিকা')
@section('page-title', 'মাল রিসিভ')

@section('content')
@include('partials.page-header', ['title' => 'রিসিভ তালিকা', 'createRoute' => route('purchases.create'), 'createLabel' => 'নতুন রিসিভ'])

<div class="card">
    <div class="card-filter">
        <form method="GET" class="filter-form">
            <div class="search-box"><i class="fas fa-search"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="সরবরাহকারী বা নম্বর...">
            </div>
            <button type="submit" class="btn btn-secondary">খুঁজুন</button>
        </form>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>রিসিভ নম্বর</th>
                    <th>সরবরাহকারী</th>
                    <th>তারিখ</th>
                    <th>মোট মূল্য</th>
                    <th>পরিশোধ</th>
                    <th>বকেয়া</th>
                    <th>অ্যাকশন</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchases as $purchase)
                <tr>
                    <td>
                        <a href="{{ route('purchases.show', $purchase) }}" class="link-primary mono">
                            #RCV-{{ str_pad($purchase->id,4,'0',STR_PAD_LEFT) }}
                        </a>
                    </td>
                    <td>{{ $purchase->supplier?->name ?? '—' }}</td>
                    <td>{{ $purchase->purchase_date->format('d M Y') }}</td>
                    <td>৳ {{ number_format($purchase->total_amount,0) }}</td>
                    <td>৳ {{ number_format($purchase->paid_amount,0) }}</td>
                    <td>
                        @if($purchase->due_amount > 0)
                            <span style="color:#ef4444;font-weight:600">৳ {{ number_format($purchase->due_amount,0) }}</span>
                        @else
                            <span style="color:#16a34a">পরিশোধিত</span>
                        @endif
                    </td>
                    <td>
                        <div class="action-btns">
                            <a href="{{ route('purchases.show',$purchase) }}" class="btn-icon-sm" title="বিবরণ"><i class="fas fa-eye"></i></a>
                            <form method="POST" action="{{ route('purchases.destroy',$purchase) }}"
                                onsubmit="return confirm('এই রিসিভ মুছলে স্টক কমে যাবে। নিশ্চিত?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-icon-sm btn-icon-danger" title="মুছুন"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="empty-row">কোনো রিসিভ পাওয়া যায়নি</td></tr>
                @endforelse
            </tbody>
            @if($purchases->isNotEmpty())
            <tfoot>
                <tr class="tfoot-summary">
                    <td colspan="3" style="text-align:right;font-weight:700;padding-right:16px">সর্বমোট</td>
                    <td style="font-weight:800">৳ {{ number_format($grandTotal, 0) }}</td>
                    <td style="font-weight:800;color:#16a34a">৳ {{ number_format($grandPaid, 0) }}</td>
                    <td style="font-weight:800;color:#dc2626">{{ $grandDue > 0 ? '৳ '.number_format($grandDue, 0) : '—' }}</td>
                    <td></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
    <div class="pagination-wrap">{{ $purchases->withQueryString()->links() }}</div>
</div>
@endsection
