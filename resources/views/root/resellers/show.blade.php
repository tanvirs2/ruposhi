@extends('layouts.root')
@section('title', $reseller->name . ' — রিসেলার')
@section('page-title', $reseller->name)

@section('content')

@if(session('success'))
<div class="rt-alert rt-alert-success" style="margin-bottom:16px">
    <i class="fas fa-circle-check"></i> {{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="rt-alert rt-alert-error" style="margin-bottom:16px">
    <i class="fas fa-circle-exclamation"></i> {{ session('error') }}
</div>
@endif

{{-- ── Summary Bar ──────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px">

    <div class="rt-card" style="margin:0;border-top:3px solid #22c55e">
        <div style="padding:14px 16px">
            <div style="font-size:.65rem;font-weight:700;color:#16a34a;letter-spacing:.05em;margin-bottom:4px">💰 মোট client payment</div>
            <div style="font-size:1.6rem;font-weight:800;color:#15803d">৳{{ number_format($totalClientPayments, 0) }}</div>
            <div style="font-size:.68rem;color:#64748b;margin-top:4px">তাদের client-রা মোট এটুকু দিয়েছে</div>
        </div>
    </div>

    <div class="rt-card" style="margin:0;border-top:3px solid #6366f1">
        <div style="padding:14px 16px">
            <div style="font-size:.65rem;font-weight:700;color:#4f46e5;letter-spacing:.05em;margin-bottom:4px">📊 মোট কমিশন ({{ $rate }}%)</div>
            <div style="font-size:1.6rem;font-weight:800;color:#4338ca">৳{{ number_format($commissionEarned, 0) }}</div>
            <div style="font-size:.68rem;color:#64748b;margin-top:4px">মোট payment-এর {{ $rate }}%</div>
        </div>
    </div>

    <div class="rt-card" style="margin:0;border-top:3px solid #94a3b8">
        <div style="padding:14px 16px">
            <div style="font-size:.65rem;font-weight:700;color:#475569;letter-spacing:.05em;margin-bottom:4px">✅ ইতিমধ্যে দেওয়া হয়েছে</div>
            <div style="font-size:1.6rem;font-weight:800;color:#334155">৳{{ number_format($totalPaidOut, 0) }}</div>
            <div style="font-size:.68rem;color:#64748b;margin-top:4px">এখন পর্যন্ত মোট payout</div>
        </div>
    </div>

    <div class="rt-card" style="margin:0;border-top:3px solid {{ $balanceDue > 0 ? '#ef4444' : '#22c55e' }}">
        <div style="padding:14px 16px">
            <div style="font-size:.65rem;font-weight:700;color:{{ $balanceDue > 0 ? '#dc2626' : '#16a34a' }};letter-spacing:.05em;margin-bottom:4px">
                {{ $balanceDue > 0 ? '⏳ এখনো বাকি আছে' : '✅ সব পরিশোধ' }}
            </div>
            <div style="font-size:1.6rem;font-weight:800;color:{{ $balanceDue > 0 ? '#dc2626' : '#16a34a' }}">
                ৳{{ number_format(abs($balanceDue), 0) }}
            </div>
            <div style="font-size:.68rem;color:#64748b;margin-top:4px">
                {{ $balanceDue > 0 ? 'এই পরিমাণ দেওয়া বাকি' : 'কোনো বকেয়া নেই' }}
            </div>
        </div>
    </div>

</div>

<div style="display:grid;grid-template-columns:1fr 380px;gap:20px;align-items:start">

    {{-- Left column --}}
    <div style="display:flex;flex-direction:column;gap:20px">

        {{-- Reseller info --}}
        <div class="rt-card" style="margin:0">
            <div class="rt-card-title" style="justify-content:space-between">
                <span><i class="fas fa-user-tie"></i> রিসেলার তথ্য</span>
                <a href="{{ route('root.resellers.edit', $reseller) }}" class="rt-btn rt-btn-ghost rt-btn-sm">
                    <i class="fas fa-pen"></i> সম্পাদনা
                </a>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;padding:4px 0">
                <div>
                    <div style="font-size:.68rem;color:#64748b">নাম</div>
                    <div style="font-weight:600">{{ $reseller->name }}</div>
                </div>
                <div>
                    <div style="font-size:.68rem;color:#64748b">ইমেইল</div>
                    <div style="font-size:.85rem">{{ $reseller->email }}</div>
                </div>
                <div>
                    <div style="font-size:.68rem;color:#64748b">কমিশন হার</div>
                    <div style="font-size:1.2rem;font-weight:800;color:#6d28d9">{{ $rate }}%</div>
                </div>
                <div>
                    <div style="font-size:.68rem;color:#64748b">মোট Client</div>
                    <div style="font-size:1.2rem;font-weight:800;color:#0891b2">{{ $clients->count() }}</div>
                </div>
            </div>
        </div>

        {{-- Payout history --}}
        <div class="rt-card" style="margin:0">
            <div class="rt-card-title"><i class="fas fa-money-bill-transfer" style="color:#22c55e"></i> পেমেন্ট ইতিহাস (দেওয়া হয়েছে)</div>
            @if($payouts->isEmpty())
                <div class="rt-empty">এখনো কোনো পেমেন্ট দেওয়া হয়নি।</div>
            @else
            <table class="rt-table">
                <thead>
                    <tr>
                        <th>তারিখ</th>
                        <th>পরিমাণ</th>
                        <th>মাধ্যম</th>
                        <th>Trx ID</th>
                        <th>নোট</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payouts as $p)
                    <tr>
                        <td>{{ $p->paid_date->format('d M Y') }}</td>
                        <td style="font-weight:700;color:#15803d">৳{{ number_format($p->amount, 0) }}</td>
                        <td><span class="rt-pill" style="background:#dcfce7;color:#166534">{{ $p->payment_method }}</span></td>
                        <td style="font-size:.78rem;color:#64748b">{{ $p->transaction_id ?? '—' }}</td>
                        <td style="font-size:.78rem;color:#64748b">{{ $p->notes ?? '—' }}</td>
                        <td>
                            <form method="POST" action="{{ route('root.resellers.payouts.destroy', [$reseller, $p]) }}"
                                  onsubmit="return confirm('এই রেকর্ড মুছবেন?')">
                                @csrf @method('DELETE')
                                <button class="rt-btn rt-btn-danger rt-btn-sm"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td style="font-weight:700">মোট পরিশোধ</td>
                        <td style="font-weight:800;color:#15803d">৳{{ number_format($totalPaidOut, 0) }}</td>
                        <td colspan="4"></td>
                    </tr>
                </tfoot>
            </table>
            @endif
        </div>

        {{-- Client payment log --}}
        <div class="rt-card" style="margin:0">
            <div class="rt-card-title"><i class="fas fa-receipt" style="color:#6366f1"></i> Client payment ইতিহাস (সর্বশেষ ২০টি)</div>
            @if($clientPayments->isEmpty())
                <div class="rt-empty">কোনো client payment নেই।</div>
            @else
            <table class="rt-table">
                <thead>
                    <tr><th>তারিখ</th><th>Client</th><th>পরিমাণ</th><th>কমিশন ({{ $rate }}%)</th></tr>
                </thead>
                <tbody>
                    @foreach($clientPayments as $cp)
                    <tr>
                        <td style="font-size:.8rem">{{ \Carbon\Carbon::parse($cp->payment_date)->format('d M Y') }}</td>
                        <td>{{ $cp->user->name ?? '—' }}</td>
                        <td style="font-weight:600">৳{{ number_format($cp->amount, 0) }}</td>
                        <td style="color:#6d28d9;font-weight:600">৳{{ number_format($cp->amount * $rate / 100, 0) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>

    </div>

    {{-- Right column: payout form --}}
    <div style="display:flex;flex-direction:column;gap:16px">

        {{-- Balance card --}}
        @if($balanceDue > 0)
        <div style="background:linear-gradient(135deg,#fef2f2,#fff);border:2px solid #fca5a5;border-radius:12px;padding:16px 18px">
            <div style="font-size:.72rem;font-weight:700;color:#dc2626;margin-bottom:4px">⚠️ পেমেন্ট বাকি আছে</div>
            <div style="font-size:2rem;font-weight:800;color:#dc2626">৳{{ number_format($balanceDue, 0) }}</div>
            <div style="font-size:.72rem;color:#64748b;margin-top:4px">{{ $reseller->name }}-কে এই পরিমাণ দেওয়া বাকি</div>
        </div>
        @else
        <div style="background:#f0fdf4;border:2px solid #86efac;border-radius:12px;padding:16px 18px">
            <div style="font-size:.72rem;font-weight:700;color:#16a34a;margin-bottom:4px">✅ সব পরিশোধ হয়েছে</div>
            <div style="font-size:.85rem;color:#15803d">কোনো বকেয়া নেই।</div>
        </div>
        @endif

        {{-- Payout form --}}
        <div class="rt-card" style="margin:0">
            <div class="rt-card-title"><i class="fas fa-paper-plane" style="color:#22c55e"></i> পেমেন্ট দিন</div>

            @if($errors->any())
            <div class="rt-alert rt-alert-error">
                @foreach($errors->all() as $e){{ $e }}<br>@endforeach
            </div>
            @endif

            <form method="POST" action="{{ route('root.resellers.payouts.store', $reseller) }}">
                @csrf
                <div class="rt-field">
                    <label class="rt-label">পরিমাণ (৳) *</label>
                    <input class="rt-input" type="number" name="amount" step="0.01" min="1"
                           value="{{ old('amount', $balanceDue > 0 ? floor($balanceDue) : '') }}" required
                           placeholder="যেমন: {{ number_format($balanceDue > 0 ? floor($balanceDue) : 0) }}">
                </div>
                <div class="rt-field">
                    <label class="rt-label">মাধ্যম *</label>
                    <select class="rt-select" name="payment_method" required>
                        @foreach($methods as $val => $label)
                        <option value="{{ $val }}" {{ old('payment_method','cash') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="rt-field">
                    <label class="rt-label">তারিখ *</label>
                    <input class="rt-input" type="date" name="paid_date"
                           value="{{ old('paid_date', now()->toDateString()) }}" required>
                </div>
                <div class="rt-field">
                    <label class="rt-label">Transaction ID (ঐচ্ছিক)</label>
                    <input class="rt-input" name="transaction_id" value="{{ old('transaction_id') }}"
                           placeholder="bKash/Nagad trx number">
                </div>
                <div class="rt-field">
                    <label class="rt-label">নোট (ঐচ্ছিক)</label>
                    <textarea class="rt-textarea" name="notes" rows="2">{{ old('notes') }}</textarea>
                </div>
                <button type="submit" class="rt-btn rt-btn-primary" style="width:100%">
                    <i class="fas fa-check"></i> পেমেন্ট রেকর্ড করুন
                </button>
            </form>
        </div>

        {{-- Clients list --}}
        @if($clients->count())
        <div class="rt-card" style="margin:0">
            <div class="rt-card-title"><i class="fas fa-briefcase"></i> এর Client-রা</div>
            @foreach($clients as $cl)
            @php $lic = $cl->activeLicense(); @endphp
            <div style="display:flex;align-items:center;gap:8px;padding:8px 14px;border-bottom:1px solid #f1f5f9">
                <div style="flex:1">
                    <div style="font-size:.82rem;font-weight:600">{{ $cl->name }}</div>
                    <div style="font-size:.7rem;color:#64748b">{{ $cl->email }}</div>
                </div>
                @if($lic)
                <span class="rt-pill rt-pill-{{ $lic->status }}" style="font-size:.62rem">
                    {{ ['active'=>'সক্রিয়','warning'=>'সংকট','grace'=>'গ্রেস','expired'=>'শেষ'][$lic->status] ?? $lic->status }}
                </span>
                @else
                <span class="rt-pill rt-pill-expired" style="font-size:.62rem">নেই</span>
                @endif
                <a href="{{ route('root.super-admins.show', $cl) }}" class="rt-btn rt-btn-ghost rt-btn-sm">
                    <i class="fas fa-eye"></i>
                </a>
            </div>
            @endforeach
        </div>
        @endif

    </div>

</div>

<div style="margin-top:16px">
    <a href="{{ route('root.resellers.index') }}" class="rt-btn rt-btn-ghost">
        <i class="fas fa-arrow-left"></i> রিসেলার তালিকায় ফিরুন
    </a>
</div>

@endsection
