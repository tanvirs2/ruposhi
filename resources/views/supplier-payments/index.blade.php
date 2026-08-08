@extends('layouts.app')
@section('title', 'সরবরাহকারী পরিশোধ তালিকা')
@section('page-title', 'সরবরাহকারী পরিশোধ তালিকা')

@push('styles')
<meta name="turbo-cache-control" content="no-cache">
@endpush

@section('content')
@include('partials.page-header', ['title' => 'পরিশোধ তালিকা', 'createRoute' => route('supplier-payments.create'), 'createLabel' => 'নতুন পরিশোধ'])

<div class="stats-grid" style="margin-bottom:20px;grid-template-columns:repeat(3,1fr)">
    <div class="stat-card" style="background:linear-gradient(135deg,#f5f3ff,#ede9fe)">
        <div class="stat-icon" style="background:#7c3aed"><i class="fas fa-coins"></i></div>
        <div class="stat-body">
            <span class="stat-label">সর্বমোট (ফিল্টার)</span>
            <span class="stat-value" style="color:#7c3aed">৳ {{ number_format($totalPaid + $totalDeposit, 0) }}</span>
        </div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
        <div class="stat-body">
            <span class="stat-label">মোট পরিশোধ (ফিল্টার)</span>
            <span class="stat-value">৳ {{ number_format($totalPaid, 0) }}</span>
        </div>
    </div>
    <div class="stat-card" style="background:linear-gradient(135deg,#eff6ff,#dbeafe)">
        <div class="stat-icon" style="background:#1d4ed8"><i class="fas fa-piggy-bank"></i></div>
        <div class="stat-body">
            <span class="stat-label">মোট জমা (ফিল্টার)</span>
            <span class="stat-value" style="color:#1d4ed8">৳ {{ number_format($totalDeposit, 0) }}</span>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-filter">
        <form method="GET" class="filter-form" data-date-snap>
            <div class="form-group-field" style="min-width:220px">
                @include('partials.area-combobox', [
                    'areas'         => $suppliers,
                    'acName'        => 'supplier_id',
                    'acValue'       => request('supplier_id'),
                    'acPlaceholder' => 'সব সরবরাহকারী (খুঁজুন)',
                    'acAllLabel'    => '— সব সরবরাহকারী —',
                ])
            </div>
            <div class="form-group-field">
                <input type="date" name="from" id="spDateFrom" value="{{ $from }}" class="form-select">
            </div>
            <div class="form-group-field">
                <input type="date" name="to" id="spDateTo" value="{{ $to }}" class="form-select">
            </div>
            @include('partials.date-range-buttons')
            <button type="submit" class="btn btn-secondary">ফিল্টার</button>
            @if(request()->hasAny(['supplier_id','from','to']))
                <a href="{{ route('supplier-payments.index') }}" class="btn btn-ghost">পরিষ্কার</a>
            @endif
            <button type="button" class="btn-export-print no-print" onclick="window.print()">
                <i class="fas fa-print"></i> প্রিন্ট
            </button>
        </form>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr><th>রিসিভ নং</th><th>সরবরাহকারী</th><th>ধরন</th><th>পরিমাণ</th><th>তারিখ</th><th>পদ্ধতি</th><th class="col-hide-print">মন্তব্য</th><th>রেকর্ডকারী</th><th class="col-hide-print">অ্যাকশন</th></tr>
            </thead>
            <tbody>
                @forelse($payments as $item)
                @php
                    $isDeposit   = $item['type'] === 'deposit';
                    $row         = $item['row'];
                    $purchaseId  = $isDeposit ? $row->purchase_id  : $row->id;
                    $supplier    = $isDeposit ? $row->purchase->supplier : $row->supplier;
                    $date        = $isDeposit ? $row->purchase->purchase_date : $row->purchase_date;
                    $method      = $isDeposit ? $row->purchase->payment_method : $row->payment_method;
                    $amount      = $isDeposit ? $row->amount : $row->paid_amount;
                    $userName    = $isDeposit ? ($row->purchase->user->name ?? '—') : ($row->user->name ?? '—');
                    $rawNotes    = $isDeposit ? $row->category_name : ($row->notes ?? '—');
                    $notes       = preg_match('/^__advance_for:(\d+)$/', $rawNotes, $m)
                                    ? 'রিসিভ #RCV-'.str_pad($m[1],4,'0',STR_PAD_LEFT).'-এর অতিরিক্ত পরিশোধ'
                                    : $rawNotes;
                @endphp
                <tr @if($isDeposit) style="background:#f0f7ff" @endif>
                    <td>
                        <a href="{{ route('purchases.show', $purchaseId) }}" class="link-primary mono">
                            #RCV-{{ str_pad($purchaseId, 4, '0', STR_PAD_LEFT) }}
                        </a>
                    </td>
                    <td>
                        @if($supplier)
                        <a href="{{ route('suppliers.ledger', $supplier) }}" class="link-primary">{{ $supplier->name }}</a>
                        @if($supplier->proprietor)
                        <div style="font-size:.78rem;color:#64748b">{{ $supplier->proprietor }}</div>
                        @endif
                        @else —
                        @endif
                    </td>
                    <td>
                        @if($isDeposit)
                            <span class="badge" style="background:#eff6ff;color:#1d4ed8"><i class="fas fa-piggy-bank" style="margin-right:3px;font-size:.7rem"></i>জমা</span>
                        @elseif($row->items_count > 0)
                            <span class="badge" style="background:#ccfbf1;color:#0f766e">পণ্য গ্রহণ</span>
                        @else
                            <span class="badge" style="background:#dcfce7;color:#15803d">পরিশোধ</span>
                        @endif
                    </td>
                    <td>
                        <strong style="color:{{ $isDeposit ? '#1d4ed8' : '#16a34a' }}">
                            ৳ {{ number_format($amount) }}
                        </strong>
                    </td>
                    <td>{{ $date->format('d/m/Y') }}</td>
                    <td><span class="badge badge-green">{{ $method }}</span></td>
                    <td class="col-hide-print">{{ $notes }}</td>
                    <td>{{ $userName }}</td>
                    <td class="col-hide-print">
                        <div class="action-btns">
                            <a href="{{ route('purchases.show', $purchaseId) }}" class="btn-icon-sm" title="ইনভয়েস"><i class="fas fa-eye"></i></a>
                            @if(!$isDeposit && $row->items_count === 0)
                            <form class="admin-only" method="POST" action="{{ route('purchases.destroy', $row) }}"
                                  data-confirm-msg="এই পরিশোধ মুছে ফেলবেন? বকেয়া পুনরুদ্ধার হবে।">
                                @csrf @method('DELETE')
                                <input type="hidden" name="redirect_to" value="supplier-payments">
                                <button type="submit" class="btn-icon-sm btn-icon-danger" title="মুছুন">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="empty-row">কোনো পরিশোধ পাওয়া যায়নি</td></tr>
                @endforelse
            </tbody>
            @if($payments->total() > 0)
            <tfoot>
                <tr class="tfoot-summary">
                    <td colspan="3" style="text-align:right;font-weight:700;padding-right:16px">সর্বমোট পরিশোধ</td>
                    <td style="font-weight:800;color:#16a34a">৳ {{ number_format($totalPaid) }}</td>
                    <td colspan="5"></td>
                </tr>
                @if($totalDeposit > 0)
                <tr class="tfoot-summary">
                    <td colspan="3" style="text-align:right;font-weight:700;padding-right:16px">সর্বমোট জমা</td>
                    <td style="font-weight:800;color:#1d4ed8">৳ {{ number_format($totalDeposit) }}</td>
                    <td colspan="5"></td>
                </tr>
                <tr class="tfoot-summary">
                    <td colspan="3" style="text-align:right;font-weight:700;padding-right:16px">সর্বমোট (পরিশোধ + জমা)</td>
                    <td style="font-weight:800;color:#7c3aed">৳ {{ number_format($totalPaid + $totalDeposit) }}</td>
                    <td colspan="5"></td>
                </tr>
                @endif
            </tfoot>
            @endif
        </table>
    </div>
    <div class="pagination-wrap">{{ $payments->withQueryString()->links() }}</div>
</div>

@push('scripts')
<script>
(function () {
    var fromEl = document.getElementById('spDateFrom');
    var toEl   = document.getElementById('spDateTo');
    if (!fromEl || !toEl) return;
    function onFromChange() {
        if (fromEl.value) toEl.value = fromEl.value;
        fromEl.form.requestSubmit ? fromEl.form.requestSubmit() : fromEl.form.submit();
    }
    function onToChange() {
        fromEl.form.requestSubmit ? fromEl.form.requestSubmit() : fromEl.form.submit();
    }
    fromEl.addEventListener('change', onFromChange);
    fromEl.addEventListener('input',  onFromChange);
    toEl.addEventListener('change', onToChange);
    toEl.addEventListener('input',  onToChange);
})();
</script>
@endpush
@endsection
