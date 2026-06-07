@extends('layouts.app')
@section('title', 'মাল রিসিভ')
@section('page-title', 'মাল রিসিভ')
@section('breadcrumb', 'সরবরাহকারী থেকে আইটেম গ্রহণ ও স্টক আপডেট')

@section('content')
<form method="POST" action="{{ route('purchases.store') }}" id="receiveForm">
@csrf

{{-- Draft restore banner --}}
<div id="draftBanner" style="display:none;align-items:center;gap:12px;flex-wrap:wrap;
     background:#fffbeb;border:1.5px solid #fbbf24;border-radius:10px;
     padding:12px 18px;margin-bottom:16px;font-size:.88rem">
    <i class="fas fa-clock-rotate-left" style="color:#d97706;font-size:1.1rem"></i>
    <span id="draftBannerText" style="flex:1;color:#92400e;font-weight:600"></span>
    <button type="button" onclick="restoreDraftData()"
        style="padding:7px 16px;border-radius:7px;border:none;background:#d97706;
               color:#fff;font-weight:700;cursor:pointer;font-size:.85rem;font-family:inherit">
        <i class="fas fa-rotate-left"></i> পুনরুদ্ধার করুন
    </button>
    <button type="button" onclick="discardDraft()"
        style="padding:7px 14px;border-radius:7px;border:1.5px solid #fbbf24;
               background:transparent;color:#92400e;font-weight:600;cursor:pointer;
               font-size:.85rem;font-family:inherit">
        <i class="fas fa-trash"></i> বাতিল
    </button>
</div>

<div class="pos-grid">

    {{-- Left: Item selection --}}
    <div class="pos-left">
        <div class="card" style="margin-bottom:16px">
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
                            <th>রিসিভ পরিমাণ
                                <button type="button" class="info-btn" data-info="সরবরাহকারীর কাছ থেকে কত বস্তা/পরিমাণ পেয়েছেন তা লিখুন। এই পরিমাণ বিদ্যমান স্টকের সাথে যোগ হবে।">i</button>
                            </th>
                            <th>ক্রয় মূল্য/বস্তা
                                <button type="button" class="info-btn" data-info="প্রতি বস্তার ক্রয় মূল্য লিখুন। এই মূল্য আইটেমের ক্রয়মূল্য আপডেট করবে এবং ভবিষ্যতে লাভ হিসাবে ব্যবহার হবে।">i</button>
                            </th>
                            <th>নতুন স্টক</th>
                            <th>মোট</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody">
                        <tr><td colspan="7" class="empty-row">কোনো আইটেম যোগ করা হয়নি</td></tr>
                    </tbody>
                    <tfoot id="itemsFoot" style="display:none">
                        <tr class="tfoot-summary">
                            <td colspan="2" style="text-align:right;font-weight:700;padding-right:12px">সর্বমোট</td>
                            <td class="tc" id="footQty" style="font-weight:800"></td>
                            <td></td>
                            <td></td>
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
            <div class="card-header"><h3><i class="fas fa-truck-ramp-box"></i> রিসিভ সারসংক্ষেপ</h3></div>
            <div style="padding:20px;display:flex;flex-direction:column;gap:14px">

                <div class="form-group-field">
                    <label>সরবরাহকারী <span class="req">*</span></label>
                    <input type="hidden" name="supplier_id" id="supplierIdInput">
                    <input type="text" id="supplierSearch" placeholder="নাম বা ফোন দিয়ে খুঁজুন..."
                        autocomplete="off" style="width:100%" required>
                    <div id="supplierError" style="display:none;margin-top:4px;font-size:.8rem;color:#dc2626;font-weight:600">
                        <i class="fas fa-circle-exclamation"></i> সরবরাহকারী নির্বাচন করুন
                    </div>
                    <div id="supplierSelected" style="display:none;margin-top:4px;font-size:.8rem;color:#0d9488;font-weight:600"></div>
                </div>

                <div class="form-group-field">
                    <label>রিসিভ তারিখ <span class="req">*</span></label>
                    <input type="date" name="purchase_date" value="{{ date('Y-m-d') }}" required>
                </div>

                <hr style="border:none;border-top:1px solid var(--border)">

                {{-- Previous due from supplier (positive) --}}
                <div id="prevDueRow" style="display:none;flex-direction:column;gap:10px;padding:12px 14px;background:#fff7ed;border:1px solid #fed7aa;border-radius:8px">
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <span style="font-size:.83rem;font-weight:600;color:#92400e">
                            <i class="fas fa-clock-rotate-left"></i> পূর্বের বকেয়া
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

                {{-- Previous advance (credit) from supplier --}}
                <div id="prevAdvanceRow" style="display:none;flex-direction:column;gap:10px;padding:12px 14px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px">
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <span style="font-size:.83rem;font-weight:600;color:#1d4ed8">
                            <i class="fas fa-piggy-bank"></i> পূর্বের অগ্রিম পরিশোধ
                        </span>
                        <span style="font-size:1rem;font-weight:800;color:#1d4ed8" id="prevAdvanceDisplay">৳ 0</span>
                    </div>
                    <div style="font-size:.78rem;color:#64748b">
                        এই অগ্রিম নতুন রিসিভের বকেয়া থেকে স্বয়ংক্রিয়ভাবে বাদ যাবে।
                    </div>
                </div>

                {{-- Stock summary --}}
                <div class="receive-stock-panel" id="stockPanel" style="display:none">
                    <div class="stock-panel-title">
                        <i class="fas fa-warehouse"></i> স্টক পরিবর্তন
                        <button type="button" class="info-btn" style="margin-left:4px" data-info="মাল রিসিভ করলে স্টক কীভাবে পরিবর্তন হবে তার পূর্বরূপ। বাম সংখ্যা বর্তমান স্টক, ডান সংখ্যা নতুন স্টক।">i</button>
                    </div>
                    <div id="stockChangeSummary"></div>
                </div>

                <div class="summary-row summary-total"><span>মোট পরিমাণ:</span><span id="totalQtyDisplay">০ বস্তা</span></div>
                <div class="summary-row">
                    <span>মোট মূল্য:</span>
                    <span style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;justify-content:flex-end">
                        <span id="totalDisplay">৳ 0</span>
                        <button type="button" id="extraCostToggleBtn" onclick="toggleExtraCosts()"
                            class="cost-toggle-btn" title="অতিরিক্ত খরচ যোগ করুন">+ খরচ</button>
                    </span>
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
                <div class="summary-row summary-total"><span>নেট মোট:</span><span id="netDisplay">৳ 0</span></div>

                <div class="form-group-field">
                    <label>পরিশোধ (৳) <span class="req">*</span>
                        <button type="button" class="info-btn" data-info="এখন সরবরাহকারীকে কত টাকা দিচ্ছেন। বাকি টাকা স্বয়ংক্রিয়ভাবে সরবরাহকারীর বকেয়ায় যোগ হবে।">i</button>
                    </label>
                    <div style="display:flex;gap:8px">
                        <input type="text" inputmode="decimal" name="paid_amount" id="paidInput" value="0" required style="flex:1">
                        <button type="button" onclick="setFullPay()"
                            style="padding:0 14px;border-radius:var(--radius-sm);border:1.5px solid var(--accent);
                                   background:var(--accent-light);color:var(--accent);font-size:.78rem;
                                   font-weight:700;cursor:pointer;white-space:nowrap">
                            সম্পূর্ণ
                        </button>
                    </div>
                </div>
                <div class="summary-row" style="color:#ef4444"><span>বকেয়া:</span><span id="dueDisplay">৳ 0</span></div>

                {{-- Advance payment warning (no items, paid > 0) --}}
                <div id="advancePayWarning" style="display:none;padding:10px 14px;background:#fefce8;border:1px solid #fde68a;border-radius:8px;gap:8px;align-items:flex-start">
                    <i class="fas fa-circle-info" style="color:#ca8a04;margin-top:2px;flex-shrink:0"></i>
                    <div style="font-size:.83rem;color:#92400e;line-height:1.5">
                        কোনো মাল নেই — <strong><span id="advancePayAmt"></span></strong> সরবরাহকারীর অগ্রিম পরিশোধ হিসেবে যোগ হবে।
                        পরবর্তী রিসিভে এই অগ্রিম স্বয়ংক্রিয়ভাবে বাদ যাবে।
                    </div>
                </div>

                {{-- Payment Method (DB-driven) --}}
                <div class="form-group-field">
                    <label><i class="fas fa-credit-card" style="color:var(--accent)"></i> পরিশোধ মোড</label>
                    <select name="payment_method" id="paymentMethod" class="form-select">
                        @foreach($paymentMethods as $group => $names)
                        <optgroup label="— {{ $group }} —">
                            @foreach($names as $i => $name)
                            <option value="{{ $name }}" @if($group === array_key_first($paymentMethods) && $i === 0) selected @endif>
                                {{ $name }}
                            </option>
                            @endforeach
                        </optgroup>
                        @endforeach
                    </select>
                    <div style="margin-top:5px;font-size:.77rem;color:#94a3b8">
                        <a href="{{ route('store-config.index') }}" target="_blank"
                            style="color:var(--accent);text-decoration:none;font-weight:500">
                            <i class="fas fa-plus-circle"></i> নতুন পদ্ধতি যোগ করুন (স্টোর কনফিগ)
                        </a>
                    </div>
                </div>

                <div class="form-group-field">
                    <label>মন্তব্য</label>
                    <textarea name="notes" rows="2" placeholder="চালান নম্বর বা অন্য তথ্য..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:14px">
                    <i class="fas fa-boxes-stacked"></i> মাল রিসিভ ও স্টক আপডেট করুন
                </button>
            </div>
        </div>
    </div>
</div>
</form>

@push('styles')
<style>
.receive-stock-panel {
    border: 1px solid var(--border);
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 4px;
}
.stock-panel-title {
    background: var(--bg);
    padding: 8px 14px;
    font-size: .82rem;
    font-weight: 600;
    color: var(--text-secondary);
    display: flex;
    align-items: center;
    gap: 6px;
}
.stock-change-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 7px 14px;
    font-size: .82rem;
    border-top: 1px solid var(--border);
    gap: 8px;
}
.stock-change-row .item-name {
    flex: 1;
    color: var(--text);
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.stock-arrow {
    display: flex;
    align-items: center;
    gap: 5px;
    color: #64748b;
    font-size: .8rem;
    white-space: nowrap;
}
.stock-arrow .old { color: #94a3b8; }
.stock-arrow .arrow { color: #16a34a; font-size: .7rem; }
.stock-arrow .new  { color: #16a34a; font-weight: 700; }
.current-stock-cell { color: #94a3b8; font-size: .85rem; }
.new-stock-cell { color: #16a34a; font-weight: 700; }

/* Cost toggle buttons (match sale form) */
.cost-toggle-btn {
    font-size:.72rem; padding:2px 8px; border-radius:20px;
    border:1.5px dashed #94a3b8; background:transparent;
    color:#94a3b8; cursor:pointer; white-space:nowrap;
    font-weight:600; line-height:1.5; transition:all .2s;
}
.cost-toggle-btn:hover { color:var(--accent); border-color:var(--accent); }
.cost-toggle-btn.active { color:#ef4444; border-color:#fca5a5; border-style:solid; }
</style>
@endpush

@push('scripts')
<script>
// ── Floating dropdown helper ─────────────────────────────────
function makeFloatingDropdown(inputEl, dropEl) {
    document.body.appendChild(dropEl);
    dropEl.style.cssText = `
        position:fixed;display:none;z-index:9999;
        background:#fff;border:1px solid #e2e8f0;
        border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.12);
        overflow-y:auto;max-height:240px;
    `;
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
    return { show, hide };
}

// ── Supplier search ──────────────────────────────────────────
const allSuppliers    = @json($suppliers);
const supplierSearch  = document.getElementById('supplierSearch');
const supplierIdInput = document.getElementById('supplierIdInput');
const supplierSelected= document.getElementById('supplierSelected');
const supplierDrop    = document.createElement('div');
const sDrop           = makeFloatingDropdown(supplierSearch, supplierDrop);
const prevAdvanceRow  = document.getElementById('prevAdvanceRow');
const prevAdvanceDisp = document.getElementById('prevAdvanceDisplay');
let supplierAdvance   = 0; // credit balance (positive = supplier has advance)

supplierSearch.addEventListener('input', function() {
    const q = this.value.trim().toLowerCase();
    if (!q) { sDrop.hide(); supplierIdInput.value = ''; supplierSelected.style.display='none'; return; }
    const matches = allSuppliers.filter(s =>
        s.name.toLowerCase().includes(q) || (s.phone && s.phone.includes(q))
    ).slice(0, 6);
    const html = matches.map(s => `
        <div class="suggestion-item" onclick="selectSupplier(${s.id})">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:8px">
                <strong style="font-size:.92rem">${s.name}</strong>
                ${parseFloat(s.due_amount) > 0
                    ? `<span style="font-size:.78rem;font-weight:700;color:#dc2626;background:#fee2e2;padding:2px 8px;border-radius:20px;white-space:nowrap;flex-shrink:0">বকেয়া: ৳${parseFloat(s.due_amount).toLocaleString()}</span>`
                    : parseFloat(s.due_amount) < 0
                    ? `<span style="font-size:.78rem;font-weight:700;color:#1d4ed8;background:#eff6ff;padding:2px 8px;border-radius:20px;white-space:nowrap;flex-shrink:0">অগ্রিম: ৳${Math.abs(parseFloat(s.due_amount)).toLocaleString()}</span>`
                    : `<span style="font-size:.78rem;font-weight:700;color:#16a34a;background:#dcfce7;padding:2px 8px;border-radius:20px;white-space:nowrap;flex-shrink:0">বকেয়ামুক্ত ✓</span>`}
            </div>
            <span style="font-size:.76rem;color:#94a3b8;display:block;margin-top:2px">
                ${s.phone ? '📞 '+s.phone : ''}${s.address ? ' · '+s.address : ''}
            </span>
        </div>
    `).join('') || `<div class="suggestion-item" style="color:#94a3b8">কোনো সরবরাহকারী পাওয়া যায়নি</div>`;
    sDrop.show(html);
});

function selectSupplier(id) {
    const s = allSuppliers.find(x => x.id === id);
    if (!s) return;

    supplierIdInput.value = s.id;
    supplierSearch.value  = s.name;

    let html = `<div style="font-weight:700;color:#0d9488;font-size:.9rem">✓ ${s.name}</div>`;
    if (s.phone) {
        html += `<div style="font-size:.78rem;color:#94a3b8;margin-top:1px">📞 ${s.phone}</div>`;
    }
    if (s.address) {
        html += `<div style="font-size:.78rem;color:#94a3b8;margin-top:1px">📍 ${s.address}</div>`;
    }

    const due = parseFloat(s.due_amount) || 0;
    if (due > 0) {
        html += `<div style="margin-top:6px;padding:8px 12px;background:#fee2e2;border:1px solid #fecaca;
                             border-radius:8px;display:flex;justify-content:space-between;align-items:center">
                    <span style="color:#991b1b;font-size:.83rem;font-weight:600">
                        <i class="fas fa-triangle-exclamation"></i> আগের বকেয়া আছে
                    </span>
                    <span style="color:#dc2626;font-size:1rem;font-weight:700">৳ ${due.toLocaleString('en', {minimumFractionDigits:2})}</span>
                 </div>`;
    } else if (due < 0) {
        html += `<div style="margin-top:6px;padding:8px 12px;background:#eff6ff;border:1px solid #bfdbfe;
                             border-radius:8px;display:flex;justify-content:space-between;align-items:center">
                    <span style="color:#1d4ed8;font-size:.83rem;font-weight:600">
                        <i class="fas fa-piggy-bank"></i> অগ্রিম পরিশোধ আছে
                    </span>
                    <span style="color:#1d4ed8;font-size:1rem;font-weight:700">৳ ${Math.abs(due).toLocaleString('en', {minimumFractionDigits:2})}</span>
                 </div>`;
    } else {
        html += `<div style="margin-top:6px;padding:6px 12px;background:#dcfce7;border:1px solid #bbf7d0;
                             border-radius:8px;font-size:.8rem;color:#15803d;font-weight:600">
                    ✓ কোনো বকেয়া নেই
                 </div>`;
    }

    supplierSelected.innerHTML = html;
    supplierSelected.style.display = 'block';
    sDrop.hide();

    // Show advance panel if supplier has credit balance (negative due)
    if (due < 0) {
        supplierAdvance = Math.abs(due);
        prevAdvanceDisp.textContent = '৳ ' + supplierAdvance.toLocaleString('en', {minimumFractionDigits:2});
        prevAdvanceRow.style.display = 'flex';
    } else {
        supplierAdvance = 0;
        prevAdvanceRow.style.display = 'none';
    }
    updateSummary();
}

// ── Items ────────────────────────────────────────────────────
const allItems = @json($items);
let cart = [];

const itemSearch  = document.getElementById('itemSearch');
const suggestions = document.getElementById('itemSuggestions');
const itemsBody   = document.getElementById('itemsBody');
const stockPanel  = document.getElementById('stockPanel');

itemSearch.addEventListener('input', function() {
    const q = this.value.toLowerCase().trim();
    if (!q) { suggestions.innerHTML = ''; return; }
    const matches = allItems.filter(i => i.name.toLowerCase().includes(q)).slice(0, 8);
    suggestions.innerHTML = matches.map(i => {
        const stock = i.stock ? i.stock.quantity : 0;
        return `<div class="suggestion-item" onclick="addItem(${i.id})">
            <strong>${i.name}</strong>
            <div style="display:flex;gap:14px;margin-top:3px">
                <span style="font-size:.78rem;color:#64748b">ক্রয়মূল্য: ৳${parseFloat(i.purchase_price).toLocaleString()}</span>
                <span style="font-size:.78rem;color:${stock <= 10 ? '#ef4444' : '#94a3b8'}">স্টক: ${stock} বস্তা</span>
            </div>
        </div>`;
    }).join('') || '<div class="suggestion-item" style="color:#94a3b8">পাওয়া যায়নি</div>';
});

function addItem(id) {
    const item = allItems.find(i => i.id === id);
    if (!item) return;
    if (cart.find(c => c.id === id)) {
        cart.find(c => c.id === id).qty++;
    } else {
        cart.push({
            id:            item.id,
            name:          item.name,
            price:         0,
            priceEntered:  false,
            lastPrice:     parseFloat(item.purchase_price) || 0,
            qty:           1,
            currentStock:  item.stock ? parseFloat(item.stock.quantity) : 0,
        });
    }
    suggestions.innerHTML = '';
    itemSearch.value = '';
    renderCart();
}

function removeItem(id) { cart = cart.filter(c => c.id !== id); renderCart(); }

function updateQty(id, val) {
    const item = cart.find(c => c.id === id);
    if (item) item.qty = parseFloat(toEnglishDigits(val)) || 0;
    updateRowTotal(id);
    updateSummary();
}

function updatePrice(id, val) {
    const item = cart.find(c => c.id === id);
    if (item) {
        item.price        = parseFloat(toEnglishDigits(val)) || 0;
        item.priceEntered = val.trim() !== '';
    }
    updateRowTotal(id);
    updateSummary();
}

function useLastPrice(id) {
    const item = cart.find(c => c.id === id);
    if (!item) return;
    item.price        = item.lastPrice;
    item.priceEntered = true;
    const inp = document.querySelector(`input[name="items[${cart.indexOf(item)}][price]"]`);
    if (inp) inp.value = item.lastPrice;
    updateRowTotal(id);
    updateSummary();
}

// Update only affected cells — no full re-render, focus stays intact
function updateRowTotal(id) {
    const item = cart.find(c => c.id === id);
    if (!item) return;
    const newStock = item.currentStock + (item.qty || 0);
    const totalCell    = document.getElementById('row-total-'    + id);
    const newStockCell = document.getElementById('row-newstock-' + id);
    const scNew        = document.getElementById('row-sc-new-'   + id);
    const scDelta      = document.getElementById('row-sc-delta-' + id);
    if (totalCell)    totalCell.textContent    = '৳ ' + (item.qty * item.price).toLocaleString();
    if (newStockCell) newStockCell.textContent = newStock + ' বস্তা';
    if (scNew)        scNew.textContent        = newStock + ' বস্তা';
    if (scDelta)      scDelta.textContent      = '(+' + item.qty + ')';
}

function renderCart() {
    scheduleDraftSave();
    const itemsFoot = document.getElementById('itemsFoot');
    if (!cart.length) {
        itemsBody.innerHTML = '<tr><td colspan="7" class="empty-row">কোনো আইটেম যোগ করা হয়নি</td></tr>';
        itemsFoot.style.display = 'none';
        stockPanel.style.display = 'none';
        updateSummary();
        checkAdvanceWarning();
        return;
    }

    itemsBody.innerHTML = cart.map((c, idx) => {
        const newStock = c.currentStock + (c.qty || 0);
        const hint = c.lastPrice > 0
            ? `<div style="font-size:.7rem;color:#94a3b8;margin-top:2px">
                   আগের: ৳${c.lastPrice.toLocaleString()}
                   <button type="button" onclick="useLastPrice(${c.id})"
                       style="font-size:.68rem;color:var(--accent);background:none;border:none;cursor:pointer;
                              padding:0 4px;font-weight:600;text-decoration:underline">ব্যবহার</button>
               </div>`
            : '';
        return `<tr>
            <td>
                ${c.name}
                <input type="hidden" name="items[${idx}][id]" value="${c.id}">
            </td>
            <td class="current-stock-cell">${c.currentStock} বস্তা</td>
            <td>
                <input type="text" inputmode="decimal" name="items[${idx}][qty]" value="${c.qty}"
                    style="width:75px"
                    oninput="updateQty(${c.id},this.value)" class="inline-input">
            </td>
            <td>
                <input type="text" inputmode="decimal" name="items[${idx}][price]" value="${c.priceEntered ? c.price : ''}"
                    style="width:100px"
                    oninput="updatePrice(${c.id},this.value)" class="inline-input">
                ${hint}
            </td>
            <td id="row-newstock-${c.id}" class="new-stock-cell">${newStock} বস্তা</td>
            <td id="row-total-${c.id}">৳ ${(c.qty * c.price).toLocaleString()}</td>
            <td><button type="button" onclick="removeItem(${c.id})" class="btn-icon-sm btn-icon-danger"><i class="fas fa-trash"></i></button></td>
        </tr>`;
    }).join('');

    // Stock change summary panel
    document.getElementById('stockChangeSummary').innerHTML = cart.map(c => {
        const newStock = c.currentStock + (c.qty || 0);
        return `<div class="stock-change-row">
            <span class="item-name">${c.name}</span>
            <span class="stock-arrow">
                <span class="old">${c.currentStock}</span>
                <span class="arrow">→</span>
                <span id="row-sc-new-${c.id}" class="new">${newStock} বস্তা</span>
                <span id="row-sc-delta-${c.id}" style="color:#16a34a;font-size:.75rem">(+${c.qty})</span>
            </span>
        </div>`;
    }).join('');
    stockPanel.style.display = 'block';

    // Update tfoot totals
    const totalQty  = cart.reduce((s, c) => s + (c.qty || 0), 0);
    const totalAmt  = cart.reduce((s, c) => s + c.qty * c.price, 0);
    document.getElementById('footQty').textContent   = totalQty + ' বস্তা';
    document.getElementById('footTotal').textContent = '৳ ' + totalAmt.toLocaleString();
    itemsFoot.style.display = '';

    // Re-attach Bengali converter to new inputs
    if (typeof attachBengaliConverter === 'function') attachBengaliConverter(itemsBody);
    updateSummary();
    checkAdvanceWarning();
}

function updateSummary() {
    const total    = cart.reduce((s, c) => s + c.qty * c.price, 0);
    const totalQty = cart.reduce((s, c) => s + (c.qty || 0), 0);
    const extra    = getExtraCostTotal();
    const net      = total + extra;
    const paid     = parseFloat(toEnglishDigits(document.getElementById('paidInput').value)) || 0;
    const rawDue = net - paid - supplierAdvance; // advance reduces due
    document.getElementById('totalDisplay').textContent    = '৳ ' + total.toLocaleString();
    document.getElementById('totalQtyDisplay').textContent = totalQty + ' বস্তা';
    document.getElementById('netDisplay').textContent      = '৳ ' + net.toLocaleString();
    const dueEl = document.getElementById('dueDisplay');
    if (rawDue <= 0) {
        dueEl.textContent = rawDue < 0 ? '— (অগ্রিম ৳' + Math.abs(rawDue).toLocaleString() + ' বাকি)' : '৳ 0';
        dueEl.style.color = '#16a34a';
    } else {
        dueEl.textContent = '৳ ' + rawDue.toLocaleString();
        dueEl.style.color = '#ef4444';
    }
    // Keep tfoot in sync on every change
    const footQty   = document.getElementById('footQty');
    const footTotal = document.getElementById('footTotal');
    if (footQty)   footQty.textContent   = totalQty + ' বস্তা';
    if (footTotal) footTotal.textContent = '৳ ' + total.toLocaleString();
}

// ── Categorised extra costs ──────────────────────────────────
const extraCategories = @json($extraCategories);
let extraCostRowCount = 0;

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

function addExtraCostRow() {
    const idx  = extraCostRowCount++;
    const opts = extraCategories.map(c => `<option value="${c}">${c}</option>`).join('');
    const row  = document.createElement('div');
    row.id = `ecr-${idx}`;
    row.style.cssText = 'display:flex;gap:6px;align-items:center;margin-bottom:6px';
    row.innerHTML = `
        <select name="extra_costs[${idx}][category]" class="extra-cost-cat form-select"
            style="flex:1;padding:6px 8px;font-size:.82rem;min-width:0" onchange="updateSummary()">
            <option value="">-- ক্যাটাগরি --</option>
            ${opts}
        </select>
        <input type="text" inputmode="decimal" name="extra_costs[${idx}][amount]"
            placeholder="৳ পরিমাণ" value="" class="extra-cost-amount"
            style="width:90px;padding:6px 8px;border:1.5px solid var(--border);border-radius:6px;font-size:.82rem"
            oninput="updateSummary()">
        <button type="button" onclick="removeExtraCostRow(${idx})"
            style="padding:5px 8px;border:none;background:#fee2e2;color:#dc2626;border-radius:6px;cursor:pointer;flex-shrink:0">
            <i class="fas fa-times"></i>
        </button>`;
    document.getElementById('extraCostRows').appendChild(row);
    if (typeof attachBengaliConverter === 'function') attachBengaliConverter(row);
    row.querySelector('select').focus();
}

function removeExtraCostRow(idx) {
    const el = document.getElementById(`ecr-${idx}`);
    if (el) el.remove();
    updateSummary();
}

function setFullPay() {
    const net = cart.reduce((s, c) => s + c.qty * c.price, 0) + getExtraCostTotal();
    document.getElementById('paidInput').value = net.toFixed(0);
    updateSummary();
}

document.getElementById('receiveForm').addEventListener('submit', function(e) {
    // Safety net: convert any remaining Bengali digits in all numeric inputs
    this.querySelectorAll('input[inputmode="decimal"], input[inputmode="numeric"], .extra-cost-amount').forEach(inp => {
        if (/[০-৯]/.test(inp.value)) inp.value = toEnglishDigits(inp.value);
    });

    const suppErr = document.getElementById('supplierError');
    if (!document.getElementById('supplierIdInput').value) {
        e.preventDefault();
        suppErr.style.display = 'block';
        document.getElementById('supplierSearch').focus();
        return;
    }
    suppErr.style.display = 'none';
    // Allow no-item submit (pure advance payment to supplier)
});

// Show/hide advance payment warning when cart is empty but paid > 0
function checkAdvanceWarning() {
    const paid = parseFloat(toEnglishDigits(document.getElementById('paidInput').value)) || 0;
    const warn = document.getElementById('advancePayWarning');
    if (!cart.length && paid > 0) {
        warn.style.display = 'flex';
        warn.querySelector('#advancePayAmt').textContent = '৳ ' + paid.toLocaleString();
    } else {
        warn.style.display = 'none';
    }
}

document.getElementById('paidInput').addEventListener('input', function() {
    updateSummary();
    checkAdvanceWarning();
});

// ══════════════════════════════════════════════════════════════
// PURCHASE DRAFT — auto-save to sessionStorage, restore on reload
// ══════════════════════════════════════════════════════════════
const DRAFT_KEY = 'purchase_draft_{{ auth()->user()->shop_id }}_{{ auth()->id() }}';
let _draftTimer  = null;
let _pendingDraft = null;

function scheduleDraftSave() {
    clearTimeout(_draftTimer);
    _draftTimer = setTimeout(saveDraft, 1500);
}

function saveDraft() {
    if (cart.length === 0 && !document.getElementById('supplierIdInput').value) return;

    const extraCosts = [];
    document.querySelectorAll('#extraCostRows > div').forEach(row => {
        const cat = row.querySelector('select')?.value  || '';
        const amt = row.querySelector('.extra-cost-amount')?.value || '0';
        extraCosts.push({ category: cat, amount: amt });
    });

    const draft = {
        cart,
        supplierId:    document.getElementById('supplierIdInput').value,
        supplierName:  document.getElementById('supplierSearch').value,
        prevDuePay:    document.getElementById('prevDuePayInput')?.value || '0',
        extraCosts,
        extraOpen:     document.getElementById('extraRow').style.display !== 'none',
        paidAmount:    document.getElementById('paidInput').value,
        paymentMethod: document.getElementById('paymentMethod').value,
        notes:         document.querySelector('textarea[name="notes"]').value,
        purchaseDate:  document.querySelector('input[name="purchase_date"]').value,
        savedAt:       Date.now(),
    };

    try { sessionStorage.setItem(DRAFT_KEY, JSON.stringify(draft)); } catch(_) {}
}

function clearDraft() {
    sessionStorage.removeItem(DRAFT_KEY);
    _pendingDraft = null;
}

function discardDraft() {
    clearDraft();
    document.getElementById('draftBanner').style.display = 'none';
}

function restoreDraftData() {
    const draft = _pendingDraft;
    if (!draft) return;

    clearDraft();
    document.getElementById('draftBanner').style.display = 'none';

    // Restore cart
    cart = draft.cart || [];
    renderCart();

    // Restore supplier
    if (draft.supplierId) {
        const sup = allSuppliers.find(s => String(s.id) === String(draft.supplierId));
        if (sup) {
            selectSupplier(sup.id);
            setTimeout(() => {
                const ppEl = document.getElementById('prevDuePayInput');
                if (ppEl && draft.prevDuePay) { ppEl.value = draft.prevDuePay; updateSummary(); }
            }, 100);
        } else {
            document.getElementById('supplierSearch').value = draft.supplierName || '';
        }
    }

    // Restore date
    if (draft.purchaseDate) {
        document.querySelector('input[name="purchase_date"]').value = draft.purchaseDate;
    }

    // Restore extra costs
    if (draft.extraOpen && draft.extraCosts?.length) {
        document.getElementById('extraRow').style.display = 'block';
        document.getElementById('extraCostRows').innerHTML = '';
        extraCostRowCount = 0;
        draft.extraCosts.forEach(ec => {
            addExtraCostRow();
            const rows = document.querySelectorAll('#extraCostRows > div');
            const last = rows[rows.length - 1];
            if (last) {
                const sel = last.querySelector('select');
                const inp = last.querySelector('.extra-cost-amount');
                if (sel) sel.value = ec.category;
                if (inp) inp.value = ec.amount;
            }
        });
    }

    // Restore paid + payment method + notes
    document.getElementById('paidInput').value = draft.paidAmount || '0';
    if (draft.paymentMethod) document.getElementById('paymentMethod').value = draft.paymentMethod;
    document.querySelector('textarea[name="notes"]').value = draft.notes || '';

    updateSummary();
    checkAdvanceWarning();
}

// ── Check for existing draft on page load ─────────────────────
(function checkDraft() {
    try {
        const raw = sessionStorage.getItem(DRAFT_KEY);
        if (!raw) return;
        const draft = JSON.parse(raw);
        if (!draft || (!draft.cart?.length && !draft.supplierId)) { clearDraft(); return; }

        _pendingDraft = draft;
        const age     = Date.now() - (draft.savedAt || 0);
        const minutes = Math.round(age / 60000);
        const timeStr = minutes < 1 ? 'এইমাত্র' : minutes < 60
            ? `${minutes} মিনিট আগে` : `${Math.round(minutes/60)} ঘণ্টা আগে`;
        const itemCount = draft.cart?.length || 0;

        document.getElementById('draftBannerText').textContent =
            `${itemCount}টি আইটেমসহ অসমাপ্ত রিসিভ পাওয়া গেছে (${timeStr} সংরক্ষিত) — পুনরুদ্ধার করবেন?`;
        document.getElementById('draftBanner').style.display = 'flex';
    } catch(_) {}
})();

// ── Save on any form input change ─────────────────────────────
document.getElementById('receiveForm').addEventListener('input',  scheduleDraftSave);
document.getElementById('receiveForm').addEventListener('change', scheduleDraftSave);

// ── Clear draft on actual submission ─────────────────────────
document.getElementById('receiveForm').addEventListener('submit', function(e) {
    if (!e.defaultPrevented) clearDraft();
}, false);
</script>
@endpush
@endsection
