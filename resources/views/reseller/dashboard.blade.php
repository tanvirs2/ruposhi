@extends('layouts.reseller')
@section('title', 'রিসেলার ড্যাশবোর্ড')
@section('page-title', 'ড্যাশবোর্ড')

@section('content')

{{-- Client stats --}}
<div class="rt-stat-grid">
    <div class="rt-stat"><div class="rt-stat-val" style="color:#93c5fd">{{ $total }}</div><div class="rt-stat-lbl">মোট ক্লায়েন্ট</div></div>
    <div class="rt-stat"><div class="rt-stat-val" style="color:#86efac">{{ $active }}</div><div class="rt-stat-lbl">সক্রিয়</div></div>
    <div class="rt-stat"><div class="rt-stat-val" style="color:#fde68a">{{ $warning }}</div><div class="rt-stat-lbl">মেয়াদ সংকট</div></div>
    <div class="rt-stat"><div class="rt-stat-val" style="color:#fca5a5">{{ $grace + $expired }}</div><div class="rt-stat-lbl">মেয়াদ শেষ / গ্রেস</div></div>
</div>

{{-- Commission summary --}}
<div class="rt-card" style="margin-bottom:16px">
    <div class="rt-card-title"><i class="fas fa-coins" style="color:#fbbf24"></i> আমার কমিশন</div>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;padding:4px 0">
        <div style="text-align:center;padding:12px;background:#060d1a;border-radius:10px">
            <div style="font-size:.75rem;color:#64748b;margin-bottom:6px">ক্লায়েন্টদের মোট পেমেন্ট</div>
            <div style="font-size:1.2rem;font-weight:800;color:#6ee7b7">৳ {{ number_format($totalPayments, 0) }}</div>
        </div>
        <div style="text-align:center;padding:12px;background:#060d1a;border-radius:10px">
            <div style="font-size:.75rem;color:#64748b;margin-bottom:6px">কমিশন রেট</div>
            <div style="font-size:1.2rem;font-weight:800;color:#fbbf24">{{ $commissionRate }}%</div>
        </div>
        <div style="text-align:center;padding:12px;background:#0a1f0a;border-radius:10px;border:1px solid #166534">
            <div style="font-size:.75rem;color:#64748b;margin-bottom:6px">আমার মোট কমিশন</div>
            <div style="font-size:1.2rem;font-weight:800;color:#86efac">৳ {{ number_format($myCommission, 0) }}</div>
        </div>
    </div>

    @if($recentPayments->count())
    <div style="margin-top:14px">
        <div style="font-size:.78rem;color:#475569;font-weight:700;margin-bottom:8px">সাম্প্রতিক পেমেন্ট</div>
        <table class="rt-table">
            <thead><tr><th>তারিখ</th><th>ক্লায়েন্ট</th><th>পরিমাণ</th><th>পদ্ধতি</th></tr></thead>
            <tbody>
                @foreach($recentPayments as $p)
                <tr>
                    <td style="font-size:.8rem">{{ $p->payment_date->format('d M Y') }}</td>
                    <td style="font-size:.85rem;font-weight:600">{{ $p->user->name }}</td>
                    <td style="color:#6ee7b7;font-weight:700">৳ {{ number_format($p->amount, 0) }}</td>
                    <td>
                        <span style="font-size:.75rem;background:#0f2640;color:#93c5fd;padding:2px 8px;border-radius:20px">
                            {{ $p->method_label }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

@if($expiringSoon->count())
<div class="rt-card">
    <div class="rt-card-title"><i class="fas fa-clock" style="color:#fde68a"></i> শীঘ্রই মেয়াদ শেষ</div>
    <table class="rt-table">
        <thead><tr><th>নাম</th><th>মেয়াদ</th><th>বাকি</th><th></th></tr></thead>
        <tbody>
            @foreach($expiringSoon as $lic)
            <tr>
                <td>{{ $lic->user->name }}</td>
                <td style="font-size:.83rem">{{ $lic->expires_at->format('d M Y') }}</td>
                <td><span class="rt-pill rt-pill-warning">{{ $lic->daysUntilExpiry() }} দিন</span></td>
                <td>
                    <a href="{{ route('reseller.clients.show', $lic->user) }}" class="rt-btn rt-btn-ghost rt-btn-sm">
                        <i class="fas fa-key"></i> নবায়ন
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<div class="rt-card">
    <div class="rt-card-title" style="justify-content:space-between">
        <span><i class="fas fa-users"></i> আমার ক্লায়েন্ট</span>
        <a href="{{ route('reseller.clients.create') }}" class="rt-btn rt-btn-primary rt-btn-sm"><i class="fas fa-plus"></i> নতুন ক্লায়েন্ট</a>
    </div>
    @if($clients->isEmpty())
        <div class="rt-empty"><i class="fas fa-users"></i> কোনো ক্লায়েন্ট নেই।</div>
    @else
    <table class="rt-table">
        <thead><tr><th>নাম</th><th>ইমেইল</th><th>মেয়াদ</th><th>অবস্থা</th></tr></thead>
        <tbody>
            @foreach($clients as $c)
            @php $lic = $c->activeLicense(); @endphp
            <tr>
                <td>{{ $c->name }}</td>
                <td style="color:#64748b;font-size:.83rem">{{ $c->email }}</td>
                <td style="font-size:.82rem">{{ $lic?->expires_at->format('d M Y') ?? '—' }}</td>
                <td>
                    @php $st = $lic?->status ?? 'expired'; @endphp
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
@endsection
