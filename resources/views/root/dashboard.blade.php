@extends('layouts.root')
@section('title', 'রুট ড্যাশবোর্ড')
@section('page-title', 'ড্যাশবোর্ড')

@section('content')

{{-- Stats --}}
<div class="rt-stat-grid">
    <div class="rt-stat">
        <div class="rt-stat-val" style="color:#93c5fd">{{ $total }}</div>
        <div class="rt-stat-lbl">মোট সুপার অ্যাডমিন</div>
    </div>
    <div class="rt-stat">
        <div class="rt-stat-val" style="color:#86efac">{{ $active }}</div>
        <div class="rt-stat-lbl">সক্রিয়</div>
    </div>
    <div class="rt-stat">
        <div class="rt-stat-val" style="color:#fde68a">{{ $warning }}</div>
        <div class="rt-stat-lbl">মেয়াদ সংকট</div>
    </div>
    <div class="rt-stat">
        <div class="rt-stat-val" style="color:#fca5a5">{{ $grace }}</div>
        <div class="rt-stat-lbl">গ্রেস পিরিয়ড</div>
    </div>
    <div class="rt-stat">
        <div class="rt-stat-val" style="color:#475569">{{ $expired }}</div>
        <div class="rt-stat-lbl">মেয়াদ শেষ</div>
    </div>
    <div class="rt-stat">
        <div class="rt-stat-val" style="color:#c4b5fd">{{ $resellerCount }}</div>
        <div class="rt-stat-lbl">রিসেলার</div>
    </div>
</div>

{{-- Expiring soon --}}
@if($expiringSoon->count())
<div class="rt-card">
    <div class="rt-card-title"><i class="fas fa-clock" style="color:#fde68a"></i> শীঘ্রই মেয়াদ শেষ (১৪ দিনের মধ্যে)</div>
    <table class="rt-table">
        <thead>
            <tr>
                <th>নাম</th>
                <th>ইমেইল</th>
                <th>মেয়াদ শেষ</th>
                <th>বাকি দিন</th>
                <th>অ্যাকশন</th>
            </tr>
        </thead>
        <tbody>
            @foreach($expiringSoon as $lic)
            <tr>
                <td>{{ $lic->user->name }}</td>
                <td style="color:#64748b;font-size:.83rem">{{ $lic->user->email }}</td>
                <td>{{ $lic->expires_at->format('d M Y') }}</td>
                <td><span class="rt-pill rt-pill-warning">{{ $lic->daysUntilExpiry() }} দিন</span></td>
                <td>
                    <a href="{{ route('root.super-admins.show', $lic->user) }}" class="rt-btn rt-btn-ghost rt-btn-sm">
                        <i class="fas fa-key"></i> নবায়ন
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- All super admins --}}
<div class="rt-card">
    <div class="rt-card-title" style="justify-content:space-between">
        <span><i class="fas fa-users-gear"></i> সকল সুপার অ্যাডমিন</span>
        <a href="{{ route('root.super-admins.create') }}" class="rt-btn rt-btn-primary rt-btn-sm">
            <i class="fas fa-plus"></i> নতুন যোগ করুন
        </a>
    </div>

    @if($superAdmins->isEmpty())
        <div class="rt-empty"><i class="fas fa-users"></i> কোনো সুপার অ্যাডমিন নেই।</div>
    @else
    <table class="rt-table">
        <thead>
            <tr>
                <th>নাম</th>
                <th>ইমেইল</th>
                <th>শপ</th>
                <th>মেয়াদ</th>
                <th>অবস্থা</th>
                <th>অ্যাকশন</th>
            </tr>
        </thead>
        <tbody>
            @foreach($superAdmins as $u)
            @php $lic = $u->activeLicense(); @endphp
            <tr>
                <td>{{ $u->name }}</td>
                <td style="color:#64748b;font-size:.83rem">{{ $u->email }}</td>
                <td style="font-size:.83rem">{{ $u->shop?->name ?? '—' }}</td>
                <td style="font-size:.82rem">
                    @if($lic) {{ $lic->expires_at->format('d M Y') }} @else — @endif
                </td>
                <td>
                    @if(!$lic)
                        <span class="rt-pill rt-pill-expired">লাইসেন্স নেই</span>
                    @else
                        @php $st = $lic->status; @endphp
                        <span class="rt-pill rt-pill-{{ $st }}">
                            {{ ['active'=>'সক্রিয়','warning'=>'সংকট','grace'=>'গ্রেস','expired'=>'মেয়াদ শেষ'][$st] }}
                        </span>
                    @endif
                </td>
                <td>
                    <a href="{{ route('root.super-admins.show', $u) }}" class="rt-btn rt-btn-ghost rt-btn-sm">
                        <i class="fas fa-eye"></i>
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

@endsection
