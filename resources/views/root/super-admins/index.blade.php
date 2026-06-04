@extends('layouts.root')
@section('title', 'সুপার অ্যাডমিন তালিকা')
@section('page-title', 'সুপার অ্যাডমিন')

@section('content')
<div class="rt-card">
    <div class="rt-card-title" style="justify-content:space-between">
        <span><i class="fas fa-user-shield"></i> সকল সুপার অ্যাডমিন</span>
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
                <th>#</th>
                <th>নাম</th>
                <th>ইমেইল</th>
                <th>শপ</th>
                <th>মেয়াদ শেষ</th>
                <th>অবস্থা</th>
                <th>অ্যাকশন</th>
            </tr>
        </thead>
        <tbody>
            @foreach($superAdmins as $i => $u)
            @php $lic = $u->activeLicense(); @endphp
            <tr>
                <td style="color:#475569">{{ $i + 1 }}</td>
                <td>{{ $u->name }}</td>
                <td style="color:#64748b;font-size:.83rem">{{ $u->email }}</td>
                <td style="font-size:.83rem">{{ $u->shop?->name ?? '—' }}</td>
                <td style="font-size:.82rem">
                    @if($lic)
                        {{ $lic->expires_at->format('d M Y') }}
                        @if($lic->status === 'warning')
                            <br><small style="color:#fde68a">{{ $lic->daysUntilExpiry() }} দিন বাকি</small>
                        @elseif($lic->status === 'grace')
                            <br><small style="color:#fca5a5">গ্রেস: {{ $lic->graceDaysLeft() }} দিন</small>
                        @endif
                    @else
                        <span style="color:#475569">—</span>
                    @endif
                </td>
                <td>
                    @if(!$lic)
                        <span class="rt-pill rt-pill-expired">নেই</span>
                    @else
                        @php $st = $lic->status; @endphp
                        <span class="rt-pill rt-pill-{{ $st }}">
                            {{ ['active'=>'সক্রিয়','warning'=>'সংকট','grace'=>'গ্রেস','expired'=>'শেষ'][$st] }}
                        </span>
                    @endif
                </td>
                <td style="display:flex;gap:6px;flex-wrap:wrap">
                    <a href="{{ route('root.super-admins.show', $u) }}" class="rt-btn rt-btn-ghost rt-btn-sm" title="বিস্তারিত">
                        <i class="fas fa-eye"></i>
                    </a>
                    <a href="{{ route('root.super-admins.edit', $u) }}" class="rt-btn rt-btn-ghost rt-btn-sm" title="সম্পাদনা">
                        <i class="fas fa-pen"></i>
                    </a>
                    <form method="POST" action="{{ route('root.super-admins.destroy', $u) }}"
                          onsubmit="return confirm('{{ $u->name }} কে মুছে ফেলবেন?')">
                        @csrf @method('DELETE')
                        <button class="rt-btn rt-btn-danger rt-btn-sm" title="মুছুন">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>
@endsection
