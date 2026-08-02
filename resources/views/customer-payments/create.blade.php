@extends('layouts.app')
@section('title', 'কাস্টমার পরিশোধ')
@section('page-title', 'কাস্টমার পরিশোধ')

@section('content')
<div class="form-card" style="max-width:560px">
    <form method="POST" action="{{ route('customer-payments.store') }}">
        @csrf

        {{-- Area filter (optional) — searchable; narrows the customer search --}}
        <div class="form-group-field" style="margin-bottom:12px">
            <label><i class="fas fa-location-dot" style="color:var(--accent)"></i> এরিয়া</label>
            <input type="text" id="areaSearch" class="form-select" autocomplete="off"
                   placeholder="— সব এরিয়া — (খুঁজতে লিখুন)" style="width:100%">
            <input type="hidden" id="areaFilter" value="">
        </div>

        {{-- Customer search --}}
        <div class="form-group-field" style="margin-bottom:0">
            <label>কাস্টমার <span class="req">*</span></label>
            <input type="text" id="customerSearch" placeholder="নাম বা ফোন দিয়ে খুঁজুন..."
                   autocomplete="off" style="width:100%">
            <input type="hidden" name="customer_id" id="customerIdInput" required>
        </div>

        {{-- Due box --}}
        <div id="dueBox" style="margin-top:10px;display:none"></div>

        <hr style="border:none;border-top:1px solid var(--border);margin:18px 0">

        <div class="form-grid">
            <div class="form-group-field">
                <label>পরিমাণ (৳) <span class="req">*</span>
                    <button type="button" class="info-btn" data-info="কাস্টমার এখন কত টাকা দিচ্ছেন। এই পরিমাণ কাস্টমারের মোট বাকী থেকে বাদ যাবে।">i</button>
                </label>
                <div style="display:flex;gap:8px">
                    <input type="text" inputmode="decimal" name="amount" id="amountInput"
                           value="{{ old('amount') }}" required placeholder="০" style="flex:1">
                    <button type="button" onclick="setFullPay()" title="সম্পূর্ণ বাকী পরিশোধ"
                        style="padding:0 14px;border-radius:var(--radius-sm);border:1.5px solid var(--accent);
                               background:var(--accent-light);color:var(--accent);font-size:.8rem;
                               font-weight:700;cursor:pointer;white-space:nowrap">
                        সম্পূর্ণ
                    </button>
                </div>
                <div id="amountWords" style="display:none;margin-top:4px;font-size:.78rem;font-weight:600;color:var(--accent)"></div>
            </div>

            <div class="form-group-field">
                <label>তারিখ <span class="req">*</span></label>
                <input type="date" name="payment_date" value="{{ old('payment_date', date('Y-m-d')) }}" required>
            </div>

            <div class="form-group-field form-full">
                <label><i class="fas fa-credit-card" style="color:var(--accent)"></i> পরিশোধ পদ্ধতি <span class="req">*</span></label>
                <select name="payment_method" class="form-select">
                    @foreach($paymentMethods as $group => $names)
                    <optgroup label="— {{ $group }} —">
                        @foreach($names as $i => $name)
                        <option value="{{ $name }}"
                            @if($group === array_key_first($paymentMethods) && $i === 0) selected @endif>
                            {{ $name }}
                        </option>
                        @endforeach
                    </optgroup>
                    @endforeach
                </select>
            </div>

            <div class="form-group-field form-full">
                <label>মন্তব্য</label>
                <textarea name="notes" rows="2" placeholder="চেক নম্বর, লেনদেন আইডি ইত্যাদি...">{{ old('notes') }}</textarea>
            </div>
        </div>

        <div class="form-actions">
            <a href="{{ route('customer-payments.index') }}" class="btn btn-ghost">বাতিল</a>
            <button type="submit" class="btn btn-primary" style="padding:12px 28px">
                <i class="fas fa-paper-plane"></i> পরিশোধ সম্পন্ন করুন
            </button>
        </div>
    </form>
</div>

@push('styles')
<style>
.cp-due-box {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 14px 16px;
    border-radius: 10px;
    font-weight: 600;
}
.cp-due-box--red   { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
.cp-due-box--green { background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; font-size:.92rem; gap:8px; }
.cp-full-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 8px;
    border: none;
    background: #dc2626;
    color: #fff;
    font-size: .82rem;
    font-weight: 700;
    cursor: pointer;
    white-space: nowrap;
    flex-shrink: 0;
}
.cp-full-btn:hover { background: #b91c1c; }
</style>
@endpush

@push('scripts')
<script>
// allCustomers is a client-side cache (server-side search), seeded with the
// pre-selected customer (if arriving from a ledger).
var allCustomers = [];
@if(!empty($preCustomer))
allCustomers.push(@json($preCustomer));
@endif
var searchEl      = document.getElementById('customerSearch');
var hiddenId      = document.getElementById('customerIdInput');
var areaEl        = document.getElementById('areaFilter');   // hidden — holds selected area_id
var areaSearchEl  = document.getElementById('areaSearch');   // visible search box
var allAreas      = @json($areas->map(fn($a) => ['id' => $a->id, 'name' => $a->name])->values());
var dueBox        = document.getElementById('dueBox');
var amountEl      = document.getElementById('amountInput');
var currentDue      = 0;

// Floating dropdown
var dropEl = document.createElement('div');
dropEl.style.cssText = 'position:fixed;display:none;z-index:9999;background:#fff;border:1px solid #e2e8f0;border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.12);overflow-y:auto;max-height:260px';
document.body.appendChild(dropEl);

function positionDrop() {
    const r = searchEl.getBoundingClientRect();
    dropEl.style.top   = (r.bottom + 4) + 'px';
    dropEl.style.left  = r.left + 'px';
    dropEl.style.width = r.width + 'px';
}
window.addEventListener('scroll', positionDrop, true);
window.addEventListener('resize', positionDrop);
document.addEventListener('click', e => {
    if (!searchEl.contains(e.target) && !dropEl.contains(e.target))
        dropEl.style.display = 'none';
});

var _custSearchTimer = null;
searchEl.addEventListener('input', function() {
    const q = this.value.trim();
    hiddenId.value=''; renderDue(null);
    if (!q && !areaEl.value) { dropEl.style.display='none'; return; }
    dropEl.innerHTML = `<div class="suggestion-item" style="color:#94a3b8">খুঁজছি…</div>`;
    positionDrop(); dropEl.style.display = 'block';
    clearTimeout(_custSearchTimer);
    _custSearchTimer = setTimeout(() => fetchCustomers(q), 250);
});

// Clicking into the box while an area is selected lists that area's customers
searchEl.addEventListener('focus', function() {
    if (!searchEl.value.trim() && areaEl.value) fetchCustomers('');
});

// ── Searchable area combobox (client-side filter) ──────────────
var areaDropEl = document.createElement('div');
areaDropEl.style.cssText = 'position:fixed;display:none;z-index:9999;background:#fff;border:1px solid #e2e8f0;border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.12);overflow-y:auto;max-height:260px';
document.body.appendChild(areaDropEl);

function positionAreaDrop() {
    const r = areaSearchEl.getBoundingClientRect();
    areaDropEl.style.top   = (r.bottom + 4) + 'px';
    areaDropEl.style.left  = r.left + 'px';
    areaDropEl.style.width = r.width + 'px';
}
window.addEventListener('scroll', positionAreaDrop, true);
window.addEventListener('resize', positionAreaDrop);
document.addEventListener('click', e => {
    if (!areaSearchEl.contains(e.target) && !areaDropEl.contains(e.target))
        areaDropEl.style.display = 'none';
});

function renderAreaMatches(q) {
    q = (q || '').trim().toLowerCase();
    const list = q ? allAreas.filter(a => a.name.toLowerCase().includes(q)) : allAreas;
    let html = `<div class="suggestion-item" onclick="clearArea()" style="color:#0d9488;font-weight:600">— সব এরিয়া —</div>`;
    html += list.map(a => `<div class="suggestion-item" onclick="selectArea(${a.id})">${a.name}</div>`).join('');
    if (q && !list.length) html += `<div class="suggestion-item" style="color:#94a3b8">কোনো এরিয়া পাওয়া যায়নি</div>`;
    areaDropEl.innerHTML = html;
    positionAreaDrop();
    areaDropEl.style.display = 'block';
}

areaSearchEl.addEventListener('input', function() { renderAreaMatches(this.value); });
areaSearchEl.addEventListener('focus', function() { renderAreaMatches(this.value); });

window.selectArea = function(id) {
    const a = allAreas.find(x => x.id === id);
    areaEl.value       = a ? a.id : '';
    areaSearchEl.value = a ? a.name : '';
    areaDropEl.style.display = 'none';
    onAreaChanged();
};
window.clearArea = function() {
    areaEl.value = '';
    areaSearchEl.value = '';
    areaDropEl.style.display = 'none';
    onAreaChanged();
};

// Picking/clearing an area resets the selection and re-lists customers in it
function onAreaChanged() {
    hiddenId.value=''; searchEl.value=''; renderDue(null);
    if (areaEl.value) {
        dropEl.innerHTML = `<div class="suggestion-item" style="color:#94a3b8">খুঁজছি…</div>`;
        positionDrop(); dropEl.style.display = 'block';
        searchEl.focus();
        fetchCustomers('');
    } else {
        dropEl.style.display = 'none';
    }
}

async function fetchCustomers(q) {
    try {
        const areaParam = areaEl.value ? `&area_id=${encodeURIComponent(areaEl.value)}` : '';
        const res = await fetch(`{{ route('customers.search') }}?q=${encodeURIComponent(q)}${areaParam}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        });
        const raw = await res.json();
        // Map endpoint shape (due_amount) → local shape (due)
        const matches = raw.map(c => ({
            id: c.id, name: c.name, phone: c.phone || '',
            proprietor: c.proprietor || '', due: parseFloat(c.due_amount) || 0,
            area: c.area ? c.area.name : '', area_id: c.area_id || null,
        }));
        matches.forEach(c => { if (!allCustomers.find(x => x.id === c.id)) allCustomers.push(c); });
        renderCustomerMatches(matches);
    } catch (_) {
        dropEl.innerHTML = `<div class="suggestion-item" style="color:#94a3b8">খুঁজতে সমস্যা হয়েছে</div>`;
        positionDrop(); dropEl.style.display = 'block';
    }
}

function renderCustomerMatches(matches) {
    dropEl.innerHTML = matches.map(c => `
        <div class="suggestion-item" onclick="selectCustomer(${c.id})">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:8px">
                <span>
                    ${c.proprietor
                        ? `<strong style="font-size:.92rem">${c.proprietor}</strong><span style="font-size:.76rem;color:#64748b;margin-left:5px">${c.name}</span>`
                        : `<strong style="font-size:.92rem">${c.name}</strong>`}
                </span>
                ${c.due > 0
                    ? `<span style="font-size:.78rem;font-weight:700;color:#dc2626;background:#fee2e2;padding:2px 8px;border-radius:20px;white-space:nowrap">বাকী: ৳${c.due.toLocaleString()}</span>`
                    : `<span style="font-size:.78rem;font-weight:700;color:#16a34a;background:#dcfce7;padding:2px 8px;border-radius:20px;white-space:nowrap">বাকীমুক্ত ✓</span>`}
            </div>
            ${(c.phone || c.area) ? `<span style="font-size:.75rem;color:#94a3b8;display:block;margin-top:2px">${c.phone ? `📞 ${c.phone}` : ''}${c.phone && c.area ? ' &nbsp;·&nbsp; ' : ''}${c.area ? `📍 ${c.area}` : ''}</span>` : ''}
        </div>
    `).join('') || `<div class="suggestion-item" style="color:#94a3b8">কোনো কাস্টমার পাওয়া যায়নি</div>`;

    positionDrop();
    dropEl.style.display = 'block';
}

function selectCustomer(id) {
    const c = allCustomers.find(x => x.id === id);
    if (!c) return;
    hiddenId.value = c.id;
    searchEl.value = c.name;
    currentDue     = c.due;
    renderDue(c);
    dropEl.style.display = 'none';
    syncAreaDisplay(c.area_id);
}

// Reflect the selected customer's area in the এরিয়া field — display only,
// does NOT call onAreaChanged() (that resets the customer selection, which
// would undo the selectCustomer() call that triggered this).
function syncAreaDisplay(areaId) {
    if (!areaId || areaEl.value == areaId) return;
    const a = allAreas.find(x => x.id === areaId);
    areaEl.value       = areaId;
    areaSearchEl.value = a ? a.name : '';
}

function renderDue(c) {
    if (!c) { dueBox.style.display = 'none'; return; }
    dueBox.style.display = 'block';
    if (c.due > 0) {
        dueBox.innerHTML = `
            <div class="cp-due-box cp-due-box--red">
                <div>
                    <div style="font-size:.78rem;opacity:.8">বর্তমান বাকী</div>
                    <div style="font-size:1.4rem;font-weight:800;font-variant-numeric:tabular-nums">
                        ৳ ${c.due.toLocaleString('en', {minimumFractionDigits:0})}
                    </div>
                </div>
                <button type="button" onclick="setFullPay()" class="cp-full-btn">
                    <i class="fas fa-check-double"></i> সম্পূর্ণ আদায়
                </button>
            </div>`;
    } else {
        dueBox.innerHTML = `<div class="cp-due-box cp-due-box--green"><i class="fas fa-circle-check"></i> কোনো বাকী নেই</div>`;
    }
}

function setFullPay() {
    if (currentDue > 0) {
        amountEl.value = currentDue.toFixed(0);
        amountEl.focus();
    }
}

// Pre-select if coming from ledger
@if($selectedId)
var pre = allCustomers.find(c => c.id == {{ $selectedId }});
if (pre) { searchEl.value = pre.name; selectCustomer(pre.id); }
@endif

document.addEventListener('turbo:load', () => bnWatchTakaWords('amountInput', 'amountWords'));
</script>
@endpush
@endsection
