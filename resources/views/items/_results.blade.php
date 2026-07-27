    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>নাম</th>
                    <th>ক্যাটাগরি</th>
                    <th class="tr">ক্রয় মূল্য</th>
                    <th class="tr">বিক্রয় মূল্য</th>
                    <th class="tc">স্টক</th>
                    <th class="tc col-hide-print">অ্যাকশন</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                <tr>
                    <td class="mono" style="color:#94a3b8">{{ $loop->iteration }}</td>
                    <td>
                        <strong>{{ $item->name }}</strong>
                        @if($item->code)
                            <div style="font-size:.75rem;color:#94a3b8" class="mono">{{ $item->code }}</div>
                        @endif
                    </td>
                    <td>{{ $item->category?->name ?? '—' }}</td>
                    <td class="tr">৳ {{ number_format($item->purchase_price, 2) }}</td>
                    <td class="tr">৳ {{ number_format($item->sale_price, 2) }}</td>
                    <td class="tc">
                        @php $qty = $item->stock?->quantity ?? 0; $low = $item->stock?->isLow(); @endphp
                        <span class="badge {{ $low ? 'badge-red' : 'badge-green' }}">
                            {{ $qty }} {{ $item->unit }}
                        </span>
                    </td>
                    <td class="tc col-hide-print">
                        <div class="action-btns">
                            <a href="{{ route('items.edit', $item) }}" class="btn-icon-sm"><i class="fas fa-pen"></i></a>
                            <form class="admin-only" method="POST" action="{{ route('items.destroy', $item) }}"
                                  data-confirm-msg="{{ $item->name }} — আইটেম মুছে ফেলবেন? স্টক ও সব বিক্রয় ইতিহাস মুছে যাবে।">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-icon-sm btn-icon-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="empty-row">কোনো আইটেম পাওয়া যায়নি</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">{{ $items->links() }}</div>
