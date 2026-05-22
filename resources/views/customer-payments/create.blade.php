@extends('layouts.app')
@section('title', 'কাস্টমার পরিশোধ')
@section('page-title', 'কাস্টমার পরিশোধ')
@section('breadcrumb', 'কাস্টমারের বাকী টাকা আদায় করুন')

@section('content')
<div class="form-card" style="max-width:560px">
    <form method="POST" action="{{ route('customer-payments.store') }}">
        @csrf

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

@php
$customersData = $customers->map(fn($c) => [
    'id'         => $c->id,
    'name'       => $c->name,
    'phone'      => $c->phone ?? '',
    'proprietor' => $c->proprietor ?? '',
    'due'        => floatval($c->due_amount),
]);
@endphp

@push('scripts')
<script>
const allCustomers = @json($customersData);
const searchEl      = document.getElementById('customerSearch');
const hiddenId      = document.getElementById('customerIdInput');
const dueBox        = document.getElementById('dueBox');
const amountEl      = document.getElementById('amountInput');
let currentDue      = 0;

// Floating dropdown
const dropEl = document.createElement('div');
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

searchEl.addEventListener('input', function() {
    const q = this.value.trim().toLowerCase();
    if (!q) { dropEl.style.display='none'; hiddenId.value=''; renderDue(null); return; }

    const matches = allCustomers.filter(c =>
        c.name.toLowerCase().includes(q) ||
        (c.proprietor && c.proprietor.toLowerCase().includes(q)) ||
        c.phone.includes(q)
    ).slice(0, 8);

    dropEl.innerHTML = matches.map(c => `
        <div class="suggestion-item" onclick="selectCustomer(${c.id})">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:8px">
                <span>
                    <strong style="font-size:.92rem">${c.name}</strong>
                    ${c.proprietor ? `<span style="font-size:.76rem;color:#64748b;margin-left:5px">প্রোঃ ${c.proprietor}</span>` : ''}
                </span>
                ${c.due > 0
                    ? `<span style="font-size:.78rem;font-weight:700;color:#dc2626;background:#fee2e2;padding:2px 8px;border-radius:20px;white-space:nowrap">বাকী: ৳${c.due.toLocaleString()}</span>`
                    : `<span style="font-size:.78rem;font-weight:700;color:#16a34a;background:#dcfce7;padding:2px 8px;border-radius:20px;white-space:nowrap">বাকীমুক্ত ✓</span>`}
            </div>
            ${c.phone ? `<span style="font-size:.75rem;color:#94a3b8;display:block;margin-top:2px">📞 ${c.phone}</span>` : ''}
        </div>
    `).join('') || `<div class="suggestion-item" style="color:#94a3b8">কোনো কাস্টমার পাওয়া যায়নি</div>`;

    positionDrop();
    dropEl.style.display = 'block';
});

function selectCustomer(id) {
    const c = allCustomers.find(x => x.id === id);
    if (!c) return;
    hiddenId.value = c.id;
    searchEl.value = c.name;
    currentDue     = c.due;
    renderDue(c);
    dropEl.style.display = 'none';
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
const pre = allCustomers.find(c => c.id == {{ $selectedId }});
if (pre) { searchEl.value = pre.name; selectCustomer(pre.id); }
@endif
</script>
@endpush
@endsection
