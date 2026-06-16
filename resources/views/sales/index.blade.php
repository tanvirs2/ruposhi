@extends('layouts.app')
@section('title', 'বিক্রয়')
@section('page-title', 'বিক্রয়')

@section('content')
@include('partials.page-header', ['title' => 'বিক্রয় তালিকা', 'createRoute' => route('sales.create'), 'createLabel' => 'নতুন বিক্রয়'])

<div class="card" id="salesCard">
    <div class="card-filter" style="flex-wrap:wrap;gap:8px">
        <form method="GET" class="filter-form" id="salesFilterForm" action="{{ route('sales.index') }}" style="flex:1;min-width:0">
            <div class="search-box"><i class="fas fa-search"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="কাস্টমার বা ইনভয়েস নম্বর..." autocomplete="off" id="salesSearchInput">
            </div>
            <select name="status" class="form-select" style="width:auto;min-width:130px" id="salesStatusSelect">
                <option value="">সব স্ট্যাটাস</option>
                <option value="completed" @selected(request('status')=='completed')>সম্পন্ন</option>
                <option value="pending"   @selected(request('status')=='pending')>মুলতুবি</option>
                <option value="cancelled" @selected(request('status')=='cancelled')>বাতিল</option>
            </select>
            <input type="date" name="date_from" class="form-select" value="{{ $dateFrom }}" title="শুরুর তারিখ" style="width:145px" id="salesDateFrom">
            <input type="date" name="date_to"   class="form-select" value="{{ $dateTo }}"   title="শেষ তারিখ"  style="width:145px" id="salesDateTo">
            @include('partials.date-range-buttons', ['fromName'=>'date_from','toName'=>'date_to','formClass'=>'#salesFilterForm'])
            <button type="submit" class="btn btn-secondary">ফিল্টার</button>
            <a href="{{ route('sales.index') }}" class="btn btn-outline" id="salesClearBtn" title="ক্লিয়ার করুন"
               style="{{ ($dateFrom || $dateTo || request('search') || request('status')) ? '' : 'display:none' }}"><i class="fas fa-xmark"></i></a>
        </form>

        {{-- View toggle --}}
        <div class="view-toggle-group">
            <button id="btnInvoiceView" class="view-toggle-btn active" onclick="setView('invoice')">
                <i class="fas fa-file-invoice"></i> ইনভয়েস ভিউ
            </button>
            <button id="btnItemView" class="view-toggle-btn" onclick="setView('item')">
                <i class="fas fa-list"></i> আইটেম ভিউ
            </button>
        </div>
    </div>

    <div id="salesResults">
        @include('sales._results')
    </div>
</div>

@push('styles')
<style>
.view-toggle-group {
    display: flex;
    border: 1.5px solid var(--border);
    border-radius: 8px;
    overflow: hidden;
    flex-shrink: 0;
}
.view-toggle-btn {
    padding: 7px 16px;
    border: none;
    background: var(--surface);
    color: var(--text-secondary);
    font-family: inherit;
    font-size: .83rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: background .15s, color .15s;
    border-right: 1.5px solid var(--border);
}
.view-toggle-btn:last-child { border-right: none; }
.view-toggle-btn:hover { background: var(--bg); color: var(--text); }
.view-toggle-btn.active { background: var(--accent); color: #fff; }
</style>
@endpush

@push('scripts')
<script>
function setView(type) {
    document.getElementById('invoiceView').style.display = type === 'invoice' ? '' : 'none';
    document.getElementById('itemView').style.display    = type === 'item'    ? '' : 'none';
    document.getElementById('btnInvoiceView').classList.toggle('active', type === 'invoice');
    document.getElementById('btnItemView').classList.toggle('active',    type === 'item');
    localStorage.setItem('saleView', type);
}

function toggleItems(btn) {
    const td      = btn.closest('td');
    const preview = td.querySelector('.item-preview');
    const full    = td.querySelector('.item-full');
    const expanded = full.style.display !== 'none';
    preview.style.display = expanded ? 'inline' : 'none';
    full.style.display    = expanded ? 'none'   : 'inline';
    const count = btn.textContent.match(/\d+/)?.[0] ?? '';
    btn.textContent = expanded ? `+${count} আরো ▾` : '▴ কম দেখুন';
}

var savedView = localStorage.getItem('saleView') || 'invoice';
setView(savedView);

/* ── AJAX live filter ── */
(function () {
    var card = document.getElementById('salesCard');
    if (!card || card._ajaxBound) return;
    card._ajaxBound = true;

    var form     = document.getElementById('salesFilterForm');
    var input    = document.getElementById('salesSearchInput');
    var status   = document.getElementById('salesStatusSelect');
    var dateFrom = document.getElementById('salesDateFrom');
    var dateTo   = document.getElementById('salesDateTo');
    var results  = document.getElementById('salesResults');
    var clearBtn = document.getElementById('salesClearBtn');

    function syncClearBtn() {
        var active = input.value.trim() || status.value || dateFrom.value || dateTo.value;
        clearBtn.style.display = active ? '' : 'none';
    }

    function buildUrl() {
        var params = new URLSearchParams();
        if (input.value.trim()) params.set('search', input.value.trim());
        if (status.value) params.set('status', status.value);
        if (dateFrom.value) params.set('date_from', dateFrom.value);
        if (dateTo.value)   params.set('date_to', dateTo.value);
        var qs = params.toString();
        return form.action + (qs ? '?' + qs : '');
    }

    function load(url, opts) {
        opts = opts || {};
        results.style.opacity = '.5';
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.text(); })
            .then(function (html) {
                results.innerHTML = html;
                results.style.opacity = '1';
                syncClearBtn();
                setView(localStorage.getItem('saleView') || 'invoice');
                if (opts.keepFocus && document.activeElement !== input) {
                    input.focus();
                    var v = input.value; input.value = ''; input.value = v;
                }
            })
            .catch(function () { results.style.opacity = '1'; window.location = url; });
    }

    var t;
    input.addEventListener('input', function () {
        clearTimeout(t);
        t = setTimeout(function () { load(buildUrl(), { keepFocus: true }); }, 300);
    });

    status.addEventListener('change', function () { load(buildUrl()); });
    dateFrom.addEventListener('change', function () { load(buildUrl()); });
    dateTo.addEventListener('change', function () { load(buildUrl()); });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        load(buildUrl());
    });

    card.addEventListener('click', function (e) {
        var link = e.target.closest('a');
        if (!link) return;
        var withinResults = results.contains(link);
        var isClear = link.id === 'salesClearBtn';
        var isPage  = withinResults && link.closest('.pagination-wrap');
        if (!isClear && !isPage) return;

        e.preventDefault();
        load(link.href);
        if (isClear) { input.value = ''; status.value = ''; dateFrom.value = ''; dateTo.value = ''; }
    });
})();
</script>
@endpush
@endsection
