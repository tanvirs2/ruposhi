@extends('layouts.root')
@section('title', 'বিজনেস ড্যাশবোর্ড')
@section('page-title', 'বিজনেস ড্যাশবোর্ড')

@section('content')

{{-- ══════════════════════════════════════════════════════
     SECTION 1: আজকের করণীয় (Action Items)
══════════════════════════════════════════════════════ --}}
<div class="rt-card" style="margin-bottom:20px">
    <div class="rt-card-title">
        <i class="fas fa-bolt" style="color:#fbbf24"></i>
        আজকে কী করণীয়?
        <small style="font-size:.7rem;color:#64748b;font-weight:400;margin-left:6px">— এই কাজগুলো না করলে আয় কমতে পারে</small>
    </div>
    <div style="display:flex;flex-direction:column;gap:8px;padding:4px 0">
        @foreach($actions as $act)
        <div style="
            display:flex;align-items:center;gap:12px;
            padding:10px 14px;border-radius:8px;
            background:{{ $act['type']==='danger' ? '#fef2f2' : ($act['type']==='warning' ? '#fffbeb' : '#f0fdf4') }};
            border-left:3px solid {{ $act['type']==='danger' ? '#ef4444' : ($act['type']==='warning' ? '#f59e0b' : '#22c55e') }};
        ">
            <i class="fas {{ $act['icon'] }}" style="color:{{ $act['type']==='danger' ? '#ef4444' : ($act['type']==='warning' ? '#d97706' : '#16a34a') }};font-size:1rem;width:18px;text-align:center"></i>
            <span style="flex:1;font-size:.85rem;color:#1e293b">{!! $act['message'] !!}</span>
            @if($act['link'])
            <a href="{{ $act['link'] }}" class="rt-btn rt-btn-ghost rt-btn-sm" style="white-space:nowrap">
                {{ $act['btn'] }} <i class="fas fa-chevron-right" style="font-size:.6rem"></i>
            </a>
            @endif
        </div>
        @endforeach
    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     SECTION 2: আয়ের হিসাব (Revenue)
══════════════════════════════════════════════════════ --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:20px">

    {{-- This month --}}
    <div class="rt-card" style="margin:0;border-top:3px solid #22c55e">
        <div style="padding:16px 18px">
            <div style="font-size:.7rem;font-weight:700;color:#16a34a;letter-spacing:.05em;margin-bottom:6px">
                💰 এই মাসের আয় (MRR)
            </div>
            <div style="font-size:1.8rem;font-weight:800;color:#15803d">
                ৳{{ number_format($revenueThisMonth, 0) }}
            </div>
            @if($revenueGrowth > 0)
            <div style="font-size:.75rem;color:#16a34a;margin-top:4px">
                <i class="fas fa-arrow-up"></i> {{ $revenueGrowth }}% গত মাসের চেয়ে বেশি
            </div>
            @elseif($revenueGrowth < 0)
            <div style="font-size:.75rem;color:#dc2626;margin-top:4px">
                <i class="fas fa-arrow-down"></i> {{ abs($revenueGrowth) }}% গত মাসের চেয়ে কম
            </div>
            @else
            <div style="font-size:.75rem;color:#64748b;margin-top:4px">গত মাসের সমান</div>
            @endif
            <div style="font-size:.68rem;color:#64748b;margin-top:8px;padding-top:8px;border-top:1px solid #e2e8f0">
                <b>MRR মানে কী?</b> প্রতি মাসে আপনার নির্দিষ্ট আয়। এটা বাড়লে বিজনেস বাড়ছে।
            </div>
        </div>
    </div>

    {{-- Last month --}}
    <div class="rt-card" style="margin:0;border-top:3px solid #94a3b8">
        <div style="padding:16px 18px">
            <div style="font-size:.7rem;font-weight:700;color:#475569;letter-spacing:.05em;margin-bottom:6px">
                📅 গত মাসের আয়
            </div>
            <div style="font-size:1.8rem;font-weight:800;color:#334155">
                ৳{{ number_format($revenueLastMonth, 0) }}
            </div>
            <div style="font-size:.75rem;color:#64748b;margin-top:4px">
                তুলনা: এই মাস {{ $revenueThisMonth >= $revenueLastMonth ? '≥' : '<' }} গত মাস
            </div>
            <div style="font-size:.68rem;color:#64748b;margin-top:8px;padding-top:8px;border-top:1px solid #e2e8f0">
                <b>কেন দেখবেন?</b> গত মাসের চেয়ে কম হলে বুঝতে হবে client হারাচ্ছেন বা নতুন আসছে না।
            </div>
        </div>
    </div>

    {{-- ARR --}}
    <div class="rt-card" style="margin:0;border-top:3px solid #6366f1">
        <div style="padding:16px 18px">
            <div style="font-size:.7rem;font-weight:700;color:#4f46e5;letter-spacing:.05em;margin-bottom:6px">
                📈 বার্ষিক আয় অনুমান (ARR)
            </div>
            <div style="font-size:1.8rem;font-weight:800;color:#4338ca">
                ৳{{ number_format($arr, 0) }}
            </div>
            <div style="font-size:.75rem;color:#6366f1;margin-top:4px">
                এই মাসের আয় × ১২
            </div>
            <div style="font-size:.68rem;color:#64748b;margin-top:8px;padding-top:8px;border-top:1px solid #e2e8f0">
                <b>ARR মানে কী?</b> এই হারে চললে বছরে মোট কত পাবেন তার অনুমান। বিনিয়োগকারীরা এটা দেখে।
            </div>
        </div>
    </div>

</div>

{{-- ══════════════════════════════════════════════════════
     SECTION 3: Client স্বাস্থ্য (Client Health)
══════════════════════════════════════════════════════ --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px">

    {{-- Client status breakdown --}}
    <div class="rt-card" style="margin:0">
        <div class="rt-card-title">
            <i class="fas fa-heartbeat" style="color:#f43f5e"></i> Client স্বাস্থ্য
            <small style="font-size:.68rem;color:#64748b;font-weight:400;margin-left:4px">— মোট {{ $total }} জন</small>
        </div>
        <div style="display:flex;flex-direction:column;gap:6px;padding:4px 0">

            @php
            $healthItems = [
                ['label'=>'সক্রিয়','count'=>$active,'color'=>'#22c55e','bg'=>'#f0fdf4','icon'=>'fa-circle-check','desc'=>'ভালো — এরা নিয়মিত পেমেন্ট করছে'],
                ['label'=>'সংকটে (১৪ দিন বাকি)','count'=>$warning,'color'=>'#f59e0b','bg'=>'#fffbeb','icon'=>'fa-clock','desc'=>'এখনই ফোন করুন — নবায়ন করাতে হবে'],
                ['label'=>'গ্রেস পিরিয়ড','count'=>$grace,'color'=>'#ef4444','bg'=>'#fef2f2','icon'=>'fa-triangle-exclamation','desc'=>'জরুরি! এরা মেয়াদ শেষেও ব্যবহার করছে'],
                ['label'=>'মেয়াদ শেষ / লাইসেন্স নেই','count'=>$expired,'color'=>'#94a3b8','bg'=>'#f8fafc','icon'=>'fa-ban','desc'=>'এরা এখন ব্যবহার করতে পারছে না'],
            ];
            @endphp

            @foreach($healthItems as $h)
            @if($h['count'] > 0 || true)
            <div style="display:flex;align-items:center;gap:10px;padding:8px 12px;border-radius:7px;background:{{ $h['bg'] }}">
                <i class="fas {{ $h['icon'] }}" style="color:{{ $h['color'] }};width:16px;text-align:center"></i>
                <div style="flex:1">
                    <div style="font-size:.8rem;font-weight:600;color:#1e293b">{{ $h['label'] }}</div>
                    <div style="font-size:.67rem;color:#64748b">{{ $h['desc'] }}</div>
                </div>
                <span style="font-size:1.3rem;font-weight:800;color:{{ $h['color'] }}">{{ $h['count'] }}</span>
            </div>
            @endif
            @endforeach

        </div>
        @if($total > 0)
        <div style="padding:10px 12px 6px;border-top:1px solid #f1f5f9;margin-top:6px">
            <div style="font-size:.68rem;color:#64748b;margin-bottom:4px">সক্রিয় হার</div>
            <div style="background:#e2e8f0;border-radius:99px;height:8px;overflow:hidden">
                <div style="background:#22c55e;height:100%;width:{{ $total > 0 ? round(($active/$total)*100) : 0 }}%;border-radius:99px;transition:width .3s"></div>
            </div>
            <div style="font-size:.7rem;color:#16a34a;margin-top:3px;font-weight:600">
                {{ $total > 0 ? round(($active/$total)*100) : 0 }}% client সক্রিয়
            </div>
        </div>
        @endif
    </div>

    {{-- Growth & reseller --}}
    <div style="display:flex;flex-direction:column;gap:16px">

        {{-- New clients this month --}}
        <div class="rt-card" style="margin:0;flex:1">
            <div style="padding:14px 18px">
                <div style="font-size:.7rem;font-weight:700;color:#0891b2;letter-spacing:.05em;margin-bottom:6px">
                    🚀 এই মাসে নতুন Client
                </div>
                <div style="font-size:2rem;font-weight:800;color:#0e7490">{{ $newClientsThisMonth }}</div>
                <div style="font-size:.68rem;color:#64748b;margin-top:6px">
                    <b>টার্গেট রাখুন:</b> প্রতি মাসে অন্তত ১ জন নতুন client যোগ করলে বিজনেস বাড়বে।
                </div>
            </div>
        </div>

        {{-- Total revenue --}}
        <div class="rt-card" style="margin:0;flex:1">
            <div style="padding:14px 18px">
                <div style="font-size:.7rem;font-weight:700;color:#7c3aed;letter-spacing:.05em;margin-bottom:6px">
                    🏦 সর্বমোট আয় (সব সময়)
                </div>
                <div style="font-size:1.6rem;font-weight:800;color:#6d28d9">৳{{ number_format($revenueAllTime, 0) }}</div>
                <div style="font-size:.68rem;color:#64748b;margin-top:6px">
                    শুরু থেকে এখন পর্যন্ত মোট payment received।
                </div>
            </div>
        </div>

    </div>

</div>

{{-- ══════════════════════════════════════════════════════
     SECTION 4: রিসেলার হিসাব
══════════════════════════════════════════════════════ --}}
@if(count($resellerSummary) > 0)
<div class="rt-card" style="margin-bottom:20px">
    <div class="rt-card-title">
        <i class="fas fa-handshake" style="color:#c4b5fd"></i>
        রিসেলার কমিশন হিসাব
        <small style="font-size:.68rem;color:#64748b;font-weight:400;margin-left:6px">— তাদের মাধ্যমে আসা আয়ের ভাগ</small>
    </div>
    <div style="display:flex;flex-direction:column;gap:0">
        @foreach($resellerSummary as $rs)
        <div style="display:flex;align-items:center;gap:12px;padding:10px 14px;border-bottom:1px solid #f1f5f9">
            <div style="flex:1">
                <div style="font-size:.85rem;font-weight:600;color:#1e293b">{{ $rs['name'] }}</div>
                <div style="font-size:.7rem;color:#64748b">কমিশন হার: {{ $rs['rate'] }}% &nbsp;·&nbsp; <a href="{{ route('root.resellers.index') }}" style="color:#6366f1">বিস্তারিত দেখুন →</a></div>
            </div>
            <div style="text-align:right">
                <div style="font-size:.7rem;color:#64748b">তাদের client-এর মোট payment</div>
                <div style="font-size:.9rem;font-weight:700;color:#334155">৳{{ number_format($rs['paid'], 0) }}</div>
            </div>
            <div style="text-align:right;min-width:100px">
                <div style="font-size:.7rem;color:#7c3aed">তাদের প্রাপ্য কমিশন</div>
                <div style="font-size:1rem;font-weight:800;color:#6d28d9">৳{{ number_format($rs['commission'], 0) }}</div>
            </div>
        </div>
        @endforeach
        <div style="padding:10px 14px;background:#f8f4ff;display:flex;justify-content:space-between;align-items:center">
            <span style="font-size:.8rem;font-weight:700;color:#1e293b">মোট কমিশন দেওয়া বাকি</span>
            <span style="font-size:1.1rem;font-weight:800;color:#7c3aed">৳{{ number_format($totalCommissionOwed, 0) }}</span>
        </div>
    </div>
    <div style="padding:8px 14px;background:#faf5ff;border-top:1px solid #e9d5ff">
        <p style="font-size:.72rem;color:#7c3aed;margin:0">
            <i class="fas fa-lightbulb"></i>
            <b>কমিশন কী?</b> রিসেলার আপনার হয়ে client এনে দেয়। বিনিময়ে সেই client-এর payment-এর একটা অংশ তাদের দিতে হয়।
            আপনি নিজেই সিদ্ধান্ত নিন কখন দেবেন — মাসে একবার বা প্রতি payment-এ।
        </p>
    </div>
</div>
@endif

{{-- ══════════════════════════════════════════════════════
     SECTION 5: বিজনেস টিপস
══════════════════════════════════════════════════════ --}}
<div class="rt-card" style="margin-bottom:20px">
    <div class="rt-card-title">
        <i class="fas fa-graduation-cap" style="color:#fbbf24"></i>
        বিজনেস টিপস — নতুন উদ্যোক্তার জন্য
    </div>
    <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px;padding:4px 0">

        @php
        $tips = [
            ['icon'=>'fa-user-plus','color'=>'#0891b2','title'=>'Client ধরে রাখুন (Retention)','body'=>'নতুন client আনার চেয়ে পুরনো client ধরে রাখা ৫ গুণ সস্তা। প্রতি মাসে expiry-র আগে একটা reminder call করুন।'],
            ['icon'=>'fa-chart-line','color'=>'#16a34a','title'=>'MRR বাড়ানোর উপায়','body'=>'MRR বাড়ানো যায় দুভাবে: নতুন client আনুন, অথবা পুরনো client-কে বেশি শাখার plan-এ upgrade করুন।'],
            ['icon'=>'fa-handshake','color'=>'#7c3aed','title'=>'রিসেলার দিয়ে scale করুন','body'=>'নিজে সব করলে সময় লাগবে। ১-২ জন trusted রিসেলার দিলে তারা client আনবে, আপনি software ভালো করবেন।'],
            ['icon'=>'fa-ban','color'=>'#dc2626','title'=>'Churn কমান (Client হারানো)','body'=>'কোনো client লাইসেন্স renew না করলে সেটা "churn"। কারণ জানুন — software সমস্যা? দাম বেশি? — সেটা ঠিক করুন।'],
        ];
        @endphp

        @foreach($tips as $tip)
        <div style="padding:12px 14px;background:#f8fafc;border-radius:8px;border-left:3px solid {{ $tip['color'] }}">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:5px">
                <i class="fas {{ $tip['icon'] }}" style="color:{{ $tip['color'] }};font-size:.85rem"></i>
                <span style="font-size:.78rem;font-weight:700;color:#1e293b">{{ $tip['title'] }}</span>
            </div>
            <p style="font-size:.72rem;color:#475569;margin:0;line-height:1.6">{{ $tip['body'] }}</p>
        </div>
        @endforeach

    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     SECTION 6: সকল Client তালিকা
══════════════════════════════════════════════════════ --}}
<div class="rt-card">
    <div class="rt-card-title" style="justify-content:space-between">
        <span><i class="fas fa-briefcase"></i> সকল ক্লায়েন্ট <small style="font-size:.7rem;color:#64748b;font-weight:400">(ব্যবসার মালিক)</small></span>
        <a href="{{ route('root.super-admins.create') }}" class="rt-btn rt-btn-primary rt-btn-sm">
            <i class="fas fa-plus"></i> নতুন ক্লায়েন্ট
        </a>
    </div>

    @if($superAdmins->isEmpty())
        <div class="rt-empty"><i class="fas fa-briefcase"></i> কোনো ক্লায়েন্ট নেই।</div>
    @else
    <table class="rt-table">
        <thead>
            <tr>
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
            <tr>
                <td style="font-weight:600">{{ $u->name }}</td>
                <td style="color:#64748b;font-size:.83rem">{{ $u->email }}</td>
                <td style="font-size:.83rem">
                    <span class="rt-pill" style="background:#1e3a5f44;color:#93c5fd;border:1px solid #1e3a5f">
                        {{ $u->my_shops_count ?? 0 }} / {{ $lic?->max_shops ?? '∞' }}
                    </span>
                </td>
                <td style="font-size:.82rem">
                    @if($lic)
                        {{ $lic->expires_at->format('d M Y') }}
                        @if($st === 'warning')
                            <br><small style="color:#f59e0b">{{ $lic->daysUntilExpiry() }} দিন বাকি</small>
                        @elseif($st === 'grace')
                            <br><small style="color:#ef4444">গ্রেস চলছে</small>
                        @endif
                    @else —
                    @endif
                </td>
                <td>
                    @if(!$lic)
                        <span class="rt-pill rt-pill-expired">নেই</span>
                    @else
                        <span class="rt-pill rt-pill-{{ $st }}">
                            {{ ['active'=>'সক্রিয়','warning'=>'সংকট','grace'=>'গ্রেস','expired'=>'শেষ'][$st] ?? $st }}
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
