@extends('layouts.app')
@section('title', 'স্টক তথ্য')
@section('page-title', 'স্টক তথ্য')

@section('content')
@include('partials.page-header', ['title' => 'সকল স্টক', 'createRoute' => null])

<div class="card" id="stockCard">
    <div class="card-filter">
        <form method="GET" class="filter-form" id="stockFilterForm" action="{{ route('stock.index') }}" style="align-items:center">
            <div class="search-box"><i class="fas fa-search"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="আইটেমের নাম..." autocomplete="off" id="stockSearchInput">
            </div>
            <div style="display:flex;align-items:center;gap:6px">
                <i class="fas fa-calendar-day" style="color:#94a3b8"></i>
                <input type="date" name="date" value="{{ $filterDate }}" id="stockDateInput"
                    style="height:38px;padding:0 10px;border:1.5px solid var(--border);border-radius:6px;font-family:inherit;font-size:.85rem">
            </div>
            {{-- Stock updated_date filter --}}
            <div style="display:flex;align-items:center;gap:6px">
                <i class="fas fa-rotate" style="color:#0d9488"></i>
                <input type="date" name="updated_date" value="{{ $updatedDate }}" id="stockUpdatedDateInput"
                    title="এই তারিখে স্টক আপডেট হয়েছে এমন পণ্য"
                    style="height:38px;padding:0 10px;border:1.5px solid {{ $updatedDate ? '#0d9488' : 'var(--border)' }};border-radius:6px;font-family:inherit;font-size:.85rem;{{ $updatedDate ? 'color:#0d9488;font-weight:600' : '' }}">
            </div>
            <button type="submit" class="btn btn-secondary">খুঁজুন</button>
            {{-- Quick: today's stock updates --}}
            <a href="{{ route('stock.index', array_merge(request()->except('updated_date', 'page'), ['updated_date' => now()->toDateString()])) }}"
               class="btn btn-ghost" id="stockTodayUpdateBtn"
               style="color:#0d9488;border-color:#0d9488;white-space:nowrap;{{ $updatedDate !== now()->toDateString() ? '' : 'display:none' }}">
                <i class="fas fa-rotate"></i> আজ আপডেট
            </a>
            {{-- Quick: full stock list (no updated_date filter) --}}
            <a href="{{ route('stock.index', request()->except('updated_date', 'page')) }}"
               class="btn btn-ghost" id="stockAllBtn"
               style="white-space:nowrap;{{ $updatedDate ? '' : 'display:none' }}">
                <i class="fas fa-list"></i> সব স্টক
            </a>
            <a href="{{ route('stock.index') }}" class="btn btn-ghost" id="stockClearBtn"
               style="{{ (request('search') || request('date') || request('updated_date')) ? '' : 'display:none' }}">পরিষ্কার</a>
            <button type="button" class="btn-export-print no-print" onclick="window.print()">
                <i class="fas fa-print"></i> প্রিন্ট
            </button>
        </form>
    </div>
    <div id="stockResults">
        @include('stock._results')
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var card = document.getElementById('stockCard');
    if (!card || card._ajaxBound) return;
    card._ajaxBound = true;

    var form        = document.getElementById('stockFilterForm');
    var input        = document.getElementById('stockSearchInput');
    var dateInput    = document.getElementById('stockDateInput');
    var updatedInput = document.getElementById('stockUpdatedDateInput');
    var results      = document.getElementById('stockResults');
    var clearBtn     = document.getElementById('stockClearBtn');
    var todayBtn     = document.getElementById('stockTodayUpdateBtn');
    var allBtn       = document.getElementById('stockAllBtn');

    var today = "{{ now()->toDateString() }}";

    function syncClearBtn() {
        var active = input.value.trim()
            || dateInput.value !== today
            || updatedInput.value;
        clearBtn.style.display = active ? '' : 'none';
        if (todayBtn) todayBtn.style.display = (updatedInput.value !== today) ? '' : 'none';
        if (allBtn)   allBtn.style.display   = updatedInput.value ? '' : 'none';
    }

    function buildUrl() {
        var params = new URLSearchParams();
        if (input.value.trim()) params.set('search', input.value.trim());
        if (dateInput.value    && dateInput.value !== today) params.set('date', dateInput.value);
        if (updatedInput.value) params.set('updated_date', updatedInput.value);
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

    dateInput.addEventListener('change', function () { load(buildUrl()); });
    dateInput.addEventListener('input',  function () { load(buildUrl()); });
    updatedInput.addEventListener('change', function () { load(buildUrl()); });
    updatedInput.addEventListener('input',  function () { load(buildUrl()); });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        load(buildUrl());
    });

    card.addEventListener('click', function (e) {
        var link = e.target.closest('a');
        if (!link) return;
        var withinResults = results.contains(link);
        var isClear  = link.id === 'stockClearBtn';
        var isToday  = link.id === 'stockTodayUpdateBtn';
        var isAll    = link.id === 'stockAllBtn';
        var isPage   = withinResults && link.closest('.pagination-wrap');
        if (!isClear && !isToday && !isAll && !isPage) return;

        e.preventDefault();
        if (isClear) { input.value = ''; dateInput.value = today; updatedInput.value = ''; }
        if (isToday) { updatedInput.value = today; }
        if (isAll)   { updatedInput.value = ''; }
        load(isPage ? link.href : buildUrl());
    });
})();
</script>
@endpush

@push('styles')
<meta name="turbo-cache-control" content="no-cache">
<style>
.stock-updated-today { background: #f0fdfa !important; }
.badge-new-stock {
    display: inline-block;
    margin-left: 6px;
    background: #0d9488;
    color: #fff;
    font-size: .65rem;
    font-weight: 700;
    padding: 1px 7px;
    border-radius: 999px;
    letter-spacing: .04em;
    vertical-align: middle;
}
</style>
@endpush
