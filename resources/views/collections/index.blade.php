@extends('layouts.app')
@section('title', 'তাগাদা লিস্ট')
@section('page-title', 'তাগাদা লিস্ট — বাকী আদায়')

@section('content')

@php
    $tgStoreName  = \App\Models\StoreConfig::get('store_name', 'আমাদের দোকান');
    $tgStorePhone = \App\Models\StoreConfig::get('store_phone');
@endphp

@push('styles')
<meta name="turbo-cache-control" content="no-cache">
@endpush

{{-- Page-specific print styles: body-inline so Turbo drops them on navigation --}}
<style>
@media print {
    .sidebar, .topbar, .no-print, #miniChatRoot, #miniCalcRoot, .pagination-wrap { display: none !important; }
    .main { margin-left: 0 !important; }
    .content { padding: 0 !important; }
    .card { box-shadow: none !important; border: none !important; }
}
.tg-days { font-size: .74rem; font-weight: 700; padding: 2px 8px; border-radius: 999px; white-space: nowrap; }
.tg-days-fresh { background: #f1f5f9; color: #475569; }
.tg-days-30    { background: #fef3c7; color: #b45309; }
.tg-days-60    { background: #ffedd5; color: #c2410c; }
.tg-days-90    { background: #fee2e2; color: #dc2626; }
</style>

<div class="no-print" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:16px">
    <h2 style="font-size:1.05rem;font-weight:700;display:flex;align-items:center;gap:8px">
        <i class="fas fa-hand-holding-dollar" style="color:var(--accent)"></i> তাগাদা লিস্ট
    </h2>
    <div style="display:flex;gap:8px">
        <button type="button" class="btn btn-secondary" onclick="window.print()">
            <i class="fas fa-print"></i> প্রিন্ট
        </button>
        <button type="button" class="btn btn-primary" onclick="tgBulkSms()">
            <i class="fas fa-paper-plane"></i> নির্বাচিতদের SMS তাগাদা
        </button>
    </div>
</div>

{{-- Stats --}}
<div class="stats-grid no-print" style="margin-bottom:20px;grid-template-columns:repeat(3,1fr)">
    <div class="stat-card stat-red">
        <div class="stat-icon"><i class="fas fa-sack-dollar"></i></div>
        <div class="stat-body">
            <span class="stat-label">মোট বাকী{{ $areaId ? ' (এলাকা)' : '' }}</span>
            <span class="stat-value">৳ {{ number_format($buckets['all']['total'], 0) }}</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#475569"><i class="fas fa-users"></i></div>
        <div class="stat-body">
            <span class="stat-label">বাকীদার কাস্টমার</span>
            {{-- no "জন" suffix — the count-up animation rewrites the text --}}
            <span class="stat-value">{{ $buckets['all']['count'] }}</span>
        </div>
    </div>
    <div class="stat-card" style="background:linear-gradient(135deg,#fef2f2,#fee2e2)">
        <div class="stat-icon" style="background:#dc2626"><i class="fas fa-triangle-exclamation"></i></div>
        <div class="stat-body">
            <span class="stat-label">৯০+ দিনের ঝুঁকি</span>
            <span class="stat-value" style="color:#dc2626">৳ {{ number_format($buckets['90']['total'], 0) }}</span>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-filter no-print">
        <form method="GET" class="filter-form">
            <div class="form-group-field" style="min-width:220px">
                @include('partials.area-combobox', [
                    'areas'         => $areas,
                    'acValue'       => $areaId,
                    'acPlaceholder' => 'সব এলাকা (খুঁজুন)',
                    'acAllLabel'    => '— সব এলাকা —',
                ])
            </div>
            <input type="hidden" name="age" value="{{ $age }}">
            <button type="submit" class="btn btn-secondary">ফিল্টার</button>
            @if(request()->hasAny(['area_id','age']))
                <a href="{{ route('collections.index') }}" class="btn btn-ghost">পরিষ্কার</a>
            @endif
        </form>
    </div>

    {{-- Aging chips --}}
    @php
        $tgChips = [
            ''   => ['সব',        $buckets['all'], '#475569'],
            '30' => ['৩০+ দিন',   $buckets['30'],  '#b45309'],
            '60' => ['৬০+ দিন',   $buckets['60'],  '#c2410c'],
            '90' => ['৯০+ দিন',   $buckets['90'],  '#dc2626'],
        ];
    @endphp
    <div class="filter-chips no-print" style="border-top:1px solid var(--border);border-bottom:none">
        @foreach($tgChips as $key => [$label, $b, $color])
            <a href="{{ route('collections.index', array_merge(request()->except(['age']), ['age' => $key])) }}"
               class="fchip {{ (string)($age ?? '') === (string)$key ? 'fchip-active' : '' }}" style="--chip:{{ $color }}">
                {{ $label }}<span class="fchip-count">{{ $b['count'] }}</span>
            </a>
        @endforeach
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="no-print" style="width:36px">
                        <input type="checkbox" id="tgCheckAll" title="সব নির্বাচন">
                    </th>
                    <th>কাস্টমার</th>
                    <th>ফোন</th>
                    <th>এলাকা</th>
                    <th>বাকী</th>
                    <th>শেষ পরিশোধ</th>
                    <th class="no-print">অ্যাকশন</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $c)
                <tr>
                    <td class="no-print">
                        @if($c->phone)
                            <input type="checkbox" class="tg-check" value="{{ $c->id }}">
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('customers.ledger', $c) }}" class="link-primary" style="font-weight:600">{{ $c->name }}</a>
                        @if($c->proprietor)<br><small style="color:#475569">{{ $c->proprietor }}</small>@endif
                    </td>
                    <td>
                        @if($c->phone)
                            <a href="tel:{{ $c->phone }}" class="link-primary mono"><i class="fas fa-phone" style="font-size:.7rem;margin-right:4px"></i>{{ $c->phone }}</a>
                        @else —
                        @endif
                    </td>
                    <td>{{ $c->area->name ?? '—' }}</td>
                    <td>
                        <strong style="color:#dc2626">৳ {{ number_format($c->due_amount, 0) }}</strong>
                        @if($c->credit_limit > 0 && $c->due_amount > $c->credit_limit)
                            <br><span class="tg-days tg-days-90" title="ক্রেডিট লিমিট ৳{{ number_format($c->credit_limit, 0) }}"><i class="fas fa-triangle-exclamation" style="font-size:.62rem"></i> লিমিট ছাড়ানো</span>
                        @endif
                    </td>
                    <td>
                        @php
                            $d = $c->days_since;
                            $cls = $d === null ? 'tg-days-fresh' : ($d >= 90 ? 'tg-days-90' : ($d >= 60 ? 'tg-days-60' : ($d >= 30 ? 'tg-days-30' : 'tg-days-fresh')));
                        @endphp
                        @if($c->never_paid)
                            <span class="tg-days {{ $cls }}">কখনো পরিশোধ করেনি{{ $d !== null ? ' · '.$d.' দিন' : '' }}</span>
                        @elseif($d !== null)
                            <span class="tg-days {{ $cls }}">{{ $d }} দিন আগে</span>
                            <br><small style="color:#334155">{{ \Carbon\Carbon::parse($c->last_paid_at)->format('d/m/Y') }}</small>
                        @else
                            <span class="tg-days tg-days-fresh">তথ্য নেই</span>
                        @endif
                    </td>
                    <td class="no-print">
                        <div class="action-btns">
                            @if($c->phone)
                            <button type="button" class="btn-icon-sm" title="WhatsApp তাগাদা" style="color:#16a34a"
                                    onclick="openWhatsApp(@js($c->phone), @js('প্রিয় ' . $c->name . ', ' . $tgStoreName . '-এ আপনার ৳' . number_format($c->due_amount, 0) . ' বাকী রয়েছে। অনুগ্রহ করে দ্রুত পরিশোধ করুন।' . ($tgStorePhone ? ' যোগাযোগ: ' . $tgStorePhone : '')))">
                                <i class="fab fa-whatsapp"></i>
                            </button>
                            <form method="POST" action="{{ route('customers.sms-reminder', $c) }}"
                                  data-confirm-msg="{{ $c->name }}-কে বাকী তাগাদার SMS পাঠাবেন?">
                                @csrf
                                <button type="submit" class="btn-icon-sm" title="SMS তাগাদা">
                                    <i class="fas fa-comment-sms"></i>
                                </button>
                            </form>
                            @endif
                            <a href="{{ route('customers.ledger', $c) }}" class="btn-icon-sm" title="লেজার"><i class="fas fa-book-open"></i></a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="empty-row">এই ফিল্টারে কোনো বাকীদার কাস্টমার নেই 🎉</td></tr>
                @endforelse
            </tbody>
            @if($customers->isNotEmpty())
            <tfoot>
                <tr class="tfoot-summary">
                    <td class="no-print"></td>
                    <td colspan="3" style="text-align:right;font-weight:700;padding-right:16px">মোট ({{ $customers->count() }} জন)</td>
                    <td style="font-weight:800;color:#dc2626">৳ {{ number_format($customers->sum('due_amount'), 0) }}</td>
                    <td></td>
                    <td class="no-print"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

{{-- Hidden form for bulk SMS — built by tgBulkSms() from checked rows --}}
<form id="tgBulkForm" method="POST" action="{{ route('collections.sms') }}" style="display:none">
    @csrf
</form>

<script>
function tgBulkSms() {
    var checks = document.querySelectorAll('.tg-check:checked');
    if (!checks.length) { alert('আগে টেবিল থেকে কাস্টমার নির্বাচন করুন (টিক দিন)।'); return; }
    if (checks.length > 50) { alert('একবারে সর্বোচ্চ ৫০ জনকে SMS পাঠানো যায়।'); return; }
    if (!confirm(checks.length + ' জন কাস্টমারকে বাকী তাগাদার SMS পাঠাবেন?')) return;
    var form = document.getElementById('tgBulkForm');
    // clear previous ids, keep the csrf input
    form.querySelectorAll('input[name="customer_ids[]"]').forEach(function (i) { i.remove(); });
    checks.forEach(function (c) {
        var inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = 'customer_ids[]'; inp.value = c.value;
        form.appendChild(inp);
    });
    form.requestSubmit ? form.requestSubmit() : form.submit();
}
(function () {
    var all = document.getElementById('tgCheckAll');
    if (!all) return;
    all.addEventListener('change', function () {
        document.querySelectorAll('.tg-check').forEach(function (c) { c.checked = all.checked; });
    });
})();
</script>
@endsection
