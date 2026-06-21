@extends('layouts.app')
@section('title', 'অনুমোদন কেন্দ্র')
@section('page-title', 'অনুমোদন কেন্দ্র')

@section('content')
@include('partials.pending-edit-diff-modal')

@include('partials.page-header', ['title' => 'অনুমোদন অপেক্ষায়'])

@php
    $totalPending = $pendingDeleteSales->count() + $pendingDeletePurchases->count() + $pendingEdits->count();
@endphp

@if($totalPending === 0)
<div class="card" style="padding:48px;text-align:center">
    <i class="fas fa-check-circle" style="font-size:2.5rem;color:#16a34a;margin-bottom:12px;display:block"></i>
    <p style="color:var(--text-secondary);font-size:.95rem">কোনো অনুমোদন অপেক্ষায় নেই।</p>
</div>
@else

{{-- ══ PENDING DELETE: SALES ══════════════════════════════════════════ --}}
@if($pendingDeleteSales->count() > 0)
<div class="card" style="margin-bottom:20px">
    <div class="card-header">
        <h3><i class="fas fa-trash" style="color:#ef4444"></i> বিক্রয় ডিলিট অনুরোধ
            <span class="badge badge-red" style="margin-left:8px">{{ $pendingDeleteSales->count() }}</span>
        </h3>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ইনভয়েস</th>
                    <th>কাস্টমার</th>
                    <th>তারিখ</th>
                    <th>মোট</th>
                    <th>অনুরোধকারী</th>
                    <th>অনুরোধের সময়</th>
                    <th>অ্যাকশন</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pendingDeleteSales as $sale)
                <tr style="background:#fff1f2">
                    <td><a href="{{ route('sales.show',$sale) }}" class="link-primary mono">#INV-{{ str_pad($sale->id,4,'0',STR_PAD_LEFT) }}</a></td>
                    <td>{{ $sale->customer?->name ?? 'ওয়াক-ইন' }}</td>
                    <td>{{ $sale->sale_date?->format('d M Y') }}</td>
                    <td>৳ {{ number_format($sale->total_amount,0) }}</td>
                    <td style="font-weight:600">{{ $sale->deleteRequestedBy?->name ?? '—' }}</td>
                    <td style="font-size:.8rem;color:var(--text-secondary)">{{ $sale->delete_requested_at?->format('d M Y, h:i a') }}</td>
                    <td>
                        <div class="action-btns">
                            <a href="{{ route('sales.show',$sale) }}" class="btn-icon-sm" title="দেখুন"><i class="fas fa-eye"></i></a>
                            <form method="POST" action="{{ route('sales.approve-delete',$sale) }}"
                                  data-confirm-msg="#INV-{{ str_pad($sale->id,4,'0',STR_PAD_LEFT) }} ডিলিট অনুমোদন করবেন? স্টক ও বাকী পুনরুদ্ধার হবে।">
                                @csrf
                                <button type="submit" class="btn-icon-sm btn-icon-danger" title="ডিলিট অনুমোদন দিন"><i class="fas fa-check"></i></button>
                            </form>
                            <form method="POST" action="{{ route('sales.reject-delete',$sale) }}">
                                @csrf
                                <button type="submit" class="btn-icon-sm" title="অনুরোধ বাতিল" style="color:#64748b"><i class="fas fa-xmark"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ══ PENDING DELETE: PURCHASES ═══════════════════════════════════════ --}}
@if($pendingDeletePurchases->count() > 0)
<div class="card" style="margin-bottom:20px">
    <div class="card-header">
        <h3><i class="fas fa-trash" style="color:#ef4444"></i> গ্রহণ ডিলিট অনুরোধ
            <span class="badge badge-red" style="margin-left:8px">{{ $pendingDeletePurchases->count() }}</span>
        </h3>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>রিসিভ নম্বর</th>
                    <th>সরবরাহকারী</th>
                    <th>তারিখ</th>
                    <th>মোট</th>
                    <th>অনুরোধকারী</th>
                    <th>অনুরোধের সময়</th>
                    <th>অ্যাকশন</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pendingDeletePurchases as $purchase)
                <tr style="background:#fff1f2">
                    <td><a href="{{ route('purchases.show',$purchase) }}" class="link-primary mono">#RCV-{{ str_pad($purchase->id,4,'0',STR_PAD_LEFT) }}</a></td>
                    <td>{{ $purchase->supplier?->name ?? '—' }}</td>
                    <td>{{ $purchase->purchase_date?->format('d M Y') }}</td>
                    <td>৳ {{ number_format($purchase->total_amount,0) }}</td>
                    <td style="font-weight:600">{{ $purchase->deleteRequestedBy?->name ?? '—' }}</td>
                    <td style="font-size:.8rem;color:var(--text-secondary)">{{ $purchase->delete_requested_at?->format('d M Y, h:i a') }}</td>
                    <td>
                        <div class="action-btns">
                            <a href="{{ route('purchases.show',$purchase) }}" class="btn-icon-sm" title="দেখুন"><i class="fas fa-eye"></i></a>
                            <form method="POST" action="{{ route('purchases.approve-delete',$purchase) }}"
                                  data-confirm-msg="#RCV-{{ str_pad($purchase->id,4,'0',STR_PAD_LEFT) }} ডিলিট অনুমোদন করবেন? স্টক কমে যাবে ও বকেয়া পুনরুদ্ধার হবে।">
                                @csrf
                                <button type="submit" class="btn-icon-sm btn-icon-danger" title="ডিলিট অনুমোদন দিন"><i class="fas fa-check"></i></button>
                            </form>
                            <form method="POST" action="{{ route('purchases.reject-delete',$purchase) }}">
                                @csrf
                                <button type="submit" class="btn-icon-sm" title="অনুরোধ বাতিল" style="color:#64748b"><i class="fas fa-xmark"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ══ PENDING EDITS ════════════════════════════════════════════════════ --}}
@if($pendingEdits->count() > 0)
<div class="card" style="margin-bottom:20px">
    <div class="card-header">
        <h3><i class="fas fa-pencil" style="color:#d97706"></i> সংশোধন অনুরোধ
            <span style="background:#fef3c7;color:#d97706;font-size:.75rem;font-weight:700;padding:2px 8px;border-radius:20px;margin-left:8px">{{ $pendingEdits->count() }}</span>
        </h3>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>রেকর্ড</th>
                    <th>ধরন</th>
                    <th>স্টাফ</th>
                    <th>অনুরোধের সময়</th>
                    <th>SMS</th>
                    <th>অ্যাকশন</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pendingEdits as $pe)
                @php
                    $isSale = $pe->model_type === 'sale';
                    $ref    = $isSale ? '#INV-'.str_pad($pe->model_id,4,'0',STR_PAD_LEFT) : '#RCV-'.str_pad($pe->model_id,4,'0',STR_PAD_LEFT);
                    $showUrl = $isSale ? route('sales.show', $pe->model_id) : route('purchases.show', $pe->model_id);
                    $approveRoute = $isSale ? route('pending-edits.approve-sale', $pe) : route('pending-edits.approve-purchase', $pe);
                    $rejectRoute  = $isSale ? route('pending-edits.reject-sale', $pe) : route('pending-edits.reject-purchase', $pe);
                    $peJson = json_encode(['original'=>$pe->original_data,'proposed'=>$pe->proposed_data,'staff'=>$pe->requestedBy?->name??'?','requested_at'=>$pe->created_at->format('d M Y, h:i a')], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT);
                @endphp
                <tr style="background:#fefce8">
                    <td><a href="{{ $showUrl }}" class="link-primary mono">{{ $ref }}</a></td>
                    <td>
                        @if($isSale)
                            <span class="badge badge-green">বিক্রয়</span>
                        @else
                            <span style="background:#ede9fe;color:#7c3aed;font-size:.75rem;font-weight:700;padding:2px 8px;border-radius:20px">গ্রহণ</span>
                        @endif
                    </td>
                    <td>{{ $pe->requestedBy?->name ?? '?' }}</td>
                    <td style="font-size:.8rem;color:var(--text-secondary)">{{ $pe->created_at->format('d M Y, h:i a') }}</td>
                    <td>
                        @if($pe->sms_sent)
                            <span style="color:#16a34a;font-size:.8rem"><i class="fas fa-check"></i> পাঠানো</span>
                        @else
                            <span style="color:#94a3b8;font-size:.8rem">—</span>
                        @endif
                    </td>
                    <td>
                        <div class="action-btns">
                            <button type="button" class="btn-icon-sm" style="color:#d97706" title="পরিবর্তন দেখুন"
                                    data-edit="{{ $peJson }}"
                                    onclick="openPendingEditModal(this, '{{ $approveRoute }}', '{{ $rejectRoute }}')">
                                <i class="fas fa-code-compare"></i>
                            </button>
                            <a href="{{ $showUrl }}" class="btn-icon-sm" title="রেকর্ড দেখুন"><i class="fas fa-eye"></i></a>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endif

{{-- ══ HISTORY ══════════════════════════════════════════════════════════ --}}
@if($editHistory->count() > 0)
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-clock-rotate-left" style="color:var(--text-secondary)"></i> সাম্প্রতিক ইতিহাস</h3>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>রেকর্ড</th>
                    <th>ধরন</th>
                    <th>স্টাফ</th>
                    <th>স্ট্যাটাস</th>
                    <th>পরিবর্তন ছিল?</th>
                    <th>সিদ্ধান্ত নিয়েছেন</th>
                    <th>সময়</th>
                </tr>
            </thead>
            <tbody>
                @forelse($editHistory as $pe)
                @php
                    $isSale = $pe->model_type === 'sale';
                    $ref    = $isSale ? '#INV-'.str_pad($pe->model_id,4,'0',STR_PAD_LEFT) : '#RCV-'.str_pad($pe->model_id,4,'0',STR_PAD_LEFT);
                    $showUrl = $isSale ? route('sales.show', $pe->model_id) : route('purchases.show', $pe->model_id);
                @endphp
                <tr>
                    <td><a href="{{ $showUrl }}" class="link-primary mono">{{ $ref }}</a></td>
                    <td style="font-size:.8rem">{{ $isSale ? 'বিক্রয়' : 'গ্রহণ' }}</td>
                    <td>{{ $pe->requestedBy?->name ?? '?' }}</td>
                    <td>
                        @if($pe->status === 'approved')
                            <span class="badge badge-green">অনুমোদিত</span>
                        @elseif($pe->status === 'rejected')
                            <span class="badge badge-red">বাতিল</span>
                        @elseif($pe->status === 'no_change')
                            <span style="background:#f1f5f9;color:#64748b;font-size:.75rem;font-weight:700;padding:2px 8px;border-radius:20px">পরিবর্তন নেই</span>
                        @elseif($pe->status === 'superseded')
                            <span style="background:#fef3c7;color:#d97706;font-size:.75rem;font-weight:700;padding:2px 8px;border-radius:20px">প্রতিস্থাপিত</span>
                        @endif
                    </td>
                    <td style="text-align:center">
                        @if($pe->status === 'no_change')
                            <i class="fas fa-minus" style="color:#94a3b8" title="পরিবর্তন ছিল না"></i>
                        @elseif($pe->has_changes)
                            <i class="fas fa-check" style="color:#ef4444" title="পরিবর্তন ছিল"></i>
                        @else
                            <i class="fas fa-minus" style="color:#94a3b8"></i>
                        @endif
                    </td>
                    <td style="font-size:.8rem;color:var(--text-secondary)">{{ $pe->decidedBy?->name ?? '—' }}</td>
                    <td style="font-size:.8rem;color:var(--text-secondary)">{{ $pe->created_at->format('d M Y, h:i a') }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="empty-row">কোনো ইতিহাস নেই</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection
