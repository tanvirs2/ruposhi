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
@media print {
    .sidebar, .topbar, .no-print, #miniChatRoot, #miniCalcRoot { display: none !important; }
    .main { margin-left: 0 !important; }
    .content { padding: 0 !important; }
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

{{-- ── Cash reconciliation (ক্যাশ মেলানো) ─────────────────────── --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:20px" class="dc-grid">

    <div class="card" style="padding:0;overflow:hidden">
        <div style="padding:14px 16px;border-bottom:1.5px solid var(--border);font-weight:700;font-size:.92rem">
            <i class="fas fa-scale-balanced" style="color:var(--accent);margin-right:6px"></i> ক্যাশ মেলানো ({{ \Carbon\Carbon::parse($date)->format('d/m/Y') }})
        </div>

        @if($reconciliation)
        @php $dcDisc = (float) $reconciliation->discrepancy; @endphp
        <div class="dc-row">
            <span class="dc-label">দিনের শুরুতে ক্যাশ</span>
            <span class="dc-val">৳ {{ number_format($reconciliation->opening_cash, 0) }}</span>
        </div>
        <div class="dc-row">
            <span class="dc-label">+ আজ ক্যাশ নীট (সেভের সময়)</span>
            <span class="dc-val">৳ {{ number_format($reconciliation->system_net, 0) }}</span>
        </div>
        <div class="dc-row">
            <span class="dc-label">= প্রত্যাশিত ক্যাশ</span>
            <span class="dc-val">৳ {{ number_format($reconciliation->opening_cash + $reconciliation->system_net, 0) }}</span>
        </div>
        <div class="dc-row">
            <span class="dc-label">হাতে গোনা ক্যাশ</span>
            <span class="dc-val" style="color:#1d4ed8">৳ {{ number_format($reconciliation->counted_cash, 0) }}</span>
        </div>
        <div class="dc-row dc-net">
            <span class="dc-label" style="color:var(--text-primary);font-size:.95rem">গরমিল</span>
            <span class="dc-val" style="font-size:1.15rem;color:{{ $dcDisc == 0 ? '#16a34a' : '#dc2626' }}">
                @if($dcDisc == 0)
                    ✓ মিলেছে
                @else
                    {{ $dcDisc < 0 ? '−' : '+' }} ৳ {{ number_format(abs($dcDisc), 0) }} {{ $dcDisc < 0 ? '(কম)' : '(বেশি)' }}
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
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                <div class="form-group">
                    <label style="font-size:.8rem;font-weight:600">দিনের শুরুতে ক্যাশ (৳)</label>
                    <input type="number" step="any" min="0" name="opening_cash" class="form-input"
                           value="{{ $reconciliation ? $reconciliation->opening_cash + 0 : ($suggestedOpening !== null ? $suggestedOpening + 0 : '') }}"
                           placeholder="0">
                    @if(!$reconciliation && $suggestedOpening !== null)
                    <small style="color:#64748b;font-size:.72rem">আগের দিনের গোনা ক্যাশ থেকে ধরা হয়েছে</small>
                    @endif
                </div>
                <div class="form-group">
                    <label style="font-size:.8rem;font-weight:600">হাতে গোনা ক্যাশ (৳) *</label>
                    <input type="number" step="any" min="0" name="counted_cash" class="form-input" required
                           value="{{ $reconciliation ? $reconciliation->counted_cash + 0 : '' }}" placeholder="ক্যাশবাক্স গুনে লিখুন">
                </div>
            </div>
            <div class="form-group" style="margin-top:8px">
                <input type="text" name="note" class="form-input" maxlength="255"
                       value="{{ $reconciliation->note ?? '' }}" placeholder="মন্তব্য (ঐচ্ছিক)">
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

<script>
function dcShowForm() {
    var f = document.getElementById('dcReconcileForm');
    if (f) f.style.display = 'block';
}
</script>

<p class="no-print" style="margin-top:14px;font-size:.78rem;color:#64748b">
    <i class="fas fa-circle-info"></i> সব সংখ্যা এই মুহূর্তের ডাটাবেস থেকে সরাসরি হিসাব করা — কোনো ক্যাশ (cache) নেই।
</p>
@endsection
