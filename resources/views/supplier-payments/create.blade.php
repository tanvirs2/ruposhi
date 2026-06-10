@extends('layouts.app')
@section('title', 'সরবরাহকারী পরিশোধ')
@section('page-title', 'সরবরাহকারী পরিশোধ')
@section('breadcrumb', 'বকেয়া টাকা সরবরাহকারীকে পরিশোধ করুন')

@section('content')
<div class="form-card" style="max-width:560px">
    <form method="POST" action="{{ route('supplier-payments.store') }}">
        @csrf

        {{-- Supplier search --}}
        <div class="form-group-field" style="margin-bottom:0">
            <label>সরবরাহকারী <span class="req">*</span></label>
            <input type="text" id="supplierSearch" placeholder="নাম বা ফোন দিয়ে খুঁজুন..."
                   autocomplete="off" value="{{ $selectedSupplier?->name ?? '' }}" style="width:100%">
            <input type="hidden" name="supplier_id" id="supplierId"
                   value="{{ $selectedSupplier?->id ?? '' }}" required>
        </div>

        {{-- Due box (shown after supplier selected) --}}
        <div id="dueBox" style="margin-top:10px;display:{{ $selectedSupplier ? 'block' : 'none' }}">
            @if($selectedSupplier)
                @php $due = $selectedSupplier->due_amount; @endphp
                @if($due > 0)
                <div class="sp-due-box sp-due-box--red">
                    <div>
                        <div style="font-size:.78rem;opacity:.8">বর্তমান বকেয়া</div>
                        <div style="font-size:1.4rem;font-weight:800;font-variant-numeric:tabular-nums">
                            ৳ {{ number_format($due, 0) }}
                        </div>
                    </div>
                    <button type="button" onclick="setFullPay()" class="sp-full-btn">
                        <i class="fas fa-check-double"></i> সম্পূর্ণ পরিশোধ
                    </button>
                </div>
                @else
                <div class="sp-due-box sp-due-box--green">
                    <i class="fas fa-circle-check"></i> কোনো বকেয়া নেই
                </div>
                @endif
            @endif
        </div>

        <hr style="border:none;border-top:1px solid var(--border);margin:18px 0">

        <div class="form-grid">
            <div class="form-group-field">
                <label>পরিশোধের পরিমাণ (৳) <span class="req">*</span></label>
                <div style="display:flex;gap:8px">
                    <input type="text" inputmode="decimal" name="amount" id="amountField"
                           value="{{ old('amount') }}" required placeholder="০" style="flex:1">
                    <button type="button" onclick="setFullPay()" title="সম্পূর্ণ বকেয়া পরিশোধ"
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
                <input type="text" name="notes" value="{{ old('notes') }}"
                    placeholder="রসিদ নম্বর বা অন্য তথ্য...">
            </div>
        </div>

        <div class="form-actions">
            <a href="{{ route('supplier-payments.index') }}" class="btn btn-ghost">বাতিল</a>
            <button type="submit" class="btn btn-primary" style="padding:12px 28px">
                <i class="fas fa-paper-plane"></i> পরিশোধ সম্পন্ন করুন
            </button>
        </div>
    </form>
</div>

@push('styles')
<style>
.sp-due-box {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 14px 16px;
    border-radius: 10px;
    font-weight: 600;
}
.sp-due-box--red   { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
.sp-due-box--green { background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; font-size:.92rem; gap:8px; }
.sp-full-btn {
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
.sp-full-btn:hover { background: #b91c1c; }
</style>
@endpush

@php
$suppliersData = $suppliers->map(fn($s) => [
    'id'    => $s->id,
    'name'  => $s->name,
    'phone' => $s->phone ?? '',
    'due'   => floatval($s->due_amount),
]);
@endphp

@push('scripts')
<script>
const allSuppliers = @json($suppliersData);

const searchEl  = document.getElementById('supplierSearch');
const hiddenId  = document.getElementById('supplierId');
const dueBox    = document.getElementById('dueBox');
const amountEl  = document.getElementById('amountField');

let currentDue = {{ $selectedSupplier?->due_amount ?? 0 }};

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

searchEl.addEventListener('input', function() {
    const q = this.value.trim().toLowerCase();
    if (!q) { dropEl.style.display='none'; hiddenId.value=''; renderDue(null); return; }

    const matches = allSuppliers.filter(s =>
        s.name.toLowerCase().includes(q) || s.phone.includes(q)
    ).slice(0, 8);

    dropEl.innerHTML = matches.map(s => `
        <div class="suggestion-item" onclick="selectSupplier(${s.id})">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:8px">
                <strong>${s.name}</strong>
                ${s.due > 0
                    ? `<span style="font-size:.78rem;font-weight:700;color:#dc2626;background:#fee2e2;padding:2px 8px;border-radius:20px">বকেয়া: ৳${s.due.toLocaleString()}</span>`
                    : `<span style="font-size:.78rem;font-weight:700;color:#16a34a;background:#dcfce7;padding:2px 8px;border-radius:20px">বকেয়ামুক্ত ✓</span>`}
            </div>
            ${s.phone ? `<span style="font-size:.75rem;color:#94a3b8;display:block;margin-top:2px">📞 ${s.phone}</span>` : ''}
        </div>
    `).join('') || `<div class="suggestion-item" style="color:#94a3b8">কোনো সরবরাহকারী পাওয়া যায়নি</div>`;

    positionDrop();
    dropEl.style.display = 'block';
});

window.addEventListener('scroll', positionDrop, true);
window.addEventListener('resize', positionDrop);
document.addEventListener('click', e => {
    if (!searchEl.contains(e.target) && !dropEl.contains(e.target))
        dropEl.style.display = 'none';
});

function selectSupplier(id) {
    const s = allSuppliers.find(x => x.id === id);
    if (!s) return;
    hiddenId.value   = s.id;
    searchEl.value   = s.name;
    currentDue       = s.due;
    renderDue(s);
    dropEl.style.display = 'none';
}

function renderDue(s) {
    if (!s) { dueBox.style.display = 'none'; return; }
    dueBox.style.display = 'block';
    if (s.due > 0) {
        dueBox.innerHTML = `
            <div class="sp-due-box sp-due-box--red">
                <div>
                    <div style="font-size:.78rem;opacity:.8">বর্তমান বকেয়া</div>
                    <div style="font-size:1.4rem;font-weight:800;font-variant-numeric:tabular-nums">
                        ৳ ${s.due.toLocaleString('en', {minimumFractionDigits:0})}
                    </div>
                </div>
                <button type="button" onclick="setFullPay()" class="sp-full-btn">
                    <i class="fas fa-check-double"></i> সম্পূর্ণ পরিশোধ
                </button>
            </div>`;
    } else {
        dueBox.innerHTML = `<div class="sp-due-box sp-due-box--green"><i class="fas fa-circle-check"></i> কোনো বকেয়া নেই</div>`;
    }
}

function setFullPay() {
    if (currentDue > 0) {
        amountEl.value = currentDue.toFixed(0);
        amountEl.focus();
    }
}

document.addEventListener('DOMContentLoaded', () => bnWatchTakaWords('amountField', 'amountWords'));
</script>
@endpush
@endsection
