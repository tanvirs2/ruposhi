@extends('layouts.root')
@section('title', 'ক্লায়েন্ট তালিকা')
@section('page-title', 'ক্লায়েন্ট')

@section('content')
<div class="rt-card">
    <div class="rt-card-title" style="justify-content:space-between;flex-wrap:wrap;gap:10px">
        <span><i class="fas fa-briefcase"></i> সকল ক্লায়েন্ট <small style="font-size:.7rem;color:#64748b;font-weight:400;margin-left:4px">(ব্যবসার মালিক)</small></span>
        <a href="{{ route('root.super-admins.create') }}" class="rt-btn rt-btn-primary rt-btn-sm">
            <i class="fas fa-plus"></i> নতুন ক্লায়েন্ট
        </a>
    </div>

    {{-- Search --}}
    <form method="GET" action="{{ route('root.super-admins.index') }}" style="margin-bottom:18px">
        <div style="display:flex;gap:8px">
            <input class="rt-input" type="text" name="q"
                   value="{{ $search }}"
                   placeholder="নাম, ইমেইল বা SA-ID দিয়ে খুঁজুন..."
                   style="flex:1">
            <button type="submit" class="rt-btn rt-btn-ghost">
                <i class="fas fa-search"></i>
            </button>
            @if($search)
            <a href="{{ route('root.super-admins.index') }}" class="rt-btn rt-btn-ghost" title="ক্লিয়ার">
                <i class="fas fa-xmark"></i>
            </a>
            @endif
        </div>
    </form>

    @if($search && $superAdmins->isEmpty())
        <div class="rt-empty">
            <i class="fas fa-search"></i>
            "{{ $search }}" — কোনো ক্লায়েন্ট পাওয়া যায়নি।
        </div>
    @elseif($superAdmins->isEmpty())
        <div class="rt-empty"><i class="fas fa-briefcase"></i> কোনো ক্লায়েন্ট নেই।</div>
    @else
    <table class="rt-table">
        <thead>
            <tr>
                <th>SA-ID</th>
                <th>নাম</th>
                <th>ইমেইল</th>
                <th>শাখা</th>
                <th>মেয়াদ শেষ</th>
                <th>অবস্থা</th>
                <th>অ্যাকশন</th>
            </tr>
        </thead>
        <tbody>
            @foreach($superAdmins as $u)
            @php $lic = $u->activeLicense(); @endphp
            <tr>
                <td>
                    <span style="background:#1e3a5f;color:#93c5fd;padding:3px 9px;border-radius:6px;font-size:.78rem;font-weight:700;font-family:monospace">
                        SA-{{ $u->id }}
                    </span>
                </td>
                <td style="font-weight:600">{{ $u->name }}</td>
                <td style="color:#64748b;font-size:.83rem">{{ $u->email }}</td>
                <td style="font-size:.83rem">
                    <span class="rt-pill" style="background:#1e3a5f44;color:#93c5fd;border:1px solid #1e3a5f">
                        {{ $u->my_shops_count }} / {{ $lic?->max_shops ?? '∞' }}
                    </span>
                </td>
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
                    <a href="{{ route('root.super-admins.show', $u) }}" class="rt-btn rt-btn-ghost rt-btn-sm" title="বিস্তারিত / নবায়ন">
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
