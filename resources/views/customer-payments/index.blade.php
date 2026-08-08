@extends('layouts.app')
@section('title', 'কাস্টমার পরিশোধ')
@section('page-title', 'কাস্টমার পরিশোধ')

@push('styles')
<meta name="turbo-cache-control" content="no-cache">
@endpush

@section('content')
@include('partials.page-header', ['title' => 'পরিশোধ তালিকা', 'createRoute' => route('customer-payments.create'), 'createLabel' => 'নতুন পরিশোধ'])

<div class="card" style="margin-bottom:20px">
    <div class="card-filter">
        <form method="GET" class="filter-form" data-date-snap>
            <div class="form-group-field" style="position:relative;min-width:220px">
                <label>কাস্টমার</label>
                <input type="hidden" name="customer_id" id="pcCustomerId" value="{{ $selectedCustomer?->id }}">
                <input type="text" id="pcCustomerSearch" class="form-select" autocomplete="off"
                       placeholder="সকল কাস্টমার (খুঁজতে লিখুন)"
                       value="{{ $selectedCustomer?->name }}">
            </div>
            <div class="form-group-field">
                <label>শুরু</label>
                <input type="date" name="from" id="pcDateFrom" value="{{ $from }}">
            </div>
            <div class="form-group-field">
                <label>শেষ</label>
                <input type="date" name="to" id="pcDateTo" value="{{ $to }}">
            </div>
            @include('partials.date-range-buttons')
            <button type="submit" class="btn btn-secondary" style="align-self:flex-end">ফিল্টার</button>
            <button type="button" class="btn-export-print no-print" onclick="window.print()" style="align-self:flex-end">
                <i class="fas fa-print"></i> প্রিন্ট
            </button>
        </form>
    </div>
</div>

<div class="stats-grid" style="margin-bottom:20px">
    <div class="stat-card stat-green">
        <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
        <div class="stat-body">
            <span class="stat-label">মোট পরিশোধ</span>
            <span class="stat-value">৳ {{ number_format($totalPaid, 0) }}</span>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ইনভয়েস</th>
                    <th>তারিখ</th>
                    <th>কাস্টমার</th>
                    <th>পরিমাণ</th>
                    <th>পদ্ধতি</th>
                    <th>মন্তব্য</th>
                    <th>রেকর্ডকারী</th>
                    <th>অ্যাকশন</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $p)
                <tr>
                    <td>
                        <a href="{{ route('sales.show', $p) }}" class="link-primary mono">
                            #INV-{{ str_pad($p->id,4,'0',STR_PAD_LEFT) }}
                        </a>
                    </td>
                    <td>{{ $p->sale_date->format('d M Y') }}</td>
                    <td>
                        <a href="{{ route('customers.ledger', $p->customer) }}" class="link-primary">
                            {{ $p->customer->name ?? '—' }}
                        </a>
                    </td>
                    <td><strong style="color:#16a34a">৳ {{ number_format($p->paid_amount, 0) }}</strong></td>
                    <td><span class="badge badge-green">{{ $p->payment_method }}</span></td>
                    <td>{{ $p->notes ?? '—' }}</td>
                    <td>{{ $p->user->name ?? '—' }}</td>
                    <td>
                        <div class="action-btns">
                            <a href="{{ route('sales.show', $p) }}" class="btn-icon-sm" title="ইনভয়েস"><i class="fas fa-eye"></i></a>
                            <form class="admin-only" method="POST" action="{{ route('sales.destroy', $p) }}"
                                  data-confirm-msg="পরিশোধ ৳{{ number_format($p->paid_amount,0) }} মুছে ফেলবেন? {{ $p->customer?->name ?? '' }}-এর বাকী ৳{{ number_format($p->paid_amount,0) }} বেড়ে যাবে।">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-icon-sm btn-icon-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="empty-row">কোনো পরিশোধ পাওয়া যায়নি</td></tr>
                @endforelse
            </tbody>
            @if($payments->isNotEmpty())
            <tfoot>
                <tr class="tfoot-summary">
                    <td colspan="3" style="text-align:right;font-weight:700;padding-right:16px">সর্বমোট (ফিল্টার)</td>
                    <td style="font-weight:800;color:#16a34a">৳ {{ number_format($totalPaid) }}</td>
                    <td colspan="4"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
    <div class="pagination-wrap">{{ $payments->withQueryString()->links() }}</div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var input = document.getElementById('pcCustomerSearch');
    var idInput = document.getElementById('pcCustomerId');
    if (!input || !idInput) return;

    var drop = document.createElement('div');
    document.body.appendChild(drop);
    drop.style.cssText = 'position:fixed;display:none;z-index:9999;background:#fff;' +
        'border:1px solid #e2e8f0;border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.12);' +
        'overflow-y:auto;max-height:260px';

    function position() {
        var r = input.getBoundingClientRect();
        drop.style.top   = (r.bottom + 4) + 'px';
        drop.style.left  = r.left + 'px';
        drop.style.width = r.width + 'px';
    }
    function show(html) { drop.innerHTML = html; position(); drop.style.display = 'block'; }
    function hide() { drop.style.display = 'none'; drop.innerHTML = ''; }

    var matches = [];
    window.pcSelectCustomer = function (id) {
        var c = matches.find(function (x) { return x.id === id; });
        if (!c) return;
        idInput.value = c.id;
        input.value   = c.name;
        hide();
        input.form.requestSubmit ? input.form.requestSubmit() : input.form.submit();
    };
    window.pcClearCustomer = function () {
        idInput.value = '';
        input.value   = '';
        hide();
        input.form.requestSubmit ? input.form.requestSubmit() : input.form.submit();
    };

    function render() {
        if (!matches.length) {
            show('<div class="suggestion-item" style="color:#94a3b8">কোনো কাস্টমার পাওয়া যায়নি</div>');
            return;
        }
        var html = '<div class="suggestion-item" style="color:#0d9488;font-weight:700" onclick="pcClearCustomer()">— সকল কাস্টমার —</div>';
        html += matches.map(function (c) {
            return '<div class="suggestion-item" onclick="pcSelectCustomer(' + c.id + ')">' +
                '<strong style="font-size:.9rem">' + c.name + '</strong>' +
                (c.phone ? '<span style="font-size:.76rem;color:#94a3b8;margin-left:8px">' + c.phone + '</span>' : '') +
                '</div>';
        }).join('');
        show(html);
    }

    var timer;
    input.addEventListener('input', function () {
        var q = input.value.trim();
        idInput.value = '';
        clearTimeout(timer);
        if (!q) { hide(); return; }
        show('<div class="suggestion-item" style="color:#94a3b8">খুঁজছি…</div>');
        timer = setTimeout(function () {
            fetch('{{ route('customers.search') }}?q=' + encodeURIComponent(q), {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
                .then(function (r) { return r.json(); })
                .then(function (data) { matches = data; render(); })
                .catch(function () { show('<div class="suggestion-item" style="color:#94a3b8">খুঁজতে সমস্যা হয়েছে</div>'); });
        }, 250);
    });
    input.addEventListener('focus', function () { if (matches.length) render(); });

    window.addEventListener('scroll', position, true);
    window.addEventListener('resize', position);
    document.addEventListener('click', function (e) {
        if (!input.contains(e.target) && !drop.contains(e.target)) hide();
    });
})();

// Date snap: selecting from-date snaps to-date to same day and submits
(function () {
    var fromEl = document.getElementById('pcDateFrom');
    var toEl   = document.getElementById('pcDateTo');
    if (!fromEl || !toEl) return;
    function onFromChange() {
        if (fromEl.value) toEl.value = fromEl.value;
        fromEl.form.requestSubmit ? fromEl.form.requestSubmit() : fromEl.form.submit();
    }
    function onToChange() {
        fromEl.form.requestSubmit ? fromEl.form.requestSubmit() : fromEl.form.submit();
    }
    fromEl.addEventListener('change', onFromChange);
    fromEl.addEventListener('input',  onFromChange);
    toEl.addEventListener('change', onToChange);
    toEl.addEventListener('input',  onToChange);
})();
</script>
@endpush
