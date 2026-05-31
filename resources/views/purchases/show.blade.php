@extends('layouts.app')
@section('title', 'রিসিভ বিবরণ')
@section('page-title', 'রিসিভ বিবরণ')

@section('content')
<div class="invoice-card">
    <div class="invoice-header">
        <div>
            <h2><i class="fas fa-truck-ramp-box" style="color:#0d9488;margin-right:8px"></i>রিসিভ #RCV-{{ str_pad($purchase->id,4,'0',STR_PAD_LEFT) }}</h2>
            <p style="color:#64748b;margin-top:4px">তারিখ: {{ $purchase->purchase_date->format('d F Y') }}</p>
        </div>
        <div style="text-align:right">
            <span class="badge badge-green"><i class="fas fa-boxes-stacked"></i> স্টক আপডেট হয়েছে</span>
        </div>
    </div>

    <div class="invoice-meta">
        <div><strong>সরবরাহকারী:</strong> {{ $purchase->supplier?->name ?? '—' }}</div>
        <div><strong>ফোন:</strong> {{ $purchase->supplier?->phone ?? '—' }}</div>
        <div><strong>ঠিকানা:</strong> {{ $purchase->supplier?->address ?? '—' }}</div>
        <div><strong>রেকর্ডকারী:</strong> {{ $purchase->user->name }}</div>
    </div>

    @if($purchase->items->count() > 0)
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>আইটেম</th>
                    <th>রিসিভ পরিমাণ</th>
                    <th>ক্রয় মূল্য/বস্তা</th>
                    <th>মোট</th>
                </tr>
            </thead>
            <tbody>
                @foreach($purchase->items as $i => $pi)
                <tr>
                    <td>{{ $i+1 }}</td>
                    <td>{{ $pi->item->name }}</td>
                    <td>
                        <span style="font-weight:600;color:#0d9488">{{ $pi->quantity }} বস্তা</span>
                    </td>
                    <td>৳ {{ number_format($pi->price,0) }}</td>
                    <td>৳ {{ number_format($pi->subtotal,0) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:var(--bg)">
                    <td colspan="2" style="font-weight:600;padding:10px 12px">মোট</td>
                    <td style="font-weight:700;color:#0d9488;padding:10px 12px">
                        {{ $purchase->items->sum('quantity') }} বস্তা
                    </td>
                    <td></td>
                    <td style="font-weight:700;padding:10px 12px">৳ {{ number_format($purchase->total_amount,0) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @else
    <div style="padding:20px 24px;background:#fefce8;border:1px solid #fde68a;border-radius:8px;margin:16px 0;display:flex;align-items:center;gap:12px">
        <i class="fas fa-piggy-bank" style="color:#ca8a04;font-size:1.4rem"></i>
        <div>
            <div style="font-weight:700;color:#92400e;font-size:.95rem">অগ্রিম পরিশোধ</div>
            <div style="font-size:.83rem;color:#78350f;margin-top:2px">কোনো মাল রিসিভ হয়নি — শুধুমাত্র সরবরাহকারীকে অগ্রিম পরিশোধ করা হয়েছে।</div>
        </div>
    </div>
    @endif

    <div class="invoice-totals">
        @php
            $itemsTotal = $purchase->total_amount - ($purchase->extra_cost ?? 0) - ($purchase->labor_cost ?? 0);
        @endphp
        @if(($purchase->extra_cost ?? 0) > 0 || ($purchase->labor_cost ?? 0) > 0)
        <div class="inv-row"><span>আইটেম মূল্য:</span><span>৳ {{ number_format($itemsTotal,0) }}</span></div>
        @endif
        @if(($purchase->extra_cost ?? 0) > 0)
        <div class="inv-row"><span>অতিরিক্ত খরচ:</span><span>+ ৳ {{ number_format($purchase->extra_cost,0) }}</span></div>
        @endif
        @if(($purchase->labor_cost ?? 0) > 0)
        <div class="inv-row"><span>শ্রমিক খরচ:</span><span>+ ৳ {{ number_format($purchase->labor_cost,0) }}</span></div>
        @endif
        <div class="inv-row inv-total"><span>মোট মূল্য:</span><span>৳ {{ number_format($purchase->total_amount,0) }}</span></div>
        <div class="inv-row">
            <span>পরিশোধ:
                @if($purchase->payment_method)
                    <span style="font-size:.78rem;font-weight:500;color:#64748b;margin-left:6px">({{ $purchase->payment_method }})</span>
                @endif
            </span>
            <span style="color:#16a34a">৳ {{ number_format($purchase->paid_amount,0) }}</span>
        </div>
        @php $hasItems = $purchase->items->count() > 0; @endphp
        @if($purchase->due_amount > 0)
            <div class="inv-row" style="color:#ef4444;font-weight:600"><span>বকেয়া:</span><span>৳ {{ number_format($purchase->due_amount,0) }}</span></div>
        @elseif($purchase->due_amount < 0 && !$hasItems)
            {{-- No-item purchase: pure advance payment --}}
            <div class="inv-row" style="color:#1d4ed8;font-weight:600">
                <span>অগ্রিম পরিশোধ:</span>
                <span>৳ {{ number_format(abs($purchase->due_amount),0) }}</span>
            </div>
        @else
            {{-- Regular purchase, fully paid (may have overpaid) --}}
            <div class="inv-row" style="color:#16a34a"><span>বকেয়া:</span><span>সম্পূর্ণ পরিশোধিত ✓</span></div>
        @endif
        {{-- Always show supplier's net balance if they have credit --}}
        @if($purchase->supplier && $purchase->supplier->due_amount < 0)
        <div class="inv-row" style="color:#1d4ed8;font-size:.9rem;font-weight:700;background:#eff6ff;padding:8px 12px;border-radius:6px;margin-top:4px">
            <span><i class="fas fa-piggy-bank"></i> সরবরাহকারীর মোট অগ্রিম:</span>
            <span>৳ {{ number_format(abs($purchase->supplier->due_amount),0) }}</span>
        </div>
        @endif
    </div>

    @if($purchase->notes)
    <div style="margin-top:16px;padding:12px;background:var(--bg);border-radius:8px;font-size:.88rem;color:#64748b">
        <strong>মন্তব্য:</strong> {{ $purchase->notes }}
    </div>
    @endif

    <div class="form-actions no-print" style="margin-top:20px">
        <a href="{{ route('purchases.index') }}" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> ফিরে যান</a>
        <button onclick="window.print()" class="btn btn-secondary"><i class="fas fa-print"></i> প্রিন্ট</button>
        <a href="{{ route('purchases.edit', $purchase) }}" class="btn" style="background:#fef9c3;color:#92400e;border:1px solid #fde68a">
            <i class="fas fa-pen-to-square"></i> সংশোধন
        </a>
        <form method="POST" action="{{ route('purchases.destroy',$purchase) }}" style="margin-left:auto"
            onsubmit="return confirm('মুছলে স্টক কমে যাবে। নিশ্চিত?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn" style="background:#fee2e2;color:#dc2626;border:1px solid #fca5a5">
                <i class="fas fa-trash"></i> মুছুন
            </button>
        </form>
    </div>
</div>
@endsection

@push('styles')
<style>
@media print {
    .sidebar, .topbar, .no-print { display: none !important; }
    .main-wrapper { margin-left: 0 !important; }
    .invoice-card { box-shadow: none; border: none; }
}
</style>
@endpush
