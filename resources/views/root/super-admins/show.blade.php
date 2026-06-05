@extends('layouts.root')
@section('title', $superAdmin->name . ' — লাইসেন্স')
@section('page-title', $superAdmin->name)

@section('content')

@php $currentLic = $superAdmin->activeLicense(); @endphp

@if(session('success'))
<div style="background:#052e16;border:1px solid #166534;color:#86efac;padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:.88rem">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
</div>
@endif
@if(session('error'))
<div style="background:#2d0a0a;border:1px solid #7f1d1d;color:#fca5a5;padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:.88rem">
    <i class="fas fa-circle-xmark"></i> {{ session('error') }}
</div>
@endif

{{-- ── Top summary bar ──────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:20px">
    <div class="rt-stat" style="text-align:left;padding:14px 18px">
        <div style="font-size:.75rem;color:#64748b;margin-bottom:4px">মোট পেমেন্ট</div>
        <div style="font-size:1.3rem;font-weight:800;color:#86efac">
            ৳ {{ number_format($payments->sum('amount'), 0) }}
        </div>
        <div style="font-size:.76rem;color:#475569;margin-top:2px">{{ $payments->count() }}টি লেনদেন</div>
    </div>
    <div class="rt-stat" style="text-align:left;padding:14px 18px">
        <div style="font-size:.75rem;color:#64748b;margin-bottom:4px">সর্বশেষ পেমেন্ট</div>
        <div style="font-size:.95rem;font-weight:700;color:#f1f5f9">
            {{ $payments->first() ? $payments->first()->payment_date->format('d M Y') : '—' }}
        </div>
        <div style="font-size:.76rem;color:#475569;margin-top:2px">
            {{ $payments->first() ? '৳ '.number_format($payments->first()->amount, 0) : 'কোনো পেমেন্ট নেই' }}
        </div>
    </div>
    <div class="rt-stat" style="text-align:left;padding:14px 18px">
        <div style="font-size:.75rem;color:#64748b;margin-bottom:4px">লাইসেন্স অবস্থা</div>
        @if($currentLic)
            @php $st = $currentLic->status; @endphp
            <span class="rt-pill rt-pill-{{ $st }}" style="font-size:.88rem">
                {{ ['active'=>'সক্রিয়','warning'=>'সংকট','grace'=>'গ্রেস','expired'=>'মেয়াদ শেষ'][$st] }}
            </span>
            <div style="font-size:.76rem;color:#475569;margin-top:6px">{{ $currentLic->expires_at->format('d M Y') }} পর্যন্ত</div>
        @else
            <span class="rt-pill rt-pill-expired">লাইসেন্স নেই</span>
        @endif
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 380px;gap:20px;align-items:start">

    {{-- License history --}}
    <div class="rt-card">
        <div class="rt-card-title"><i class="fas fa-file-contract"></i> লাইসেন্স ইতিহাস</div>

        @if($licenses->isEmpty())
            <div class="rt-empty"><i class="fas fa-file-slash"></i> কোনো লাইসেন্স নেই।</div>
        @else
        <table class="rt-table">
            <thead>
                <tr>
                    <th>শুরু</th>
                    <th>মেয়াদ</th>
                    <th>গ্রেস শেষ</th>
                    <th>প্ল্যান</th>
                    <th>অবস্থা</th>
                </tr>
            </thead>
            <tbody>
                @foreach($licenses as $lic)
                <tr>
                    <td style="font-size:.82rem">{{ $lic->starts_at->format('d M Y') }}</td>
                    <td style="font-size:.82rem">{{ $lic->expires_at->format('d M Y') }}</td>
                    <td style="font-size:.82rem">{{ $lic->grace_ends_at->format('d M Y') }}</td>
                    <td style="font-size:.8rem">{{ $lic->plan }}</td>
                    <td>
                        @php $st = $lic->status; @endphp
                        <span class="rt-pill rt-pill-{{ $st }}">
                            {{ ['active'=>'সক্রিয়','warning'=>'সংকট','grace'=>'গ্রেস','expired'=>'শেষ'][$st] }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    {{-- Right column: License renewal + Payment record --}}
    <div style="display:flex;flex-direction:column;gap:20px">

    {{-- Extend License Form --}}
    <div class="rt-card">
        <div class="rt-card-title"><i class="fas fa-key"></i> লাইসেন্স নবায়ন</div>

        @if($currentLic)
        <div style="background:#060d1a;border-radius:9px;padding:14px;margin-bottom:18px;font-size:.85rem">
            <div style="color:#64748b;margin-bottom:6px">বর্তমান মেয়াদ</div>
            <div style="font-size:1.1rem;font-weight:700;color:#f1f5f9">{{ $currentLic->expires_at->format('d M Y') }}</div>
            @php $st = $currentLic->status; @endphp
            <span class="rt-pill rt-pill-{{ $st }}" style="margin-top:8px;display:inline-block">
                {{ ['active'=>'সক্রিয়','warning'=>'সংকট — '.$currentLic->daysUntilExpiry().' দিন বাকি','grace'=>'গ্রেস — '.$currentLic->graceDaysLeft().' দিন বাকি','expired'=>'মেয়াদ শেষ'][$st] }}
            </span>
            <div style="margin-top:10px;color:#94a3b8;font-size:.8rem">
                <i class="fas fa-store" style="color:#f59e0b"></i>
                শাখা সীমা: <strong style="color:#fbbf24">{{ $currentLic->max_shops ?? '∞' }}</strong>
                &nbsp;|&nbsp; বর্তমান: <strong style="color:#86efac">{{ $superAdmin->myShops()->count() }}</strong>
            </div>
        </div>
        @endif

        <form method="POST" action="{{ route('root.super-admins.extend-license', $superAdmin) }}">
            @csrf

            <div class="rt-field">
                <label class="rt-label">বাড়ানোর ধরন</label>
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:.88rem">
                        <input type="radio" name="extend_type" value="plan" checked onchange="toggleExtendType()"> প্ল্যান অনুযায়ী
                    </label>
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:.88rem">
                        <input type="radio" name="extend_type" value="days" onchange="toggleExtendType()"> নির্দিষ্ট দিন
                    </label>
                </div>
            </div>

            <div class="rt-field" id="planField">
                <label class="rt-label">প্ল্যান</label>
                <select class="rt-select" name="plan">
                    <option value="monthly">মাসিক (৩০ দিন)</option>
                    <option value="quarterly">ত্রৈমাসিক (৯০ দিন)</option>
                    <option value="yearly">বার্ষিক (৩৬৫ দিন)</option>
                </select>
            </div>

            <div class="rt-field" id="daysField" style="display:none">
                <label class="rt-label">দিনের সংখ্যা</label>
                <input class="rt-input" type="number" name="days" min="1" max="3650" placeholder="যেমন: 45">
            </div>

            <div class="rt-field">
                <label class="rt-label">কোথা থেকে বাড়াবেন?</label>
                <select class="rt-select" name="from">
                    <option value="expiry">বর্তমান মেয়াদ শেষের পর থেকে</option>
                    <option value="today">আজ থেকে</option>
                </select>
            </div>

            <div class="rt-field">
                <label class="rt-label">সর্বোচ্চ শাখা (ঐচ্ছিক)</label>
                <input class="rt-input" type="number" name="max_shops"
                       value="{{ $currentLic?->max_shops ?? 1 }}" min="1"
                       placeholder="১ = basic, ২+ = multi-branch">
                <small style="color:#64748b;font-size:.76rem">খালি রাখলে বর্তমান সীমা বজায় থাকবে</small>
            </div>

            <div class="rt-field">
                <label class="rt-label">নোট (ঐচ্ছিক)</label>
                <textarea class="rt-textarea" name="notes" rows="2"></textarea>
            </div>

            <button type="submit" class="rt-btn rt-btn-primary" style="width:100%">
                <i class="fas fa-rotate-right"></i> লাইসেন্স নবায়ন করুন
            </button>
        </form>
    </div>

    {{-- ── Record Payment Form ───────────────────────────────── --}}
    <div class="rt-card">
        <div class="rt-card-title"><i class="fas fa-money-bill-wave" style="color:#86efac"></i> পেমেন্ট রেকর্ড করুন</div>

        <form method="POST" action="{{ route('root.payments.store') }}">
            @csrf
            <input type="hidden" name="user_id" value="{{ $superAdmin->id }}">
            <input type="hidden" name="license_id" value="{{ $currentLic?->id }}">

            <div class="rt-field">
                <label class="rt-label">পরিমাণ (৳)</label>
                <input class="rt-input" type="number" name="amount" min="1" step="0.01"
                       placeholder="যেমন: 500" required>
            </div>

            <div class="rt-field">
                <label class="rt-label">পেমেন্ট পদ্ধতি</label>
                <select class="rt-select" name="payment_method" required>
                    <option value="">— বেছে নিন —</option>
                    @foreach(\App\Models\PaymentLog::paymentMethods() as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="rt-field">
                <label class="rt-label">ট্রানজেকশন আইডি / রেফারেন্স</label>
                <input class="rt-input" type="text" name="transaction_id"
                       placeholder="যেমন: bKash TrxID (ঐচ্ছিক)">
            </div>

            <div class="rt-field">
                <label class="rt-label">পেমেন্টের তারিখ</label>
                <input class="rt-input" type="date" name="payment_date"
                       value="{{ now()->toDateString() }}" required>
            </div>

            <div class="rt-field">
                <label class="rt-label">নোট (ঐচ্ছিক)</label>
                <textarea class="rt-textarea" name="notes" rows="2"
                          placeholder="যেমন: মাসিক নবায়ন — জুন ২০২৬"></textarea>
            </div>

            <button type="submit" class="rt-btn rt-btn-primary" style="width:100%">
                <i class="fas fa-check"></i> পেমেন্ট সেভ করুন
            </button>
        </form>
    </div>

    </div>{{-- end right column --}}

</div>

{{-- ── Payment History ──────────────────────────────────────── --}}
@if($payments->count())
<div class="rt-card" style="margin-top:20px">
    <div class="rt-card-title" style="justify-content:space-between">
        <span><i class="fas fa-receipt" style="color:#86efac"></i> পেমেন্ট ইতিহাস</span>
        <span style="font-size:.82rem;color:#86efac;font-weight:700">
            মোট: ৳ {{ number_format($payments->sum('amount'), 0) }}
        </span>
    </div>
    <table class="rt-table">
        <thead>
            <tr>
                <th>তারিখ</th>
                <th>পরিমাণ</th>
                <th>পদ্ধতি</th>
                <th>ট্রানজেকশন আইডি</th>
                <th>রেকর্ডকারী</th>
                <th>নোট</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($payments as $p)
            <tr>
                <td style="font-size:.82rem;white-space:nowrap">{{ $p->payment_date->format('d M Y') }}</td>
                <td style="font-weight:700;color:#86efac">৳ {{ number_format($p->amount, 0) }}</td>
                <td>
                    <span style="font-size:.8rem;background:#0f2640;color:#93c5fd;padding:2px 9px;border-radius:20px">
                        {{ $p->method_label }}
                    </span>
                </td>
                <td style="font-size:.8rem;color:#64748b">{{ $p->transaction_id ?? '—' }}</td>
                <td style="font-size:.8rem;color:#94a3b8">{{ $p->recordedBy->name }}</td>
                <td style="font-size:.8rem;color:#64748b;max-width:160px">{{ $p->notes ?? '—' }}</td>
                <td>
                    <form method="POST" action="{{ route('root.payments.destroy', $p) }}"
                          onsubmit="return confirm('এই পেমেন্ট রেকর্ড মুছে ফেলবেন?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="rt-btn rt-btn-ghost rt-btn-sm"
                                style="color:#f87171;border-color:#7f1d1d">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<div style="margin-top:14px">
    <a href="{{ route('root.super-admins.index') }}" class="rt-btn rt-btn-ghost">
        <i class="fas fa-arrow-left"></i> তালিকায় ফিরুন
    </a>
    <a href="{{ route('root.payments.index') }}" class="rt-btn rt-btn-ghost" style="margin-left:8px">
        <i class="fas fa-receipt"></i> সব পেমেন্ট দেখুন
    </a>
</div>

@push('scripts')
<script>
function toggleExtendType() {
    const type = document.querySelector('input[name="extend_type"]:checked').value;
    document.getElementById('planField').style.display = type === 'plan' ? 'block' : 'none';
    document.getElementById('daysField').style.display = type === 'days' ? 'block' : 'none';
}
</script>
@endpush
@endsection
