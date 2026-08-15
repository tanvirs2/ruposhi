@extends('layouts.app')
@section('title', 'দিনশেষ রিপোর্ট')
@section('page-title', 'দিনশেষ রিপোর্ট — মালিকের সারাংশ')

@section('content')

@push('styles')
<meta name="turbo-cache-control" content="no-cache">
@endpush

<style>
.dc-row { display: flex; justify-content: space-between; align-items: center; padding: 11px 16px; border-bottom: 1px solid var(--border); font-size: .9rem; }
.dc-row:last-child { border-bottom: none; }
.dc-row .dc-label { color: var(--text-secondary); font-weight: 600; }
.dc-row .dc-val { font-weight: 800; font-size: 1rem; }
.dc-net { background: var(--surface-2); border-radius: 0 0 12px 12px; }
.dc-tabs { display: flex; gap: 6px; margin-bottom: 16px; border-bottom: 2px solid var(--border); }
.dc-tab { padding: 10px 18px; font-weight: 700; font-size: .88rem; color: var(--text-secondary); background: none; border: none; border-bottom: 3px solid transparent; margin-bottom: -2px; cursor: pointer; }
.dc-tab.active { color: var(--accent); border-bottom-color: var(--accent); }
.dc-tabpane { display: none; }
.dc-tabpane.active { display: block; }
.dc-ledger { display: grid; grid-template-columns: 1fr auto 1fr; }
.dc-ledger-col { display: flex; flex-direction: column; }
.dc-ledger-divider { width: 1px; background: var(--border); }
.dc-ledger-head { padding: 12px 16px; font-weight: 800; font-size: .88rem; text-align: center; }
.dc-ledger-row { display: flex; justify-content: space-between; gap: 10px; padding: 10px 16px; border-bottom: 1px solid var(--border); font-size: .88rem; }
.dc-ledger-row .dc-ledger-label { color: var(--text-secondary); font-weight: 600; }
.dc-ledger-row .dc-ledger-amt { font-weight: 800; white-space: nowrap; }
.dc-ledger-total { background: var(--surface-2); font-size: .95rem; }
@media (max-width: 700px) {
    .dc-ledger { grid-template-columns: 1fr; }
    .dc-ledger-divider { width: 100%; height: 1px; }
}
@media print {
    .sidebar, .topbar, .no-print, #miniChatRoot, #miniCalcRoot { display: none !important; }
    .main { margin-left: 0 !important; }
    .content { padding: 0 !important; }
    .dc-tabpane { display: block !important; }
}

/* ── Cash-reconciliation money inputs ────────────────────────── */
.dc-money-group label { display: block; font-size: .82rem; font-weight: 700; color: var(--text-primary); margin-bottom: 6px; }
.dc-money-group .dc-money-hint { display: block; margin-top: 5px; font-size: .74rem; color: var(--text-secondary); }
.dc-money-wrap { position: relative; }
.dc-money-wrap .dc-money-prefix {
    position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
    font-weight: 800; font-size: 1.05rem; color: var(--text-secondary); pointer-events: none;
}
.dc-money-field {
    width: 100%; box-sizing: border-box;
    padding: 12px 14px 12px 34px;
    font-size: 1.15rem; font-weight: 700; text-align: right;
    font-family: inherit; color: var(--text-primary);
    background: var(--surface);
    border: 1.5px solid var(--border); border-radius: var(--radius-sm);
    outline: none; transition: border-color .15s, box-shadow .15s;
    -moz-appearance: textfield;
}
.dc-money-field::-webkit-outer-spin-button,
.dc-money-field::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
.dc-money-field:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(79,70,229,.12); }
.dc-money-field.dc-money-required { border-color: #93c5fd; background: #eff6ff; }
html[data-theme="dark"] .dc-money-field.dc-money-required { background: rgba(59,130,246,.08); }
.dc-money-field.dc-money-required:focus { border-color: #1d4ed8; box-shadow: 0 0 0 3px rgba(29,78,216,.15); }
.dc-note-field {
    width: 100%; box-sizing: border-box;
    padding: 10px 14px; font-size: .88rem; font-family: inherit; color: var(--text-primary);
    background: var(--surface); border: 1.5px solid var(--border); border-radius: var(--radius-sm);
    outline: none; transition: border-color .15s, box-shadow .15s;
}
.dc-note-field:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(79,70,229,.1); }
@media (max-width: 480px) {
    #dcReconcileForm > div:first-child { grid-template-columns: 1fr !important; }
}
</style>

<div class="no-print" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:16px">
    <form method="GET" class="filter-form" style="display:flex;gap:8px;align-items:center">
        <input type="date" name="date" value="{{ $date }}" class="form-select" onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()">
        <button type="submit" class="btn btn-secondary">দেখুন</button>
    </form>
    <div style="display:flex;gap:8px">
        <button type="button" class="btn btn-secondary" onclick="window.print()">
            <i class="fas fa-print"></i> প্রিন্ট
        </button>
        <form method="POST" action="{{ route('reports.day-close.sms') }}"
              data-confirm-msg="দিনশেষ সারাংশ SMS {{ $ownerPhone ?: '(নম্বর সেট নেই)' }} নম্বরে পাঠাবেন?">
            @csrf
            <input type="hidden" name="date" value="{{ $date }}">
            <button type="submit" class="btn btn-primary" @if(!$ownerPhone) disabled title="দোকানের ফোন নম্বর সেট করা নেই" @endif>
                <i class="fas fa-paper-plane"></i> মালিকের ফোনে SMS
            </button>
        </form>
    </div>
</div>

<div class="dc-tabs no-print">
    <button type="button" class="dc-tab active" data-dctab="cash" onclick="dcSwitchTab('cash')">ক্যাশ মেলানো</button>
    <button type="button" class="dc-tab" data-dctab="summary" onclick="dcSwitchTab('summary')">সারাংশ</button>
</div>

<div class="dc-tabpane" id="dcTabSummary">

{{-- Top stats --}}
<div class="stats-grid" style="margin-bottom:20px;grid-template-columns:repeat(4,1fr)">
    <div class="stat-card stat-green">
        <div class="stat-icon"><i class="fas fa-cart-shopping"></i></div>
        <div class="stat-body">
            <span class="stat-label">মোট বিক্রয় ({{ $salesCount }}টি)</span>
            <span class="stat-value">৳ {{ number_format($salesTotal, 0) }}</span>
        </div>
    </div>
    <div class="stat-card" style="background:linear-gradient(135deg,#eff6ff,#dbeafe)">
        <div class="stat-icon" style="background:#1d4ed8"><i class="fas fa-hand-holding-dollar"></i></div>
        <div class="stat-body">
            <span class="stat-label">নগদ আদায়</span>
            <span class="stat-value" style="color:#1d4ed8">৳ {{ number_format($salesPaid + $standalonePayments, 0) }}</span>
        </div>
    </div>
    <div class="stat-card" style="background:linear-gradient(135deg,#fff7ed,#ffedd5)">
        <div class="stat-icon" style="background:#ea580c"><i class="fas fa-money-bill-transfer"></i></div>
        <div class="stat-body">
            <span class="stat-label">খরচ + পরিশোধ</span>
            <span class="stat-value" style="color:#ea580c">৳ {{ number_format($cashOut, 0) }}</span>
        </div>
    </div>
    <div class="stat-card {{ $newDue > 0 ? 'stat-red' : '' }}">
        <div class="stat-icon" @if($newDue <= 0) style="background:#16a34a" @endif><i class="fas fa-clock-rotate-left"></i></div>
        <div class="stat-body">
            <span class="stat-label">আজ নতুন বাকী</span>
            <span class="stat-value">৳ {{ number_format($newDue, 0) }}</span>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px" class="dc-grid">
    <style>@media (max-width: 900px) { .dc-grid { grid-template-columns: 1fr !important; } }</style>

    {{-- Cash reconciliation --}}
    <div class="card" style="padding:0;overflow:hidden">
        <div style="padding:14px 16px;border-bottom:1.5px solid var(--border);font-weight:700;font-size:.92rem">
            <i class="fas fa-cash-register" style="color:var(--accent);margin-right:6px"></i> ক্যাশ হিসাব ({{ \Carbon\Carbon::parse($date)->format('d/m/Y') }})
        </div>
        <div class="dc-row">
            <span class="dc-label">বিক্রয় থেকে নগদ</span>
            <span class="dc-val" style="color:#16a34a">+ ৳ {{ number_format($salesPaid, 0) }}</span>
        </div>
        <div class="dc-row">
            <span class="dc-label">বাকী আদায় (আলাদা পরিশোধ)</span>
            <span class="dc-val" style="color:#16a34a">+ ৳ {{ number_format($standalonePayments, 0) }}</span>
        </div>
        <div class="dc-row">
            <span class="dc-label">ক্যাশে জমা (খরচ ও জমা)</span>
            <span class="dc-val" style="color:#16a34a">+ ৳ {{ number_format($deposits, 0) }}</span>
        </div>
        <div class="dc-row">
            <span class="dc-label">পণ্য গ্রহণে পরিশোধ</span>
            <span class="dc-val" style="color:#dc2626">− ৳ {{ number_format($purchasePaid, 0) }}</span>
        </div>
        <div class="dc-row">
            <span class="dc-label">সরবরাহকারী পরিশোধ (আলাদা)</span>
            <span class="dc-val" style="color:#dc2626">− ৳ {{ number_format($supplierPayments, 0) }}</span>
        </div>
        <div class="dc-row">
            <span class="dc-label">খরচ</span>
            <span class="dc-val" style="color:#dc2626">− ৳ {{ number_format($expenses, 0) }}</span>
        </div>
        <div class="dc-row dc-net">
            <span class="dc-label" style="color:var(--text-primary);font-size:.95rem">আজ ক্যাশ নীট (ঢুকেছে − বেরিয়েছে)</span>
            <span class="dc-val" style="font-size:1.15rem;color:{{ $cashNet >= 0 ? '#16a34a' : '#dc2626' }}">
                {{ $cashNet < 0 ? '−' : '' }} ৳ {{ number_format(abs($cashNet), 0) }}
            </span>
        </div>
    </div>

    {{-- Day summary --}}
    <div class="card" style="padding:0;overflow:hidden">
        <div style="padding:14px 16px;border-bottom:1.5px solid var(--border);font-weight:700;font-size:.92rem">
            <i class="fas fa-clipboard-list" style="color:var(--accent);margin-right:6px"></i> দিনের সারাংশ
        </div>
        <div class="dc-row">
            <span class="dc-label">মোট বিক্রয়</span>
            <span class="dc-val">৳ {{ number_format($salesTotal, 0) }} <small style="color:#64748b;font-weight:600">({{ $salesCount }}টি)</small></span>
        </div>
        <div class="dc-row">
            <span class="dc-label">মোট পণ্য গ্রহণ</span>
            <span class="dc-val">৳ {{ number_format($purchaseTotal, 0) }} <small style="color:#64748b;font-weight:600">({{ $purchaseCount }}টি)</small></span>
        </div>
        <div class="dc-row">
            <span class="dc-label">আজ নতুন বাকী (বিক্রয় − আদায়)</span>
            <span class="dc-val" style="color:{{ $newDue > 0 ? '#dc2626' : '#16a34a' }}">৳ {{ number_format($newDue, 0) }}</span>
        </div>
        <div class="dc-row">
            <span class="dc-label">মোট ক্যাশ ইন</span>
            <span class="dc-val" style="color:#16a34a">৳ {{ number_format($cashIn, 0) }}</span>
        </div>
        <div class="dc-row">
            <span class="dc-label">মোট ক্যাশ আউট</span>
            <span class="dc-val" style="color:#dc2626">৳ {{ number_format($cashOut, 0) }}</span>
        </div>
        <div class="dc-row dc-net">
            <span class="dc-label" style="color:var(--text-primary)">বিস্তারিত দেখুন</span>
            <span style="display:flex;gap:10px;flex-wrap:wrap" class="no-print">
                <a href="{{ route('reports.sales', ['from' => $date, 'to' => $date]) }}" class="link-primary" style="font-size:.82rem;font-weight:700">বিক্রয় রিপোর্ট</a>
                <a href="{{ route('expenses.index', ['from' => $date, 'to' => $date]) }}" class="link-primary" style="font-size:.82rem;font-weight:700">খরচ ও জমা</a>
            </span>
        </div>
    </div>
</div>

</div>{{-- /dcTabSummary --}}

<div class="dc-tabpane active" id="dcTabCash">

{{-- ── Cash ledger: বাম = ইন, ডান = আউট ─────────────────────── --}}
<div class="card" style="padding:0;overflow:hidden;margin-bottom:20px">
    <div style="padding:14px 16px;border-bottom:1.5px solid var(--border);font-weight:700;font-size:.92rem">
        <i class="fas fa-money-bill-transfer" style="color:var(--accent);margin-right:6px"></i> দিনের ক্যাশ ইন/আউট ({{ \Carbon\Carbon::parse($date)->format('d/m/Y') }})
    </div>
    <div class="dc-ledger">
        <div class="dc-ledger-col">
            <div class="dc-ledger-head" style="color:#16a34a">ইন (ক্যাশ ঢুকেছে)</div>
            <div class="dc-ledger-row">
                <span class="dc-ledger-label">বিক্রয় থেকে নগদ</span>
                <span class="dc-ledger-amt">৳ {{ number_format($salesPaid, 0) }}</span>
            </div>
            <div class="dc-ledger-row">
                <span class="dc-ledger-label">বাকী আদায় (আলাদা পরিশোধ)</span>
                <span class="dc-ledger-amt">৳ {{ number_format($standalonePayments, 0) }}</span>
            </div>
            <div class="dc-ledger-row">
                <span class="dc-ledger-label">ক্যাশে জমা (খরচ ও জমা)</span>
                <span class="dc-ledger-amt">৳ {{ number_format($deposits, 0) }}</span>
            </div>
            <div class="dc-ledger-row dc-ledger-total" style="align-items:flex-start">
                <span class="dc-ledger-label" style="color:var(--text-primary);padding-top:2px">মোট ইন</span>
                <span style="text-align:right">
                    <span class="dc-ledger-amt" style="display:block;color:#16a34a">৳ {{ number_format($cashIn, 0) }}</span>
                    @if(\App\Support\BanglaWords::taka($cashIn) !== '')
                    <small style="display:block;font-size:.72rem;color:#15803d;font-weight:600;margin-top:2px;white-space:normal">{{ \App\Support\BanglaWords::taka($cashIn) }} মাত্র</small>
                    @endif
                </span>
            </div>
        </div>
        <div class="dc-ledger-divider"></div>
        <div class="dc-ledger-col">
            <div class="dc-ledger-head" style="color:#dc2626">আউট (ক্যাশ বেরিয়েছে)</div>
            <div class="dc-ledger-row">
                <span class="dc-ledger-label">পণ্য গ্রহণে পরিশোধ</span>
                <span class="dc-ledger-amt">৳ {{ number_format($purchasePaid, 0) }}</span>
            </div>
            <div class="dc-ledger-row">
                <span class="dc-ledger-label">সরবরাহকারী পরিশোধ (আলাদা)</span>
                <span class="dc-ledger-amt">৳ {{ number_format($supplierPayments, 0) }}</span>
            </div>
            <div class="dc-ledger-row">
                <span class="dc-ledger-label">খরচ</span>
                <span class="dc-ledger-amt">৳ {{ number_format($expenses, 0) }}</span>
            </div>
            <div class="dc-ledger-row dc-ledger-total" style="align-items:flex-start">
                <span class="dc-ledger-label" style="color:var(--text-primary);padding-top:2px">মোট আউট</span>
                <span style="text-align:right">
                    <span class="dc-ledger-amt" style="display:block;color:#dc2626">৳ {{ number_format($cashOut, 0) }}</span>
                    @if(\App\Support\BanglaWords::taka($cashOut) !== '')
                    <small style="display:block;font-size:.72rem;color:#dc2626;font-weight:600;margin-top:2px;white-space:normal">{{ \App\Support\BanglaWords::taka($cashOut) }} মাত্র</small>
                    @endif
                </span>
            </div>
        </div>
    </div>
    <div class="dc-row dc-net">
        <span class="dc-label" style="color:var(--text-primary);font-size:.95rem">আজ ক্যাশ নীট (ইন − আউট)</span>
        <span class="dc-val" style="font-size:1.15rem;color:{{ $cashNet >= 0 ? '#16a34a' : '#dc2626' }}">
            {{ $cashNet < 0 ? '−' : '' }} ৳ {{ number_format(abs($cashNet), 0) }}
        </span>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px" class="dc-grid">

    <div class="card" style="padding:0;overflow:hidden">
        <div style="padding:14px 16px;border-bottom:1.5px solid var(--border);font-weight:700;font-size:.92rem">
            <i class="fas fa-scale-balanced" style="color:var(--accent);margin-right:6px"></i> ক্যাশ মেলানো (গোনা) — {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}
        </div>

        @if($reconciliation)
        @php $dcDisc = (float) $reconciliation->discrepancy; @endphp
        @if($reconciliationStale)
        <div class="dc-row" style="background:#fffbeb;border-bottom:1px solid #fde68a">
            <span style="font-size:.82rem;color:#92400e;font-weight:600">
                <i class="fas fa-triangle-exclamation"></i>
                এই দিনের ক্যাশ মেলানো সেভ করার পর বিক্রয়/ক্রয়/খরচের তথ্য পরিবর্তিত হয়েছে —
                নিচের গরমিল পুরনো (সেভের সময়ের) হিসাবে দেখানো হচ্ছে। বর্তমান ক্যাশ নীট এখন
                ৳ {{ number_format($cashNet, 0) }}। সঠিক গরমিল পেতে "আবার মেলান" চাপুন।
            </span>
        </div>
        @endif
        @php
            // Helper: words for a taka figure that may be negative or zero —
            // BanglaWords::taka() only handles positives, so wrap it here.
            $dcWords = function ($v) {
                $v = (float) $v;
                if ($v == 0) return 'শূন্য টাকা মাত্র';
                $w = \App\Support\BanglaWords::taka(abs($v));
                return $w === '' ? 'শূন্য টাকা মাত্র' : ($v < 0 ? '(−) ' . $w . ' মাত্র' : $w . ' মাত্র');
            };
            $dcExpected = $reconciliation->opening_cash + $reconciliation->system_net;
        @endphp
        <div class="dc-row" style="align-items:flex-start">
            <span class="dc-label" style="padding-top:2px">দিনের শুরুতে ক্যাশ</span>
            <span style="text-align:right">
                <span class="dc-val" style="display:block">৳ {{ number_format($reconciliation->opening_cash, 0) }}</span>
                <small style="display:block;font-size:.72rem;color:#64748b;font-weight:600;margin-top:2px;white-space:normal">{{ $dcWords($reconciliation->opening_cash) }}</small>
            </span>
        </div>
        <div class="dc-row" style="align-items:flex-start">
            <span class="dc-label" style="padding-top:2px">+ আজ ক্যাশ নীট (সেভের সময়)</span>
            <span style="text-align:right">
                <span class="dc-val" style="display:block">৳ {{ number_format($reconciliation->system_net, 0) }}</span>
                <small style="display:block;font-size:.72rem;color:#64748b;font-weight:600;margin-top:2px;white-space:normal">{{ $dcWords($reconciliation->system_net) }}</small>
            </span>
        </div>
        <div class="dc-row" style="align-items:flex-start">
            <span class="dc-label" style="padding-top:2px">= প্রত্যাশিত ক্যাশ</span>
            <span style="text-align:right">
                <span class="dc-val" style="display:block">৳ {{ number_format($dcExpected, 0) }}</span>
                <small style="display:block;font-size:.72rem;color:#64748b;font-weight:600;margin-top:2px;white-space:normal">{{ $dcWords($dcExpected) }}</small>
            </span>
        </div>
        <div class="dc-row" style="align-items:flex-start">
            <span class="dc-label" style="padding-top:2px">হাতে গোনা ক্যাশ</span>
            <span style="text-align:right">
                <span class="dc-val" style="display:block;color:#1d4ed8">৳ {{ number_format($reconciliation->counted_cash, 0) }}</span>
                <small style="display:block;font-size:.72rem;color:#1d4ed8;font-weight:600;margin-top:2px;white-space:normal">{{ $dcWords($reconciliation->counted_cash) }}</small>
            </span>
        </div>
        <div class="dc-row dc-net" style="align-items:flex-start">
            <span class="dc-label" style="color:var(--text-primary);font-size:.95rem;padding-top:2px">গরমিল</span>
            <span style="text-align:right">
                <span class="dc-val" style="display:block;font-size:1.15rem;color:{{ $dcDisc == 0 ? '#16a34a' : '#dc2626' }}">
                    @if($dcDisc == 0)
                        ✓ মিলেছে
                    @else
                        {{ $dcDisc < 0 ? '−' : '+' }} ৳ {{ number_format(abs($dcDisc), 0) }} {{ $dcDisc < 0 ? '(কম)' : '(বেশি)' }}
                    @endif
                </span>
                @if($dcDisc != 0 && \App\Support\BanglaWords::taka(abs($dcDisc)) !== '')
                <small style="display:block;font-size:.72rem;color:#64748b;font-weight:600;margin-top:2px;white-space:normal">
                    {{ \App\Support\BanglaWords::taka(abs($dcDisc)) }} মাত্র {{ $dcDisc < 0 ? '(কম)' : '(বেশি)' }}
                </small>
                @endif
            </span>
        </div>
        @if($reconciliation->note)
        <div class="dc-row"><span class="dc-label">মন্তব্য</span><span style="font-size:.82rem">{{ $reconciliation->note }}</span></div>
        @endif
        <div class="dc-row no-print" style="border-top:1px dashed var(--border)">
            <span style="font-size:.76rem;color:#64748b">
                সেভ করেছেন: {{ $reconciliation->user->name ?? '—' }}, {{ $reconciliation->updated_at->format('d/m/Y h:ia') }}
            </span>
            <button type="button" class="btn btn-ghost" style="font-size:.78rem;padding:4px 10px" onclick="dcShowForm()">আবার মেলান</button>
        </div>
        @endif

        <form method="POST" action="{{ route('reports.day-close.reconcile') }}" id="dcReconcileForm"
              class="no-print" style="padding:14px 16px;display:{{ $reconciliation ? 'none' : 'block' }}">
            @csrf
            <input type="hidden" name="date" value="{{ $date }}">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="dc-money-group">
                    <label for="dcOpeningCash">দিনের শুরুতে ক্যাশ</label>
                    <div class="dc-money-wrap">
                        <span class="dc-money-prefix">৳</span>
                        <input type="number" step="any" min="0" name="opening_cash" id="dcOpeningCash" class="dc-money-field"
                               value="{{ $reconciliation ? $reconciliation->opening_cash + 0 : ($suggestedOpening !== null ? $suggestedOpening + 0 : '') }}"
                               placeholder="0">
                    </div>
                    @if(!$reconciliation && $suggestedOpening !== null)
                    <small class="dc-money-hint">আগের দিনের গোনা ক্যাশ থেকে ধরা হয়েছে</small>
                    @endif
                </div>
                <div class="dc-money-group">
                    <label for="dcCountedCash" style="color:#1d4ed8">হাতে গোনা ক্যাশ <span style="color:#dc2626">*</span></label>
                    <div class="dc-money-wrap">
                        <span class="dc-money-prefix" style="color:#1d4ed8">৳</span>
                        <input type="number" step="any" min="0" name="counted_cash" id="dcCountedCash" class="dc-money-field dc-money-required" required
                               value="{{ $reconciliation ? $reconciliation->counted_cash + 0 : '' }}" placeholder="ক্যাশবাক্স গুনে লিখুন">
                    </div>
                    <small class="dc-money-hint">ক্যাশবাক্স হাতে গুনে এখানে লিখুন</small>
                </div>
            </div>
            <div class="dc-money-group" style="margin-top:14px">
                <label for="dcNote">মন্তব্য <span style="font-weight:400;color:var(--text-secondary)">(ঐচ্ছিক)</span></label>
                <input type="text" name="note" id="dcNote" class="dc-note-field" maxlength="255"
                       value="{{ $reconciliation->note ?? '' }}" placeholder="যেমন: ৫০০ টাকা ভাংতি রাখা হয়েছে">
            </div>
            <div style="margin-top:10px;display:flex;justify-content:space-between;align-items:center;gap:10px">
                <small style="color:#64748b;font-size:.74rem">প্রত্যাশিত = শুরুর ক্যাশ + আজ ক্যাশ নীট (৳ {{ number_format($cashNet, 0) }})</small>
                <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> মিলিয়ে সেভ করুন</button>
            </div>
        </form>
    </div>

    <div class="card" style="padding:0;overflow:hidden">
        <div style="padding:14px 16px;border-bottom:1.5px solid var(--border);font-weight:700;font-size:.92rem">
            <i class="fas fa-clock-rotate-left" style="color:var(--accent);margin-right:6px"></i> গরমিল হিস্ট্রি (শেষ ৭ দিন)
        </div>
        @forelse($recentClosings as $rc)
        @php $rcD = (float) $rc->discrepancy; @endphp
        <div class="dc-row">
            <span class="dc-label">
                <a href="{{ route('reports.day-close', ['date' => $rc->close_date->toDateString()]) }}" class="link-primary">{{ $rc->close_date->format('d/m/Y') }}</a>
                <small style="color:#94a3b8;margin-left:6px">গোনা ৳{{ number_format($rc->counted_cash, 0) }}</small>
            </span>
            <span class="dc-val" style="font-size:.9rem;color:{{ $rcD == 0 ? '#16a34a' : '#dc2626' }}">
                {{ $rcD == 0 ? '✓ মিলেছে' : ($rcD < 0 ? '−' : '+') . ' ৳' . number_format(abs($rcD), 0) }}
            </span>
        </div>
        @empty
        <div style="padding:20px 16px;font-size:.84rem;color:#64748b;text-align:center">এখনো কোনো দিনের ক্যাশ মেলানো হয়নি</div>
        @endforelse
    </div>
</div>

</div>{{-- /dcTabCash --}}

<script>
function dcShowForm() {
    var f = document.getElementById('dcReconcileForm');
    if (f) f.style.display = 'block';
}
function dcSwitchTab(name) {
    var panes = document.querySelectorAll('.dc-tabpane');
    for (var i = 0; i < panes.length; i++) panes[i].classList.remove('active');
    var tabs = document.querySelectorAll('.dc-tab');
    for (var j = 0; j < tabs.length; j++) tabs[j].classList.remove('active');
    var pane = document.getElementById(name === 'cash' ? 'dcTabCash' : 'dcTabSummary');
    if (pane) pane.classList.add('active');
    var tab = document.querySelector('.dc-tab[data-dctab="' + name + '"]');
    if (tab) tab.classList.add('active');
}
</script>

<p class="no-print" style="margin-top:14px;font-size:.78rem;color:#64748b">
    <i class="fas fa-circle-info"></i> সব সংখ্যা এই মুহূর্তের ডাটাবেস থেকে সরাসরি হিসাব করা — কোনো ক্যাশ (cache) নেই।
</p>
@endsection
