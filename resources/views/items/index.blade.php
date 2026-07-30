@extends('layouts.app')
@section('title', 'আইটেমস')
@section('page-title', 'আইটেমস')

@section('content')
@include('partials.page-header', ['title' => 'আইটেম তালিকা', 'createRoute' => route('items.create'), 'createLabel' => 'নতুন আইটেম'])

<div class="card" id="itemsCard">
    <div class="card-filter">
        <form method="GET" class="filter-form" id="itemsFilterForm" action="{{ route('items.index') }}">
            <div class="search-box"><i class="fas fa-search"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="নাম বা কোড..." autocomplete="off" id="itemsSearchInput">
            </div>
            <select name="category_id" class="form-select" id="itemsCategorySelect">
                <option value="">সব ক্যাটাগরি</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>{{ $cat->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-secondary">ফিল্টার</button>
            <a href="{{ route('items.index') }}" class="btn btn-ghost" id="itemsClearBtn"
               style="{{ request()->hasAny(['search','category_id']) ? '' : 'display:none' }}">পরিষ্কার</a>
            <button type="button" class="btn-export-print no-print" id="itemsPrintBtn">
                <i class="fas fa-print"></i> প্রিন্ট
            </button>
        </form>
    </div>
    <div id="itemsResults">
        @include('items._results')
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var card = document.getElementById('itemsCard');
    if (!card || card._ajaxBound) return;
    card._ajaxBound = true;

    var form     = document.getElementById('itemsFilterForm');
    var input    = document.getElementById('itemsSearchInput');
    var category = document.getElementById('itemsCategorySelect');
    var results  = document.getElementById('itemsResults');
    var clearBtn = document.getElementById('itemsClearBtn');

    function syncClearBtn() {
        var active = input.value.trim() || category.value;
        clearBtn.style.display = active ? '' : 'none';
    }

    function buildUrl() {
        var params = new URLSearchParams();
        if (input.value.trim()) params.set('search', input.value.trim());
        if (category.value) params.set('category_id', category.value);
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

    // প্রিন্ট — swap the paginated rows for the FULL filtered list first,
    // print, then put the paginated view back
    document.getElementById('itemsPrintBtn').addEventListener('click', function () {
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
                // Wait for layout/paint (and self-hosted fonts) before opening the
                // print dialog — calling window.print() right after a large innerHTML
                // swap can catch Chrome mid-layout and produce a blank print preview.
                var doPrint = function () {
                    requestAnimationFrame(function () {
                        requestAnimationFrame(function () {
                            window.print();
                            setTimeout(restore, 2000);
                        });
                    });
                };
                if (document.fonts && document.fonts.ready) { document.fonts.ready.then(doPrint); }
                else { doPrint(); }
            })
            .catch(function () { results.style.opacity = '1'; window.print(); });
    });

    category.addEventListener('change', function () { load(buildUrl()); });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        load(buildUrl());
    });

    card.addEventListener('click', function (e) {
        var link = e.target.closest('a');
        if (!link) return;
        var withinResults = results.contains(link);
        var isClear = link.id === 'itemsClearBtn';
        var isPage  = withinResults && link.closest('.pagination-wrap');
        if (!isClear && !isPage) return;

        e.preventDefault();
        load(link.href);
        if (isClear) { input.value = ''; category.value = ''; }
    });
})();
</script>
@endpush
