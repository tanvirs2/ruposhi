{{-- Status filter chips --}}
@php
    $chips = [
        'active'  => ['বকেয়া/অগ্রিম', $counts['active'],  '#0d9488'],
        'due'     => ['বাকী আছে',      $counts['due'],     '#dc2626'],
        'advance' => ['অগ্রিম',         $counts['advance'], '#1d4ed8'],
        'clean'   => ['পরিষ্কার',       $counts['clean'],   '#16a34a'],
        'all'     => ['সব',            $counts['all'],     '#475569'],
    ];
@endphp
<div class="filter-chips">
    @foreach($chips as $key => [$label, $cnt, $color])
        <a href="{{ route('suppliers.index', array_merge(request()->except(['status','page']), ['status' => $key])) }}"
           class="fchip {{ $status === $key ? 'fchip-active' : '' }}" style="--chip:{{ $color }}" data-ajax-filter>
            {{ $label }}<span class="fchip-count">{{ $cnt }}</span>
        </a>
    @endforeach
</div>

<div class="table-wrap">
    <table class="data-table">
        <colgroup>
            <col style="width:50px">    {{-- # --}}
            <col style="width:170px">   {{-- নাম --}}
            <col style="width:150px">   {{-- প্রোপ্রাইটর --}}
            <col style="width:130px">   {{-- ফোন --}}
            <col style="width:180px">   {{-- ঠিকানা --}}
            <col style="width:120px">   {{-- বকেয়া --}}
            <col class="col-hide-print" style="width:90px">    {{-- অ্যাকশন --}}
        </colgroup>
        <thead>
            <tr><th>#</th><th>নাম</th><th>প্রোপ্রাইটর</th><th>ফোন</th><th>ঠিকানা</th><th>বকেয়া</th><th class="col-hide-print">অ্যাকশন</th></tr>
        </thead>
        <tbody>
            @forelse($suppliers as $supplier)
            <tr>
                <td class="mono">{{ $loop->iteration }}</td>
                <td><strong>{{ $supplier->name }}</strong></td>
                <td>{{ $supplier->proprietor ?? '—' }}</td>
                <td>{{ $supplier->phone ?? '—' }}</td>
                <td>{{ $supplier->address ? \Str::limit($supplier->address, 30) : '—' }}</td>
                <td>
                    @if($supplier->due_amount > 0)
                        <span class="badge badge-red">৳ {{ number_format($supplier->due_amount, 0) }}</span>
                    @elseif($supplier->due_amount < 0)
                        <span class="badge" style="background:#eff6ff;color:#1d4ed8">অগ্রিম -৳ {{ number_format(abs($supplier->due_amount), 0) }}</span>
                    @else
                        <span class="badge badge-green">পরিষ্কার</span>
                    @endif
                </td>
                <td class="col-hide-print">
                    <div class="action-btns">
                        <a href="{{ route('suppliers.ledger', $supplier) }}" class="btn-icon-sm" title="লেজার" style="color:#0d9488">
                            <i class="fas fa-book-open"></i>
                        </a>
                        <a href="{{ route('supplier-payments.create', ['supplier_id' => $supplier->id]) }}" class="btn-icon-sm" title="পরিশোধ যোগ" style="color:#16a34a">
                            <i class="fas fa-money-bill-wave"></i>
                        </a>
                        <a href="{{ route('suppliers.edit', $supplier) }}" class="btn-icon-sm" title="সম্পাদনা">
                            <i class="fas fa-pen"></i>
                        </a>
                        <form class="admin-only" method="POST" action="{{ route('suppliers.destroy', $supplier) }}"
                              data-confirm-msg="{{ $supplier->name }} — সরবরাহকারী মুছে ফেলবেন? বকেয়া: ৳{{ number_format($supplier->due_amount,0) }}। এই কাজ পূর্বাবস্থায় ফেরানো যাবে না।">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-icon-sm btn-icon-danger" title="মুছুন"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="empty-row">কোনো সরবরাহকারী পাওয়া যায়নি</td></tr>
            @endforelse
        </tbody>
        @if($suppliers->isNotEmpty())
        <tfoot>
            <tr class="tfoot-summary">
                <td colspan="5" style="text-align:right;font-weight:700;padding-right:16px">মোট বকেয়া</td>
                <td style="font-weight:800;color:#dc2626">৳ {{ number_format($grossDue, 0) }}</td>
                <td></td>
            </tr>
            @if($totalCredit > 0)
            <tr class="tfoot-summary">
                <td colspan="5" style="text-align:right;font-weight:700;padding-right:16px">মোট অগ্রিম (−)</td>
                <td style="font-weight:800;color:#1d4ed8">৳ {{ number_format($totalCredit, 0) }}</td>
                <td></td>
            </tr>
            <tr class="tfoot-summary">
                <td colspan="5" style="text-align:right;font-weight:700;padding-right:16px">নিট বকেয়া</td>
                <td style="font-weight:800;color:{{ $totalDue > 0 ? '#dc2626' : '#16a34a' }}">
                    ৳ {{ number_format(abs($totalDue), 0) }}
                </td>
                <td></td>
            </tr>
            @endif
        </tfoot>
        @endif
    </table>
</div>
<div class="pagination-wrap">{{ $suppliers->links() }}</div>
