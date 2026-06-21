@extends('layouts.app')
@section('title', 'পণ্য গ্রহণ তালিকা')
@section('page-title', 'পণ্য গ্রহণ')

@section('content')
@include('partials.page-header', ['title' => 'গ্রহণ তালিকা', 'createRoute' => route('purchases.create'), 'createLabel' => 'নতুন গ্রহণ'])
@if(auth()->user()->canManageShop() && (($pendingDeleteCount ?? 0) + ($pendingEditCount ?? 0)) > 0)
<div style="background:#fff7ed;border:1.5px solid #fbbf24;border-radius:10px;padding:10px 18px;
            margin-bottom:12px;display:flex;align-items:center;gap:16px;font-size:.88rem;flex-wrap:wrap">
    <i class="fas fa-clock" style="color:#d97706;font-size:1rem"></i>
    @if(($pendingDeleteCount ?? 0) > 0)
        <span style="color:#92400e;font-weight:600"><i class="fas fa-trash" style="color:#ef4444"></i> {{ $pendingDeleteCount }}টি ডিলিট অনুমোদন অপেক্ষায়</span>
    @endif
    @if(($pendingEditCount ?? 0) > 0)
        <span style="color:#92400e;font-weight:600"><i class="fas fa-pencil" style="color:#d97706"></i> {{ $pendingEditCount }}টি সংশোধন অনুমোদন অপেক্ষায়</span>
    @endif
    <span style="color:#92400e;font-size:.82rem">নিচের তালিকায় দেখুন।</span>
</div>
@endif

<div class="card" id="purchasesCard">
    <div class="card-filter" style="flex-wrap:wrap;gap:8px">
        <form method="GET" class="filter-form" id="purchasesFilterForm" action="{{ route('purchases.index') }}" style="flex:1;min-width:0">
            <div class="search-box"><i class="fas fa-search"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="সরবরাহকারী বা নম্বর..." autocomplete="off" id="purchasesSearchInput">
            </div>
            <input type="date" name="date_from" class="form-select" value="{{ $dateFrom }}" title="শুরুর তারিখ" style="width:145px" id="purchasesDateFrom">
            <input type="date" name="date_to"   class="form-select" value="{{ $dateTo }}"   title="শেষ তারিখ"  style="width:145px" id="purchasesDateTo">
            @include('partials.date-range-buttons', ['fromName'=>'date_from','toName'=>'date_to','formClass'=>'#purchasesFilterForm'])
            <button type="submit" class="btn btn-secondary">ফিল্টার</button>
            <a href="{{ route('purchases.index') }}" class="btn btn-outline" id="purchasesClearBtn" title="ক্লিয়ার করুন"
               style="{{ ($dateFrom || $dateTo || request('search')) ? '' : 'display:none' }}"><i class="fas fa-xmark"></i></a>
        </form>

        {{-- View toggle --}}
        <div class="view-toggle-group">
            <button id="btnInvoiceView" class="view-toggle-btn active" onclick="setView('invoice')">
                <i class="fas fa-file-invoice"></i> ইনভয়েস ভিউ
            </button>
            <button id="btnItemView" class="view-toggle-btn" onclick="setView('item')">
                <i class="fas fa-list"></i> আইটেম ভিউ
            </button>
            @if(auth()->user()->canManageShop())
            <button id="btnUserView" class="view-toggle-btn" onclick="setView('user')">
                <i class="fas fa-users"></i> ইউজার ভিউ
            </button>
            @endif
        </div>
    </div>

    <div id="purchasesResults">
        @include('purchases._results')
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
.view-toggle-btn.active {
    background: var(--accent);
    color: #fff;
}
</style>
@endpush

@push('scripts')
<script>
function setView(type) {
    document.getElementById('invoiceView').style.display = type === 'invoice' ? '' : 'none';
    document.getElementById('itemView').style.display    = type === 'item'    ? '' : 'none';
    var uv = document.getElementById('purchaseUserView');
    if (uv) uv.style.display = type === 'user' ? '' : 'none';
    document.getElementById('btnInvoiceView').classList.toggle('active', type === 'invoice');
    document.getElementById('btnItemView').classList.toggle('active',    type === 'item');
    var bu = document.getElementById('btnUserView');
    if (bu) bu.classList.toggle('active', type === 'user');
    localStorage.setItem('purchaseView', type);
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

// Restore last used view
var savedView = localStorage.getItem('purchaseView') || 'invoice';
setView(savedView);

/* ── AJAX live filter ── */
(function () {
    var card = document.getElementById('purchasesCard');
    if (!card || card._ajaxBound) return;
    card._ajaxBound = true;

    var form     = document.getElementById('purchasesFilterForm');
    var input    = document.getElementById('purchasesSearchInput');
    var dateFrom = document.getElementById('purchasesDateFrom');
    var dateTo   = document.getElementById('purchasesDateTo');
    var results  = document.getElementById('purchasesResults');
    var clearBtn = document.getElementById('purchasesClearBtn');

    function syncClearBtn() {
        var active = input.value.trim() || dateFrom.value || dateTo.value;
        clearBtn.style.display = active ? '' : 'none';
    }

    function buildUrl() {
        var params = new URLSearchParams();
        if (input.value.trim()) params.set('search', input.value.trim());
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
                setView(localStorage.getItem('purchaseView') || 'invoice');
                // reset to invoice if user view was active but now empty

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
        var isClear = link.id === 'purchasesClearBtn';
        var isPage  = withinResults && link.closest('.pagination-wrap');
        if (!isClear && !isPage) return;

        e.preventDefault();
        load(link.href);
        if (isClear) { input.value = ''; dateFrom.value = ''; dateTo.value = ''; }
    });
})();
</script>
@endpush
@endsection
