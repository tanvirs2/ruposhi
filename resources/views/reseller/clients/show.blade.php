@extends('layouts.reseller')
@section('title', $user->name . ' — বিবরণ')
@section('page-title', $user->name)

@section('content')

@php $currentLic = $user->activeLicense(); @endphp

@if(session('success'))
<div style="background:#022c22;border:1px solid #166534;color:#6ee7b7;padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:.88rem">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
</div>
@endif
@if(session('error'))
<div style="background:#2d0a0a;border:1px solid #7f1d1d;color:#fca5a5;padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:.88rem">
    <i class="fas fa-circle-xmark"></i> {{ session('error') }}
</div>
@endif

{{-- ── Summary bar ──────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:20px">
    <div class="rt-stat" style="text-align:left;padding:14px 18px">
        <div style="font-size:.75rem;color:#64748b;margin-bottom:4px">মোট পেমেন্ট</div>
        <div style="font-size:1.3rem;font-weight:800;color:#6ee7b7">
            ৳ {{ number_format($payments->sum('amount'), 0) }}
        </div>
        <div style="font-size:.76rem;color:#475569;margin-top:2px">{{ $payments->count() }}টি লেনদেন</div>
    </div>
    <div class="rt-stat" style="text-align:left;padding:14px 18px">
        <div style="font-size:.75rem;color:#64748b;margin-bottom:4px">শাখা সংখ্যা</div>
        <div style="font-size:1.3rem;font-weight:800;color:#93c5fd">{{ $shops->count() }}</div>
        <div style="font-size:.76rem;color:#475569;margin-top:2px">
            {{ $shops->where('is_locked', false)->count() }} সক্রিয়
            @if($shops->where('is_locked', true)->count())
                · {{ $shops->where('is_locked', true)->count() }} লক
            @endif
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

<div style="display:grid;grid-template-columns:1fr 360px;gap:20px;align-items:start">

    {{-- Left: info + license history + payment history --}}
    <div style="display:flex;flex-direction:column;gap:20px">

        {{-- Client info --}}
        <div class="rt-card">
            <div class="rt-card-title"><i class="fas fa-user-circle"></i> ক্লায়েন্ট তথ্য</div>
            <table class="rt-table">
                <tbody>
                    <tr>
                        <td style="color:#64748b;width:130px">নাম</td>
                        <td style="font-weight:600">{{ $user->name }}</td>
                    </tr>
                    <tr>
                        <td style="color:#64748b">ইমেইল</td>
                        <td>{{ $user->email }}</td>
                    </tr>
                    <tr>
                        <td style="color:#64748b">যোগদানের তারিখ</td>
                        <td style="font-size:.83rem">{{ $user->created_at->format('d M Y') }}</td>
                    </tr>
                    <tr>
                        <td style="color:#64748b">শাখাসমূহ</td>
                        <td>
                            @forelse($shops as $shop)
                                <span style="display:inline-flex;align-items:center;gap:5px;
                                    background:{{ $shop->is_locked ? '#1f2937' : '#0f2640' }};
                                    color:{{ $shop->is_locked ? '#6b7280' : '#93c5fd' }};
                                    padding:2px 10px;border-radius:20px;font-size:.78rem;margin:2px">
                                    @if($shop->is_locked)<i class="fas fa-lock" style="font-size:.65rem"></i>@endif
                                    {{ $shop->name }}
                                </span>
                            @empty
                                <span style="color:#475569">কোনো শাখা নেই</span>
                            @endforelse
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- License history --}}
        <div class="rt-card">
            <div class="rt-card-title"><i class="fas fa-file-contract"></i> লাইসেন্স ইতিহাস</div>
            @if($licenses->isEmpty())
                <div class="rt-empty">কোনো লাইসেন্স নেই।</div>
            @else
            <table class="rt-table">
                <thead>
                    <tr>
                        <th>শুরু</th>
                        <th>মেয়াদ শেষ</th>
                        <th>প্ল্যান</th>
                        <th>শাখা সীমা</th>
                        <th>অবস্থা</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($licenses as $lic)
                    @php $st = $lic->status; @endphp
                    <tr>
                        <td style="font-size:.82rem">{{ $lic->starts_at->format('d M Y') }}</td>
                        <td style="font-size:.82rem">{{ $lic->expires_at->format('d M Y') }}</td>
                        <td style="font-size:.8rem">{{ $lic->plan }}</td>
                        <td style="text-align:center">{{ $lic->max_shops ?? '∞' }}</td>
                        <td><span class="rt-pill rt-pill-{{ $st }}">
                            {{ ['active'=>'সক্রিয়','warning'=>'সংকট','grace'=>'গ্রেস','expired'=>'শেষ'][$st] }}
                        </span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>

        {{-- Payment history --}}
        @if($payments->count())
        <div class="rt-card">
            <div class="rt-card-title" style="justify-content:space-between">
                <span><i class="fas fa-receipt" style="color:#6ee7b7"></i> পেমেন্ট ইতিহাস</span>
                <span style="font-size:.82rem;color:#6ee7b7;font-weight:700">
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
                        <th>নোট</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payments as $p)
                    <tr>
                        <td style="font-size:.82rem;white-space:nowrap">{{ $p->payment_date->format('d M Y') }}</td>
                        <td style="font-weight:700;color:#6ee7b7">৳ {{ number_format($p->amount, 0) }}</td>
                        <td>
                            <span style="font-size:.78rem;background:#0f2640;color:#93c5fd;padding:2px 9px;border-radius:20px">
                                {{ $p->method_label }}
                            </span>
                        </td>
                        <td style="font-size:.8rem;color:#64748b">{{ $p->transaction_id ?? '—' }}</td>
                        <td style="font-size:.8rem;color:#64748b">{{ $p->notes ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

    </div>

    {{-- Right: Extend license --}}
    <div class="rt-card">
        <div class="rt-card-title"><i class="fas fa-key"></i> লাইসেন্স নবায়ন</div>

        @if($currentLic)
        <div style="background:#060d1a;border-radius:9px;padding:14px;margin-bottom:18px;font-size:.85rem">
            <div style="color:#64748b;margin-bottom:4px">বর্তমান মেয়াদ</div>
            <div style="font-size:1.05rem;font-weight:700;color:#f1f5f9">{{ $currentLic->expires_at->format('d M Y') }}</div>
            @php $st = $currentLic->status; @endphp
            <span class="rt-pill rt-pill-{{ $st }}" style="margin-top:8px;display:inline-block">
                {{ ['active'=>'সক্রিয়','warning'=>'সংকট — '.$currentLic->daysUntilExpiry().' দিন','grace'=>'গ্রেস — '.$currentLic->graceDaysLeft().' দিন','expired'=>'মেয়াদ শেষ'][$st] }}
            </span>
        </div>
        @endif

        <form method="POST" action="{{ route('reseller.clients.extend-license', $user) }}">
            @csrf
            <div class="rt-field">
                <label class="rt-label">প্ল্যান</label>
                <select class="rt-select" name="plan">
                    <option value="monthly">মাসিক (৩০ দিন)</option>
                    <option value="quarterly">ত্রৈমাসিক (৯০ দিন)</option>
                    <option value="yearly">বার্ষিক (৩৬৫ দিন)</option>
                </select>
            </div>
            <div class="rt-field">
                <label class="rt-label">কোথা থেকে বাড়াবেন?</label>
                <select class="rt-select" name="from">
                    <option value="expiry">বর্তমান মেয়াদের পর</option>
                    <option value="today">আজ থেকে</option>
                </select>
            </div>
            <button type="submit" class="rt-btn rt-btn-primary" style="width:100%">
                <i class="fas fa-rotate-right"></i> লাইসেন্স নবায়ন
            </button>
        </form>
    </div>

</div>

<div style="margin-top:14px">
    <a href="{{ route('reseller.clients.index') }}" class="rt-btn rt-btn-ghost">
        <i class="fas fa-arrow-left"></i> তালিকায় ফিরুন
    </a>
</div>

@endsection
