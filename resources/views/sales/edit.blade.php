@extends('layouts.app')
@section('title', 'বিক্রয় সংশোধন — #' . str_pad($sale->id, 6, '0', STR_PAD_LEFT))
@section('page-title', 'বিক্রয় সংশোধন')

@section('content')
<form method="POST" action="{{ route('sales.update', $sale) }}" id="saleForm">
@csrf @method('PUT')
<div class="pos-grid">

    {{-- Left: Items --}}
    <div class="pos-left">
        <div class="card pos-search-card" style="margin-bottom:16px">
            <div class="card-header"><h3><i class="fas fa-search"></i> আইটেম যোগ করুন</h3></div>
            <div style="padding:16px">
                <div class="search-box" style="margin-bottom:12px">
                    <i class="fas fa-search"></i>
                    <input type="text" id="itemSearch" placeholder="আইটেমের নাম লিখুন...">
                </div>
                <div id="itemSuggestions" class="suggestions-list"></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
                <h3><i class="fas fa-list"></i> নির্বাচিত আইটেম</h3>
                <button type="button" id="profitToggleBtn" onclick="toggleProfitCols()" class="btn btn-ghost" style="font-size:.8rem;padding:5px 12px;gap:6px">
                    <i class="fas fa-eye-slash" id="profitToggleIcon"></i>
                    <span id="profitToggleText">লাভ দেখুন</span>
                </button>
            </div>
            <div class="table-wrap">
                <table class="data-table" id="itemsTable">
                    <thead>
                        <tr>
                            <th>আইটেম</th>
                            <th>পরিমাণ</th>
                            <th class="col-secret" style="display:none">ক্রয়মূল্য</th>
                            <th>বিক্রয়মূল্য <small style="font-weight:400;color:#94a3b8">(পরিবর্তনযোগ্য)</small></th>
                            <th class="col-secret" style="display:none">লাভ/বস্তা</th>
                            <th>মোট</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody">
                        <tr><td colspan="5" class="empty-row">কোনো আইটেম যোগ করা হয়নি</td></tr>
                    </tbody>
                    <tfoot id="itemsFoot" style="display:none">
                        <tr class="tfoot-summary">
                            <td style="text-align:right;font-weight:700;padding-right:12px">সর্বমোট</td>
                            <td class="tc" id="footQty" style="font-weight:800"></td>
                            <td class="col-secret" style="display:none"></td>
                            <td></td>
                            <td class="col-secret" style="display:none"></td>
                            <td class="tr" id="footTotal" style="font-weight:800"></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- Right: Summary --}}
    <div class="pos-right">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-pen-to-square"></i> বিক্রয় সংশোধন</h3>
            </div>
            <div style="padding:20px;display:flex;flex-direction:column;gap:14px">

                {{-- Edited notice --}}
                <div style="padding:10px 14px;background:#fef9c3;border:1px solid #fde68a;border-radius:8px;font-size:.82rem;color:#92400e;font-weight:600">
                    <i class="fas fa-triangle-exclamation"></i>
                    আপনি চালান <strong>#{{ str_pad($sale->id, 6, '0', STR_PAD_LEFT) }}</strong> সংশোধন করছেন।
                    স্টক ও কাস্টমারের বাকী স্বয়ংক্রিয়ভাবে আপডেট হবে।
                </div>

                <div class="form-group-field">
                    <label>কাস্টমার</label>
                    <input type="hidden" name="customer_id" id="customerIdInput" value="{{ $sale->customer_id }}">
                    <input type="text" id="customerSearch" autocomplete="off" style="width:100%"
                        value="{{ $sale->customer?->name ?? '' }}"
                        placeholder="নাম লিখুন বা খুঁজুন...">
                    <div id="customerSelected" style="display:none;margin-top:6px;font-size:.85rem"></div>
                </div>

                <div class="form-group-field">
                    <label>তারিখ <span class="req">*</span></label>
                    <input type="date" name="sale_date" value="{{ $sale->sale_date->format('Y-m-d') }}" required>
                </div>

                <div class="form-group-field">
                    <label>স্ট্যাটাস</label>
                    <select name="status" class="form-select">
                        <option value="completed" @selected($sale->status === 'completed')>সম্পন্ন</option>
                        <option value="pending"   @selected($sale->status === 'pending')>মুলতুবি</option>
                    </select>
                </div>

                <hr style="border:none;border-top:1px solid var(--border)">

                {{-- Previous due row --}}
                <div id="prevDueRow" style="display:none;flex-direction:column;gap:10px;padding:12px 14px;background:#fff7ed;border:1px solid #fed7aa;border-radius:8px">
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <span style="font-size:.83rem;font-weight:600;color:#92400e">
                            <i class="fas fa-clock-rotate-left"></i> পূর্বের বাকী
                        </span>
                        <span style="font-size:1rem;font-weight:800;color:#b45309" id="prevDueDisplay">৳ 0</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px">
                        <span style="font-size:.8rem;font-weight:600;color:#92400e;white-space:nowrap">এখন দেবেন (৳):</span>
                        <input type="text" inputmode="decimal" id="prevDuePayInput" value="0" placeholder="0"
                            oninput="onPrevDuePayChange()"
                            style="flex:1;padding:7px 10px;border:1.5px solid #fbbf24;border-radius:6px;
                                   font-size:.92rem;font-weight:700;color:#92400e;background:#fff;min-width:0">
                        <button type="button" onclick="setFullPrevDuePay()"
                            style="padding:7px 13px;border-radius:6px;border:none;background:#b45309;
                                   color:#fff;font-size:.78rem;font-weight:700;cursor:pointer;white-space:nowrap;flex-shrink:0">
                            সম্পূর্ণ
                        </button>
                    </div>
                </div>

                <div class="summary-row">
                    <span>মোট বিক্রয়:</span>
                    <span style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;justify-content:flex-end">
                        <span id="totalDisplay">৳ 0.00</span>
                        <button type="button" id="discountToggleBtn" onclick="toggleField('discount')"
                            class="cost-toggle-btn" title="ছাড় যোগ করুন">+ ছাড়</button>
                        <button type="button" id="extraCostToggleBtn" onclick="toggleExtraCosts()"
                            class="cost-toggle-btn" title="অতিরিক্ত খরচ যোগ করুন">+ খরচ</button>
                    </span>
                </div>
                <div id="discountRow" style="display:none">
                    <div class="form-group-field" style="margin-bottom:0">
                        <label>ছাড় (৳)</label>
                        <input type="text" inputmode="decimal" name="discount" id="discountInput"
                            value="{{ $sale->discount }}">
                    </div>
                </div>
                {{-- Categorised extra costs --}}
                <div id="extraRow" style="display:none">
                    <div style="border:1.5px solid #e2e8f0;border-radius:8px;padding:12px 12px 8px;background:#fafafa">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                            <span style="font-size:.82rem;font-weight:700;color:#475569">
                                <i class="fas fa-coins" style="color:#7c3aed"></i> অতিরিক্ত খরচ
                            </span>
                            <a href="{{ route('extra-cost-categories.index') }}" target="_blank"
                               style="font-size:.72rem;color:var(--accent);text-decoration:none">
                                <i class="fas fa-gear"></i> ক্যাটাগরি ম্যানেজ
                            </a>
                        </div>
                        <div id="extraCostRows"></div>
                        <button type="button" onclick="addExtraCostRow()"
                            style="margin-top:6px;width:100%;padding:7px;border:1.5px dashed #c4b5fd;
                                   border-radius:6px;background:transparent;color:#7c3aed;
                                   font-size:.8rem;font-weight:600;cursor:pointer">
                            <i class="fas fa-plus"></i> আরেকটি খরচ যোগ করুন
                        </button>
                    </div>
                </div>

                <div class="summary-row summary-total"><span>নেট মোট:</span><span id="netDisplay">৳ 0.00</span></div>

                <div class="form-group-field">
                    <label>পরিশোধ (৳) <span class="req">*</span></label>
                    <div style="display:flex;gap:8px">
                        <input type="text" inputmode="decimal" name="paid_amount" id="paidInput"
                            value="{{ $sale->paid_amount }}" style="flex:1">
                        <button type="button" onclick="setFullPay()" title="সম্পূর্ণ পরিশোধ"
                            style="padding:0 12px;border-radius:var(--radius-sm);border:1.5px solid var(--accent);
                                   background:var(--accent-light);color:var(--accent);font-size:.78rem;
                                   font-weight:700;cursor:pointer;white-space:nowrap">
                            সম্পূর্ণ
                        </button>
                    </div>
                    <div id="paidWords" style="display:none;margin-top:4px;font-size:.78rem;font-weight:600;color:var(--accent)"></div>
                    <div id="walkinWarning" style="display:none;margin-top:6px;padding:8px 12px;
                        background:#fee2e2;border:1px solid #fecaca;border-radius:8px;
                        font-size:.82rem;color:#991b1b;font-weight:600">
                        <i class="fas fa-circle-exclamation"></i>
                        কাস্টমার ছাড়া বিক্রয়ে সম্পূর্ণ পরিশোধ আবশ্যক!
                    </div>
                </div>

                <div class="summary-row" style="color:#ef4444"><span>বকেয়া:</span><span id="dueDisplay">৳ 0.00</span></div>

                <div class="form-group-field">
                    <label><i class="fas fa-credit-card" style="color:var(--accent)"></i> পরিশোধ মোড</label>
                    <select name="payment_method" id="paymentMethod" class="form-select">
                        @foreach($paymentMethods as $group => $names)
                        <optgroup label="— {{ $group }} —">
                            @foreach($names as $name)
                            <option value="{{ $name }}" @selected($sale->payment_method === $name)>
                                {{ $name }}
                            </option>
                            @endforeach
                        </optgroup>
                        @endforeach
                    </select>
                </div>

                <hr style="border:none;border-top:1px solid var(--border)">

                {{-- Profit panel --}}
                <div class="profit-panel" id="profitPanel" style="display:none">
                    <div class="profit-panel-header"><i class="fas fa-chart-line"></i> লাভের বিবরণ</div>
                    <div class="profit-panel-body">
                        <div class="profit-row"><span>মোট খরচ (ক্রয়):</span><span id="costDisplay">৳ 0.00</span></div>
                        <div class="profit-row"><span>মোট আয় (বিক্রয়):</span><span id="revenueDisplay">৳ 0.00</span></div>
                        <div class="profit-row profit-net"><span>আনুমানিক লাভ:</span><span id="profitDisplay">৳ 0.00</span></div>
                        <div style="margin-top:8px;text-align:center">
                            <span class="margin-badge" id="marginBadge">—</span>
                            <span id="marginPct" style="font-size:.8rem;color:#64748b;margin-left:6px"></span>
                        </div>
                    </div>
                </div>

                <div class="form-group-field">
                    <label>মন্তব্য</label>
                    <textarea name="notes" rows="2">{{ $sale->notes }}</textarea>
                </div>

                {{-- Edit note (required) --}}
                <div class="form-group-field">
                    <label style="color:#92400e">
                        <i class="fas fa-pen-to-square"></i> সংশোধনের কারণ
                        <button type="button" class="info-btn" data-info="কেন এই বিক্রয় সংশোধন করছেন তা লিখুন। এটি চালানে দেখাবে।">i</button>
                    </label>
                    <textarea name="edit_note" rows="2" placeholder="যেমন: ভুল আইটেম দেওয়া হয়েছিল, পরিমাণ ভুল ছিল..."
                        style="border-color:#fde68a;background:#fefce8">{{ $sale->edit_note }}</textarea>
                </div>

                <div style="display:flex;gap:10px">
                    <a href="{{ route('sales.show', $sale) }}" class="btn btn-ghost" style="flex:1;justify-content:center">
                        <i class="fas fa-xmark"></i> বাতিল
                    </a>
                    <button type="submit" class="btn btn-primary" style="flex:2;justify-content:center;padding:14px">
                        <i class="fas fa-floppy-disk"></i> সংশোধন সংরক্ষণ করুন
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
</form>

@push('styles')
<style>
.profit-good  { color: #16a34a; font-weight: 600; }
.profit-med   { color: #d97706; font-weight: 600; }
.profit-poor  { color: #dc2626; font-weight: 600; }
.margin-badge { display:inline-block;padding:3px 12px;border-radius:20px;font-size:.8rem;font-weight:600;letter-spacing:.03em; }
.margin-badge.good { background:#dcfce7;color:#15803d; }
.margin-badge.med  { background:#fef9c3;color:#92400e; }
.margin-badge.poor { background:#fee2e2;color:#b91c1c; }
.profit-panel { border:1px solid var(--border);border-radius:10px;overflow:hidden; }
.profit-panel-header { background:var(--bg);padding:10px 14px;font-size:.85rem;font-weight:600;color:var(--text-secondary);display:flex;align-items:center;gap:6px; }
.profit-panel-body { padding:12px 14px;display:flex;flex-direction:column;gap:8px; }
.profit-row { display:flex;justify-content:space-between;font-size:.88rem;color:var(--text-secondary); }
.profit-row.profit-net { font-size:.95rem;font-weight:700;color:var(--text);border-top:1px dashed var(--border);padding-top:8px;margin-top:2px; }
.suggestion-item .cost-hint { font-size:.75rem;color:#94a3b8;margin-top:1px; }
.cost-toggle-btn {
    font-size:.72rem; padding:2px 8px; border-radius:20px;
    border:1.5px dashed #94a3b8; background:transparent; color:#94a3b8;
    cursor:pointer; white-space:nowrap; font-weight:600; line-height:1.5; transition:all .2s;
}
.cost-toggle-btn:hover { color:var(--accent); border-color:var(--accent); }
.cost-toggle-btn.active { color:#ef4444; border-color:#fca5a5; border-style:solid; }
</style>
@endpush

@push('scripts')
<script>
// ── Pre-loaded sale data ─────────────────────────────────────
@php
$existingItems = $sale->items->map(fn($si) => [
    'id'           => $si->item_id,
    'name'         => $si->item->name ?? '?',
    'cost'         => floatval($si->item->purchase_price ?? 0),
    'price'        => floatval($si->price),
    'defaultPrice' => floatval($si->price),
    'qty'          => floatval($si->quantity),
    'stock'        => floatval($si->item->stock?->quantity ?? 0) + floatval($si->quantity),
    // add back the sold qty so stock shows correctly
]);
@endphp
var existingCartData = @json($existingItems);

// ── Floating dropdown helper ─────────────────────────────────
function makeFloatingDropdown(inputEl, dropEl) {
    document.body.appendChild(dropEl);
    dropEl.style.cssText = `position:fixed;display:none;z-index:9999;background:#fff;border:1px solid #e2e8f0;border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.12);overflow-y:auto;max-height:240px;`;
    function position() {
        const r = inputEl.getBoundingClientRect();
        dropEl.style.top   = (r.bottom + 4) + 'px';
        dropEl.style.left  = r.left + 'px';
        dropEl.style.width = r.width + 'px';
    }
    function show(html) { dropEl.innerHTML = html; position(); dropEl.style.display = 'block'; }
    function hide() { dropEl.style.display = 'none'; dropEl.innerHTML = ''; }
    window.addEventListener('scroll', position, true);
    window.addEventListener('resize', position);
    document.addEventListener('click', function(e) {
        if (!inputEl.contains(e.target) && !dropEl.contains(e.target)) hide();
    });
    return { show, hide, position };
}

// Margin above which a row is flagged "অতিরিক্ত!". Must match the same
// constant in sales/create.blade.php — new sale and edit should agree.
// (var, not const — Turbo re-evaluates body scripts on every visit.)
var EXCESS_PCT = 15;

// ── Customer search ──────────────────────────────────────────
// allCustomers is a client-side cache, seeded with this sale's customer.
var allCustomers      = [];
@if(!empty($preCustomer))
allCustomers.push(@json($preCustomer));
@endif
var customerSearch  = document.getElementById('customerSearch');
var customerIdInput = document.getElementById('customerIdInput');
var customerSelected= document.getElementById('customerSelected');
var prevDueRow      = document.getElementById('prevDueRow');
var prevDueDisplay  = document.getElementById('prevDueDisplay');
var customerDrop    = document.createElement('div');
var cDrop           = makeFloatingDropdown(customerSearch, customerDrop);
var currentPrevDue = 0;
var prevDuePay     = 0;

var _custSearchTimer = null;
customerSearch.addEventListener('input', function() {
    const q = this.value.trim();
    if (!q) {
        cDrop.hide();
        customerIdInput.value = '';
        customerSelected.style.display = 'none';
        prevDueRow.style.display = 'none';
        resetPrevDuePay();
        currentPrevDue = 0;
        document.getElementById('walkinWarning').style.display = 'none';
        return;
    }
    cDrop.show(`<div class="suggestion-item" style="color:#94a3b8">খুঁজছি…</div>`);
    clearTimeout(_custSearchTimer);
    _custSearchTimer = setTimeout(() => fetchCustomers(q), 250);
});

async function fetchCustomers(q) {
    try {
        const res = await fetch(`{{ route('customers.search') }}?q=${encodeURIComponent(q)}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        });
        const matches = await res.json();
        matches.forEach(c => { if (!allCustomers.find(x => x.id === c.id)) allCustomers.push(c); });
        renderCustomerMatches(matches);
    } catch (_) {
        cDrop.show(`<div class="suggestion-item" style="color:#94a3b8">খুঁজতে সমস্যা হয়েছে</div>`);
    }
}

function renderCustomerMatches(matches) {
    const html = matches.map(c => `
        <div class="suggestion-item" onclick="selectCustomer(${c.id})">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:8px">
                <span>
                    ${c.proprietor
                        ? `<strong style="font-size:.92rem">${c.proprietor}</strong><span style="font-size:.78rem;color:#64748b;margin-left:6px">${c.name}</span>`
                        : `<strong style="font-size:.92rem">${c.name}</strong>`}
                </span>
                ${parseFloat(c.due_amount) > 0
                    ? `<span style="font-size:.78rem;font-weight:700;color:#dc2626;background:#fee2e2;padding:2px 8px;border-radius:20px;white-space:nowrap;flex-shrink:0">বাকী: ৳${parseFloat(c.due_amount).toLocaleString()}</span>`
                    : parseFloat(c.due_amount) < 0
                    ? `<span style="font-size:.78rem;font-weight:700;color:#1d4ed8;background:#eff6ff;padding:2px 8px;border-radius:20px;white-space:nowrap;flex-shrink:0">অগ্রিম: ৳${Math.abs(parseFloat(c.due_amount)).toLocaleString()}</span>`
                    : `<span style="font-size:.78rem;font-weight:700;color:#16a34a;background:#dcfce7;padding:2px 8px;border-radius:20px;white-space:nowrap;flex-shrink:0">বাকীমুক্ত ✓</span>`}
            </div>
            <span style="font-size:.76rem;color:#94a3b8;display:block;margin-top:2px">
                ${c.phone ? `📞 ${c.phone}` : ''}${c.phone && c.area?.name ? ' &nbsp;·&nbsp; ' : ''}${c.area?.name ? `📍 ${c.area.name}` : ''}
            </span>
        </div>
    `).join('') || `<div class="suggestion-item" style="color:#94a3b8">কোনো কাস্টমার পাওয়া যায়নি</div>`;
    cDrop.show(html);
}

function selectCustomer(id) {
    const c = allCustomers.find(x => x.id === id);
    if (!c) return;
    customerIdInput.value = c.id;
    customerSearch.value  = c.name;
    let html = `<div style="font-weight:700;color:#0d9488;font-size:.9rem">✓ ${c.name}</div>`;
    if (c.proprietor) html += `<div style="font-size:.8rem;color:#475569;margin-top:1px">প্রোঃ ${c.proprietor}</div>`;
    if (c.phone)      html += `<div style="font-size:.78rem;color:#94a3b8;margin-top:1px">📞 ${c.phone}</div>`;
    if (c.area?.name) html += `<div style="font-size:.78rem;color:#94a3b8;margin-top:1px">📍 ${c.area.name}</div>`;
    const due = parseFloat(c.due_amount) || 0;
    if (due > 0) {
        html += `<div style="margin-top:6px;padding:8px 12px;background:#fee2e2;border:1px solid #fecaca;border-radius:8px;display:flex;justify-content:space-between;align-items:center">
                    <span style="color:#991b1b;font-size:.83rem;font-weight:600"><i class="fas fa-triangle-exclamation"></i> আগের বাকী আছে</span>
                    <span style="color:#dc2626;font-size:1rem;font-weight:700">৳ ${due.toLocaleString('en', {minimumFractionDigits:2})}</span>
                 </div>`;
        prevDueDisplay.textContent = '৳ ' + due.toLocaleString('en', {minimumFractionDigits:2});
        prevDueRow.style.display = 'flex';
    } else if (due < 0) {
        html += `<div style="margin-top:6px;padding:8px 12px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;display:flex;justify-content:space-between;align-items:center">
                    <span style="color:#1d4ed8;font-size:.83rem;font-weight:600"><i class="fas fa-piggy-bank"></i> অগ্রিম পরিশোধ আছে</span>
                    <span style="color:#1d4ed8;font-size:1rem;font-weight:700">৳ ${Math.abs(due).toLocaleString('en', {minimumFractionDigits:2})}</span>
                 </div>`;
        prevDueRow.style.display = 'none';
        resetPrevDuePay();
    } else {
        html += `<div style="margin-top:6px;padding:6px 12px;background:#dcfce7;border:1px solid #bbf7d0;border-radius:8px;font-size:.8rem;color:#15803d;font-weight:600">✓ কোনো বাকী নেই</div>`;
        prevDueRow.style.display = 'none';
        resetPrevDuePay();
    }
    resetPrevDuePay();
    currentPrevDue = due;
    customerSelected.innerHTML = html;
    customerSelected.style.display = 'block';
    cDrop.hide();
}

// ── Items ────────────────────────────────────────────────────
var allItems   = @json($items);
var cart         = [];

var itemSearch  = document.getElementById('itemSearch');
var suggestions = document.getElementById('itemSuggestions');
var itemsBody   = document.getElementById('itemsBody');
var profitPanel = document.getElementById('profitPanel');

itemSearch.addEventListener('input', function() {
    const q = this.value.toLowerCase().trim();
    if (!q) { suggestions.innerHTML = ''; return; }
    const matches = allItems.filter(i => i.name.toLowerCase().includes(q));
    suggestions.innerHTML = matches.map(i => {
        const avail  = i.stock ? parseFloat(i.stock.quantity) : 0;
        const minQty = i.stock ? parseFloat(i.stock.min_quantity) : 0;
        const stockColor = avail <= 0 ? '#dc2626' : avail <= minQty ? '#d97706' : '#16a34a';
        const stockLabel = avail <= 0 ? '✗ স্টক নেই' : `স্টক: ${avail}`;
        const dimmed = avail <= 0 ? 'opacity:.55;cursor:not-allowed' : '';
        return `<div class="suggestion-item" onclick="addItem(${i.id})" style="${dimmed}">
            <div style="display:flex;justify-content:space-between;align-items:center">
                <strong>${i.name}</strong>
                <span style="font-size:.78rem;font-weight:700;color:${stockColor};background:${avail<=0?'#fee2e2':avail<=minQty?'#fef9c3':'#dcfce7'};padding:2px 8px;border-radius:20px">${stockLabel}</span>
            </div>
            <div style="display:flex;gap:12px;margin-top:2px">
                <span style="font-size:.8rem;color:#64748b">বিক্রয়: ৳${parseFloat(i.sale_price).toLocaleString()}</span>
                ${profitVisible ? `<span style="font-size:.8rem;color:#94a3b8">ক্রয়: ৳${parseFloat(i.purchase_price).toLocaleString()}</span>` : ''}
            </div>
        </div>`;
    }).join('') || '<div class="suggestion-item" style="color:#94a3b8">কোনো আইটেম পাওয়া যায়নি</div>';
});

function addItem(id) {
    const item  = allItems.find(i => i.id === id);
    if (!item) return;
    const avail = item.stock ? parseFloat(item.stock.quantity) : 0;
    const existing = cart.find(c => c.id === id);
    if (existing) {
        existing.qty++;
        if (existing.qty > avail) showStockToast(`⚠ "${item.name}" — স্টকে মাত্র ${avail} টি আছে।`, 'warn');
    } else {
        if (avail <= 0) showStockToast(`⚠ "${item.name}" — স্টকে কোনো পণ্য নেই।`, 'warn');
        cart.push({ id: item.id, name: item.name, cost: parseFloat(item.purchase_price),
                    price: parseFloat(item.sale_price), defaultPrice: parseFloat(item.sale_price),
                    qty: 1, stock: avail });
    }
    suggestions.innerHTML = ''; itemSearch.value = '';
    renderCart();
}

function removeItem(id) { cart = cart.filter(c => c.id !== id); renderCart(); }

function updateQty(id, val) {
    const item = cart.find(c => c.id === id); if (!item) return;
    item.qty = parseFloat(toEnglishDigits(val)) || 0;
    const badge = document.getElementById('stock-badge-' + id);
    if (badge) {
        if (item.qty > item.stock) { badge.textContent = '⚠ স্টক: ' + item.stock; badge.style.background='#fef9c3'; badge.style.color='#92400e'; }
        else                       { badge.textContent = 'স্টক: ' + item.stock;   badge.style.background='#dcfce7'; badge.style.color='#15803d'; }
    }
    updateRowTotal(id); updateSummary();
}

function updatePrice(id, val) {
    const item = cart.find(c => c.id === id);
    if (item) item.price = parseFloat(toEnglishDigits(val)) || 0;
    updateRowTotal(id); updateSummary();
}

function updateRowTotal(id) {
    const item = cart.find(c => c.id === id); if (!item) return;
    const cell = document.getElementById('row-total-' + id);
    if (cell) cell.textContent = '৳ ' + (item.qty * item.price).toFixed(0);

    // Live profit cell (price changes after render → update separately)
    const profitCell = document.getElementById('row-profit-' + id);
    if (profitCell) {
        const profitPerUnit = item.price - item.cost;
        profitCell.className     = `col-secret ${profitClass(profitPerUnit, item.cost)}`;
        profitCell.style.display = profitVisible ? '' : 'none';
        profitCell.textContent   = (profitPerUnit >= 0 ? '+' : '') + '৳' + profitPerUnit.toFixed(0);
    }

    // ── Loss warning per row ──────────────────────────────────
    const lossWarn = document.getElementById('loss-warn-' + id);
    if (lossWarn) lossWarn.style.display = (item.cost > 0 && item.price > 0 && item.price < item.cost) ? 'inline-flex' : 'none';

    // ── Excessive profit warning per row (>10% margin) ────────
    const excessWarn = document.getElementById('excess-warn-' + id);
    if (excessWarn) {
        const pct = item.cost > 0 ? (item.price - item.cost) / item.cost * 100 : 0;
        const isExcess = item.cost > 0 && item.price > 0 && pct > EXCESS_PCT;
        excessWarn.style.display = isExcess ? 'inline-flex' : 'none';
        if (isExcess) excessWarn.title = `লাভ: ${pct.toFixed(1)}%`;
    }
}

function profitClass(profit, cost) {
    if (cost <= 0) return 'profit-good';
    const pct = profit / cost * 100;
    return pct >= 8 ? 'profit-good' : pct >= 4 ? 'profit-med' : 'profit-poor';
}

var profitVisible = false;
function toggleProfitCols() {
    profitVisible = !profitVisible;
    document.querySelectorAll('.col-secret').forEach(el => { el.style.display = profitVisible ? '' : 'none'; });
    document.getElementById('profitToggleIcon').className = profitVisible ? 'fas fa-eye-slash' : 'fas fa-eye';
    document.getElementById('profitToggleText').textContent = profitVisible ? 'লাভ লুকান' : 'লাভ দেখুন';
    if (profitPanel) profitPanel.style.display = profitVisible && cart.length ? 'block' : 'none';
}

function emptyColspan() { return profitVisible ? 7 : 5; }

function renderCart() {
    const itemsFoot = document.getElementById('itemsFoot');
    if (!cart.length) {
        itemsBody.innerHTML = `<tr><td colspan="${emptyColspan()}" class="empty-row">কোনো আইটেম যোগ করা হয়নি</td></tr>`;
        if (profitPanel) profitPanel.style.display = 'none';
        itemsFoot.style.display = 'none';
        updateSummary(); return;
    }
    itemsBody.innerHTML = cart.map((c, idx) => {
        const profitPerUnit = c.price - c.cost;
        const pClass = profitClass(profitPerUnit, c.cost);
        const profitStr = (profitPerUnit >= 0 ? '+' : '') + '৳' + profitPerUnit.toFixed(0);
        const secretDisplay = profitVisible ? '' : 'display:none';
        const overStock = c.qty > c.stock;
        const stockBg  = overStock ? '#fef9c3' : '#dcfce7';
        const stockClr = overStock ? '#92400e' : '#15803d';
        const stockTxt = (overStock ? '⚠ স্টক: ' : 'স্টক: ') + (c.stock ?? '?');
        return `<tr id="cart-row-${idx}">
            <td>
                <div style="display:flex;align-items:flex-start;gap:6px">
                    <div style="flex:1">
                        <span id="item-name-${idx}">${c.name}</span><br>
                        <span id="stock-badge-${c.id}" style="font-size:.72rem;font-weight:700;background:${stockBg};color:${stockClr};padding:1px 7px;border-radius:20px;display:inline-block;margin-top:2px">${stockTxt}</span>
                        <input type="hidden" name="items[${idx}][id]" id="item-id-input-${idx}" value="${c.id}">
                    </div>
                    <button type="button" onclick="startChangeItem(${idx})" title="আইটেম পরিবর্তন"
                        style="flex-shrink:0;padding:3px 7px;border-radius:5px;border:1px solid #cbd5e1;background:#f8fafc;color:#475569;font-size:.72rem;cursor:pointer;margin-top:2px">
                        <i class="fas fa-retweet"></i>
                    </button>
                </div>
                <div id="item-search-box-${idx}" style="display:none;margin-top:6px">
                    <input type="text" placeholder="নতুন আইটেম খুঁজুন..." autocomplete="off"
                        id="item-change-input-${idx}"
                        oninput="searchForChange(${idx}, this.value)"
                        style="width:100%;padding:5px 8px;border:1.5px solid #3b82f6;border-radius:6px;font-size:.82rem">
                    <div id="item-change-results-${idx}" style="background:#fff;border:1px solid #e2e8f0;border-radius:6px;box-shadow:0 4px 12px rgba(0,0,0,.1);max-height:180px;overflow-y:auto;margin-top:2px"></div>
                    <button type="button" onclick="cancelChangeItem(${idx})" style="margin-top:4px;font-size:.75rem;color:#64748b;background:none;border:none;cursor:pointer">✕ বাতিল</button>
                </div>
            </td>
            <td><input type="text" inputmode="decimal" name="items[${idx}][qty]" value="${c.qty}" style="width:70px" oninput="updateQty(${c.id},this.value)" class="inline-input"></td>
            <td class="col-secret" style="color:#94a3b8;font-size:.88rem;${secretDisplay}">৳ ${c.cost.toLocaleString()}</td>
            <td>
                <input type="text" inputmode="decimal" name="items[${idx}][price]" value="${c.price}" id="price-${c.id}" style="width:100px" oninput="updatePrice(${c.id},this.value)" class="inline-input">
                <span id="loss-warn-${c.id}"
                    style="display:${c.cost>0 && c.price>0 && c.price < c.cost ? 'inline-flex':'none'};align-items:center;gap:3px;margin-left:5px;font-size:.72rem;font-weight:700;color:#b91c1c;background:#fee2e2;border:1px solid #fca5a5;border-radius:20px;padding:1px 7px;vertical-align:middle;white-space:nowrap">
                    ⚠ লোকসান!
                </span>
                ${(()=>{ const pct=c.cost>0?(c.price-c.cost)/c.cost*100:0; const isEx=c.cost>0&&c.price>0&&pct>EXCESS_PCT; return `<span id="excess-warn-${c.id}"
                    title="${isEx?`লাভ: ${pct.toFixed(1)}%`:''}"
                    style="display:${isEx?'inline-flex':'none'};align-items:center;gap:3px;margin-left:5px;font-size:.72rem;font-weight:700;color:#92400e;background:#fef9c3;border:1px solid #fde68a;border-radius:20px;padding:1px 7px;vertical-align:middle;white-space:nowrap">
                    <i class="fas fa-triangle-exclamation"></i>
                </span>`; })()}
            </td>
            <td id="row-profit-${c.id}" class="col-secret ${pClass}" style="${secretDisplay}">${profitStr}</td>
            <td id="row-total-${c.id}">৳ ${(c.qty * c.price).toFixed(0)}</td>
            <td><button type="button" onclick="removeItem(${c.id})" class="btn-icon-sm btn-icon-danger"><i class="fas fa-trash"></i></button></td>
        </tr>`;
    }).join('');
    if (profitPanel) profitPanel.style.display = profitVisible ? 'block' : 'none';
    itemsFoot.style.display = '';
    if (typeof attachBengaliConverter === 'function') attachBengaliConverter(itemsBody);
    updateSummary();
}

// ── Categorised extra costs ──────────────────────────────────
var extraCategories = @json($extraCategories);
var extraCostRowCount = 0;

function getExtraCostTotal() {
    let total = 0;
    document.querySelectorAll('.extra-cost-amount').forEach(inp => {
        total += parseFloat(toEnglishDigits(inp.value)) || 0;
    });
    return total;
}

function toggleExtraCosts() {
    const row = document.getElementById('extraRow');
    const btn = document.getElementById('extraCostToggleBtn');
    const open = row.style.display === 'none';
    row.style.display = open ? 'block' : 'none';
    btn.textContent   = open ? '✕ খরচ' : '+ খরচ';
    btn.classList.toggle('active', open);
    if (open && document.getElementById('extraCostRows').children.length === 0) {
        addExtraCostRow();
    }
    if (!open) {
        document.getElementById('extraCostRows').innerHTML = '';
        extraCostRowCount = 0;
        updateSummary();
    }
}

function addExtraCostRow(catName, amount) {
    const idx  = extraCostRowCount++;
    const opts = extraCategories.map(c =>
        `<option value="${c}" ${catName === c ? 'selected' : ''}>${c}</option>`
    ).join('');
    const row = document.createElement('div');
    row.id = `ecr-${idx}`;
    row.style.cssText = 'display:flex;gap:6px;align-items:center;margin-bottom:6px';
    row.innerHTML = `
        <select name="extra_costs[${idx}][category]" class="extra-cost-cat form-select"
            style="flex:1;padding:6px 8px;font-size:.82rem;min-width:0"
            onchange="updateSummary()">
            <option value="">-- ক্যাটাগরি --</option>
            ${opts}
        </select>
        <input type="text" inputmode="decimal" name="extra_costs[${idx}][amount]"
            placeholder="৳ পরিমাণ" value="${amount !== undefined ? amount : ''}"
            class="extra-cost-amount"
            style="width:90px;padding:6px 8px;border:1.5px solid var(--border);border-radius:6px;font-size:.82rem"
            oninput="updateSummary()">
        <button type="button" onclick="removeExtraCostRow(${idx})"
            style="padding:5px 8px;border:none;background:#fee2e2;color:#dc2626;border-radius:6px;cursor:pointer;flex-shrink:0">
            <i class="fas fa-times"></i>
        </button>`;
    document.getElementById('extraCostRows').appendChild(row);
    if (typeof attachBengaliConverter === 'function') attachBengaliConverter(row);
    if (catName === undefined) row.querySelector('select').focus();
    updateSummary();
}

function removeExtraCostRow(idx) {
    const el = document.getElementById(`ecr-${idx}`);
    if (el) el.remove();
    updateSummary();
}

var fieldMap = {
    discount: { row: 'discountRow', btn: 'discountToggleBtn', inp: 'discountInput', labelOn: '✕ ছাড়', labelOff: '+ ছাড়' },
};

function toggleField(key) {
    const f   = fieldMap[key];
    const row = document.getElementById(f.row);
    const btn = document.getElementById(f.btn);
    const inp = document.getElementById(f.inp);
    const open = row.style.display === 'none';
    row.style.display = open ? 'block' : 'none';
    btn.textContent   = open ? f.labelOn : f.labelOff;
    btn.classList.toggle('active', open);
    if (!open) { inp.value = '0'; updateSummary(); }
    else { inp.focus(); }
}

function getNet() {
    return Math.max(0,
        cart.reduce((s, c) => s + c.qty * c.price, 0)
        - (parseFloat(toEnglishDigits(document.getElementById('discountInput').value)) || 0)
        + getExtraCostTotal()
    );
}

function updateSummary() {
    const total     = cart.reduce((s, c) => s + c.qty * c.price, 0);
    const totalCost = cart.reduce((s, c) => s + c.qty * c.cost,  0);
    const discount  = parseFloat(toEnglishDigits(document.getElementById('discountInput').value)) || 0;
    const extra     = getExtraCostTotal();
    const net       = Math.max(0, total - discount + extra);
    const paid      = parseFloat(toEnglishDigits(document.getElementById('paidInput').value)) || 0;
    const due       = Math.max(0, net - paid);
    const profit    = net - totalCost - extra;
    const marginPct = totalCost > 0 ? (profit / totalCost * 100) : 0;

    document.getElementById('totalDisplay').textContent   = '৳ ' + total.toFixed(2);
    document.getElementById('netDisplay').textContent     = '৳ ' + net.toFixed(2);
    document.getElementById('dueDisplay').textContent     = '৳ ' + due.toFixed(2);
    document.getElementById('costDisplay').textContent    = '৳ ' + totalCost.toFixed(2);
    document.getElementById('revenueDisplay').textContent = '৳ ' + net.toFixed(2);
    document.getElementById('profitDisplay').textContent  = (profit >= 0 ? '+' : '') + '৳ ' + profit.toFixed(2);
    document.getElementById('profitDisplay').style.color  = profit >= 0 ? '#16a34a' : '#dc2626';

    const badge = document.getElementById('marginBadge');
    const pctEl = document.getElementById('marginPct');
    if (cart.length) {
        pctEl.textContent = `(${marginPct >= 0 ? '+' : ''}${marginPct.toFixed(1)}%)`;
        if (marginPct >= 8)      { badge.textContent = '✓ ভালো লাভ';  badge.className = 'margin-badge good'; }
        else if (marginPct >= 4) { badge.textContent = '~ মধ্যম লাভ'; badge.className = 'margin-badge med'; }
        else if (marginPct >= 0) { badge.textContent = '↓ কম লাভ';    badge.className = 'margin-badge poor'; }
        else                     { badge.textContent = '✗ লোকসান';     badge.className = 'margin-badge poor'; }
    }
    const footQty   = document.getElementById('footQty');
    const footTotal = document.getElementById('footTotal');
    const totalQty  = cart.reduce((s, c) => s + (c.qty || 0), 0);
    if (footQty)   footQty.textContent   = totalQty;
    if (footTotal) footTotal.textContent = '৳ ' + total.toFixed(0);
}

document.getElementById('discountInput').addEventListener('input', function() {
    if (prevDuePay > 0) document.getElementById('paidInput').value = (getNet() + prevDuePay).toFixed(0);
    updateSummary();
});
document.getElementById('paidInput').addEventListener('input', updateSummary);
document.getElementById('paidInput').addEventListener('blur', function() {
    if (this.value.trim() === '') this.value = '0';
});

function setFullPay() {
    document.getElementById('paidInput').value = (getNet() + prevDuePay).toFixed(0);
    updateSummary();
}
function onPrevDuePayChange() {
    const raw = parseFloat(toEnglishDigits(document.getElementById('prevDuePayInput').value)) || 0;
    prevDuePay = Math.min(Math.max(0, raw), currentPrevDue);
    document.getElementById('paidInput').value = (getNet() + prevDuePay).toFixed(0);
    updateSummary();
}
function setFullPrevDuePay() {
    document.getElementById('prevDuePayInput').value = currentPrevDue.toFixed(0);
    onPrevDuePayChange();
}
function resetPrevDuePay() {
    prevDuePay = 0;
    const inp = document.getElementById('prevDuePayInput');
    if (inp) inp.value = '0';
    document.getElementById('paidInput').value = getNet().toFixed(0);
    updateSummary();
}

// ── Change item inline ───────────────────────────────────────
function startChangeItem(idx) {
    document.getElementById('item-search-box-' + idx).style.display = 'block';
    document.getElementById('item-change-input-' + idx).focus();
}
function cancelChangeItem(idx) {
    document.getElementById('item-search-box-' + idx).style.display = 'none';
    document.getElementById('item-change-input-' + idx).value = '';
    document.getElementById('item-change-results-' + idx).innerHTML = '';
}
function searchForChange(idx, q) {
    const resultsEl = document.getElementById('item-change-results-' + idx);
    if (!q.trim()) { resultsEl.innerHTML = ''; return; }
    const matches = allItems.filter(i => i.name.toLowerCase().includes(q.toLowerCase()));
    resultsEl.innerHTML = matches.map(i => {
        const avail = i.stock ? parseFloat(i.stock.quantity) : 0;
        const stockColor = avail <= 0 ? '#dc2626' : '#16a34a';
        return `<div onclick="replaceItem(${idx}, ${i.id})" style="padding:7px 10px;cursor:pointer;font-size:.83rem;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center"
            onmouseover="this.style.background='#f0f9ff'" onmouseout="this.style.background=''">
            <span>${i.name}</span>
            <span style="font-size:.75rem;font-weight:700;color:${stockColor};margin-left:8px">স্টক: ${avail}</span>
        </div>`;
    }).join('') || '<div style="padding:8px 10px;color:#94a3b8;font-size:.82rem">পাওয়া যায়নি</div>';
}
function replaceItem(idx, newId) {
    const newItem = allItems.find(i => i.id === newId);
    if (!newItem) return;
    const old = cart[idx];
    const avail = newItem.stock ? parseFloat(newItem.stock.quantity) : 0;
    cart[idx] = {
        id: newItem.id, name: newItem.name,
        cost: parseFloat(newItem.purchase_price),
        price: parseFloat(newItem.sale_price),
        defaultPrice: parseFloat(newItem.sale_price),
        qty: old.qty,   // keep same qty
        stock: avail
    };
    renderCart();
}

// ── Submit validation ────────────────────────────────────────
var _stockConfirmPending = false;
document.getElementById('saleForm').addEventListener('submit', function(e) {
    const paidEl      = document.getElementById('paidInput');
    if (paidEl.value.trim() === '') paidEl.value = '0';
    const hasItems    = cart.length > 0;
    const hasCustomer = !!customerIdInput.value;
    const paid        = parseFloat(toEnglishDigits(paidEl.value)) || 0;
    const net         = getNet();

    if (!hasItems) {
        if (!hasCustomer) { e.preventDefault(); showStockToast('আইটেম ছাড়া বিক্রয়ে কাস্টমার নির্বাচন আবশ্যক!', 'error'); customerSearch.focus(); return; }
        if (paid <= 0)    { e.preventDefault(); showStockToast('পরিশোধের পরিমাণ লিখুন!', 'error'); paidEl.focus(); return; }
        return;
    }
    if (!hasCustomer && paid < net) {
        e.preventDefault();
        document.getElementById('walkinWarning').style.display = 'block';
        paidEl.focus();
        showStockToast('ওয়াক-ইন কাস্টমারের জন্য সম্পূর্ণ পরিশোধ আবশ্যক!', 'error');
        return;
    }
    document.getElementById('walkinWarning').style.display = 'none';

    // ── Zero price = hard block (same rule as sales/create) ──
    // No items at all is a valid due payment; an item at ৳0 is a forgotten
    // field that ships stock for no money. Server enforces it too.
    const zeroPriced = cart.filter(c => !(c.price > 0));
    if (zeroPriced.length) {
        e.preventDefault();
        const first = document.getElementById('price-' + zeroPriced[0].id);
        if (first) { first.focus(); first.select(); }
        showStockToast(zeroPriced.length === 1
            ? `"${zeroPriced[0].name}" — দাম দিন`
            : `${zeroPriced.length}টি আইটেমের দাম দেওয়া হয়নি`, 'error');
        return;
    }

    if (_stockConfirmPending) { _stockConfirmPending = false; return; }
    const overItems = cart.filter(c => c.qty > (c.stock ?? Infinity));
    if (overItems.length) {
        e.preventDefault();
        const lines = overItems.map(c => `• ${c.name} (চাহিদা: ${c.qty}, স্টক: ${c.stock})`).join('\n');
        showStockConfirm(lines, () => { _stockConfirmPending = true; document.getElementById('saleForm').requestSubmit(); });
    }
});

// ── Stock confirm dialog ─────────────────────────────────────
function showStockConfirm(details, onConfirm) {
    let d = document.getElementById('stockConfirmDialog');
    if (!d) {
        d = document.createElement('div'); d.id = 'stockConfirmDialog';
        d.style.cssText = 'position:fixed;inset:0;z-index:99998;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;';
        d.innerHTML = `<div style="background:#fff;border-radius:14px;padding:28px 26px;max-width:400px;width:92%;box-shadow:0 20px 60px rgba(0,0,0,.25)">
            <h3 style="font-size:1rem;color:#0f172a;margin-bottom:12px">⚠ স্টক অপর্যাপ্ত</h3>
            <pre id="stockConfirmLines" style="font-size:.83rem;color:#92400e;background:#fef9c3;border:1px solid #fde68a;border-radius:8px;padding:10px 12px;white-space:pre-wrap;margin-bottom:16px;font-family:inherit"></pre>
            <p style="font-size:.84rem;color:#64748b;margin-bottom:20px">তবুও কি সংশোধন সম্পন্ন করবেন? স্টক মাইনাস (-) হবে।</p>
            <div style="display:flex;gap:10px;justify-content:flex-end">
                <button id="stockConfirmCancel" style="padding:9px 20px;border-radius:8px;border:1.5px solid #e2e8f0;background:#fff;cursor:pointer;font-size:.88rem;font-weight:600;color:#475569">বাতিল</button>
                <button id="stockConfirmOk" style="padding:9px 20px;border-radius:8px;border:none;background:#d97706;color:#fff;cursor:pointer;font-size:.88rem;font-weight:600">হ্যাঁ, সংশোধন করুন</button>
            </div></div>`;
        document.body.appendChild(d);
    }
    document.getElementById('stockConfirmLines').textContent = details;
    d.style.display = 'flex';
    document.getElementById('stockConfirmCancel').onclick = () => { d.style.display = 'none'; };
    document.getElementById('stockConfirmOk').onclick    = () => { d.style.display = 'none'; onConfirm(); };
}

function showStockToast(msg, type) {
    let t = document.getElementById('stockToast');
    if (!t) {
        t = document.createElement('div'); t.id = 'stockToast';
        t.style.cssText = 'position:fixed;bottom:28px;left:50%;transform:translateX(-50%) translateY(20px);z-index:99999;padding:12px 22px;border-radius:10px;font-size:.88rem;font-weight:600;box-shadow:0 8px 24px rgba(0,0,0,.18);max-width:380px;text-align:center;white-space:pre-line;opacity:0;transition:opacity .25s,transform .25s;pointer-events:none;';
        document.body.appendChild(t);
    }
    clearTimeout(t._timer);
    t.textContent = msg;
    if (type === 'error') { t.style.background='#dc2626'; t.style.color='#fff'; t.style.border='none'; }
    else { t.style.background='#fef9c3'; t.style.color='#92400e'; t.style.border='1px solid #fde68a'; }
    t.style.opacity='1'; t.style.transform='translateX(-50%) translateY(0)';
    t._timer = setTimeout(() => { t.style.opacity='0'; t.style.transform='translateX(-50%) translateY(20px)'; }, 3500);
}

// ── Initialise cart from existing sale items ─────────────────
(function initCart() {
    cart = existingCartData.map(d => ({ ...d }));
    renderCart();

    // Re-set paid to original value after renderCart resets it
    document.getElementById('paidInput').value = '{{ $sale->paid_amount }}';
    updateSummary();

    // Pre-show discount section if sale has a discount
    @if(($sale->discount ?? 0) > 0)
    (function() {
        const f = fieldMap['discount'];
        document.getElementById(f.row).style.display = 'block';
        document.getElementById(f.btn).textContent = f.labelOn;
        document.getElementById(f.btn).classList.add('active');
    })();
    @endif

    // Pre-populate existing extra costs
    @if($sale->extraCosts->isNotEmpty())
    (function() {
        document.getElementById('extraRow').style.display = 'block';
        document.getElementById('extraCostToggleBtn').textContent = '✕ খরচ';
        document.getElementById('extraCostToggleBtn').classList.add('active');
        @foreach($sale->extraCosts as $ec)
        addExtraCostRow('{{ $ec->category_name }}', {{ $ec->amount }});
        @endforeach
    })();
    @elseif(($sale->extra_cost ?? 0) > 0)
    {{-- Legacy: old record with single extra_cost but no rows --}}
    (function() {
        document.getElementById('extraRow').style.display = 'block';
        document.getElementById('extraCostToggleBtn').textContent = '✕ খরচ';
        document.getElementById('extraCostToggleBtn').classList.add('active');
        addExtraCostRow('অতিরিক্ত খরচ', {{ $sale->extra_cost }});
    })();
    @endif

    // Pre-select customer info display
    @if($sale->customer_id)
    const preCust = allCustomers.find(c => c.id == {{ $sale->customer_id }});
    if (preCust) {
        let html = `<div style="font-weight:700;color:#0d9488;font-size:.9rem">✓ ${preCust.name}</div>`;
        if (preCust.proprietor) html += `<div style="font-size:.8rem;color:#475569;margin-top:1px">প্রোঃ ${preCust.proprietor}</div>`;
        if (preCust.phone)      html += `<div style="font-size:.78rem;color:#94a3b8;margin-top:1px">📞 ${preCust.phone}</div>`;
        customerSelected.innerHTML = html;
        customerSelected.style.display = 'block';
    }
    @endif
})();

document.addEventListener('turbo:load', () => bnWatchTakaWords('paidInput', 'paidWords'));
</script>
@endpush
@endsection
