    {{-- ══ INVOICE VIEW ══════════════════════════════════════════ --}}
    <div id="invoiceView">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ইনভয়েস</th>
                        <th>কাস্টমার</th>
                        <th>পণ্যের বিবরণ</th>
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
                                <form class="admin-only" method="POST" action="{{ route('sales.destroy',$sale) }}"
                                      data-confirm-msg="বিক্রয় #INV-{{ str_pad($sale->id,4,'0',STR_PAD_LEFT) }} মুছে ফেলবেন? ৳{{ number_format($sale->total_amount,0) }} — স্টক ও বাকী পুনরুদ্ধার হবে।">
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
        <div class="pagination-wrap">{{ $sales->links() }}</div>
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
        <div class="pagination-wrap">{{ $sales->links() }}</div>
    </div>
