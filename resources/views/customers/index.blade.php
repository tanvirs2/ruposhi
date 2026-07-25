@extends('layouts.app')
@section('title', 'কাস্টমার')
@section('page-title', 'কাস্টমার')

@section('content')
@include('partials.page-header', [
    'title'       => 'কাস্টমার তালিকা',
    'createRoute' => route('customers.create'),
    'createLabel' => 'নতুন কাস্টমার',
    'extraRoute'  => auth()->user()->canManageShop() ? route('contacts.import.form', 'customers') : null,
    'extraLabel'  => 'CSV ইমপোর্ট',
])

<div class="card" id="custCard">
    <div class="card-filter">
        <form method="GET" action="{{ route('customers.index') }}" class="filter-form" id="custFilterForm">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="search-box" style="flex:1;min-width:200px">
                <i class="fas fa-search"></i>
                <input type="text" name="search" value="{{ $search }}" placeholder="নাম, প্রোপ্রাইটর বা ফোন..."
                       autocomplete="off" id="custSearchInput">
            </div>
            <div class="form-group-field" style="min-width:180px">
                @include('partials.area-combobox', [
                    'acId' => 'custAreaSelect',
                    'acValue' => request('area_id'),
                    'acPlaceholder' => 'সকল এরিয়া (খুঁজুন)',
                    'acAllLabel' => 'সকল এরিয়া',
                ])
            </div>
            <button type="submit" class="btn btn-secondary"><i class="fas fa-search"></i> খুঁজুন</button>
            <a href="{{ route('customers.index') }}" class="btn btn-ghost" id="custClearBtn"
               style="{{ ($search || request('area_id') || $status !== 'active') ? '' : 'display:none' }}">
                <i class="fas fa-xmark"></i> পরিষ্কার
            </a>
            <button type="button" class="btn-export-print no-print" id="custPrintBtn">
                <i class="fas fa-print"></i> প্রিন্ট
            </button>
        </form>
    </div>

    <div id="custResults">
        @include('customers._results')
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var card  = document.getElementById('custCard');
    if (!card || card._ajaxBound) return;   // guard: don't re-bind on repeated turbo:load
    card._ajaxBound = true;

    var form    = document.getElementById('custFilterForm');
    var input   = document.getElementById('custSearchInput');
    var area    = document.getElementById('custAreaSelect');
    var results = document.getElementById('custResults');
    var clearBtn = document.getElementById('custClearBtn');
    var statusInput = form.querySelector('input[name="status"]');

    function syncClearBtn() {
        var active = input.value.trim() || area.value || statusInput.value !== 'active';
        clearBtn.style.display = active ? '' : 'none';
    }

    function buildUrl() {
        var params = new URLSearchParams();
        if (input.value.trim()) params.set('search', input.value.trim());
        if (area.value) params.set('area_id', area.value);
        if (statusInput.value) params.set('status', statusInput.value);
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
                    var v = input.value; input.value = ''; input.value = v; // caret to end
                }
            })
            .catch(function () { results.style.opacity = '1'; window.location = url; });
    }

    // প্রিন্ট — swap the paginated 15 rows for the FULL filtered list first,
    // print, then put the paginated view back
    document.getElementById('custPrintBtn').addEventListener('click', function () {
        var url = buildUrl();
        url += (url.indexOf('?') === -1 ? '?' : '&') + 'print=1';
        var snapshot = results.innerHTML;
        var restored = false;
        function restore() {
            if (restored) return;
            restored = true;
            results.innerHTML = snapshot;
        }
        results.style.opacity = '.5';
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.text(); })
            .then(function (html) {
                results.innerHTML = html;
                results.style.opacity = '1';
                window.addEventListener('afterprint', restore, { once: true });
                window.print();
                setTimeout(restore, 2000);   // fallback if afterprint never fires
            })
            .catch(function () { results.style.opacity = '1'; window.print(); });
    });

    // live search
    var t;
    input.addEventListener('input', function () {
        clearTimeout(t);
        t = setTimeout(function () { load(buildUrl(), { keepFocus: true }); }, 300);
    });

    // area dropdown
    area.addEventListener('change', function () { load(buildUrl()); });

    // form submit (Enter key / খুঁজুন button)
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        load(buildUrl());
    });

    // status chips + পরিষ্কার link + pagination — all live inside #custCard,
    // delegate so re-rendered content (chips/pagination) stays wired
    card.addEventListener('click', function (e) {
        var link = e.target.closest('a');
        if (!link) return;
        var withinResults = results.contains(link);
        var isChip   = link.hasAttribute('data-ajax-filter');
        var isClear  = link.id === 'custClearBtn';
        var isPage   = withinResults && link.closest('.pagination-wrap');
        if (!isChip && !isClear && !isPage) return;

        e.preventDefault();
        var url = link.href;
        load(url);
        if (isChip) { statusInput.value = new URL(url, window.location.origin).searchParams.get('status') || 'active'; }
        if (isClear) {
            input.value = ''; area.value = ''; statusInput.value = 'active';
            var acs = card.querySelector('.area-combobox .ac-search'); if (acs) acs.value = '';
        }
    });
})();
</script>
@endpush
