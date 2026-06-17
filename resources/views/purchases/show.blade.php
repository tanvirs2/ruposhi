@extends('layouts.app')
@section('title', 'রিসিভ বিবরণ')
@section('page-title', 'রিসিভ বিবরণ')
@section('no-print-header', '1')

@section('content')
<div class="invoice-card" id="purchaseInvoice">
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
        @if($purchase->supplier?->proprietor)
        <div><strong>প্রোপ্রাইটর:</strong> {{ $purchase->supplier->proprietor }}</div>
        @endif
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
                    <td style="font-weight:700;padding:10px 12px">৳ {{ number_format($purchase->items->sum('subtotal'),0) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @else
    <div style="padding:20px 24px;background:#fefce8;border:1px solid #fde68a;border-radius:8px;margin:16px 0;display:flex;align-items:center;gap:12px">
        <i class="fas fa-piggy-bank" style="color:#ca8a04;font-size:1.4rem"></i>
        <div>
            <div style="font-weight:700;color:#92400e;font-size:.95rem">অগ্রিম পরিশোধ</div>
            <div style="font-size:.83rem;color:#78350f;margin-top:2px">কোনো পণ্য গ্রহণ হয়নি — শুধুমাত্র সরবরাহকারীকে অগ্রিম পরিশোধ করা হয়েছে।</div>
        </div>
    </div>
    @endif

    <div class="invoice-totals">
        @php
            $rawTotal        = $purchase->items->sum('subtotal');
            $extraCostVal    = $purchase->extra_cost ?? 0;
            $hasItems        = $purchase->items->count() > 0;
            $hasDeposits     = $purchase->deposits->isNotEmpty();
            $hasExtraCosts   = $purchase->extraCosts->isNotEmpty()
                               || ($purchase->extraCosts->isEmpty() && $extraCostVal > 0);
            $supplierNet     = $purchase->supplier ? $purchase->supplier->due_amount : null;
            $previousDue     = $purchase->supplier
                               ? ($purchase->supplier->due_amount - $purchase->due_amount)
                               : 0;
            $hasPreviousDue  = $purchase->supplier && $previousDue != 0;
            $totalObligation = $purchase->total_amount + $previousDue;
            $afterPayment    = $totalObligation - $purchase->paid_amount;
            $showBreakdown   = $hasExtraCosts || $hasPreviousDue;
        @endphp

        @if($hasItems)
            {{-- Item subtotal line — only shown when there's more to add below --}}
            @if($showBreakdown)
            <div class="inv-row"><span>আইটেম মূল্য:</span><span>৳ {{ number_format($rawTotal,0) }}</span></div>
            @endif

            {{-- Extra cost lines --}}
            @foreach($purchase->extraCosts as $ec)
            <div class="inv-row">
                <span>{{ $ec->category_name }}:</span>
                <span style="color:#7c3aed">+ ৳ {{ number_format($ec->amount,0) }}</span>
            </div>
            @endforeach
            @if($purchase->extraCosts->isEmpty() && $extraCostVal > 0)
            <div class="inv-row">
                <span>অতিরিক্ত খরচ:</span>
                <span style="color:#7c3aed">+ ৳ {{ number_format($extraCostVal,0) }}</span>
            </div>
            @endif

            @if($hasPreviousDue)
            {{-- Previous supplier balance folded into the chain --}}
            <div class="inv-row" style="font-weight:600;color:{{ $previousDue > 0 ? '#ef4444' : '#1d4ed8' }}">
                <span>পূর্বের বাকী:</span>
                <span>
                    @if($previousDue > 0)+ ৳ {{ number_format($previousDue,0) }}
                    @else− অগ্রিম ৳ {{ number_format(abs($previousDue),0) }}
                    @endif
                </span>
            </div>
            {{-- Separator → total obligation --}}
            <div style="border-top:1px dashed #cbd5e1;margin:8px 0 4px"></div>
            <div class="inv-row inv-total"><span>সর্বমোট প্রদেয়:</span><span>৳ {{ number_format($totalObligation,0) }}</span></div>
            @else
            {{-- No previous due: show মোট মূল্য (with optional divider after extras) --}}
            @if($showBreakdown)<div style="border-top:1px solid #e2e8f0;margin:4px 0"></div>@endif
            <div class="inv-row inv-total"><span>মোট মূল্য:</span><span>৳ {{ number_format($purchase->total_amount,0) }}</span></div>
            @endif

            {{-- Payment --}}
            <div class="inv-row" style="color:#16a34a">
                <span>পরিশোধ
                    @if($purchase->payment_method)
                    <span style="font-size:.78rem;font-weight:500;color:#64748b;margin-left:6px">({{ $purchase->payment_method }})</span>
                    @endif
                </span>
                <span>− ৳ {{ number_format($purchase->paid_amount,0) }}</span>
            </div>

            {{-- Deposits --}}
            @if($hasDeposits)
                @if($hasPreviousDue)
                {{-- Show intermediate balance before deposits --}}
                <div style="border-top:1px dashed #cbd5e1;margin:8px 0 4px"></div>
                <div class="inv-row" style="color:#475569">
                    <span>অবশিষ্ট দায়:</span>
                    <span>৳ {{ number_format($afterPayment,0) }}</span>
                </div>
                @endif
                @foreach($purchase->deposits as $dep)
                <div class="inv-row" style="color:#1d4ed8">
                    <span><i class="fas fa-piggy-bank" style="font-size:.75rem"></i> জমা ({{ $dep->category_name }}):</span>
                    <span>− ৳ {{ number_format($dep->amount,0) }}</span>
                </div>
                @endforeach
            @endif

            {{-- Final result --}}
            @if($hasPreviousDue)
            {{-- Full ledger: end with supplier net total --}}
            <div style="border-top:2px solid #94a3b8;margin:8px 0 4px"></div>
            @else
            {{-- No previous due: show per-purchase বকেয়া status --}}
            @if($purchase->due_amount > 0)
            <div class="inv-row" style="color:#ef4444;font-weight:600"><span>বকেয়া:</span><span>৳ {{ number_format($purchase->due_amount,0) }}</span></div>
            @elseif($purchase->due_amount == 0)
            <div class="inv-row" style="color:#16a34a"><span>বকেয়া:</span><span>সম্পূর্ণ পরিশোধিত ✓</span></div>
            @else
            <div class="inv-row" style="color:#16a34a"><span>বকেয়া:</span><span>সম্পূর্ণ পরিশোধিত ✓</span></div>
            <div class="inv-row" style="color:#1d4ed8;font-weight:600">
                <span>অতিরিক্ত পরিশোধ:</span>
                <span>৳ {{ number_format(abs($purchase->due_amount),0) }}</span>
            </div>
            @endif
            @endif

            {{-- Supplier net total (always shown when supplier exists) --}}
            @if($purchase->supplier)
            <div class="inv-row" style="font-weight:700;font-size:1rem;background:{{ $supplierNet > 0 ? '#fef2f2' : ($supplierNet < 0 ? '#eff6ff' : '#f0fdf4') }};padding:10px 12px;border-radius:6px;margin-top:{{ $hasPreviousDue ? '0' : '6px' }}">
                <span>সরবরাহকারীর মোট {{ $supplierNet > 0 ? 'বকেয়া' : ($supplierNet < 0 ? 'অগ্রিম' : 'হিসাব') }}:</span>
                <span style="color:{{ $supplierNet > 0 ? '#dc2626' : ($supplierNet < 0 ? '#1d4ed8' : '#16a34a') }}">
                    @if($supplierNet == 0) পরিষ্কার ✓
                    @else ৳ {{ number_format(abs($supplierNet),0) }}
                    @endif
                </span>
            </div>
            @endif

        @else
            {{-- ── ADVANCE PAYMENT (no items) ── --}}
            <div class="inv-row" style="color:#16a34a;font-weight:600">
                <span>অগ্রিম পরিশোধ
                    @if($purchase->payment_method)
                    <span style="font-size:.78rem;font-weight:500;color:#64748b;margin-left:6px">({{ $purchase->payment_method }})</span>
                    @endif
                </span>
                <span>৳ {{ number_format($purchase->paid_amount,0) }}</span>
            </div>
            @if($purchase->supplier)
            <div class="inv-row" style="font-weight:700;font-size:1rem;background:{{ $supplierNet > 0 ? '#fef2f2' : ($supplierNet < 0 ? '#eff6ff' : '#f0fdf4') }};padding:10px 12px;border-radius:6px;margin-top:6px">
                <span>সরবরাহকারীর মোট {{ $supplierNet > 0 ? 'বকেয়া' : ($supplierNet < 0 ? 'অগ্রিম' : 'হিসাব') }}:</span>
                <span style="color:{{ $supplierNet > 0 ? '#dc2626' : ($supplierNet < 0 ? '#1d4ed8' : '#16a34a') }}">
                    @if($supplierNet == 0) পরিষ্কার ✓
                    @else ৳ {{ number_format(abs($supplierNet),0) }}
                    @endif
                </span>
            </div>
            @endif
        @endif

        @if(\App\Support\BanglaWords::taka($purchase->paid_amount) !== '')
        <div style="margin-top:6px;font-size:.85rem;color:#333">
            <strong>কথায় (পরিশোধ):</strong> {{ \App\Support\BanglaWords::taka($purchase->paid_amount) }} মাত্র
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
        <form class="admin-only" method="POST" action="{{ route('purchases.destroy',$purchase) }}" style="margin-left:auto"
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
    @page {
        size: A4 portrait;
        margin: 14mm 14mm 14mm 14mm;
    }

    body * { visibility: hidden; }

    #purchaseInvoice,
    #purchaseInvoice * { visibility: visible; }

    #purchaseInvoice {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        max-width: 100% !important;
        box-shadow: none !important;
        border: none !important;
        font-size: .82rem !important;
        background: #fff !important;
    }

    tfoot { page-break-inside: avoid; }
}
</style>
@endpush
