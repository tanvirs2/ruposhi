@extends('layouts.app')
@section('title', 'সরবরাহকারী লেজার — '.$supplier->name)
@section('page-title', 'সরবরাহকারী লেজার রিপোর্ট')
@section('no-print-header', '1')

@push('styles')
<meta name="turbo-cache-control" content="no-cache">
@endpush

@section('content')

{{-- ── Print header ────────────────────────────────────────────── --}}
<div class="sl-print-header" style="display:none">
    <div style="font-size:1.1rem;font-weight:800;color:#000">{{ \App\Models\StoreConfig::get('store_name','আমার দোকান') }}</div>
    <div style="font-size:.85rem;color:#555;margin-top:2px">সরবরাহকারী লেজার রিপোর্ট</div>
    <div style="font-size:.8rem;color:#777">
        {{ \Carbon\Carbon::parse($from)->format('d M Y') }} — {{ \Carbon\Carbon::parse($to)->format('d M Y') }}
    </div>
</div>

{{-- ── Top bar ─────────────────────────────────────────────────── --}}
<div class="page-header-bar no-print">
    <div>
        <h2 style="font-size:1.1rem;font-weight:700">{{ $supplier->name }}</h2>
        <p style="font-size:.82rem;color:#64748b;margin-top:2px">
            @if($supplier->phone)<i class="fas fa-phone" style="width:14px;font-size:.8rem"></i> {{ $supplier->phone }} @endif
            @if($supplier->address) &nbsp;·&nbsp; <i class="fas fa-location-dot" style="width:14px;font-size:.8rem"></i> {{ $supplier->address }} @endif
        </p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
        <a href="{{ route('supplier-payments.create', ['supplier_id' => $supplier->id]) }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> পরিশোধ যোগ করুন
        </a>
        <button type="button" class="btn btn-export-print" onclick="window.print()">
            <i class="fas fa-print"></i> প্রিন্ট
        </button>
        <a href="{{ route('suppliers.ledger', $supplier->id) }}?from={{ $from }}&to={{ $to }}&export=csv"
           class="btn btn-export">
            <i class="fas fa-file-csv"></i> CSV
        </a>
        <a href="{{ route('suppliers.index') }}" class="btn btn-ghost">
            <i class="fas fa-arrow-left"></i> ফিরে যান
        </a>
    </div>
</div>

{{-- ── Filter ────────────────────────────────────────────────────── --}}
<div class="card no-print" style="margin-bottom:20px">
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
                <button type="button" onclick="slRange('this_month')" class="btn btn-ghost" style="font-size:.8rem;padding:8px 12px">এই মাস</button>
                <button type="button" onclick="slRange('last_month')" class="btn btn-ghost" style="font-size:.8rem;padding:8px 12px">গত মাস</button>
                <button type="button" onclick="slRange('this_year')"  class="btn btn-ghost" style="font-size:.8rem;padding:8px 12px">এই বছর</button>
                <button type="button" onclick="slRange('all')"        class="btn btn-ghost" style="font-size:.8rem;padding:8px 12px">সব</button>
            </div>
            <button type="submit" class="btn btn-primary" style="align-self:flex-end">
                <i class="fas fa-search"></i> দেখুন
            </button>
        </form>
    </div>
</div>

{{-- ── Supplier info (print only) ─────────────────────────────── --}}
<div class="sl-info-card" style="display:none">
    <table class="sl-info-table">
        <tr>
            <td><strong>প্রতিষ্ঠান</strong></td>
            <td>{{ $supplier->name }}</td>
            <td><strong>ফোন</strong></td>
            <td>{{ $supplier->phone ?? '—' }}</td>
        </tr>
        @if($supplier->address)
        <tr>
            <td><strong>ঠিকানা</strong></td>
            <td colspan="3">{{ $supplier->address }}</td>
        </tr>
        @endif
        <tr>
            <td><strong>সময়কাল</strong></td>
            <td colspan="3">{{ \Carbon\Carbon::parse($from)->format('d M Y') }} — {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</td>
        </tr>
    </table>
</div>

{{-- ── KPI Cards ─────────────────────────────────────────────────── --}}
<div class="sl-kpi-row">
    <div class="sl-kpi sl-kpi-blue">
        <div class="sl-kpi-label"><i class="fas fa-truck-ramp-box"></i> মোট ক্রয়</div>
        <div class="sl-kpi-value">৳ {{ number_format($totalDebit, 0) }}</div>
        <div class="sl-kpi-sub">{{ $receiptCount }}টি রিসিভ</div>
    </div>
    <div class="sl-kpi sl-kpi-green">
        <div class="sl-kpi-label"><i class="fas fa-money-bill-wave"></i> মোট পরিশোধ</div>
        <div class="sl-kpi-value">৳ {{ number_format($totalCredit, 0) }}</div>
        <div class="sl-kpi-sub">{{ $deposits->count() }}টি পরিশোধ</div>
    </div>
    @if($openingBalance != 0)
    <div class="sl-kpi {{ $openingBalance > 0 ? 'sl-kpi-orange' : 'sl-kpi-green' }}">
        <div class="sl-kpi-label"><i class="fas fa-history"></i> পূর্বের অবশিষ্ট</div>
        <div class="sl-kpi-value">৳ {{ number_format(abs($openingBalance), 0) }}</div>
        <div class="sl-kpi-sub">{{ $from }} এর আগের {{ $openingBalance > 0 ? 'দেনা' : 'পাওনা' }}</div>
    </div>
    @endif
    <div class="sl-kpi {{ $realTotalDue > 0 ? 'sl-kpi-red' : 'sl-kpi-green' }}">
        <div class="sl-kpi-label"><i class="fas fa-scale-balanced"></i> সর্বমোট বকেয়া</div>
        <div class="sl-kpi-value">৳ {{ number_format($realTotalDue, 0) }}</div>
        <div class="sl-kpi-sub">সকল লেনদেন হিসেবে</div>
    </div>
</div>

{{-- ── View toggle: পুরনো (এক টেবিল) ↔ নতুন (দুই টেবিল) ── --}}
<div class="sl-view-toggle no-print">
    <span style="font-size:.82rem;color:#64748b;font-weight:600">ভিউ:</span>
    <button type="button" id="slViewBtnOld" class="sl-view-btn" onclick="slSetView('old')">
        <i class="fas fa-table-list"></i> এক টেবিল
    </button>
    <button type="button" id="slViewBtnNew" class="sl-view-btn" onclick="slSetView('new')">
        <i class="fas fa-table-columns"></i> দুই টেবিল
    </button>
</div>

{{-- ══════════ OLD VIEW — single combined table (default) ══════════ --}}
<div id="supplierViewOld">
{{-- ── Ledger Table ──────────────────────────────────────────────── --}}
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-book-open"></i> লেনদেনের বিবরণ</h3>
    </div>
    <div class="table-wrap">
        <table class="data-table sl-ledger-table">
            <colgroup>
                <col style="width:90px">   {{-- চালান নং --}}
                <col style="width:105px">  {{-- তারিখ --}}
                <col>                      {{-- বিবরণ --}}
                <col style="width:80px">   {{-- পরিমাণ --}}
                <col style="width:100px">  {{-- দর --}}
                <col style="width:130px">  {{-- বাকি/Dr --}}
                <col style="width:130px">  {{-- জমা/Cr --}}
                <col style="width:150px">  {{-- অবশিষ্ট --}}
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

                {{-- Opening balance row — only shown when there's an actual carryover
                     (matches customer ledger's behaviour; skips a meaningless ৳0 row
                     when the filter range covers all history) --}}
                @if($openingBalance != 0)
                <tr class="sl-opening-row">
                    <td class="tc" style="color:#cbd5e1">—</td>
                    <td style="color:#334155;font-size:.82rem">
                        {{ \Carbon\Carbon::parse($from)->format('d M Y') }}
                    </td>
                    <td colspan="5">
                        <span style="font-style:italic;color:#64748b;font-size:.88rem">
                            <i class="fas fa-clock-rotate-left" style="font-size:.75rem;margin-right:4px"></i>
                            পূর্বের অবশিষ্ট
                        </span>
                    </td>
                    <td style="text-align:right;font-variant-numeric:tabular-nums">
                        <span class="{{ $openingBalance > 0 ? 'sl-debit' : 'sl-credit' }}">
                            ৳ {{ number_format(abs($openingBalance), 0) }}
                            <br><small class="sl-bal-tag">{{ $openingBalance > 0 ? 'দেনা' : 'পাওনা' }}</small>
                        </span>
                    </td>
                </tr>
                @endif

                @php $prevPurchaseId = null; @endphp
                @forelse($combined as $row)
                @php
                    $isItem      = $row->type === 'item';
                    $isPay       = $row->type === 'payment' || $row->type === 'purchase_payment';
                    $isDeposit   = $row->type === 'deposit';
                    $isExtraCost = $row->type === 'extra_cost';
                    $isNewGroup  = $row->purchase_id && $row->purchase_id !== $prevPurchaseId;
                    if ($row->purchase_id) $prevPurchaseId = $row->purchase_id;
                    elseif ($row->type === 'payment') $prevPurchaseId = null;
                    $rowClass = $isPay      ? 'sl-payment-row'
                              : ($isDeposit   ? 'sl-deposit-row'
                              : ($isExtraCost ? 'sl-extracost-row'
                              : 'sl-item-row'));
                @endphp
                <tr class="{{ $rowClass }} {{ $isNewGroup ? 'sl-new-group' : '' }}">

                    {{-- চালান নং (receive/purchase number) — own column, like the customer ledger.
                         purchase_payment rows have purchase_id but no link (controller sets link=null
                         for that type), so link (not purchase_id) decides whether it's clickable. --}}
                    <td class="tc mono" style="font-size:.82rem">
                        @if($row->link)
                            <a href="{{ $row->link }}" class="link-primary" title="রিসিভ দেখুন">{{ $row->ref }}</a>
                        @elseif($row->ref)
                            <span style="color:#64748b">{{ $row->ref }}</span>
                        @else
                            <span style="color:#cbd5e1">—</span>
                        @endif
                    </td>

                    {{-- Date --}}
                    <td style="white-space:nowrap;font-size:.83rem;color:#334155">
                        {{ \Carbon\Carbon::parse($row->date)->format('d M Y') }}
                    </td>

                    {{-- Description --}}
                    <td>
                        @if($isItem)
                            <span style="font-weight:600;color:var(--text)">{{ $row->label }}</span>
                        @elseif($isExtraCost)
                            <span class="sl-extracost-label">
                                <i class="fas fa-plus-circle" style="font-size:.72rem;margin-right:3px"></i>
                                {{ $row->label }}
                            </span>
                        @elseif($isDeposit)
                            <span class="sl-deposit-label">
                                <i class="fas fa-piggy-bank" style="font-size:.72rem;margin-right:3px"></i>
                                {{ $row->label }}
                            </span>
                        @else
                            <span class="sl-pay-label">
                                <i class="fas fa-circle-check" style="font-size:.72rem"></i>
                                {{ $row->label }}
                            </span>
                        @endif
                    </td>

                    {{-- পরিমাণ --}}
                    <td style="text-align:right;font-size:.88rem">
                        @if($row->qty > 0)
                            <span style="font-weight:600">{{ number_format($row->qty, 0) }}</span>
                        @else
                            <span style="color:#cbd5e1">—</span>
                        @endif
                    </td>

                    {{-- দর --}}
                    <td style="text-align:right;font-size:.88rem">
                        @if($row->rate > 0)
                            <span>{{ number_format($row->rate, 0) }}</span>
                        @else
                            <span style="color:#cbd5e1">—</span>
                        @endif
                    </td>

                    {{-- বাকি (debit — goods received) --}}
                    <td style="text-align:right;font-variant-numeric:tabular-nums">
                        @if($row->debit > 0)
                            <span class="sl-debit">৳ {{ number_format($row->debit, 0) }}</span>
                        @else
                            <span style="color:#cbd5e1">—</span>
                        @endif
                    </td>

                    {{-- জমা (credit — payment made) --}}
                    <td style="text-align:right;font-variant-numeric:tabular-nums">
                        @if($row->credit > 0)
                            <span class="sl-credit">৳ {{ number_format($row->credit, 0) }}</span>
                        @else
                            <span style="color:#cbd5e1">—</span>
                        @endif
                    </td>

                    {{-- অবশিষ্ট --}}
                    <td style="text-align:right;font-variant-numeric:tabular-nums">
                        @if($row->balance == 0)
                            <span style="color:#64748b;font-weight:600">৳ 0</span>
                            <br><small class="sl-bal-tag">নিষ্পত্তি</small>
                        @else
                            <span class="{{ $row->balance > 0 ? 'sl-debit' : 'sl-credit' }}">
                                ৳ {{ number_format(abs($row->balance), 0) }}
                            </span>
                            <br><small class="sl-bal-tag {{ $row->balance > 0 ? 'sl-tag-due' : 'sl-tag-credit' }}">
                                {{ $row->balance > 0 ? 'দেনা' : 'পাওনা' }}
                            </small>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="empty-row">এই সময়কালে কোনো লেনদেন নেই</td></tr>
                @endforelse

            </tbody>

            @if($combined->isNotEmpty())
            <tfoot>
                <tr class="sl-tfoot">
                    <td colspan="5">সর্বমোট</td>
                    <td style="text-align:right;font-variant-numeric:tabular-nums">
                        <span class="sl-mask-value" onclick="this.classList.toggle('revealed')">
                            <span class="sl-mask-dots">—</span>
                            <span class="sl-debit sl-mask-real">৳ {{ number_format($totalDebit, 0) }}</span>
                        </span>
                    </td>
                    <td style="text-align:right;font-variant-numeric:tabular-nums">
                        <span class="sl-mask-value" onclick="this.classList.toggle('revealed')">
                            <span class="sl-mask-dots">—</span>
                            <span class="sl-credit sl-mask-real">৳ {{ number_format($totalCredit, 0) }}</span>
                        </span>
                    </td>
                    <td style="text-align:right;font-variant-numeric:tabular-nums">
                        @php $finalBal = $combined->last()->balance ?? $openingBalance; @endphp
                        @if($finalBal == 0)
                            <span style="color:#64748b;font-weight:700">৳ 0</span>
                        @else
                            <span class="{{ $finalBal > 0 ? 'sl-debit' : 'sl-credit' }}">
                                ৳ {{ number_format(abs($finalBal), 0) }}
                            </span>
                            <br><small class="sl-bal-tag {{ $finalBal > 0 ? 'sl-tag-due' : 'sl-tag-credit' }}">
                                {{ $finalBal > 0 ? 'দেনা' : 'পাওনা' }}
                            </small>
                        @endif
                    </td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
</div>{{-- /#supplierViewOld --}}

{{-- ══════════ NEW VIEW — জমা তালিকা + লেনদেনের বিবরণ (bills only) ══════════ --}}
<div id="supplierViewNew" style="display:none">

{{-- ── জমা তালিকা (payments — kept in its OWN table) ─────────────── --}}
<div class="card sl-deposit-card">
    <div class="card-header">
        <h3><i class="fas fa-money-bill-wave" style="color:#16a34a"></i> জমা তালিকা</h3>
        <span style="font-size:.78rem;color:#64748b;margin-left:auto">{{ $deposits->count() }}টি জমা</span>
    </div>
    <div class="table-wrap">
        <table class="data-table sl-deposit-table">
            <colgroup>
                <col style="width:150px">  {{-- তারিখ --}}
                <col style="width:120px">  {{-- পদ্ধতি --}}
                <col>                      {{-- সূত্র / মন্তব্য --}}
                <col style="width:130px">  {{-- পরিমাণ --}}
            </colgroup>
            <thead>
                <tr>
                    <th>তারিখ</th>
                    <th>পদ্ধতি / বিবরণ</th>
                    <th>সূত্র</th>
                    <th style="text-align:right">পরিমাণ (৳)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($deposits as $d)
                <tr class="sl-deposit-row">
                    <td style="white-space:nowrap;font-size:.83rem;color:#334155">
                        {{ \Carbon\Carbon::parse($d->date)->format('d M Y') }}
                    </td>
                    <td>
                        @if($d->type === 'deposit')
                            <span class="sl-deposit-label"><i class="fas fa-piggy-bank" style="font-size:.72rem;margin-right:3px"></i> {{ $d->label }}</span>
                        @else
                            <span class="sl-pay-label"><i class="fas fa-circle-check" style="font-size:.72rem"></i> {{ $d->label }}</span>
                        @endif
                    </td>
                    <td>
                        @if($d->link)
                            <a href="{{ $d->link }}" class="link-primary mono" style="font-size:.82rem">{{ $d->ref }}</a>
                        @elseif($d->ref)
                            <span style="color:#64748b">{{ $d->ref }}</span>
                        @else
                            <span style="color:#475569">সরাসরি পরিশোধ</span>
                        @endif
                    </td>
                    <td style="text-align:right;font-variant-numeric:tabular-nums">
                        <span class="sl-credit">৳ {{ number_format($d->credit, 0) }}</span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="empty-row">এই সময়কালে কোনো জমা নেই</td></tr>
                @endforelse
            </tbody>
            @if($deposits->count())
            <tfoot>
                <tr class="sl-tfoot">
                    <td colspan="3" style="font-weight:700">মোট জমা</td>
                    <td style="text-align:right;font-weight:700;color:#16a34a">
                        <span class="sl-mask-value" onclick="this.classList.toggle('revealed')">
                            <span class="sl-mask-dots">—</span>
                            <span class="sl-mask-real">৳ {{ number_format($totalDeposits, 0) }}</span>
                        </span>
                    </td>
                </tr>
                <tr class="sl-tfoot sl-netdue-row">
                    <td colspan="3" style="font-weight:800">নিট বকেয়া <span style="font-weight:500;color:#64748b;font-size:.8rem">(অবশিষ্ট ক্রয় − মোট জমা)</span></td>
                    @php $netDue = ($openingBalance + $bills->sum('debit')) - $totalDeposits; @endphp
                    <td style="text-align:right;font-weight:800;color:{{ $netDue > 0 ? '#b45309' : ($netDue < 0 ? '#15803d' : '#64748b') }}">
                        ৳ {{ number_format(abs($netDue), 0) }}
                        @if($netDue < 0) <div style="font-size:.7rem;color:#15803d">(অতিরিক্ত)</div> @endif
                    </td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

{{-- ── Ledger Table (লেনদেনের বিবরণ — purchases only) ──────────────────── --}}
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-truck-ramp-box" style="color:#1d4ed8"></i> লেনদেনের বিবরণ (ক্রয়)</h3>
    </div>
    <div class="table-wrap">
        <table class="data-table sl-ledger-table">
            <colgroup>
                <col style="width:90px">   {{-- চালান নং --}}
                <col style="width:105px">  {{-- তারিখ --}}
                <col>                      {{-- বিবরণ --}}
                <col style="width:80px">   {{-- পরিমাণ --}}
                <col style="width:100px">  {{-- দর --}}
                <col style="width:130px">  {{-- বাকি --}}
                <col style="width:150px">  {{-- অবশিষ্ট --}}
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
                @if($openingBalance != 0)
                <tr class="sl-opening-row">
                    <td class="tc" style="color:#cbd5e1">—</td>
                    <td style="color:#334155;font-size:.82rem">{{ \Carbon\Carbon::parse($from)->format('d M Y') }}</td>
                    <td colspan="4">
                        <span style="font-style:italic;color:#64748b;font-size:.88rem">
                            <i class="fas fa-clock-rotate-left" style="font-size:.75rem;margin-right:4px"></i> পূর্বের অবশিষ্ট
                        </span>
                    </td>
                    <td style="text-align:right;font-variant-numeric:tabular-nums">
                        <span class="{{ $openingBalance > 0 ? 'sl-debit' : 'sl-credit' }}">
                            ৳ {{ number_format(abs($openingBalance), 0) }}
                            <br><small class="sl-bal-tag">{{ $openingBalance > 0 ? 'দেনা' : 'পাওনা' }}</small>
                        </span>
                    </td>
                </tr>
                @endif
                @php $prevPurchaseId3 = null; @endphp
                @forelse($bills as $row)
                @php
                    $isItem3      = $row->type === 'item';
                    $isExtraCost3 = $row->type === 'extra_cost';
                    $isNewGroup3  = $row->purchase_id && $row->purchase_id !== $prevPurchaseId3;
                    if ($row->purchase_id) $prevPurchaseId3 = $row->purchase_id;
                @endphp
                <tr class="{{ $isExtraCost3 ? 'sl-extracost-row' : 'sl-item-row' }} {{ $isNewGroup3 ? 'sl-new-group' : '' }}">
                    <td class="tc mono" style="font-size:.82rem">
                        @if($row->link)
                            <a href="{{ $row->link }}" class="link-primary" title="রিসিভ দেখুন">{{ $row->ref }}</a>
                        @else
                            <span style="color:#64748b">{{ $row->ref }}</span>
                        @endif
                    </td>
                    <td style="white-space:nowrap;font-size:.83rem;color:#334155">{{ \Carbon\Carbon::parse($row->date)->format('d M Y') }}</td>
                    <td>
                        @if($isItem3)
                            <span style="font-weight:600;color:var(--text)">{{ $row->label }}</span>
                        @else
                            <span class="sl-extracost-label"><i class="fas fa-plus-circle" style="font-size:.72rem;margin-right:3px"></i> {{ $row->label }}</span>
                        @endif
                    </td>
                    <td style="text-align:right;font-size:.88rem">
                        @if($row->qty > 0)<span style="font-weight:600">{{ number_format($row->qty, 0) }}</span>@else<span style="color:#cbd5e1">—</span>@endif
                    </td>
                    <td style="text-align:right;font-size:.88rem">
                        @if($row->rate > 0)<span>{{ number_format($row->rate, 0) }}</span>@else<span style="color:#cbd5e1">—</span>@endif
                    </td>
                    <td style="text-align:right;font-variant-numeric:tabular-nums">
                        <span class="sl-debit">৳ {{ number_format($row->debit, 0) }}</span>
                    </td>
                    <td style="text-align:right;font-variant-numeric:tabular-nums">
                        <span class="sl-debit">৳ {{ number_format($row->billBalance, 0) }}</span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="empty-row">এই সময়কালে কোনো ক্রয় নেই</td></tr>
                @endforelse
            </tbody>
            @if($bills->count())
            <tfoot>
                <tr class="sl-tfoot">
                    <td colspan="5" style="font-weight:700">সর্বমোট</td>
                    <td style="text-align:right;font-weight:700;color:#dc2626">
                        <span class="sl-mask-value" onclick="this.classList.toggle('revealed')">
                            <span class="sl-mask-dots">—</span>
                            <span class="sl-mask-real">৳ {{ number_format($bills->sum('debit'), 0) }}</span>
                        </span>
                    </td>
                    <td style="text-align:right;font-weight:800">
                        @php $billsFinal = $bills->last()->billBalance ?? $openingBalance; @endphp
                        ৳ {{ number_format($billsFinal, 0) }}
                    </td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

</div>{{-- /#supplierViewNew --}}

<script>
// Toggle between the classic single-table view and the split two-table view.
// Default = old (single table); choice remembered in localStorage.
function slSetView(mode) {
    var isNew = mode === 'new';
    var vOld = document.getElementById('supplierViewOld');
    var vNew = document.getElementById('supplierViewNew');
    if (vOld) vOld.style.display = isNew ? 'none' : '';
    if (vNew) vNew.style.display = isNew ? '' : 'none';
    var bOld = document.getElementById('slViewBtnOld');
    var bNew = document.getElementById('slViewBtnNew');
    if (bOld) bOld.classList.toggle('active', !isNew);
    if (bNew) bNew.classList.toggle('active', isNew);
    try { localStorage.setItem('sl_ledger_view', mode); } catch (e) {}
    if (window.setupCompactTables) window.setupCompactTables(isNew ? vNew : vOld);
}
(function () {
    var saved = 'old';
    try { saved = localStorage.getItem('sl_ledger_view') || 'old'; } catch (e) {}
    slSetView(saved);
})();
</script>

@endsection

@push('styles')
<style>
/* ── KPI row ──────────────────────────────────────────────── */
.sl-kpi-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 14px;
    margin-bottom: 24px;
}
.sl-kpi {
    border-radius: 12px;
    padding: 16px 18px;
    border: 1px solid transparent;
}
.sl-kpi-label { font-size: .78rem; font-weight: 600; opacity: .75; margin-bottom: 6px; display:flex; align-items:center; gap:6px; }
.sl-kpi-value { font-size: 1.35rem; font-weight: 800; line-height: 1.1; font-variant-numeric: tabular-nums; }
.sl-kpi-sub   { font-size: .72rem; opacity: .6; margin-top: 4px; }
.sl-kpi-blue   { background:#eff6ff; border-color:#bfdbfe; color:#1e40af; }
.sl-kpi-green  { background:#f0fdf4; border-color:#bbf7d0; color:#15803d; }
.sl-kpi-orange { background:#fff7ed; border-color:#fed7aa; color:#c2410c; }
.sl-kpi-red    { background:#fef2f2; border-color:#fecaca; color:#b91c1c; }

/* ── Ledger table ────────────────────────────────────────── */
.sl-ledger-table td, .sl-ledger-table th { white-space: nowrap; }

/* Group separator — dashed top border before first item of each receipt */
.sl-new-group td { border-top: 2px dashed #e2e8f0 !important; }

.sl-opening-row { background: #f8fafc; }
.sl-payment-row  { background: #f0fdf4; }
.sl-deposit-row  { background: #eff6ff; }
.sl-item-row     { background: var(--card-bg); }
.sl-extracost-row { background: #fdf4ff; }
.sl-laborcost-row { background: #fff1f2; }
.sl-extracost-label { color: #7e22ce; font-weight: 600; font-size: .88rem; }
.sl-laborcost-label { color: #be123c; font-weight: 600; font-size: .88rem; }
.sl-deposit-label   { color: #1d4ed8; font-weight: 600; font-size: .88rem; }

.sl-debit  { color: #dc2626; font-weight: 700; }
.sl-credit { color: #16a34a; font-weight: 700; }

.sl-bal-tag     { font-size: .7rem; font-weight: 500; }
.sl-tag-due     { color: #b45309; }
.sl-tag-credit  { color: #15803d; }

/* Reference link/tag */
.sl-ref-link {
    display: inline-block;
    margin-left: 6px;
    font-size: .72rem;
    color: #0d9488;
    font-weight: 600;
    text-decoration: none;
    background: #f0fdfa;
    border: 1px solid #99f6e4;
    border-radius: 4px;
    padding: 0 5px;
    vertical-align: middle;
}
.sl-ref-link:hover { text-decoration: underline; }

.sl-ref-tag {
    display: inline-block;
    margin-left: 6px;
    font-size: .72rem;
    color: #64748b;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 4px;
    padding: 0 5px;
    vertical-align: middle;
}

.sl-pay-label {
    color: #16a34a;
    font-weight: 600;
    font-size: .9rem;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

/* Footer */
.sl-tfoot td {
    padding: 10px 12px;
    font-weight: 700;
    background: var(--bg);
    border-top: 2px solid var(--border);
}

/* ── Print info table ─────────────────────────────────────── */
.sl-info-table {
    width: 100%;
    border-collapse: collapse;
    font-size: .85rem;
    margin-bottom: 12px;
    border: 1px solid #ccc;
}
.sl-info-table td { padding: 5px 10px; border: 1px solid #ddd; }
.sl-info-table td:nth-child(odd) { background: #f8fafc; font-weight: 600; width: 15%; }

/* ── তফুতের বাকি/জমা টোটাল — ডিফল্টে মাস্কড, ক্লিকে দেখা যায় ── */
.sl-mask-value { cursor: pointer; user-select: none; display: inline-block; }
.sl-mask-value .sl-mask-real { display: none; }
.sl-mask-value .sl-mask-dots { color: #cbd5e1; }
.sl-mask-value.revealed .sl-mask-real { display: inline; }
.sl-mask-value.revealed .sl-mask-dots { display: none; }

/* ── ভিউ টগল (এক টেবিল / দুই টেবিল) ── */
.sl-view-toggle { display: flex; align-items: center; gap: 8px; margin-bottom: 16px; }
.sl-view-btn {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: .82rem; font-weight: 600; padding: 6px 14px;
    border: 1px solid var(--border); border-radius: 8px;
    background: var(--surface); color: var(--text-secondary);
    cursor: pointer; transition: background .15s, color .15s, border-color .15s;
}
.sl-view-btn:hover { background: var(--surface-2); }
.sl-view-btn.active { background: var(--accent, #0d9488); color: #fff; border-color: var(--accent, #0d9488); }

/* ── জমা তালিকা (আলাদা টেবিল) ── */
.sl-deposit-card { margin-bottom: 18px; }
.sl-deposit-table td, .sl-deposit-table th { white-space: nowrap; }
.sl-deposit-row td { background: #f0fdf4; }
.sl-netdue-row td { border-top: 2px solid var(--border) !important; font-size: .95rem; }

/* ── Print — ink-saving: white backgrounds, dark text, tight header ── */
@media print {
    .sidebar, .topbar, .no-print { display: none !important; }
    .main-wrapper { margin-left: 0 !important; }
    .content { padding: 0 !important; }
    .sl-print-header  { display: block !important; text-align: center; margin-bottom: 2px; }
    .sl-print-header > div:first-child { font-size: .95rem !important; }
    .sl-print-header > div { margin-top: 0 !important; line-height: 1.15; font-size: 8px !important; }
    .sl-info-card     { display: block !important; margin-bottom: 3px; }
    .sl-info-table    { font-size: 8px !important; margin-bottom: 0 !important; }
    .sl-info-table td { padding: 1px 6px !important; line-height: 1.25; }
    .sl-kpi-row       { grid-template-columns: repeat(4, 1fr); gap: 4px; margin-bottom: 4px; }
    .sl-kpi           { padding: 2px 6px; border: 0.5px solid #999 !important; }
    .sl-kpi-label     { font-size: 7px !important; margin-bottom: 1px !important; }
    .sl-kpi-value     { font-size: 10px !important; }
    .sl-kpi-sub       { font-size: 6.5px !important; margin-top: 0 !important; }
    .sl-kpi-label i, .sl-kpi-sub i { display: none; }
    .card             { box-shadow: none !important; border: none !important; }
    .sl-payment-row, .sl-opening-row, .sl-tfoot td,
    .sl-deposit-row td { background: #fff !important; }
    .sl-opening-row td { border-top: 1.5px solid #000; border-bottom: 1.5px solid #000; }
    .sl-new-group td  { border-top: 1px dashed #999 !important; }

    /* Compact rows: inline rem font sizes on cells beat the global 10px
       table rule, and the two-line অবশিষ্ট cell (৳ + দেনা tag) doubled
       every row's height on paper. Stylesheet !important beats inline. */
    .sl-ledger-table td, .sl-ledger-table td span, .sl-ledger-table td small,
    .sl-ledger-table td a, .sl-ledger-table td strong,
    .sl-deposit-table td, .sl-deposit-table td span, .sl-deposit-table td a { font-size: 9.5px !important; }
    .sl-ledger-table td, .sl-deposit-table td { padding: 1px 5px !important; line-height: 1.3 !important; }
    .sl-ledger-table td br { display: none; }
    .sl-bal-tag { font-size: 7.5px !important; margin-left: 2px; }
    .sl-ref-tag, .sl-ref-link { font-size: 8px !important; padding: 0 3px !important; }
    .sl-ledger-table td i, .sl-deposit-table td i { display: none; }

    /* সর্বমোট রো-র বাকি/জমা টোটাল প্রিন্টে সম্পূর্ণ হাইড — শুধু অবশিষ্ট দেখা যাবে */
    .sl-mask-value { display: none !important; }
}
</style>
@endpush

@push('scripts')
<script>
function slRange(type) {
    const today = new Date();
    let from, to;
    if (type === 'this_month') {
        from = new Date(today.getFullYear(), today.getMonth(), 1);
        to   = today;
    } else if (type === 'last_month') {
        from = new Date(today.getFullYear(), today.getMonth() - 1, 1);
        to   = new Date(today.getFullYear(), today.getMonth(), 0);
    } else if (type === 'this_year') {
        from = new Date(today.getFullYear(), 0, 1);
        to   = today;
    } else {
        from = new Date('2000-01-01');
        to   = today;
    }
    // Local date format — avoids UTC timezone shift for UTC+6
    const fmt = d => {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    };
    document.querySelector('input[name=from]').value = fmt(from);
    document.querySelector('input[name=to]').value   = fmt(to);
    // Use ID to avoid submitting the logout form in the topbar
    document.getElementById('ledgerFilterForm').submit();
}
</script>
@endpush
