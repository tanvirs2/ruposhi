@extends('layouts.app')
@section('title', 'গ্রহণ সংশোধন — #RCV-' . str_pad($purchase->id, 4, '0', STR_PAD_LEFT))
@section('page-title', 'গ্রহণ সংশোধন')

@section('content')
<form method="POST" action="{{ route('purchases.update', $purchase) }}" id="receiveForm">
@csrf @method('PUT')
<div class="pos-grid">

    {{-- Left: Item selection --}}
    <div class="pos-left">
        <div class="card pos-search-card" style="margin-bottom:16px">
            <div class="card-header"><h3><i class="fas fa-box-open"></i> আইটেম যোগ করুন</h3></div>
            <div style="padding:16px">
                <div class="search-box" style="margin-bottom:12px">
                    <i class="fas fa-search"></i>
                    <input type="text" id="itemSearch" placeholder="আইটেমের নাম লিখুন...">
                </div>
                <div id="itemSuggestions" class="suggestions-list"></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3><i class="fas fa-list-check"></i> রিসিভ তালিকা</h3></div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>আইটেম</th>
                            <th>বর্তমান স্টক</th>
                            <th>রিসিভ পরিমাণ</th>
                            <th>ক্রয় মূল্য/বস্তা</th>
                            <th>নতুন স্টক</th>
                            <th>মোট</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody">
                        <tr><td colspan="7" class="empty-row">কোনো আইটেম যোগ করা হয়নি</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Right: Summary --}}
    <div class="pos-right">
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-pen-to-square"></i> রিসিভ সংশোধন</h3></div>
            <div style="padding:20px;display:flex;flex-direction:column;gap:14px">

                {{-- Notice --}}
                <div style="padding:10px 14px;background:#fef9c3;border:1px solid #fde68a;border-radius:8px;font-size:.82rem;color:#92400e;font-weight:600">
                    <i class="fas fa-triangle-exclamation"></i>
                    রিসিভ <strong>#RCV-{{ str_pad($purchase->id, 4, '0', STR_PAD_LEFT) }}</strong> সংশোধন।
                    স্টক ও সরবরাহকারীর বকেয়া স্বয়ংক্রিয়ভাবে আপডেট হবে।
                </div>

                <div class="form-group-field">
                    <label>সরবরাহকারী</label>
                    <input type="hidden" name="supplier_id" id="supplierIdInput" value="{{ $purchase->supplier_id }}">
                    <input type="text" id="supplierSearch" placeholder="নাম বা ফোন দিয়ে খুঁজুন..."
                        autocomplete="off" style="width:100%"
                        value="{{ $purchase->supplier?->name ?? '' }}">
                    <div id="supplierSelected" style="display:none;margin-top:4px;font-size:.8rem;color:#0d9488;font-weight:600"></div>
                </div>

                <div class="form-group-field">
                    <label>রিসিভ তারিখ <span class="req">*</span></label>
                    <input type="date" name="purchase_date" value="{{ $purchase->purchase_date->format('Y-m-d') }}" required>
                </div>

                <hr style="border:none;border-top:1px solid var(--border)">

                <div class="receive-stock-panel" id="stockPanel" style="display:none">
                    <div class="stock-panel-title"><i class="fas fa-warehouse"></i> স্টক পরিবর্তন</div>
                    <div id="stockChangeSummary"></div>
                </div>

                <div class="summary-row summary-total"><span>মোট পরিমাণ:</span><span id="totalQtyDisplay">০ বস্তা</span></div>
                <div class="summary-row">
                    <span>মোট মূল্য:</span>
                    <span style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;justify-content:flex-end">
                        <span id="totalDisplay">৳ 0</span>
                        <button type="button" id="extraCostToggleBtn" onclick="toggleExtraCosts()"
                            class="cost-toggle-btn {{ $purchase->extraCosts->isNotEmpty() ? 'active' : '' }}">
                            {{ $purchase->extraCosts->isNotEmpty() ? '✕ খরচ' : '+ খরচ' }}</button>
                        <button type="button" id="depositToggleBtn" onclick="toggleDeposits()"
                            class="cost-toggle-btn {{ $purchase->deposits->isNotEmpty() ? 'active' : '' }}"
                            style="background:#eff6ff;color:#1d4ed8;border-color:#93c5fd">
                            {{ $purchase->deposits->isNotEmpty() ? '✕ জমা' : '+ জমা' }}</button>
                    </span>
                </div>
                {{-- Categorised extra costs --}}
                <div id="extraRow" style="{{ $purchase->extraCosts->isNotEmpty() ? '' : 'display:none' }}">
                    <div style="border:1.5px solid #e2e8f0;border-radius:8px;padding:12px 12px 8px;background:#fafafa">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                            <span style="font-size:.82rem;font-weight:700;color:#475569">
                                <i class="fas fa-coins" style="color:#7c3aed"></i> অতিরিক্ত খরচ
                            </span>
                            <a href="{{ route('extra-cost-categories.index') }}" target="_blank"
                               style="font-size:.72rem;color:var(--accent);text-decoration:none">
                                <i class="fas fa-gear"></i> ক্যাটাগরি
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
                {{-- Categorised deposits --}}
                <div id="depositRow" style="{{ $purchase->deposits->isNotEmpty() ? '' : 'display:none' }}">
                    <div style="border:1.5px solid #93c5fd;border-radius:8px;padding:12px 12px 8px;background:#eff6ff">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                            <span style="font-size:.82rem;font-weight:700;color:#1d4ed8">
                                <i class="fas fa-piggy-bank"></i> জমা
                            </span>
                            <a href="{{ route('deposit-categories.index') }}" target="_blank"
                               style="font-size:.72rem;color:#1d4ed8;text-decoration:none">
                                <i class="fas fa-gear"></i> ক্যাটাগরি
                            </a>
                        </div>
                        <div id="depositRows"></div>
                        <button type="button" onclick="addDepositRow()"
                            style="margin-top:6px;width:100%;padding:7px;border:1.5px dashed #93c5fd;
                                   border-radius:6px;background:transparent;color:#1d4ed8;
                                   font-size:.8rem;font-weight:600;cursor:pointer">
                            <i class="fas fa-plus"></i> আরেকটি জমা যোগ করুন
                        </button>
                    </div>
                </div>
                <div class="summary-row summary-total"><span>নেট মোট:</span><span id="netDisplay">৳ 0</span></div>

                <div class="form-group-field">
                    <label>পরিশোধ (৳) <span class="req">*</span></label>
                    <div style="display:flex;gap:8px">
                        <input type="text" inputmode="decimal" name="paid_amount" id="paidInput"
                            value="{{ $purchase->paid_amount }}" style="flex:1">
                        <button type="button" onclick="setFullPay()"
                            style="padding:0 14px;border-radius:var(--radius-sm);border:1.5px solid var(--accent);
                                   background:var(--accent-light);color:var(--accent);font-size:.78rem;
                                   font-weight:700;cursor:pointer;white-space:nowrap">
                            সম্পূর্ণ
                        </button>
                    </div>
                    <div id="paidWords" style="display:none;margin-top:4px;font-size:.78rem;font-weight:600;color:var(--accent)"></div>
                </div>
                <div class="summary-row" style="color:#ef4444"><span>বকেয়া:</span><span id="dueDisplay">৳ 0</span></div>

                <div class="form-group-field">
                    <label><i class="fas fa-credit-card" style="color:var(--accent)"></i> পরিশোধ মোড</label>
                    <select name="payment_method" class="form-select">
                        @foreach($paymentMethods as $group => $names)
                        <optgroup label="— {{ $group }} —">
                            @foreach($names as $name)
                            <option value="{{ $name }}" @selected($purchase->payment_method === $name)>{{ $name }}</option>
                            @endforeach
                        </optgroup>
                        @endforeach
                    </select>
                </div>

                <div class="form-group-field">
                    <label>মন্তব্য</label>
                    <textarea name="notes" rows="2">{{ $purchase->notes }}</textarea>
                </div>

                <div style="display:flex;gap:10px">
                    <a href="{{ route('purchases.show', $purchase) }}" class="btn btn-ghost" style="flex:1;justify-content:center">
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
.receive-stock-panel { border:1px solid var(--border);border-radius:10px;overflow:hidden;margin-bottom:4px; }
.stock-panel-title { background:var(--bg);padding:8px 14px;font-size:.82rem;font-weight:600;color:var(--text-secondary);display:flex;align-items:center;gap:6px; }
.stock-change-row { display:flex;justify-content:space-between;align-items:center;padding:7px 14px;font-size:.82rem;border-top:1px solid var(--border);gap:8px; }
.stock-change-row .item-name { flex:1;color:var(--text);font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.stock-arrow { display:flex;align-items:center;gap:5px;color:#64748b;font-size:.8rem;white-space:nowrap; }
.stock-arrow .old { color:#94a3b8; }
.stock-arrow .arrow { color:#16a34a;font-size:.7rem; }
.stock-arrow .new { color:#16a34a;font-weight:700; }
.current-stock-cell { color:#94a3b8;font-size:.85rem; }
.new-stock-cell { color:#16a34a;font-weight:700; }
.cost-toggle-btn { font-size:.72rem;padding:2px 8px;border-radius:20px;border:1.5px dashed #94a3b8;background:transparent;color:#94a3b8;cursor:pointer;white-space:nowrap;font-weight:600;line-height:1.5;transition:all .2s; }
.cost-toggle-btn:hover { color:var(--accent);border-color:var(--accent); }
.cost-toggle-btn.active { color:#ef4444;border-color:#fca5a5;border-style:solid; }
</style>
@endpush

@push('scripts')
<script>
// ── Pre-loaded existing items ────────────────────────────────
@php
$existingItems = $purchase->items->map(fn($pi) => [
    'id'           => $pi->item_id,
    'name'         => $pi->item->name ?? '?',
    'price'        => floatval($pi->price),
    'qty'          => floatval($pi->quantity),
    'currentStock' => floatval(($pi->item->stock?->quantity ?? 0)) + floatval($pi->quantity), // add back sold qty
    'lastPrice'    => floatval($pi->price),
    'priceEntered' => true,
]);
@endphp
var existingCartData = @json($existingItems);

// ── Floating dropdown helper ─────────────────────────────────
function makeFloatingDropdown(inputEl, dropEl) {
    document.body.appendChild(dropEl);
    dropEl.style.cssText = `position:fixed;display:none;z-index:9999;background:#fff;border:1px solid #e2e8f0;border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.12);overflow-y:auto;max-height:240px;`;
    // Flip above the input when there isn't room below (short/laptop screens).
    // Zoom gotcha: topbar A/A+/A- scales <html> via CSS zoom; dropEl is a
    // <body> descendant of that same zoomed <html>, so pixel values written
    // to its inline style get re-scaled again at render — divide by zoom
    // before writing to cancel it (see sales/create.blade.php for detail).
    function position() {
        const r = inputEl.getBoundingClientRect();
        const z = parseFloat(getComputedStyle(document.documentElement).zoom) || 1;
        const dropH = Math.min(dropEl.scrollHeight || 240, 240);
        const spaceBelow = window.innerHeight - r.bottom, spaceAbove = r.top;
        if (spaceBelow < dropH + 8 && spaceAbove > spaceBelow) {
            dropEl.style.top = 'auto'; dropEl.style.bottom = ((window.innerHeight - r.top + 4) / z) + 'px';
        } else {
            dropEl.style.bottom = 'auto'; dropEl.style.top = ((r.bottom+4) / z)+'px';
        }
        dropEl.style.left = (r.left / z)+'px'; dropEl.style.width = (r.width / z)+'px';
    }
    let trackTimer = null;
    function startTracking() { stopTracking(); trackTimer = setInterval(() => { if (dropEl.style.display !== 'none') position(); }, 200); }
    function stopTracking()  { if (trackTimer) { clearInterval(trackTimer); trackTimer = null; } }
    function show(html) { dropEl.innerHTML = html; position(); dropEl.style.display = 'block'; startTracking(); }
    function hide() { dropEl.style.display = 'none'; dropEl.innerHTML = ''; stopTracking(); }
    window.addEventListener('scroll', position, true);
    window.addEventListener('resize', position);
    document.addEventListener('click', e => { if (!inputEl.contains(e.target) && !dropEl.contains(e.target)) hide(); });
    return { show, hide };
}

// ── Supplier search ──────────────────────────────────────────
var allSuppliers    = @json($suppliers);
var supplierSearch  = document.getElementById('supplierSearch');
var supplierIdInput = document.getElementById('supplierIdInput');
var supplierSelected= document.getElementById('supplierSelected');
var supplierDrop    = document.createElement('div');
var sDrop           = makeFloatingDropdown(supplierSearch, supplierDrop);

supplierSearch.addEventListener('input', function() {
    const q = this.value.trim().toLowerCase();
    if (!q) { sDrop.hide(); supplierIdInput.value=''; supplierSelected.style.display='none'; return; }
    const matches = allSuppliers.filter(s =>
        s.name.toLowerCase().includes(q) ||
        (s.proprietor && s.proprietor.toLowerCase().includes(q)) ||
        (s.phone && s.phone.includes(q))
    ).slice(0,6);
    sDrop.show(matches.map(s => `
        <div class="suggestion-item" onclick="selectSupplier(${s.id})">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:8px">
                <span>
                    ${s.proprietor
                        ? `<strong style="font-size:.92rem">${s.proprietor}</strong><span style="font-size:.78rem;color:#64748b;margin-left:6px">${s.name}</span>`
                        : `<strong style="font-size:.92rem">${s.name}</strong>`}
                </span>
                ${parseFloat(s.due_amount) > 0
                    ? `<span style="font-size:.78rem;font-weight:700;color:#dc2626;background:#fee2e2;padding:2px 8px;border-radius:20px;white-space:nowrap;flex-shrink:0">বকেয়া: ৳${parseFloat(s.due_amount).toLocaleString()}</span>`
                    : parseFloat(s.due_amount) < 0
                    ? `<span style="font-size:.78rem;font-weight:700;color:#1d4ed8;background:#eff6ff;padding:2px 8px;border-radius:20px;white-space:nowrap;flex-shrink:0">অগ্রিম: ৳${Math.abs(parseFloat(s.due_amount)).toLocaleString()}</span>`
                    : `<span style="font-size:.78rem;font-weight:700;color:#16a34a;background:#dcfce7;padding:2px 8px;border-radius:20px;white-space:nowrap;flex-shrink:0">বকেয়ামুক্ত ✓</span>`}
            </div>
            <span style="font-size:.76rem;color:#94a3b8;display:block;margin-top:2px">
                ${s.phone ? `📞 ${s.phone}` : ''}${s.phone && s.address ? ' &nbsp;·&nbsp; ' : ''}${s.address ? `📍 ${s.address}` : ''}
            </span>
        </div>
    `).join('')||'<div class="suggestion-item" style="color:#94a3b8">পাওয়া যায়নি</div>');
});

function selectSupplier(id) {
    const s = allSuppliers.find(x=>x.id===id); if(!s) return;
    supplierIdInput.value=s.id; supplierSearch.value=s.name;
    const due = parseFloat(s.due_amount) || 0;
    let html = `<div style="font-weight:700;color:#0d9488;font-size:.9rem">✓ ${s.name}</div>`;
    if (s.phone) html += `<div style="font-size:.78rem;color:#94a3b8;margin-top:1px">📞 ${s.phone}</div>`;
    if (due > 0) {
        html += `<div style="margin-top:6px;padding:8px 12px;background:#fee2e2;border:1px solid #fecaca;border-radius:8px;display:flex;justify-content:space-between;align-items:center"><span style="color:#991b1b;font-size:.83rem;font-weight:600"><i class="fas fa-triangle-exclamation"></i> আগের বকেয়া আছে</span><span style="color:#dc2626;font-size:1rem;font-weight:700">৳ ${due.toLocaleString('en',{minimumFractionDigits:2})}</span></div>`;
    } else if (due < 0) {
        html += `<div style="margin-top:6px;padding:8px 12px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;display:flex;justify-content:space-between;align-items:center"><span style="color:#1d4ed8;font-size:.83rem;font-weight:600"><i class="fas fa-piggy-bank"></i> অগ্রিম পরিশোধ আছে</span><span style="color:#1d4ed8;font-size:1rem;font-weight:700">৳ ${Math.abs(due).toLocaleString('en',{minimumFractionDigits:2})}</span></div>`;
    } else {
        html += `<div style="margin-top:6px;padding:6px 12px;background:#dcfce7;border:1px solid #bbf7d0;border-radius:8px;font-size:.8rem;color:#15803d;font-weight:600">✓ কোনো বকেয়া নেই</div>`;
    }
    supplierSelected.innerHTML=html;
    supplierSelected.style.display='block'; sDrop.hide();
}

// ── Items ────────────────────────────────────────────────────
var allItems = @json($items);
var cart = [];
var itemSearch  = document.getElementById('itemSearch');
var suggestions = document.getElementById('itemSuggestions');
var itemsBody   = document.getElementById('itemsBody');
var stockPanel  = document.getElementById('stockPanel');

itemSearch.addEventListener('input', function() {
    const q = this.value.toLowerCase().trim();
    if (!q) { suggestions.innerHTML=''; return; }
    const matches = allItems.filter(i=>i.name.toLowerCase().includes(q));
    suggestions.innerHTML = matches.map(i => {
        const stock = i.stock ? i.stock.quantity : 0;
        return `<div class="suggestion-item" onclick="addItem(${i.id})"><strong>${i.name}</strong><div style="font-size:.78rem;color:#64748b;margin-top:2px">ক্রয়মূল্য: ৳${parseFloat(i.purchase_price).toLocaleString()} &nbsp;|&nbsp; স্টক: ${stock}</div></div>`;
    }).join('')||'<div class="suggestion-item" style="color:#94a3b8">পাওয়া যায়নি</div>';
});

function addItem(id) {
    const item = allItems.find(i=>i.id===id); if(!item) return;
    if (cart.find(c=>c.id===id)) { cart.find(c=>c.id===id).qty++; }
    else { cart.push({id:item.id,name:item.name,price:0,priceEntered:false,lastPrice:parseFloat(item.purchase_price)||0,qty:1,currentStock:item.stock?parseFloat(item.stock.quantity):0}); }
    suggestions.innerHTML=''; itemSearch.value=''; renderCart();
}

function removeItem(id) { cart=cart.filter(c=>c.id!==id); renderCart(); }

// ── Change item inline (swap to a different item) ────────────
function startChangeItem(idx) {
    document.getElementById('item-search-box-' + idx).style.display = 'block';
    document.getElementById('item-change-input-' + idx).focus();
}
function cancelChangeItem(idx) {
    const box = document.getElementById('item-search-box-' + idx);
    if (box) box.style.display = 'none';
    const inp = document.getElementById('item-change-input-' + idx);
    if (inp) inp.value = '';
    const res = document.getElementById('item-change-results-' + idx);
    if (res) res.innerHTML = '';
}
function searchForChange(idx, q) {
    const resultsEl = document.getElementById('item-change-results-' + idx);
    if (!q.trim()) { resultsEl.innerHTML = ''; return; }
    const matches = allItems.filter(i => i.name.toLowerCase().includes(q.toLowerCase()));
    resultsEl.innerHTML = matches.map(i => {
        const avail = i.stock ? parseFloat(i.stock.quantity) : 0;
        return `<div onclick="replaceItem(${idx}, ${i.id})" style="padding:7px 10px;cursor:pointer;font-size:.83rem;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center"
            onmouseover="this.style.background='#f0f9ff'" onmouseout="this.style.background=''">
            <span>${i.name}</span>
            <span style="font-size:.75rem;font-weight:700;color:#475569;margin-left:8px">স্টক: ${avail}</span>
        </div>`;
    }).join('') || '<div style="padding:8px 10px;color:#94a3b8;font-size:.82rem">পাওয়া যায়নি</div>';
}
function replaceItem(idx, newId) {
    const newItem = allItems.find(i => i.id === newId);
    if (!newItem) return;
    const old = cart[idx];
    cart[idx] = {
        id: newItem.id, name: newItem.name,
        price: 0, priceEntered: false,
        lastPrice: parseFloat(newItem.purchase_price) || 0,
        qty: old.qty,   // keep same received quantity
        currentStock: newItem.stock ? parseFloat(newItem.stock.quantity) : 0,
    };
    renderCart();
}
function updateQty(id,val) { var item=cart.find(c=>c.id===id); if(item){item.qty=parseFloat(toEnglishDigits(val))||0;} updateRowTotal(id); updateSummary(); renderStockPanel(); }
function renderStockPanel() {
    if (!stockPanel) return;
    if (!cart.length) { stockPanel.style.display='none'; return; }
    document.getElementById('stockChangeSummary').innerHTML = cart.map(function(c) {
        var ns = c.currentStock + (c.qty||0);
        return '<div class="stock-change-row"><span class="item-name">'+c.name+'</span><span class="stock-arrow"><span class="old">'+c.currentStock+'</span><span class="arrow">→</span><span class="new">'+ns+' বস্তা</span><span style="color:#16a34a;font-size:.75rem">(+'+c.qty+')</span></span></div>';
    }).join('');
    stockPanel.style.display = 'block';
}
function updatePrice(id,val) { const item=cart.find(c=>c.id===id); if(item){item.price=parseFloat(toEnglishDigits(val))||0;item.priceEntered=val.trim()!=='';} updateRowTotal(id); updateSummary(); }
function useLastPrice(id) {
    const item=cart.find(c=>c.id===id); if(!item) return;
    item.price=item.lastPrice; item.priceEntered=true;
    const idx=cart.indexOf(item);
    const inp=document.querySelector(`input[name="items[${idx}][price]"]`);
    if(inp) inp.value=item.lastPrice;
    updateRowTotal(id); updateSummary();
}

function updateRowTotal(id) {
    const item=cart.find(c=>c.id===id); if(!item) return;
    const ns=item.currentStock+(item.qty||0);
    const t=document.getElementById('row-total-'+id); const ns2=document.getElementById('row-newstock-'+id);
    if(t) t.textContent='৳ '+(item.qty*item.price).toLocaleString();
    if(ns2) ns2.textContent=ns+' বস্তা';
}

function renderCart() {
    if (!cart.length) { itemsBody.innerHTML='<tr><td colspan="7" class="empty-row">কোনো আইটেম যোগ করা হয়নি</td></tr>'; stockPanel.style.display='none'; updateSummary(); return; }
    itemsBody.innerHTML = cart.map((c,idx) => {
        const ns = c.currentStock+(c.qty||0);
        const hint = c.lastPrice>0 ? `<div style="font-size:.7rem;color:#94a3b8;margin-top:2px">আগের: ৳${c.lastPrice.toLocaleString()} <button type="button" onclick="useLastPrice(${c.id})" style="font-size:.68rem;color:var(--accent);background:none;border:none;cursor:pointer;padding:0 4px;font-weight:600;text-decoration:underline">ব্যবহার</button></div>` : '';
        return `<tr>
            <td>
                <div style="display:flex;align-items:flex-start;gap:6px">
                    <div style="flex:1"><span>${c.name}</span></div>
                    <button type="button" onclick="startChangeItem(${idx})" title="আইটেম পরিবর্তন"
                        style="flex-shrink:0;padding:3px 7px;border-radius:5px;border:1px solid #cbd5e1;background:#f8fafc;color:#475569;font-size:.72rem;cursor:pointer">
                        <i class="fas fa-retweet"></i>
                    </button>
                </div>
                <input type="hidden" name="items[${idx}][id]" value="${c.id}">
                <div id="item-search-box-${idx}" style="display:none;margin-top:6px">
                    <input type="text" placeholder="নতুন আইটেম খুঁজুন..." autocomplete="off"
                        id="item-change-input-${idx}" oninput="searchForChange(${idx}, this.value)"
                        style="width:100%;padding:5px 8px;border:1.5px solid #3b82f6;border-radius:6px;font-size:.82rem">
                    <div id="item-change-results-${idx}" style="background:#fff;border:1px solid #e2e8f0;border-radius:6px;box-shadow:0 4px 12px rgba(0,0,0,.1);max-height:180px;overflow-y:auto;margin-top:2px"></div>
                    <button type="button" onclick="cancelChangeItem(${idx})" style="margin-top:4px;font-size:.75rem;color:#64748b;background:none;border:none;cursor:pointer">✕ বাতিল</button>
                </div>
            </td>
            <td class="current-stock-cell">${c.currentStock} বস্তা</td>
            <td><input type="text" inputmode="decimal" name="items[${idx}][qty]" value="${c.qty}" style="width:75px" oninput="updateQty(${c.id},this.value)" class="inline-input"></td>
            <td><input type="text" inputmode="decimal" name="items[${idx}][price]" value="${c.priceEntered?c.price:''}" style="width:100px" oninput="updatePrice(${c.id},this.value)" class="inline-input">${hint}</td>
            <td id="row-newstock-${c.id}" class="new-stock-cell">${ns} বস্তা</td>
            <td id="row-total-${c.id}">৳ ${(c.qty*c.price).toLocaleString()}</td>
            <td><button type="button" onclick="removeItem(${c.id})" class="btn-icon-sm btn-icon-danger"><i class="fas fa-trash"></i></button></td>
        </tr>`;
    }).join('');
    document.getElementById('stockChangeSummary').innerHTML = cart.map(c => {
        const ns=c.currentStock+(c.qty||0);
        return `<div class="stock-change-row"><span class="item-name">${c.name}</span><span class="stock-arrow"><span class="old">${c.currentStock}</span><span class="arrow">→</span><span class="new">${ns} বস্তা</span><span style="color:#16a34a;font-size:.75rem">(+${c.qty})</span></span></div>`;
    }).join('');
    stockPanel.style.display='block';
    if(typeof attachBengaliConverter==='function') attachBengaliConverter(itemsBody);
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

// ── Categorised deposits ──────────────────────────────────────
var depositCategories = @json($depositCategories);
var depositRowCount = 0;

function getDepositTotal() {
    let total = 0;
    document.querySelectorAll('.deposit-amount').forEach(inp => {
        total += parseFloat(toEnglishDigits(inp.value)) || 0;
    });
    return total;
}

function toggleDeposits() {
    const row = document.getElementById('depositRow');
    const btn = document.getElementById('depositToggleBtn');
    const open = row.style.display === 'none';
    row.style.display = open ? 'block' : 'none';
    btn.textContent = open ? '✕ জমা' : '+ জমা';
    btn.classList.toggle('active', open);
    if (open && document.getElementById('depositRows').children.length === 0) addDepositRow();
    if (!open) { document.getElementById('depositRows').innerHTML = ''; depositRowCount = 0; updateSummary(); }
}

function addDepositRow(catName, amount) {
    const idx  = depositRowCount++;
    const opts = depositCategories.map(c =>
        `<option value="${c}" ${c === catName ? 'selected' : ''}>${c}</option>`
    ).join('');
    const row = document.createElement('div');
    row.id = `dpr-${idx}`;
    row.style.cssText = 'display:flex;gap:6px;align-items:center;margin-bottom:6px';
    row.innerHTML = `
        <select name="deposit_rows[${idx}][category]" class="deposit-cat form-select"
            style="flex:1;padding:6px 8px;font-size:.82rem;min-width:0" onchange="updateSummary()">
            <option value="">-- ক্যাটাগরি --</option>
            ${opts}
        </select>
        <input type="text" inputmode="decimal" name="deposit_rows[${idx}][amount]"
            placeholder="৳ পরিমাণ" value="${amount || ''}" class="deposit-amount"
            style="width:90px;padding:6px 8px;border:1.5px solid #93c5fd;border-radius:6px;font-size:.82rem"
            oninput="updateSummary()">
        <button type="button" onclick="removeDepositRow(${idx})"
            style="padding:5px 8px;border:none;background:#fee2e2;color:#dc2626;border-radius:6px;cursor:pointer;flex-shrink:0">
            <i class="fas fa-times"></i>
        </button>`;
    document.getElementById('depositRows').appendChild(row);
    if (typeof attachBengaliConverter === 'function') attachBengaliConverter(row);
}

function removeDepositRow(idx) {
    const el = document.getElementById(`dpr-${idx}`);
    if (el) el.remove();
    updateSummary();
}

function toggleExtraCosts() {
    const row = document.getElementById('extraRow');
    const btn = document.getElementById('extraCostToggleBtn');
    const open = row.style.display === 'none';
    row.style.display = open ? 'block' : 'none';
    btn.textContent   = open ? '✕ খরচ' : '+ খরচ';
    btn.classList.toggle('active', open);
    if (open && document.getElementById('extraCostRows').children.length === 0) addExtraCostRow();
    if (!open) { document.getElementById('extraCostRows').innerHTML = ''; extraCostRowCount = 0; updateSummary(); }
}

function addExtraCostRow(catName, amount) {
    const idx  = extraCostRowCount++;
    const opts = extraCategories.map(c =>
        `<option value="${c}" ${c === catName ? 'selected' : ''}>${c}</option>`
    ).join('');
    const row = document.createElement('div');
    row.id = `ecr-${idx}`;
    row.style.cssText = 'display:flex;gap:6px;align-items:center;margin-bottom:6px';
    row.innerHTML = `
        <select name="extra_costs[${idx}][category]" class="extra-cost-cat form-select"
            style="flex:1;padding:6px 8px;font-size:.82rem;min-width:0" onchange="updateSummary()">
            <option value="">-- ক্যাটাগরি --</option>
            ${opts}
        </select>
        <input type="text" inputmode="decimal" name="extra_costs[${idx}][amount]"
            placeholder="৳ পরিমাণ" value="${amount || ''}" class="extra-cost-amount"
            style="width:90px;padding:6px 8px;border:1.5px solid var(--border);border-radius:6px;font-size:.82rem"
            oninput="updateSummary()">
        <button type="button" onclick="removeExtraCostRow(${idx})"
            style="padding:5px 8px;border:none;background:#fee2e2;color:#dc2626;border-radius:6px;cursor:pointer;flex-shrink:0">
            <i class="fas fa-times"></i>
        </button>`;
    document.getElementById('extraCostRows').appendChild(row);
    if (typeof attachBengaliConverter === 'function') attachBengaliConverter(row);
}

function removeExtraCostRow(idx) {
    const el = document.getElementById(`ecr-${idx}`);
    if (el) el.remove();
    updateSummary();
}

function updateSummary() {
    const total    = cart.reduce((s,c)=>s+c.qty*c.price,0);
    const totalQty = cart.reduce((s,c)=>s+(c.qty||0),0);
    const extra    = getExtraCostTotal();
    const net      = total + extra;
    const paid     = parseFloat(toEnglishDigits(document.getElementById('paidInput').value))||0;
    const deposit  = getDepositTotal();
    const rawDue   = net - paid - deposit;
    document.getElementById('totalDisplay').textContent    = '৳ '+total.toLocaleString();
    document.getElementById('totalQtyDisplay').textContent = totalQty+' বস্তা';
    document.getElementById('netDisplay').textContent      = '৳ '+net.toLocaleString();
    const dueEl = document.getElementById('dueDisplay');
    if (rawDue <= 0) {
        dueEl.textContent = rawDue < 0 ? '— (অগ্রিম ৳'+Math.abs(rawDue).toLocaleString()+' বাকি)' : '৳ 0';
        dueEl.style.color = '#16a34a';
    } else {
        dueEl.textContent = '৳ '+rawDue.toLocaleString();
        dueEl.style.color = '#ef4444';
    }
}

function setFullPay() {
    const total    = cart.reduce((s,c)=>s+c.qty*c.price,0);
    const net      = total + getExtraCostTotal() - getDepositTotal();
    document.getElementById('paidInput').value = Math.max(0, net).toFixed(0);
    updateSummary();
}
document.getElementById('paidInput').addEventListener('input', updateSummary);

document.getElementById('receiveForm').addEventListener('submit',function(e){
    // Convert any remaining Bengali digits in numeric inputs
    this.querySelectorAll('input[inputmode="decimal"], input[inputmode="numeric"]').forEach(inp => {
        if (/[০-৯]/.test(inp.value)) inp.value = toEnglishDigits(inp.value);
    });
    // Supplier is required; items are OPTIONAL — a no-item receive is a pure
    // advance payment to the supplier (same as the create form & supplier payment).
    if (!supplierIdInput.value) {
        e.preventDefault();
        alert('সরবরাহকারী নির্বাচন করুন।');
        supplierSearch.focus();
    }
});

// ── Init from existing purchase ──────────────────────────────
(function init() {
    cart = existingCartData.map(d=>({...d}));
    renderCart();
    document.getElementById('paidInput').value = '{{ $purchase->paid_amount }}';
    updateSummary();
    @if($purchase->supplier_id)
    // Reuse selectSupplier() (not a hand-rolled duplicate) so the previous-due/
    // advance banner shows immediately on load — matches what happens when the
    // supplier is picked manually from the search dropdown.
    selectSupplier({{ $purchase->supplier_id }});
    @endif

    // Pre-populate existing extra costs
    @foreach($purchase->extraCosts as $ec)
    addExtraCostRow('{{ $ec->category_name }}', {{ $ec->amount }});
    @endforeach

    // Pre-populate existing deposits
    @if($purchase->deposits->isNotEmpty())
    document.getElementById('depositRow').style.display = 'block';
    @foreach($purchase->deposits as $dep)
    addDepositRow('{{ $dep->category_name }}', {{ $dep->amount }});
    @endforeach
    @endif
})();

document.addEventListener('turbo:load', () => bnWatchTakaWords('paidInput', 'paidWords'));
</script>
@endpush
@endsection
