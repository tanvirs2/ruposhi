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
        <a href="{{ route('customers.index', array_merge(request()->except(['status','page']), ['status' => $key])) }}"
           class="fchip {{ $status === $key ? 'fchip-active' : '' }}" style="--chip:{{ $color }}" data-ajax-filter>
            {{ $label }}<span class="fchip-count">{{ $cnt }}</span>
        </a>
    @endforeach
</div>

<div class="table-wrap">
    <table class="data-table print-uniform-rows">
        <colgroup>
            <col style="width:50px">    {{-- # --}}
            <col style="width:180px">   {{-- প্রতিষ্ঠান --}}
            <col style="width:150px">   {{-- প্রোপ্রাইটর --}}
            <col style="width:120px">   {{-- এরিয়া --}}
            <col style="width:130px">   {{-- ফোন --}}
            <col style="width:120px">   {{-- বকেয়া --}}
            <col class="col-hide-print" style="width:90px">    {{-- অ্যাকশন --}}
        </colgroup>
        <thead>
            <tr>
                <th>#</th>
                <th>প্রতিষ্ঠান</th>
                <th>প্রোপ্রাইটর</th>
                <th>এরিয়া</th>
                <th>ফোন</th>
                <th>বকেয়া</th>
                <th class="col-hide-print">অ্যাকশন</th>
            </tr>
        </thead>
        <tbody>
            @forelse($customers as $customer)
            <tr>
                <td class="mono">{{ $loop->iteration }}</td>
                <td class="print-ellipsis-td"><strong>{{ $customer->name }}</strong></td>
                <td class="print-ellipsis-td">{{ $customer->proprietor ?? '—' }}</td>
                <td>{{ $customer->area?->name ?? '—' }}</td>
                <td>{{ $customer->phone ?? '—' }}</td>
                <td>
                    @if($customer->due_amount > 0)
                        <span class="badge badge-red">৳ {{ number_format($customer->due_amount, 0) }}</span>
                        @if($customer->credit_limit > 0 && $customer->due_amount > $customer->credit_limit)
                            <i class="fas fa-triangle-exclamation" style="color:#dc2626;font-size:.72rem;margin-left:3px" title="ক্রেডিট লিমিট ৳{{ number_format($customer->credit_limit, 0) }} ছাড়িয়ে গেছে"></i>
                        @endif
                    @elseif($customer->due_amount < 0)
                        <span class="badge" style="background:#eff6ff;color:#1d4ed8">অগ্রিম ৳ {{ number_format(abs($customer->due_amount), 0) }}</span>
                    @else
                        <span class="badge badge-green">পরিষ্কার</span>
                    @endif
                </td>
                <td class="col-hide-print">
                    <div class="action-btns">
                        <a href="{{ route('customers.ledger', $customer) }}" class="btn-icon-sm" title="লেজার" style="color:#0d9488">
                            <i class="fas fa-book-open"></i>
                        </a>
                        @if($customer->due_amount > 0 && $customer->phone)
                        <form method="POST" action="{{ route('customers.sms-reminder', $customer) }}"
                              data-confirm-msg="{{ $customer->name }}-কে বাকী reminder SMS পাঠাবেন?">
                            @csrf
                            <button type="submit" class="btn-icon-sm" title="বাকী reminder SMS" style="color:#7c3aed">
                                <i class="fas fa-comment-sms"></i>
                            </button>
                        </form>
                        @endif
                        <a href="{{ route('customers.edit', $customer) }}" class="btn-icon-sm" title="সম্পাদনা">
                            <i class="fas fa-pen"></i>
                        </a>
                        <form class="admin-only" method="POST" action="{{ route('customers.destroy', $customer) }}"
                              data-confirm-msg="{{ $customer->name }} — কাস্টমার মুছে ফেলবেন? বাকী: ৳{{ number_format($customer->due_amount,0) }}। এই কাজ পূর্বাবস্থায় ফেরানো যাবে না।">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-icon-sm btn-icon-danger" title="মুছুন">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="empty-row">কোনো কাস্টমার পাওয়া যায়নি</td></tr>
            @endforelse
        </tbody>
        @if($customers->isNotEmpty())
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
<div class="pagination-wrap">{{ $customers->links() }}</div>
