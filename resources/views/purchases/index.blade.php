@extends('layouts.app')
@section('title', 'মাল রিসিভ তালিকা')
@section('page-title', 'মাল রিসিভ')

@section('content')
@include('partials.page-header', ['title' => 'রিসিভ তালিকা', 'createRoute' => route('purchases.create'), 'createLabel' => 'নতুন রিসিভ'])

<div class="card">
    <div class="card-filter" style="flex-wrap:wrap;gap:8px">
        <form method="GET" class="filter-form" style="flex:1;min-width:0">
            <div class="search-box"><i class="fas fa-search"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="সরবরাহকারী বা নম্বর...">
            </div>
            <input type="date" name="date_from" class="form-select" value="{{ $dateFrom }}" title="শুরুর তারিখ" style="width:145px">
            <input type="date" name="date_to"   class="form-select" value="{{ $dateTo }}"   title="শেষ তারিখ"  style="width:145px">
            @include('partials.date-range-buttons', ['fromName'=>'date_from','toName'=>'date_to'])
            <button type="submit" class="btn btn-secondary">ফিল্টার</button>
            @if($dateFrom || $dateTo || request('search'))
                <a href="{{ route('purchases.index') }}" class="btn btn-outline" title="ক্লিয়ার করুন"><i class="fas fa-xmark"></i></a>
            @endif
        </form>

        {{-- View toggle --}}
        <div class="view-toggle-group">
            <button id="btnInvoiceView" class="view-toggle-btn active" onclick="setView('invoice')">
                <i class="fas fa-file-invoice"></i> ইনভয়েস ভিউ
            </button>
            <button id="btnItemView" class="view-toggle-btn" onclick="setView('item')">
                <i class="fas fa-list"></i> আইটেম ভিউ
            </button>
        </div>
    </div>

    {{-- ══ INVOICE VIEW ══════════════════════════════════════════ --}}
    <div id="invoiceView">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>রিসিভ নম্বর</th>
                        <th>সরবরাহকারী</th>
                        <th>মালের বিবরণ</th>
                        <th>তারিখ</th>
                        <th>মোট মূল্য</th>
                        <th>পরিশোধ</th>
                        <th>বকেয়া</th>
                        <th>অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchases as $purchase)
                    @php
                        $itemLines = $purchase->items->map(fn($pi) => ($pi->item->name ?? '?') . ' ×' . (int)$pi->quantity)->toArray();
                        $preview   = implode(', ', array_slice($itemLines, 0, 2));
                        $hasMore   = count($itemLines) > 2;
                        $full      = implode(', ', $itemLines);
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('purchases.show', $purchase) }}" class="link-primary mono">
                                #RCV-{{ str_pad($purchase->id,4,'0',STR_PAD_LEFT) }}
                            </a>
                        </td>
                        <td>{{ $purchase->supplier?->name ?? '—' }}</td>
                        <td style="max-width:220px">
                            @if($purchase->items->isEmpty())
                                <span style="color:#94a3b8;font-size:.8rem">— অগ্রিম পরিশোধ</span>
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
                        <td>
                            {{ $purchase->purchase_date->format('d M Y') }}
                            <br><small style="color:#94a3b8;font-size:.75rem">{{ $purchase->created_at->format('h:i a') }}</small>
                        </td>
                        <td>৳ {{ number_format($purchase->total_amount,0) }}</td>
                        <td>৳ {{ number_format($purchase->paid_amount,0) }}</td>
                        <td>
                            @if($purchase->due_amount > 0)
                                <span style="color:#ef4444;font-weight:600">৳ {{ number_format($purchase->due_amount,0) }}</span>
                            @elseif($purchase->due_amount < 0)
                                <span style="background:#eff6ff;color:#1d4ed8;font-size:.78rem;font-weight:700;padding:2px 8px;border-radius:20px">
                                    অগ্রিম ৳ {{ number_format(abs($purchase->due_amount),0) }}
                                </span>
                            @else
                                <span style="color:#16a34a">পরিশোধিত</span>
                            @endif
                        </td>
                        <td>
                            <div class="action-btns">
                                <a href="{{ route('purchases.show',$purchase) }}" class="btn-icon-sm" title="বিবরণ"><i class="fas fa-eye"></i></a>
                                <form class="admin-only" method="POST" action="{{ route('purchases.destroy',$purchase) }}"
                                    onsubmit="return confirm('এই রিসিভ মুছলে স্টক কমে যাবে। নিশ্চিত?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon-sm btn-icon-danger" title="মুছুন"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="empty-row">কোনো রিসিভ পাওয়া যায়নি</td></tr>
                    @endforelse
                </tbody>
                @if($purchases->isNotEmpty())
                <tfoot>
                    <tr class="tfoot-summary">
                        <td colspan="4" style="text-align:right;font-weight:700;padding-right:16px">সর্বমোট</td>
                        <td style="font-weight:800">৳ {{ number_format($grandTotal, 0) }}</td>
                        <td style="font-weight:800;color:#16a34a">৳ {{ number_format($grandPaid, 0) }}</td>
                        <td style="font-weight:800;color:#dc2626">৳ {{ number_format($grossDue, 0) }}</td>
                        <td></td>
                    </tr>
                    @if($totalCredit > 0)
                    <tr class="tfoot-summary">
                        <td colspan="4" style="text-align:right;font-weight:700;padding-right:16px">মোট অগ্রিম (−)</td>
                        <td></td><td></td>
                        <td style="font-weight:800;color:#1d4ed8">৳ {{ number_format($totalCredit, 0) }}</td>
                        <td></td>
                    </tr>
                    <tr class="tfoot-summary">
                        <td colspan="4" style="text-align:right;font-weight:700;padding-right:16px">নিট বকেয়া</td>
                        <td></td><td></td>
                        <td style="font-weight:800;color:{{ $grandDue > 0 ? '#dc2626' : '#16a34a' }}">
                            ৳ {{ number_format(abs($grandDue), 0) }}
                            @if($grandDue <= 0) <span style="font-size:.75rem">(অগ্রিম)</span> @endif
                        </td>
                        <td></td>
                    </tr>
                    @endif
                </tfoot>
                @endif
            </table>
        </div>
        <div class="pagination-wrap">{{ $purchases->withQueryString()->links() }}</div>
    </div>

    {{-- ══ ITEM VIEW ═══════════════════════════════════════════════ --}}
    <div id="itemView" style="display:none">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>রিসিভ নম্বর</th>
                        <th>সরবরাহকারী</th>
                        <th>আইটেমের নাম</th>
                        <th style="text-align:right">পরিমাণ</th>
                        <th style="text-align:right">একক মূল্য</th>
                        <th style="text-align:right">সাবটোটাল</th>
                        <th>তারিখ</th>
                    </tr>
                </thead>
                <tbody>
                    @php $itemViewEmpty = true; $totalQty = 0; $totalSubtotal = 0; @endphp
                    @foreach($purchases as $purchase)
                        @foreach($purchase->items as $pi)
                            @php
                                $itemViewEmpty = false;
                                $totalQty      += $pi->quantity;
                                $totalSubtotal += $pi->subtotal;
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('purchases.show', $purchase) }}" class="link-primary mono">
                                        #RCV-{{ str_pad($purchase->id,4,'0',STR_PAD_LEFT) }}
                                    </a>
                                </td>
                                <td>{{ $purchase->supplier?->name ?? '—' }}</td>
                                <td style="font-weight:500">{{ $pi->item->name ?? '—' }}</td>
                                <td style="text-align:right">{{ number_format($pi->quantity, 0) }}</td>
                                <td style="text-align:right">৳ {{ number_format($pi->price, 2) }}</td>
                                <td style="text-align:right;font-weight:600">৳ {{ number_format($pi->subtotal, 0) }}</td>
                                <td>
                                    {{ $purchase->purchase_date->format('d M Y') }}
                                    <br><small style="color:#94a3b8;font-size:.75rem">{{ $purchase->created_at->format('h:i a') }}</small>
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                    @if($itemViewEmpty)
                    <tr><td colspan="7" class="empty-row">কোনো আইটেম পাওয়া যায়নি</td></tr>
                    @endif
                </tbody>
                @if(!$itemViewEmpty)
                <tfoot>
                    <tr class="tfoot-summary">
                        <td colspan="3" style="text-align:right;font-weight:700;padding-right:16px">সর্বমোট</td>
                        <td style="text-align:right;font-weight:800">{{ number_format($totalQty, 0) }}</td>
                        <td></td>
                        <td style="text-align:right;font-weight:800;color:#dc2626">৳ {{ number_format($totalSubtotal, 0) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        <div class="pagination-wrap">{{ $purchases->withQueryString()->links() }}</div>
    </div>
</div>

@push('styles')
<style>
.view-toggle-group {
    display: flex;
    border: 1.5px solid var(--border);
    border-radius: 8px;
    overflow: hidden;
    flex-shrink: 0;
}
.view-toggle-btn {
    padding: 7px 16px;
    border: none;
    background: var(--surface);
    color: var(--text-secondary);
    font-family: inherit;
    font-size: .83rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: background .15s, color .15s;
    border-right: 1.5px solid var(--border);
}
.view-toggle-btn:last-child { border-right: none; }
.view-toggle-btn:hover { background: var(--bg); color: var(--text); }
.view-toggle-btn.active {
    background: var(--accent);
    color: #fff;
}
</style>
@endpush

@push('scripts')
<script>
function setView(type) {
    document.getElementById('invoiceView').style.display = type === 'invoice' ? '' : 'none';
    document.getElementById('itemView').style.display    = type === 'item'    ? '' : 'none';
    document.getElementById('btnInvoiceView').classList.toggle('active', type === 'invoice');
    document.getElementById('btnItemView').classList.toggle('active',    type === 'item');
    localStorage.setItem('purchaseView', type);
}

function toggleItems(btn) {
    const td      = btn.closest('td');
    const preview = td.querySelector('.item-preview');
    const full    = td.querySelector('.item-full');
    const expanded = full.style.display !== 'none';
    preview.style.display = expanded ? 'inline' : 'none';
    full.style.display    = expanded ? 'none'   : 'inline';
    const count = btn.textContent.match(/\d+/)?.[0] ?? '';
    btn.textContent = expanded ? `+${count} আরো ▾` : '▴ কম দেখুন';
}

// Restore last used view
const savedView = localStorage.getItem('purchaseView') || 'invoice';
setView(savedView);
</script>
@endpush
@endsection
