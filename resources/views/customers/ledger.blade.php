@extends('layouts.app')
@section('title', 'কাস্টমার লেজার — '.$customer->name)
@section('page-title', 'কাস্টমার লেজার রিপোর্ট')
@section('no-print-header', '1')

@push('styles')
<meta name="turbo-cache-control" content="no-cache">
@endpush

@section('content')

{{-- ── Print header ──────────────────────────────────────────── --}}
<div class="ledger-print-header" style="display:none">
    <div style="font-size:1.1rem;font-weight:800;color:#000">{{ \App\Models\StoreConfig::get('store_name','আমার দোকান') }}</div>
    <div style="font-size:.85rem;color:#555;margin-top:2px">কাস্টমার লেজার রিপোর্ট</div>
    <div style="font-size:.8rem;color:#777">
        {{ \Carbon\Carbon::parse($from)->format('d M Y') }} — {{ \Carbon\Carbon::parse($to)->format('d M Y') }}
    </div>
</div>

{{-- ── Top action bar ────────────────────────────────────────── --}}
<div class="page-header-bar no-print">
    <div>
        <h2 style="font-size:1.1rem;font-weight:700">{{ $customer->name }}</h2>
        <p style="font-size:.82rem;color:#64748b;margin-top:2px">
            @if($customer->phone) {{ $customer->phone }} &nbsp;·&nbsp; @endif
            @if($customer->proprietor) প্রোঃ {{ $customer->proprietor }} &nbsp;·&nbsp; @endif
            @if($customer->area) {{ $customer->area->name }} @endif
        </p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
        <a href="{{ route('sales.create', ['customer_id' => $customer->id]) }}" class="btn btn-primary">
            <i class="fas fa-plus-circle"></i> নতুন বিক্রয়
        </a>
        <button type="button" class="btn btn-export-print" onclick="window.print()">
            <i class="fas fa-print"></i> প্রিন্ট
        </button>
        <a href="{{ route('customers.ledger', $customer->id) }}?from={{ $from }}&to={{ $to }}&export=csv"
           class="btn btn-export"><i class="fas fa-file-csv"></i> CSV</a>
        <a href="{{ route('customers.ledger-select') }}" class="btn btn-ghost">
            <i class="fas fa-arrow-left"></i> ফিরে যান
        </a>
    </div>
</div>

{{-- ── Filter ─────────────────────────────────────────────────── --}}
<div class="card no-print" style="margin-bottom:18px">
    <div class="card-filter">
        <form method="GET" class="filter-form" id="ledgerFilterForm" data-date-snap>
            <div class="form-group-field">
                <label>শুরুর তারিখ</label>
                <input type="date" name="from" value="{{ $from }}">
            </div>
            <div class="form-group-field">
                <label>শেষ তারিখ</label>
                <input type="date" name="to" value="{{ $to }}">
            </div>
            <div style="align-self:flex-end;display:flex;gap:6px;flex-wrap:wrap">
                <button type="button" onclick="ledgerRange('this_month')" class="btn btn-ghost" style="font-size:.8rem;padding:8px 12px">এই মাস</button>
                <button type="button" onclick="ledgerRange('last_month')" class="btn btn-ghost" style="font-size:.8rem;padding:8px 12px">গত মাস</button>
                <button type="button" onclick="ledgerRange('this_year')"  class="btn btn-ghost" style="font-size:.8rem;padding:8px 12px">এই বছর</button>
                <button type="button" onclick="ledgerRange('all')"        class="btn btn-ghost" style="font-size:.8rem;padding:8px 12px">সব</button>
            </div>
            <button type="submit" class="btn btn-primary" style="align-self:flex-end">
                <i class="fas fa-search"></i> দেখুন
            </button>
        </form>
    </div>
</div>

{{-- ── Customer info (print only) ────────────────────────────── --}}
<div class="ledger-customer-card" style="display:none">
    <table class="ledger-info-table">
        <tr>
            <td><strong>কাস্টমার নামঃ</strong> <strong>{{ $customer->name }}</strong></td>
            <td><strong>প্রতিষ্ঠানের নামঃ</strong> <strong>{{ $customer->proprietor ?? '—' }}</strong></td>
        </tr>
        <tr>
            <td><strong>ফোনঃ</strong> {{ $customer->phone ?? '—' }}</td>
            <td><strong>ঠিকানাঃ</strong> {{ $customer->address ?? '—' }}</td>
        </tr>
        <tr>
            <td colspan="2"><strong>লেজার রিপোর্ট -</strong> {{ \Carbon\Carbon::parse($from)->format('Y-m-d') }} থেকে {{ \Carbon\Carbon::parse($to)->format('Y-m-d') }} পর্যন্ত</td>
        </tr>
    </table>
</div>

{{-- ── KPI Cards ──────────────────────────────────────────────── --}}
<div class="ledger-kpi-row">
    <div class="ledger-kpi ledger-kpi-blue">
        <div class="ledger-kpi-label"><i class="fas fa-receipt"></i> মোট বিক্রয়</div>
        <div class="ledger-kpi-value">৳ {{ number_format($totalSales, 0) }}</div>
        <div class="ledger-kpi-sub">{{ $ledger->where('type','item')->pluck('sale_id')->unique()->count() }}টি চালান</div>
    </div>
    <div class="ledger-kpi ledger-kpi-green">
        <div class="ledger-kpi-label"><i class="fas fa-money-bill-wave"></i> মোট পরিশোধ</div>
        <div class="ledger-kpi-value">৳ {{ number_format($totalCredits, 0) }}</div>
        <div class="ledger-kpi-sub">{{ $deposits->count() }}টি জমা</div>
    </div>
    <div class="ledger-kpi {{ $periodBalance > 0 ? 'ledger-kpi-red' : 'ledger-kpi-green' }}">
        <div class="ledger-kpi-label"><i class="fas fa-calculator"></i> এই সময়ের বাকী</div>
        @if($periodBalance < 0)
            <div class="ledger-kpi-value" style="color:#1d4ed8">অগ্রিম ৳ {{ number_format(abs($periodBalance), 0) }}</div>
        @else
            <div class="ledger-kpi-value">৳ {{ number_format($periodBalance, 0) }}</div>
        @endif
        <div class="ledger-kpi-sub">নির্বাচিত পিরিয়ড অনুযায়ী</div>
    </div>
    <div class="ledger-kpi {{ $realTotalDue > 0 ? 'ledger-kpi-orange' : 'ledger-kpi-green' }}">
        <div class="ledger-kpi-label"><i class="fas fa-scale-balanced"></i> সর্বমোট বাকী</div>
        @if($realTotalDue < 0)
            <div class="ledger-kpi-value" style="color:#1d4ed8">অগ্রিম ৳ {{ number_format(abs($realTotalDue), 0) }}</div>
        @else
            <div class="ledger-kpi-value">৳ {{ number_format($realTotalDue, 0) }}</div>
        @endif
        <div class="ledger-kpi-sub">সকল লেনদেন হিসেবে</div>
    </div>
</div>

{{-- ── View toggle: পুরনো (এক টেবিল) ↔ নতুন (দুই টেবিল) ── --}}
<div class="cl-view-toggle no-print">
    <span style="font-size:.82rem;color:#64748b;font-weight:600">ভিউ:</span>
    <button type="button" id="clViewBtnOld" class="cl-view-btn" onclick="clSetView('old')">
        <i class="fas fa-table-list"></i> এক টেবিল
    </button>
    <button type="button" id="clViewBtnNew" class="cl-view-btn" onclick="clSetView('new')">
        <i class="fas fa-table-columns"></i> দুই টেবিল
    </button>
</div>

{{-- ══════════ OLD VIEW — single combined table (default) ══════════ --}}
<div id="ledgerViewOld">
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-book-open" style="color:#1d4ed8"></i> লেনদেনের বিবরণ</h3>
    </div>
    <div class="table-wrap">
        <table class="data-table cl-table">
            <colgroup>
                <col style="width:90px">    {{-- চালান নং --}}
                <col style="width:120px">   {{-- তারিখ --}}
                <col style="width:220px">   {{-- বিবরণ --}}
                <col style="width:70px">    {{-- পরিমাণ --}}
                <col style="width:70px">    {{-- দর --}}
                <col style="width:90px">    {{-- বাকি --}}
                <col style="width:90px">    {{-- জমা --}}
                <col style="width:110px">   {{-- অবশিষ্ট --}}
            </colgroup>
            <thead>
                <tr>
                    <th class="tc">চালান নং</th>
                    <th>তারিখ</th>
                    <th>বিবরণ</th>
                    <th style="text-align:right">পরিমাণ</th>
                    <th style="text-align:right">দর</th>
                    <th style="text-align:right">বাকি (৳)</th>
                    <th style="text-align:right">জমা (৳)</th>
                    <th style="text-align:right">অবশিষ্ট (৳)</th>
                </tr>
            </thead>
            <tbody>
                @php $running2 = $openingBalance; $prevSaleId2 = null; @endphp
                @if($openingBalance != 0)
                <tr class="cl-opening-row">
                    <td colspan="7" style="font-weight:700;color:#475569">পূর্বের অবশিষ্ট</td>
                    <td style="text-align:right;font-weight:800;color:{{ $openingBalance > 0 ? '#b45309' : '#15803d' }}">
                        {{ number_format(abs($openingBalance), 0) }}
                    </td>
                </tr>
                @endif
                @forelse($combined as $row)
                @php
                    $running2   += $row['debit'] - $row['credit'];
                    $isNewSale2  = $row['sale_id'] && $row['sale_id'] !== $prevSaleId2 && $row['type'] === 'item';
                    if ($row['sale_id']) $prevSaleId2 = $row['sale_id'];
                    $rowClass2 = match($row['type']) {
                        'payment'    => 'cl-payment-row',
                        'discount'   => 'cl-discount-row',
                        'extra_cost' => 'cl-extracost-row',
                        default      => '',
                    };
                @endphp
                <tr class="{{ $rowClass2 }} {{ $isNewSale2 && !$loop->first ? 'cl-new-sale' : '' }}">
                    <td class="tc mono" style="font-size:.82rem">
                        @if($row['sale_id'])
                            <a href="{{ route('sales.show', $row['sale_id']) }}" class="link-primary">{{ str_pad($row['sale_id'], 6, '0', STR_PAD_LEFT) }}</a>
                        @else
                            <span class="cl-dash">—</span>
                        @endif
                    </td>
                    <td class="cl-date" style="white-space:nowrap">
                        @php $dt2 = \Carbon\Carbon::parse($row['datetime']); @endphp
                        {{ $dt2->format('Y-m-d') }} <span class="cl-time">{{ $dt2->format('h:i:s a') }}</span>
                    </td>
                    <td>
                        @if($row['type'] === 'payment')
                            <span class="cl-payment-label"><i class="fas fa-circle-check" style="font-size:.72rem;margin-right:3px"></i> {{ $row['label'] }}</span>
                        @elseif($row['type'] === 'discount')
                            <span class="cl-discount-label"><i class="fas fa-tag" style="font-size:.72rem;margin-right:3px"></i> {{ $row['label'] }}</span>
                        @elseif($row['type'] === 'extra_cost')
                            <span class="cl-extracost-label"><i class="fas fa-plus-circle" style="font-size:.72rem;margin-right:3px"></i> {{ $row['label'] }}</span>
                        @else
                            <span class="cl-item-name">{{ $row['label'] }}</span>
                        @endif
                    </td>
                    <td style="text-align:right;font-variant-numeric:tabular-nums">
                        @if($row['qty'] > 0)<strong>{{ number_format($row['qty'], 0) }}</strong>@else<span class="cl-dash">-</span>@endif
                    </td>
                    <td style="text-align:right;font-variant-numeric:tabular-nums;color:#475569">
                        @if($row['rate'] > 0){{ number_format($row['rate'], 0) }}@else<span class="cl-dash">-</span>@endif
                    </td>
                    <td style="text-align:right;font-variant-numeric:tabular-nums">
                        @if($row['debit'] > 0)<span class="cl-debit">{{ number_format($row['debit'], 0) }}</span>@else<span class="cl-dash">-</span>@endif
                    </td>
                    <td style="text-align:right;font-variant-numeric:tabular-nums">
                        @if($row['credit'] > 0)<span class="cl-credit">{{ number_format($row['credit'], 0) }}</span>@else<span class="cl-dash">-</span>@endif
                    </td>
                    <td style="text-align:right;font-variant-numeric:tabular-nums;font-weight:700;color:{{ $running2 > 0 ? '#b45309' : ($running2 < 0 ? '#15803d' : '#64748b') }}">
                        {{ number_format(abs($running2), 0) }}
                        @if($running2 < 0)<div style="font-size:.68rem;font-weight:600;color:#15803d;margin-top:1px">(অতিরিক্ত)</div>@endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="empty-row">এই সময়কালে কোনো লেনদেন নেই</td></tr>
                @endforelse
            </tbody>
            @if($combined->count())
            <tfoot>
                <tr class="cl-tfoot">
                    <td colspan="3" style="font-weight:700">সর্বমোট</td>
                    <td style="text-align:right">
                        <span class="cl-mask-value" onclick="this.classList.toggle('revealed')"><span class="cl-mask-dots">—</span><span class="cl-mask-real">{{ number_format($combined->sum('qty'), 0) }}</span></span>
                    </td>
                    <td></td>
                    <td style="text-align:right;font-weight:700;color:#dc2626">
                        <span class="cl-mask-value" onclick="this.classList.toggle('revealed')"><span class="cl-mask-dots">—</span><span class="cl-mask-real">{{ number_format($totalSales + $totalDiscount, 0) }}</span></span>
                    </td>
                    <td style="text-align:right;font-weight:700;color:#15803d">
                        <span class="cl-mask-value" onclick="this.classList.toggle('revealed')"><span class="cl-mask-dots">—</span><span class="cl-mask-real">{{ number_format($totalCredits + $totalDiscount, 0) }}</span></span>
                    </td>
                    <td style="text-align:right;font-weight:800;color:{{ $running2 > 0 ? '#b45309' : ($running2 < 0 ? '#15803d' : '#64748b') }}">
                        {{ number_format(abs($running2), 0) }}
                        @if($running2 < 0) <div style="font-size:.7rem;color:#15803d">(অতিরিক্ত)</div> @endif
                    </td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
</div>{{-- /#ledgerViewOld --}}

{{-- ══════════ NEW VIEW — deposits table + bills table ══════════ --}}
<div id="ledgerViewNew" style="display:none">

@php
    // Final running bill = opening + all bill movements (payments are NOT
    // deducted here — they live in the জমা table below); net due subtracts them.
    $mainFinal = $openingBalance + $ledger->sum('debit') - $ledger->sum('credit');
    $netDue    = $mainFinal - $totalDeposits;
@endphp

{{-- ── জমা তালিকা (payments — kept in its OWN table) ─────────────── --}}
<div class="card cl-deposit-card">
    <div class="card-header">
        <h3><i class="fas fa-money-bill-wave" style="color:#16a34a"></i> জমা তালিকা</h3>
        <span style="font-size:.78rem;color:#64748b;margin-left:auto">{{ $deposits->count() }}টি জমা</span>
    </div>
    <div class="table-wrap">
        <table class="data-table cl-deposit-table">
            <colgroup>
                <col style="width:190px">  {{-- তারিখ --}}
                <col style="width:120px">  {{-- পদ্ধতি --}}
                <col>                      {{-- সূত্র / মন্তব্য --}}
                <col style="width:130px">  {{-- পরিমাণ --}}
            </colgroup>
            <thead>
                <tr>
                    <th>তারিখ</th>
                    <th>পদ্ধতি</th>
                    <th>সূত্র / মন্তব্য</th>
                    <th style="text-align:right">পরিমাণ (৳)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($deposits as $d)
                @php $ddt = \Carbon\Carbon::parse($d['datetime']); @endphp
                <tr class="cl-deposit-row">
                    <td class="cl-date" style="white-space:nowrap">
                        {{ $ddt->format('Y-m-d') }} <span class="cl-time">{{ $ddt->format('h:i:s a') }}</span>
                    </td>
                    <td>
                        <span class="cl-payment-label">
                            <i class="fas fa-circle-check" style="font-size:.72rem;margin-right:3px"></i>
                            {{ $d['method'] ?: 'নগদ' }}
                        </span>
                    </td>
                    <td>
                        @if($d['sale_id'])
                            <a href="{{ route('sales.show', $d['sale_id']) }}" class="link-primary mono" style="font-size:.82rem">
                                চালান {{ str_pad($d['sale_id'], 6, '0', STR_PAD_LEFT) }}
                            </a>
                        @else
                            <span style="color:#475569">{{ $d['notes'] ?: 'সরাসরি পরিশোধ' }}</span>
                        @endif
                    </td>
                    <td style="text-align:right;font-variant-numeric:tabular-nums">
                        <span class="cl-credit">{{ number_format($d['amount'], 0) }}</span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="empty-row">এই সময়কালে কোনো জমা নেই</td></tr>
                @endforelse
            </tbody>
            @if($deposits->count())
            <tfoot>
                <tr class="cl-tfoot">
                    <td colspan="3" style="font-weight:700">মোট জমা</td>
                    <td style="text-align:right;font-weight:700;color:#15803d">
                        <span class="cl-mask-value" onclick="this.classList.toggle('revealed')">
                            <span class="cl-mask-dots">—</span>
                            <span class="cl-mask-real">{{ number_format($totalDeposits, 0) }}</span>
                        </span>
                    </td>
                </tr>
                <tr class="cl-tfoot cl-netdue-row">
                    <td colspan="3" style="font-weight:800">নিট বাকী <span style="font-weight:500;color:#64748b;font-size:.8rem">(অবশিষ্ট − মোট জমা)</span></td>
                    <td style="text-align:right;font-weight:800;color:{{ $netDue > 0 ? '#b45309' : ($netDue < 0 ? '#15803d' : '#64748b') }}">
                        {{ number_format(abs($netDue), 0) }}
                        @if($netDue < 0) <div style="font-size:.7rem;color:#15803d">(অতিরিক্ত)</div> @endif
                    </td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

{{-- ── Ledger Table (বিক্রয় খতিয়ান — bills only) ──────────────────── --}}
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-book-open" style="color:#1d4ed8"></i> বিক্রয় খতিয়ান</h3>
    </div>
    <div class="table-wrap">
        <table class="data-table cl-table">
            <colgroup>
                <col style="width:90px">    {{-- চালান নং --}}
                <col style="width:120px">   {{-- তারিখ --}}
                <col style="width:240px">   {{-- বিবরণ --}}
                <col style="width:70px">    {{-- পরিমাণ --}}
                <col style="width:70px">    {{-- দর --}}
                <col style="width:100px">   {{-- বাকি --}}
                <col style="width:120px">   {{-- অবশিষ্ট --}}
            </colgroup>
            <thead>
                <tr>
                    <th class="tc">চালান নং</th>
                    <th>তারিখ</th>
                    <th>বিবরণ</th>
                    <th style="text-align:right">পরিমাণ</th>
                    <th style="text-align:right">দর</th>
                    <th style="text-align:right">বাকি (৳)</th>
                    <th style="text-align:right">অবশিষ্ট (৳)</th>
                </tr>
            </thead>
            <tbody>
                @php $running = $openingBalance; $prevSaleId = null; @endphp

                {{-- Opening balance row --}}
                @if($openingBalance != 0)
                <tr class="cl-opening-row">
                    <td colspan="6" style="font-weight:700;color:#475569">পূর্বের অবশিষ্ট</td>
                    <td style="text-align:right;font-weight:800;color:{{ $openingBalance > 0 ? '#b45309' : '#15803d' }}">
                        {{ number_format(abs($openingBalance), 0) }}
                    </td>
                </tr>
                @endif

                @forelse($ledger as $row)
                @php
                    $running    += $row['debit'] - $row['credit'];
                    $isNewSale   = $row['sale_id'] && $row['sale_id'] !== $prevSaleId && $row['type'] === 'item';
                    if ($row['sale_id']) $prevSaleId = $row['sale_id'];
                @endphp
                @php
                    $rowClass = match($row['type']) {
                        'discount'   => 'cl-discount-row',
                        'extra_cost' => 'cl-extracost-row',
                        default      => '',
                    };
                @endphp
                <tr class="{{ $rowClass }} {{ $isNewSale && !$loop->first ? 'cl-new-sale' : '' }}">
                    <td class="tc mono" style="font-size:.82rem">
                        @if($row['sale_id'])
                            <a href="{{ route('sales.show', $row['sale_id']) }}" class="link-primary">
                                {{ str_pad($row['sale_id'], 6, '0', STR_PAD_LEFT) }}
                            </a>
                        @else
                            <span class="cl-dash">—</span>
                        @endif
                    </td>
                    <td class="cl-date" style="white-space:nowrap">
                        @php
                            $dt = \Carbon\Carbon::parse($row['datetime']);
                        @endphp
                        {{ $dt->format('Y-m-d') }} <span class="cl-time">{{ $dt->format('h:i:s a') }}</span>
                    </td>
                    <td>
                        @if($row['type'] === 'discount')
                            <span class="cl-discount-label">
                                <i class="fas fa-tag" style="font-size:.72rem;margin-right:3px"></i>
                                {{ $row['label'] }}
                            </span>
                        @elseif($row['type'] === 'extra_cost')
                            <span class="cl-extracost-label">
                                <i class="fas fa-plus-circle" style="font-size:.72rem;margin-right:3px"></i>
                                {{ $row['label'] }}
                            </span>
                        @else
                            <span class="cl-item-name">{{ $row['label'] }}</span>
                        @endif
                    </td>
                    <td style="text-align:right;font-variant-numeric:tabular-nums">
                        @if($row['qty'] > 0)
                            <strong>{{ number_format($row['qty'], 0) }}</strong>
                        @else
                            <span class="cl-dash">-</span>
                        @endif
                    </td>
                    <td style="text-align:right;font-variant-numeric:tabular-nums;color:#475569">
                        @if($row['rate'] > 0)
                            {{ number_format($row['rate'], 0) }}
                        @else
                            <span class="cl-dash">-</span>
                        @endif
                    </td>
                    <td style="text-align:right;font-variant-numeric:tabular-nums">
                        @if($row['debit'] > 0)
                            <span class="cl-debit">{{ number_format($row['debit'], 0) }}</span>
                        @elseif($row['credit'] > 0)
                            <span class="cl-credit">− {{ number_format($row['credit'], 0) }}</span>
                        @else
                            <span class="cl-dash">-</span>
                        @endif
                    </td>
                    <td style="text-align:right;font-variant-numeric:tabular-nums;font-weight:700;
                        color:{{ $running > 0 ? '#b45309' : ($running < 0 ? '#15803d' : '#64748b') }}">
                        {{ number_format(abs($running), 0) }}
                        @if($running < 0)
                            <div style="font-size:.68rem;font-weight:600;color:#15803d;margin-top:1px">(অতিরিক্ত)</div>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="empty-row">এই সময়কালে কোনো লেনদেন নেই</td></tr>
                @endforelse
            </tbody>
            @if($ledger->count())
            <tfoot>
                <tr class="cl-tfoot">
                    <td colspan="3" style="font-weight:700">সর্বমোট</td>
                    <td style="text-align:right">
                        <span class="cl-mask-value" onclick="this.classList.toggle('revealed')">
                            <span class="cl-mask-dots">—</span>
                            <span class="cl-mask-real">{{ number_format($ledger->sum('qty'), 0) }}</span>
                        </span>
                    </td>
                    <td></td>
                    <td style="text-align:right;font-weight:700;color:#dc2626">
                        <span class="cl-mask-value" onclick="this.classList.toggle('revealed')">
                            <span class="cl-mask-dots">—</span>
                            <span class="cl-mask-real">{{ number_format($ledger->sum('debit') - $ledger->sum('credit'), 0) }}</span>
                        </span>
                    </td>
                    <td style="text-align:right;font-weight:800;color:{{ $running > 0 ? '#b45309' : ($running < 0 ? '#15803d' : '#64748b') }}">
                        {{ number_format(abs($running), 0) }}
                        @if($running < 0) <div style="font-size:.7rem;color:#15803d">(অতিরিক্ত)</div> @endif
                    </td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

</div>{{-- /#ledgerViewNew --}}

<script>
// Toggle between the classic single-table view and the split two-table view.
// Default = old (single table); choice remembered in localStorage.
function clSetView(mode) {
    var isNew = mode === 'new';
    var vOld = document.getElementById('ledgerViewOld');
    var vNew = document.getElementById('ledgerViewNew');
    if (vOld) vOld.style.display = isNew ? 'none' : '';
    if (vNew) vNew.style.display = isNew ? '' : 'none';
    var bOld = document.getElementById('clViewBtnOld');
    var bNew = document.getElementById('clViewBtnNew');
    if (bOld) bOld.classList.toggle('active', !isNew);
    if (bNew) bNew.classList.toggle('active', isNew);
    try { localStorage.setItem('cl_ledger_view', mode); } catch (e) {}
    // The expand button is only added to tables measurable at setup time —
    // a view hidden during that first pass got offsetHeight=0 and no button.
    // Re-run it now that this view is visible.
    if (window.setupCompactTables) window.setupCompactTables(isNew ? vNew : vOld);
}
(function () {
    var saved = 'old';
    try { saved = localStorage.getItem('cl_ledger_view') || 'old'; } catch (e) {}
    clSetView(saved);
})();
</script>

@endsection

@push('styles')
<style>
/* ── KPI ──────────────────────────────────────────────────── */
.ledger-kpi-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 14px;
    margin-bottom: 20px;
}
.ledger-kpi { border-radius:12px; padding:16px 18px; border:1px solid transparent; }
.ledger-kpi-label { font-size:.78rem; font-weight:600; opacity:.75; margin-bottom:6px; display:flex; align-items:center; gap:6px; }
.ledger-kpi-value { font-size:1.3rem; font-weight:800; font-variant-numeric:tabular-nums; }
.ledger-kpi-sub   { font-size:.72rem; opacity:.6; margin-top:4px; }
.ledger-kpi-blue   { background:#eff6ff; border-color:#bfdbfe; color:#1e40af; }
.ledger-kpi-green  { background:#f0fdf4; border-color:#bbf7d0; color:#15803d; }
.ledger-kpi-orange { background:#fff7ed; border-color:#fed7aa; color:#c2410c; }
.ledger-kpi-red    { background:#fef2f2; border-color:#fecaca; color:#b91c1c; }

/* ── Table ───────────────────────────────────────────────── */
.cl-table td, .cl-table th { white-space: nowrap; }

.cl-opening-row td {
    background: #f8fafc;
    border-top: 2px solid #e2e8f0;
    border-bottom: 2px solid #e2e8f0;
    padding: 8px 12px;
}

.cl-new-sale td { border-top: 1px dashed #e2e8f0; }

.cl-payment-row td { background: #f0fdf4; }

.cl-date  { color: #334155; font-size: .78rem; white-space: nowrap; }
.cl-time  { font-size: .72rem; color: #94a3b8; margin-top:1px; }

.cl-item-name    { font-weight: 600; font-size: .85rem; word-break: break-word; }
.cl-payment-label { color: #15803d; font-weight: 600; font-size: .88rem; }

.cl-debit  { color: #dc2626; font-weight: 600; }
.cl-credit { color: #15803d; font-weight: 600; }
.cl-dash   { color: #cbd5e1; }

.cl-discount-row td { background: #fefce8; }
.cl-discount-label  { color: #92400e; font-weight: 600; font-size: .88rem; }

.cl-extracost-row td { background: #fdf4ff; }
.cl-extracost-label  { color: #7e22ce; font-weight: 600; font-size: .88rem; }
.cl-laborcost-row td { background: #fff1f2; }
.cl-laborcost-label  { color: #be123c; font-weight: 600; font-size: .88rem; }

.cl-tfoot td {
    padding: 10px 12px;
    font-weight: 700;
    background: var(--bg);
    border-top: 2px solid var(--border);
}

/* ── ভিউ টগল (এক টেবিল / দুই টেবিল) ── */
.cl-view-toggle { display: flex; align-items: center; gap: 8px; margin-bottom: 16px; }
.cl-view-btn {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: .82rem; font-weight: 600; padding: 6px 14px;
    border: 1px solid var(--border); border-radius: 8px;
    background: var(--surface); color: var(--text-secondary);
    cursor: pointer; transition: background .15s, color .15s, border-color .15s;
}
.cl-view-btn:hover { background: var(--surface-2); }
.cl-view-btn.active { background: var(--accent, #0d9488); color: #fff; border-color: var(--accent, #0d9488); }

/* ── জমা তালিকা (আলাদা টেবিল) ── */
.cl-deposit-card { margin-bottom: 18px; }
.cl-deposit-table td, .cl-deposit-table th { white-space: nowrap; }
.cl-deposit-row td { background: #f0fdf4; }
.cl-netdue-row td { border-top: 2px solid var(--border) !important; font-size: .95rem; }

/* ── তফুতের বিক্রয়/জমা টোটাল — ডিফল্টে মাস্কড, ক্লিকে দেখা যায় ── */
.cl-mask-value { cursor: pointer; user-select: none; display: inline-block; }
.cl-mask-value .cl-mask-real { display: none; }
.cl-mask-value .cl-mask-dots { color: #cbd5e1; }
.cl-mask-value.revealed .cl-mask-real { display: inline; }
.cl-mask-value.revealed .cl-mask-dots { display: none; }

/* ── Print — ink-saving: white backgrounds, dark text, tight header ── */
@media print {
    .sidebar, .topbar, .no-print { display: none !important; }
    .main-wrapper { margin-left: 0 !important; }
    .content { padding: 0 !important; }
    .ledger-print-header  { display: block !important; text-align: center; margin-bottom: 2px; }
    .ledger-print-header > div:first-child { font-size: 1.3rem !important; }
    .ledger-print-header > div { margin-top: 0 !important; line-height: 1.15; font-size: 8px !important; }
    .ledger-customer-card { display: block !important; margin-bottom: 3px; }
    .ledger-info-table    { font-size: 8px !important; }
    .ledger-info-table td { padding: 1px 6px !important; line-height: 1.25; }
    .ledger-kpi-row { display: none !important; }
    .card { box-shadow: none !important; border: none !important; }
    /* White rows — keep structure with borders instead of tinted backgrounds */
    .cl-payment-row td, .cl-opening-row td, .cl-discount-row td,
    .cl-extracost-row td, .cl-laborcost-row td, .cl-tfoot td,
    .cl-deposit-row td { background: #fff !important; }
    .cl-opening-row td { border-top: 1.5px solid #000; border-bottom: 1.5px solid #000; }
    .cl-tfoot td { border-top: 1.5px solid #000; }

    /* Compact rows: inline rem font sizes on cells beat the global 10px
       table rule; stylesheet !important beats inline styles. */
    .cl-table td, .cl-table td span, .cl-table td small,
    .cl-table td a, .cl-table td strong, .cl-table td div,
    .cl-deposit-table td, .cl-deposit-table td span, .cl-deposit-table td a { font-size: 9.5px !important; }
    .cl-table td, .cl-deposit-table td { padding: 1px 5px !important; line-height: 1.3 !important; }
    .cl-table td br { display: none; }
    .cl-time { font-size: 7.5px !important; }
    .cl-table td i, .cl-deposit-table td i { display: none; }

    /* সর্বমোট রো-র বিক্রয়/জমা টোটাল প্রিন্টে সম্পূর্ণ হাইড — শুধু অবশিষ্ট দেখা যাবে */
    .cl-mask-value { display: none !important; }
}

/* ── Print info table ────────────────────────────────────── */
.ledger-customer-card { margin-bottom: 12px; }
.ledger-info-table {
    width: 100%;
    border-collapse: collapse;
    font-size: .85rem;
    border: 1px solid #ccc;
}
.ledger-info-table td {
    padding: 5px 10px;
    border: 1px solid #ddd;
}
</style>
@endpush

@push('scripts')
<script>
function ledgerRange(type) {
    const today = new Date();
    let from, to;
    if (type === 'this_month')      { from = new Date(today.getFullYear(), today.getMonth(), 1); to = today; }
    else if (type === 'last_month') { from = new Date(today.getFullYear(), today.getMonth()-1, 1); to = new Date(today.getFullYear(), today.getMonth(), 0); }
    else if (type === 'this_year')  { from = new Date(today.getFullYear(), 0, 1); to = today; }
    else                            { from = new Date('2000-01-01'); to = today; }
    const fmt = d => {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    };
    document.querySelector('input[name=from]').value = fmt(from);
    document.querySelector('input[name=to]').value   = fmt(to);
    document.getElementById('ledgerFilterForm').submit();
}
</script>
@endpush
