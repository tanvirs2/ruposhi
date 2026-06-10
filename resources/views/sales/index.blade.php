@extends('layouts.app')
@section('title', 'বিক্রয়')
@section('page-title', 'বিক্রয়')

@section('content')
@include('partials.page-header', ['title' => 'বিক্রয় তালিকা', 'createRoute' => route('sales.create'), 'createLabel' => 'নতুন বিক্রয়'])

<div class="card">
    <div class="card-filter" style="flex-wrap:wrap;gap:8px">
        <form method="GET" class="filter-form" id="salesFilterForm" style="flex:1;min-width:0">
            <div class="search-box"><i class="fas fa-search"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="কাস্টমার বা ইনভয়েস নম্বর...">
            </div>
            <select name="status" class="form-select" style="width:auto;min-width:130px">
                <option value="">সব স্ট্যাটাস</option>
                <option value="completed" @selected(request('status')=='completed')>সম্পন্ন</option>
                <option value="pending"   @selected(request('status')=='pending')>মুলতুবি</option>
                <option value="cancelled" @selected(request('status')=='cancelled')>বাতিল</option>
            </select>
            <input type="date" name="date_from" class="form-select" value="{{ $dateFrom }}" title="শুরুর তারিখ" style="width:145px">
            <input type="date" name="date_to"   class="form-select" value="{{ $dateTo }}"   title="শেষ তারিখ"  style="width:145px">
            @include('partials.date-range-buttons', ['fromName'=>'date_from','toName'=>'date_to','formClass'=>'#salesFilterForm'])
            <button type="submit" class="btn btn-secondary">ফিল্টার</button>
            @if($dateFrom || $dateTo || request('search') || request('status'))
                <a href="{{ route('sales.index') }}" class="btn btn-outline" title="ক্লিয়ার করুন"><i class="fas fa-xmark"></i></a>
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
                        <td>
                            {{ $sale->sale_date->format('d M Y') }}
                            <br><small style="color:#94a3b8;font-size:.75rem">{{ $sale->created_at->format('h:i a') }}</small>
                        </td>
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

    {{-- ══ ITEM VIEW ═══════════════════════════════════════════════ --}}
    <div id="itemView" style="display:none">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ইনভয়েস</th>
                        <th>কাস্টমার</th>
                        <th>আইটেমের নাম</th>
                        <th style="text-align:right">পরিমাণ</th>
                        <th style="text-align:right">একক মূল্য</th>
                        <th style="text-align:right">সাবটোটাল</th>
                        <th>তারিখ</th>
                    </tr>
                </thead>
                <tbody>
                    @php $itemViewEmpty = true; $totalQty = 0; $totalSubtotal = 0; @endphp
                    @foreach($sales as $sale)
                        @foreach($sale->items as $si)
                            @php
                                $itemViewEmpty  = false;
                                $totalQty      += $si->quantity;
                                $totalSubtotal += $si->subtotal;
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('sales.show', $sale) }}" class="link-primary mono">
                                        #INV-{{ str_pad($sale->id,4,'0',STR_PAD_LEFT) }}
                                    </a>
                                </td>
                                <td>{{ $sale->customer?->name ?? 'ওয়াক-ইন' }}</td>
                                <td style="font-weight:500">{{ $si->item->name ?? '—' }}</td>
                                <td style="text-align:right">{{ number_format($si->quantity, 0) }}</td>
                                <td style="text-align:right">৳ {{ number_format($si->price, 2) }}</td>
                                <td style="text-align:right;font-weight:600">৳ {{ number_format($si->subtotal, 0) }}</td>
                                <td>
                                    {{ $sale->sale_date->format('d M Y') }}
                                    <br><small style="color:#94a3b8;font-size:.75rem">{{ $sale->created_at->format('h:i a') }}</small>
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
                        <td style="text-align:right;font-weight:800;color:#16a34a">৳ {{ number_format($totalSubtotal, 0) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        <div class="pagination-wrap">{{ $sales->withQueryString()->links() }}</div>
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
.view-toggle-btn.active { background: var(--accent); color: #fff; }
</style>
@endpush

@push('scripts')
<script>
function setView(type) {
    document.getElementById('invoiceView').style.display = type === 'invoice' ? '' : 'none';
    document.getElementById('itemView').style.display    = type === 'item'    ? '' : 'none';
    document.getElementById('btnInvoiceView').classList.toggle('active', type === 'invoice');
    document.getElementById('btnItemView').classList.toggle('active',    type === 'item');
    localStorage.setItem('saleView', type);
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

const savedView = localStorage.getItem('saleView') || 'invoice';
setView(savedView);
</script>
@endpush
@endsection
