@extends('layouts.app')
@section('title', 'বিক্রয়')
@section('page-title', 'বিক্রয়')

@section('content')
@include('partials.page-header', ['title' => 'বিক্রয় তালিকা', 'createRoute' => route('sales.create'), 'createLabel' => 'নতুন বিক্রয়'])

<div class="card">
    <div class="card-filter">
        <form method="GET" class="filter-form">
            <div class="search-box"><i class="fas fa-search"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="কাস্টমার বা ইনভয়েস নম্বর...">
            </div>
            <select name="status" class="form-select">
                <option value="">সব স্ট্যাটাস</option>
                <option value="completed" @selected(request('status')=='completed')>সম্পন্ন</option>
                <option value="pending"   @selected(request('status')=='pending')>মুলতুবি</option>
                <option value="cancelled" @selected(request('status')=='cancelled')>বাতিল</option>
            </select>
            <button type="submit" class="btn btn-secondary">ফিল্টার</button>
        </form>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ইনভয়েস</th>
                    <th>কাস্টমার</th>
                    <th>মালের বিবরণ</th>
                    <th>তারিখ</th>
                    <th>মোট</th>
                    <th>পরিশোধ</th>
                    <th>বকেয়া</th>
                    <th>স্ট্যাটাস</th>
                    <th>অ্যাকশন</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sales as $sale)
                @php
                    $itemLines = $sale->items->map(fn($si) => ($si->item->name ?? '?') . ' ×' . (int)$si->quantity)->toArray();
                    $preview   = implode(', ', array_slice($itemLines, 0, 2));
                    $hasMore   = count($itemLines) > 2;
                    $full      = implode(', ', $itemLines);
                @endphp
                <tr>
                    <td><a href="{{ route('sales.show', $sale) }}" class="link-primary mono">#INV-{{ str_pad($sale->id,4,'0',STR_PAD_LEFT) }}</a></td>
                    <td>{{ $sale->customer?->name ?? 'ওয়াক-ইন' }}</td>
                    <td style="max-width:220px">
                        @if($sale->items->isEmpty())
                            <span style="color:#94a3b8;font-size:.8rem">— পণ্য নেই</span>
                        @else
                            <span class="item-preview" style="font-size:.8rem;color:#475569">{{ $preview }}</span>
                            @if($hasMore)
                                <span class="item-full" style="display:none;font-size:.8rem;color:#475569">{{ $full }}</span>
                                <button type="button" onclick="toggleItems(this)"
                                    style="display:inline-block;margin-left:4px;font-size:.72rem;color:var(--accent);
                                           background:none;border:none;cursor:pointer;font-weight:600;padding:0">
                                    +{{ count($itemLines)-2 }} আরো ▾
                                </button>
                            @endif
                        @endif
                    </td>
                    <td>{{ $sale->sale_date->format('d M Y') }}</td>
                    <td>৳ {{ number_format($sale->total_amount,2) }}</td>
                    <td style="color:#16a34a">৳ {{ number_format($sale->paid_amount,2) }}</td>
                    <td>{{ $sale->due_amount > 0 ? '৳ '.number_format($sale->due_amount,2) : '—' }}</td>
                    <td>
                        @if($sale->status==='completed') <span class="badge badge-green">সম্পন্ন</span>
                        @elseif($sale->status==='pending') <span class="badge badge-yellow">মুলতুবি</span>
                        @else <span class="badge badge-red">বাতিল</span> @endif
                    </td>
                    <td>
                        <div class="action-btns">
                            <a href="{{ route('sales.show',$sale) }}" class="btn-icon-sm"><i class="fas fa-eye"></i></a>
                            <form class="admin-only" method="POST" action="{{ route('sales.destroy',$sale) }}" onsubmit="return confirm('এই বিক্রয় মুছে ফেলবেন? স্টক পুনরুদ্ধার হবে।')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-icon-sm btn-icon-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="empty-row">কোনো বিক্রয় পাওয়া যায়নি</td></tr>
                @endforelse
            </tbody>
            @if($sales->isNotEmpty())
            <tfoot>
                <tr class="tfoot-summary">
                    <td colspan="4" style="text-align:right;font-weight:700;padding-right:16px">সর্বমোট</td>
                    <td style="font-weight:800">৳ {{ number_format($grandTotal, 0) }}</td>
                    <td style="font-weight:800;color:#16a34a">৳ {{ number_format($grandPaid, 0) }}</td>
                    <td style="font-weight:800;color:#dc2626">{{ $grandDue > 0 ? '৳ '.number_format($grandDue, 0) : '—' }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
    <div class="pagination-wrap">{{ $sales->withQueryString()->links() }}</div>
</div>

@push('scripts')
<script>
function toggleItems(btn) {
    const td      = btn.closest('td');
    const preview = td.querySelector('.item-preview');
    const full    = td.querySelector('.item-full');
    const expanded = full.style.display !== 'none';
    preview.style.display = expanded ? 'inline' : 'none';
    full.style.display    = expanded ? 'none'   : 'inline';
    btn.textContent = expanded
        ? btn.textContent.replace('▴ কম', '') + '▾ আরো'
        : '▴ কম';
    // fix label properly
    const count = btn.textContent.match(/\d+/)?.[0] ?? '';
    btn.textContent = expanded ? `+${count} আরো ▾` : '▴ কম দেখুন';
}
</script>
@endpush
@endsection
