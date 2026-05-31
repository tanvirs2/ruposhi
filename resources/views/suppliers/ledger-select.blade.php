@extends('layouts.app')
@section('title', 'সরবরাহকারী লেজার রিপোর্ট')
@section('page-title', 'সরবরাহকারী লেজার রিপোর্ট')

@section('content')

<div class="ledger-select-wrap">

    <div class="ledger-select-card">
        <div class="ledger-select-icon" style="background:#fff7ed;color:#c2410c">
            <i class="fas fa-book-open"></i>
        </div>
        <h2 class="ledger-select-title">সরবরাহকারী লেজার রিপোর্ট</h2>
        <p class="ledger-select-sub">সরবরাহকারী নির্বাচন করুন এবং সময়কাল দিন</p>

        <form id="ledgerForm" onsubmit="goToLedger(event)" class="ledger-select-form">

            {{-- Supplier search --}}
            <div class="ls-field">
                <label class="ls-label">সরবরাহকারী</label>
                <div class="ls-search-wrap">
                    <i class="fas fa-search ls-search-icon"></i>
                    <input type="text" id="supplierSearch" class="ls-input" placeholder="নাম বা ফোন দিয়ে খুঁজুন..."
                        autocomplete="off" oninput="filterSuppliers()">
                </div>
                <div id="supplierDropdown" class="ls-dropdown" style="display:none"></div>
                <input type="hidden" id="supplierId" name="supplier_id">
                <div id="selectedSupplier" class="ls-selected" style="display:none">
                    <i class="fas fa-truck-check" style="color:#16a34a;margin-right:6px"></i>
                    <span id="selectedName"></span>
                    <button type="button" onclick="clearSupplier()" class="ls-clear-btn">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            {{-- Date range --}}
            <div class="ls-date-row">
                <div class="ls-field">
                    <label class="ls-label">শুরুর তারিখ</label>
                    <input type="date" name="from" id="fromDate" class="ls-input" value="{{ now()->toDateString() }}">
                </div>
                <div class="ls-field">
                    <label class="ls-label">শেষ তারিখ</label>
                    <input type="date" name="to" id="toDate" class="ls-input" value="{{ now()->toDateString() }}">
                </div>
            </div>

            {{-- Quick range --}}
            <div class="ls-quick-row">
                <button type="button" onclick="lsRange('this_month')" class="ls-quick-btn">এই মাস</button>
                <button type="button" onclick="lsRange('last_month')" class="ls-quick-btn">গত মাস</button>
                <button type="button" onclick="lsRange('this_year')"  class="ls-quick-btn">এই বছর</button>
                <button type="button" onclick="lsRange('all')"        class="ls-quick-btn">সব</button>
            </div>

            <button type="submit" class="btn btn-primary ls-submit" id="submitBtn" disabled>
                <i class="fas fa-search"></i> লেজার দেখুন
            </button>

        </form>
    </div>

    {{-- All suppliers list --}}
    @if(count($suppliers))
    <div class="ls-recent-card">
        <div class="ls-recent-title"><i class="fas fa-truck"></i> সকল সরবরাহকারী</div>
        <div class="ls-recent-list">
            @foreach($suppliers as $s)
            <button type="button" class="ls-recent-item" onclick="pickSupplier({{ $s->id }}, '{{ addslashes($s->name) }}')">
                <div class="ls-recent-name">{{ $s->name }}</div>
                @if($s->phone)
                <div class="ls-recent-sub"><i class="fas fa-phone" style="font-size:.7rem"></i> {{ $s->phone }}</div>
                @endif
                @if($s->due_amount > 0)
                <span class="ls-due-badge">বাকী ৳{{ number_format($s->due_amount, 0) }}</span>
                @elseif($s->due_amount < 0)
                <span class="ls-due-badge" style="background:#eff6ff;color:#1d4ed8">অগ্রিম ৳{{ number_format(abs($s->due_amount), 0) }}</span>
                @endif
            </button>
            @endforeach
        </div>
    </div>
    @endif

</div>

@endsection

@push('styles')
<style>
.ledger-select-wrap {
    max-width: 900px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 24px;
    align-items: start;
}
@media (max-width: 700px) {
    .ledger-select-wrap { grid-template-columns: 1fr; }
}
.ledger-select-card {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 36px 32px;
    text-align: center;
}
.ledger-select-icon {
    width: 64px; height: 64px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.6rem;
    margin: 0 auto 16px;
}
.ledger-select-title { font-size: 1.2rem; font-weight: 800; margin-bottom: 6px; }
.ledger-select-sub   { font-size: .85rem; color: #64748b; margin-bottom: 28px; }
.ledger-select-form  { text-align: left; }
.ls-field            { margin-bottom: 16px; }
.ls-label            { display: block; font-size: .82rem; font-weight: 600; color: #475569; margin-bottom: 6px; }
.ls-input {
    width: 100%;
    padding: 10px 14px 10px 38px;
    border: 1.5px solid var(--border);
    border-radius: 8px;
    font-size: .9rem;
    font-family: inherit;
    box-sizing: border-box;
    transition: border-color .15s;
}
.ls-input:focus { outline: none; border-color: var(--accent); }
.ls-date-row .ls-input { padding-left: 14px; }
.ls-search-wrap   { position: relative; }
.ls-search-icon   {
    position: absolute; left: 12px; top: 50%;
    transform: translateY(-50%);
    color: #94a3b8; font-size: .85rem; pointer-events: none;
}
.ls-dropdown {
    border: 1.5px solid var(--border);
    border-top: none;
    border-radius: 0 0 8px 8px;
    background: #fff;
    max-height: 240px;
    overflow-y: auto;
    box-shadow: 0 8px 20px rgba(0,0,0,.08);
    position: relative; z-index: 10;
}
.ls-dropdown-item     { padding: 10px 14px; cursor: pointer; border-bottom: 1px solid #f1f5f9; transition: background .1s; }
.ls-dropdown-item:hover { background: var(--accent-light); }
.ls-dropdown-item:last-child { border-bottom: none; }
.ls-dropdown-name { font-weight: 600; font-size: .9rem; }
.ls-dropdown-sub  { font-size: .78rem; color: #64748b; margin-top: 2px; }
.ls-selected {
    display: flex; align-items: center;
    background: #f0fdf4; border: 1.5px solid #bbf7d0; border-radius: 8px;
    padding: 10px 14px; font-weight: 600; color: #15803d; font-size: .9rem; margin-top: 6px;
}
.ls-clear-btn { margin-left: auto; background: none; border: none; color: #64748b; cursor: pointer; padding: 0 2px; font-size: .85rem; }
.ls-clear-btn:hover { color: #dc2626; }
.ls-date-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.ls-quick-row { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px; }
.ls-quick-btn {
    padding: 5px 14px; border: 1.5px solid var(--border); border-radius: 20px;
    background: #fff; font-size: .78rem; font-family: inherit; font-weight: 600; color: #475569; cursor: pointer; transition: all .15s;
}
.ls-quick-btn:hover { border-color: var(--accent); color: var(--accent); background: var(--accent-light); }
.ls-submit { width: 100%; padding: 12px; font-size: .95rem; border-radius: 10px; }
.ls-submit:disabled { opacity: .45; cursor: not-allowed; }
.ls-recent-card { background: #fff; border: 1px solid var(--border); border-radius: 16px; overflow: hidden; }
.ls-recent-title { padding: 14px 16px; font-size: .82rem; font-weight: 700; color: #475569; background: var(--bg); border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 8px; }
.ls-recent-list  { max-height: 420px; overflow-y: auto; }
.ls-recent-item  {
    display: flex; flex-direction: column; align-items: flex-start;
    width: 100%; padding: 10px 16px; border: none; background: none;
    border-bottom: 1px solid #f1f5f9; cursor: pointer; text-align: left;
    font-family: inherit; transition: background .1s; position: relative;
}
.ls-recent-item:hover { background: var(--accent-light); }
.ls-recent-name { font-weight: 600; font-size: .88rem; }
.ls-recent-sub  { font-size: .76rem; color: #64748b; margin-top: 1px; }
.ls-due-badge {
    position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
    background: #fee2e2; color: #b91c1c; font-size: .7rem; font-weight: 700; padding: 2px 8px; border-radius: 20px;
}
</style>
@endpush

@push('scripts')
<script>
const allSuppliers = @json($suppliers);
let selectedId = null;

function filterSuppliers() {
    const q = document.getElementById('supplierSearch').value.toLowerCase().trim();
    const dd = document.getElementById('supplierDropdown');
    if (!q) { dd.style.display = 'none'; return; }

    const matches = allSuppliers.filter(s =>
        s.name.toLowerCase().includes(q) ||
        (s.phone && s.phone.includes(q))
    ).slice(0, 8);

    if (!matches.length) { dd.innerHTML = '<div class="ls-dropdown-item" style="color:#94a3b8">কোনো সরবরাহকারী পাওয়া যায়নি</div>'; dd.style.display = 'block'; return; }

    dd.innerHTML = matches.map(s => `
        <div class="ls-dropdown-item" onclick="pickSupplier(${s.id}, '${s.name.replace(/'/g,"\\'")}')">
            <div class="ls-dropdown-name">${s.name}</div>
            <div class="ls-dropdown-sub">
                ${s.phone ?? ''}
                ${parseFloat(s.due_amount) > 0
                    ? ' &nbsp;·&nbsp; <span style="color:#dc2626">বাকী ৳' + Number(s.due_amount).toLocaleString() + '</span>'
                    : parseFloat(s.due_amount) < 0
                    ? ' &nbsp;·&nbsp; <span style="color:#1d4ed8">অগ্রিম ৳' + Math.abs(Number(s.due_amount)).toLocaleString() + '</span>'
                    : ''}
            </div>
        </div>`).join('');
    dd.style.display = 'block';
}

function pickSupplier(id, name) {
    selectedId = id;
    document.getElementById('supplierId').value = id;
    document.getElementById('supplierSearch').style.display = 'none';
    document.querySelector('.ls-search-icon').style.display = 'none';
    document.getElementById('supplierDropdown').style.display = 'none';
    document.getElementById('selectedName').textContent = name;
    document.getElementById('selectedSupplier').style.display = 'flex';
    document.getElementById('submitBtn').disabled = false;
}

function clearSupplier() {
    selectedId = null;
    document.getElementById('supplierId').value = '';
    document.getElementById('supplierSearch').value = '';
    document.getElementById('supplierSearch').style.display = 'block';
    document.querySelector('.ls-search-icon').style.display = 'block';
    document.getElementById('selectedSupplier').style.display = 'none';
    document.getElementById('submitBtn').disabled = true;
}

function goToLedger(e) {
    e.preventDefault();
    if (!selectedId) return;
    const from = document.getElementById('fromDate').value;
    const to   = document.getElementById('toDate').value;
    window.location.href = `/suppliers/${selectedId}/ledger?from=${from}&to=${to}`;
}

function lsRange(type) {
    const today = new Date();
    let from, to;
    if (type === 'this_month') { from = new Date(today.getFullYear(), today.getMonth(), 1); to = today; }
    else if (type === 'last_month') { from = new Date(today.getFullYear(), today.getMonth()-1, 1); to = new Date(today.getFullYear(), today.getMonth(), 0); }
    else if (type === 'this_year') { from = new Date(today.getFullYear(), 0, 1); to = today; }
    else { from = new Date('2000-01-01'); to = today; }
    const fmt = d => d.toISOString().slice(0,10);
    document.getElementById('fromDate').value = fmt(from);
    document.getElementById('toDate').value   = fmt(to);
}

document.addEventListener('click', e => {
    if (!e.target.closest('.ls-search-wrap') && !e.target.closest('.ls-dropdown'))
        document.getElementById('supplierDropdown').style.display = 'none';
});
</script>
@endpush
