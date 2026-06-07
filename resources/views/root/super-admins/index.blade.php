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

    {{-- Status filter tabs --}}
    @php
        $tabs = [
            ''        => ['label' => 'সব',        'icon' => 'fa-list',           'color' => '#93c5fd', 'bg' => '#1e3a5f',   'count' => $counts['all']],
            'expired' => ['label' => 'মেয়াদ শেষ', 'icon' => 'fa-circle-xmark',  'color' => '#fca5a5', 'bg' => '#7f1d1d44', 'count' => $counts['expired']],
            'grace'   => ['label' => 'গ্রেস',      'icon' => 'fa-clock',          'color' => '#fca5a5', 'bg' => '#7f1d1d33', 'count' => $counts['grace']],
            'warning' => ['label' => 'সংকট',       'icon' => 'fa-triangle-exclamation', 'color' => '#fde68a', 'bg' => '#78350f44', 'count' => $counts['warning']],
            'active'  => ['label' => 'সক্রিয়',    'icon' => 'fa-circle-check',   'color' => '#86efac', 'bg' => '#14532d44', 'count' => $counts['active']],
        ];
        $currentStatus = $status ?? '';
    @endphp

    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px">
        @foreach($tabs as $key => $tab)
        @if($tab['count'] > 0 || $key === '')
        <a href="{{ route('root.super-admins.index', array_filter(['status' => $key ?: null, 'q' => $search])) }}"
           style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:9px;text-decoration:none;font-size:.82rem;font-weight:600;transition:.15s;
                  {{ $currentStatus === $key
                        ? "background:{$tab['bg']};color:{$tab['color']};border:1.5px solid {$tab['color']}55;"
                        : 'background:#060d1a;color:#64748b;border:1.5px solid #1e3a5f;' }}">
            <i class="fas {{ $tab['icon'] }}"></i>
            {{ $tab['label'] }}
            <span style="background:{{ $currentStatus === $key ? $tab['color'].'22' : '#1e3a5f' }};
                         color:{{ $currentStatus === $key ? $tab['color'] : '#94a3b8' }};
                         padding:1px 7px;border-radius:20px;font-size:.74rem">
                {{ $tab['count'] }}
            </span>
        </a>
        @endif
        @endforeach
    </div>

    {{-- Search --}}
    <form method="GET" action="{{ route('root.super-admins.index') }}" style="margin-bottom:18px">
        @if($currentStatus)
            <input type="hidden" name="status" value="{{ $currentStatus }}">
        @endif
        <div style="display:flex;gap:8px">
            <input class="rt-input" type="text" name="q"
                   value="{{ $search }}"
                   placeholder="নাম, ইমেইল বা SA-ID দিয়ে খুঁজুন..."
                   style="flex:1">
            <button type="submit" class="rt-btn rt-btn-ghost">
                <i class="fas fa-search"></i>
            </button>
            @if($search)
            <a href="{{ route('root.super-admins.index', array_filter(['status' => $currentStatus ?: null])) }}"
               class="rt-btn rt-btn-ghost" title="ক্লিয়ার">
                <i class="fas fa-xmark"></i>
            </a>
            @endif
        </div>
    </form>

    @if($superAdmins->isEmpty())
        <div class="rt-empty">
            <i class="fas fa-search"></i>
            @if($search)
                "{{ $search }}" — কোনো ক্লায়েন্ট পাওয়া যায়নি।
            @elseif($currentStatus === 'expired')
                কোনো মেয়াদ শেষ ক্লায়েন্ট নেই। 🎉
            @else
                কোনো ক্লায়েন্ট নেই।
            @endif
        </div>
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
            @php $lic = $u->activeLicense(); $st = $lic?->status; @endphp
            <tr style="{{ (!$lic || $st === 'expired') ? 'opacity:.7' : '' }}">
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
                        @if($st === 'warning')
                            <br><small style="color:#fde68a">{{ $lic->daysUntilExpiry() }} দিন বাকি</small>
                        @elseif($st === 'grace')
                            <br><small style="color:#fca5a5">গ্রেস: {{ $lic->graceDaysLeft() }} দিন</small>
                        @elseif($st === 'expired')
                            <br><small style="color:#ef4444">{{ $lic->expires_at->diffForHumans() }}</small>
                        @endif
                    @else
                        <span style="color:#475569">—</span>
                    @endif
                </td>
                <td>
                    @if(!$lic)
                        <span class="rt-pill rt-pill-expired">লাইসেন্স নেই</span>
                    @else
                        <span class="rt-pill rt-pill-{{ $st }}">
                            {{ ['active'=>'সক্রিয়','warning'=>'সংকট','grace'=>'গ্রেস','expired'=>'মেয়াদ শেষ'][$st] }}
                        </span>
                    @endif
                </td>
                <td style="display:flex;gap:6px;flex-wrap:wrap">
                    <a href="{{ route('root.super-admins.show', $u) }}"
                       class="rt-btn rt-btn-sm {{ (!$lic || $st === 'expired') ? 'rt-btn-danger' : 'rt-btn-ghost' }}"
                       title="{{ (!$lic || $st === 'expired') ? 'নবায়ন করুন' : 'বিস্তারিত' }}">
                        @if(!$lic || $st === 'expired')
                            <i class="fas fa-rotate-right"></i>
                        @else
                            <i class="fas fa-eye"></i>
                        @endif
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
