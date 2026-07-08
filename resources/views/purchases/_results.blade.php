    {{-- ══ INVOICE VIEW ══════════════════════════════════════════ --}}
    <div id="invoiceView">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>রিসিভ নম্বর</th>
                        <th>সরবরাহকারী</th>
                        <th>পণ্যের বিবরণ</th>
                        <th>তারিখ</th>
                        <th>মোট মূল্য</th>
                        <th>পরিশোধ</th>
                        <th>বকেয়া</th>
                        @if(auth()->user()->canManageShop())<th>ইউজার</th>@endif
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
                        $pendingDelete = (bool) $purchase->delete_requested_at;
                        $pendingEdit   = ($pendingEditsMap ?? collect())->get($purchase->id);
                    @endphp
                    <tr @if($pendingDelete) style="background:#fff7ed;opacity:.85" @elseif($pendingEdit) style="background:#fefce8" @endif>
                        <td>
                            <a href="{{ route('purchases.show', $purchase) }}" class="link-primary mono">
                                #RCV-{{ str_pad($purchase->id,4,'0',STR_PAD_LEFT) }}
                            </a>
                        </td>
                        <td>{{ $purchase->supplier?->name ?? '—' }}</td>
                        <td style="max-width:220px">
                            @if($purchase->items->isEmpty())
                                <span style="color:#94a3b8;font-size:.8rem">— পরিশোধ (পণ্য ছাড়া)</span>
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
                            <br><small style="color:#475569;font-size:.75rem">{{ $purchase->created_at->format('h:i a') }}</small>
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
                        @if(auth()->user()->canManageShop())
                        <td style="font-size:.8rem;color:var(--text-secondary);white-space:nowrap">{{ $purchase->user?->name ?? '—' }}</td>
                        @endif
                        <td>
                            <div class="action-btns">
                                <a href="{{ route('purchases.show',$purchase) }}" class="btn-icon-sm" title="বিবরণ"><i class="fas fa-eye"></i></a>
                                @if($pendingDelete)
                                    @if(auth()->user()->canManageShop())
                                        <form method="POST" action="{{ route('purchases.approve-delete',$purchase) }}"
                                              data-confirm-msg="রিসিভ #RCV-{{ str_pad($purchase->id,4,'0',STR_PAD_LEFT) }} ডিলিট অনুমোদন করবেন?">
                                            @csrf
                                            <button type="submit" class="btn-icon-sm btn-icon-danger" title="ডিলিট অনুমোদন"><i class="fas fa-check"></i></button>
                                        </form>
                                        <form method="POST" action="{{ route('purchases.reject-delete',$purchase) }}">
                                            @csrf
                                            <button type="submit" class="btn-icon-sm" title="বাতিল" style="color:#64748b"><i class="fas fa-xmark"></i></button>
                                        </form>
                                    @else
                                        <span style="font-size:.72rem;color:#ef4444;font-weight:600;white-space:nowrap" title="ডিলিট অনুমোদনের অপেক্ষায়">
                                            <i class="fas fa-clock"></i> ডিলিট অপেক্ষায়
                                        </span>
                                    @endif
                                @elseif($pendingEdit)
                                    @if(auth()->user()->canManageShop())
                                        @php $peJson = json_encode(['original'=>$pendingEdit->original_data,'proposed'=>$pendingEdit->proposed_data,'staff'=>$pendingEdit->requestedBy?->name??'?','requested_at'=>$pendingEdit->created_at->format('d M Y, h:i a')], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); @endphp
                                        <button type="button" class="btn-icon-sm" title="সংশোধন দেখুন ও সিদ্ধান্ত নিন"
                                                style="color:#d97706"
                                                data-edit="{{ $peJson }}"
                                                onclick="openPendingEditModal(this, '{{ route('pending-edits.approve-purchase',$pendingEdit) }}', '{{ route('pending-edits.reject-purchase',$pendingEdit) }}')">
                                            <i class="fas fa-pencil"></i>
                                        </button>
                                    @else
                                        <span style="font-size:.72rem;color:#d97706;font-weight:600;white-space:nowrap" title="সংশোধন অনুমোদনের অপেক্ষায়">
                                            <i class="fas fa-clock"></i> সংশোধন অপেক্ষায়
                                        </span>
                                        <a href="{{ route('purchases.edit',$purchase) }}" class="btn-icon-sm" title="আবার সম্পাদনা করুন"><i class="fas fa-pencil"></i></a>
                                    @endif
                                @else
                                    @if(auth()->user()->canManageShop())
                                        <a href="{{ route('purchases.edit',$purchase) }}" class="btn-icon-sm" title="সংশোধন করুন"><i class="fas fa-pencil"></i></a>
                                        <form method="POST" action="{{ route('purchases.destroy',$purchase) }}"
                                              data-confirm-msg="রিসিভ #RCV-{{ str_pad($purchase->id,4,'0',STR_PAD_LEFT) }} মুছে ফেলবেন? ৳{{ number_format($purchase->total_amount,0) }} — স্টক কমে যাবে ও বকেয়া পুনরুদ্ধার হবে।">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-icon-sm btn-icon-danger" title="মুছুন"><i class="fas fa-trash"></i></button>
                                        </form>
                                    @else
                                        <a href="{{ route('purchases.edit',$purchase) }}" class="btn-icon-sm" title="সংশোধন করুন"><i class="fas fa-pencil"></i></a>
                                        <form method="POST" action="{{ route('purchases.request-delete',$purchase) }}"
                                              data-confirm-msg="রিসিভ #RCV-{{ str_pad($purchase->id,4,'0',STR_PAD_LEFT) }} ডিলিটের অনুরোধ পাঠাবেন?">
                                            @csrf
                                            <button type="submit" class="btn-icon-sm btn-icon-danger" title="ডিলিট অনুরোধ"><i class="fas fa-trash"></i></button>
                                        </form>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="{{ auth()->user()->canManageShop() ? 9 : 8 }}" class="empty-row">কোনো রিসিভ পাওয়া যায়নি</td></tr>
                    @endforelse
                </tbody>
                @if($purchases->isNotEmpty())
                <tfoot>
                    <tr class="tfoot-summary">
                        <td colspan="{{ auth()->user()->canManageShop() ? 4 : 4 }}" style="text-align:right;font-weight:700;padding-right:16px">সর্বমোট</td>
                        <td style="font-weight:800">৳ {{ number_format($grandTotal, 0) }}</td>
                        <td style="font-weight:800;color:#16a34a">৳ {{ number_format($grandPaid, 0) }}</td>
                        <td style="font-weight:800;color:#dc2626">৳ {{ number_format($grossDue, 0) }}</td>
                        @if(auth()->user()->canManageShop())<td></td>@endif
                        <td></td>
                    </tr>
                    @if($totalCredit > 0)
                    <tr class="tfoot-summary">
                        <td colspan="4" style="text-align:right;font-weight:700;padding-right:16px">মোট অগ্রিম (−)</td>
                        <td></td><td></td>
                        <td style="font-weight:800;color:#1d4ed8">৳ {{ number_format($totalCredit, 0) }}</td>
                        @if(auth()->user()->canManageShop())<td></td>@endif
                        <td></td>
                    </tr>
                    <tr class="tfoot-summary">
                        <td colspan="4" style="text-align:right;font-weight:700;padding-right:16px">নিট বকেয়া</td>
                        <td></td><td></td>
                        <td style="font-weight:800;color:{{ $grandDue > 0 ? '#dc2626' : '#16a34a' }}">
                            ৳ {{ number_format(abs($grandDue), 0) }}
                            @if($grandDue <= 0) <span style="font-size:.75rem">(অগ্রিম)</span> @endif
                        </td>
                        @if(auth()->user()->canManageShop())<td></td>@endif
                        <td></td>
                    </tr>
                    @endif
                </tfoot>
                @endif
            </table>
        </div>
        <div class="pagination-wrap">{{ $purchases->links() }}</div>
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
                                    <br><small style="color:#475569;font-size:.75rem">{{ $purchase->created_at->format('h:i a') }}</small>
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
        <div class="pagination-wrap">{{ $purchases->links() }}</div>
    </div>

{{-- ══ USER VIEW ════════════════════════════════════════════════ --}}
@if(auth()->user()->canManageShop())
<div id="purchaseUserView" style="display:none">
    @if(($userSummary ?? collect())->isEmpty())
    <div style="text-align:center;padding:40px;color:var(--text-secondary)">কোনো রিসিভ নেই</div>
    @else
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ইউজার</th>
                    <th style="text-align:right">রিসিভ সংখ্যা</th>
                    <th style="text-align:right">মোট মূল্য</th>
                    <th style="text-align:right">পরিশোধ</th>
                    <th style="text-align:right">বকেয়া</th>
                </tr>
            </thead>
            <tbody>
                @foreach($userSummary ?? [] as $row)
                <tr>
                    <td style="font-weight:600">{{ $row['name'] }}</td>
                    <td style="text-align:right">{{ $row['count'] }}</td>
                    <td style="text-align:right;font-weight:700">৳ {{ number_format($row['total'],0) }}</td>
                    <td style="text-align:right;color:#16a34a">৳ {{ number_format($row['paid'],0) }}</td>
                    <td style="text-align:right;color:{{ $row['due'] > 0 ? '#dc2626' : 'var(--text-secondary)' }}">
                        {{ $row['due'] > 0 ? '৳ '.number_format($row['due'],0) : '—' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="tfoot-summary">
                    <td style="font-weight:700">মোট</td>
                    <td style="text-align:right;font-weight:700">{{ collect($userSummary ?? [])->sum('count') }}</td>
                    <td style="text-align:right;font-weight:800">৳ {{ number_format($grandTotal,0) }}</td>
                    <td style="text-align:right;font-weight:800;color:#16a34a">৳ {{ number_format($grandPaid,0) }}</td>
                    <td style="text-align:right;font-weight:800;color:#dc2626">৳ {{ number_format($grandDue,0) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif
</div>
@endif

@include('partials.pending-edit-diff-modal')
