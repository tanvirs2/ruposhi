@extends('layouts.reseller')
@section('title', 'রিসেলার ড্যাশবোর্ড')
@section('page-title', 'ড্যাশবোর্ড')

@section('content')

<div class="rt-stat-grid">
    <div class="rt-stat"><div class="rt-stat-val" style="color:#93c5fd">{{ $total }}</div><div class="rt-stat-lbl">মোট ক্লায়েন্ট</div></div>
    <div class="rt-stat"><div class="rt-stat-val" style="color:#86efac">{{ $active }}</div><div class="rt-stat-lbl">সক্রিয়</div></div>
    <div class="rt-stat"><div class="rt-stat-val" style="color:#fde68a">{{ $warning }}</div><div class="rt-stat-lbl">মেয়াদ সংকট</div></div>
    <div class="rt-stat"><div class="rt-stat-val" style="color:#fca5a5">{{ $grace + $expired }}</div><div class="rt-stat-lbl">মেয়াদ শেষ / গ্রেস</div></div>
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
                    <a href="{{ route('reseller.clients.index') }}" class="rt-btn rt-btn-ghost rt-btn-sm">
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
