@extends('layouts.super')
@section('title', 'শপ রিপোর্ট')
@section('page-title', 'শপ রিপোর্ট')

@section('content')

{{-- Date filter --}}
<div class="sa-card" style="padding:18px 22px">
    <form method="GET" action="{{ route('super.reports') }}"
          style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
        <div style="display:flex;align-items:center;gap:8px">
            <label style="font-size:.84rem;color:#94a3b8;white-space:nowrap">
                <i class="fas fa-calendar" style="color:#f59e0b"></i> থেকে
            </label>
            <input type="date" name="from" value="{{ $from }}"
                   style="background:#0f172a;border:1px solid #334155;color:#e2e8f0;
                          border-radius:7px;padding:7px 11px;font-size:.88rem;font-family:inherit">
        </div>
        <div style="display:flex;align-items:center;gap:8px">
            <label style="font-size:.84rem;color:#94a3b8;white-space:nowrap">পর্যন্ত</label>
            <input type="date" name="to" value="{{ $to }}"
                   style="background:#0f172a;border:1px solid #334155;color:#e2e8f0;
                          border-radius:7px;padding:7px 11px;font-size:.88rem;font-family:inherit">
        </div>
        <button type="submit" class="sa-btn sa-btn-primary sa-btn-sm">
            <i class="fas fa-filter"></i> ফিল্টার
        </button>
        {{-- Quick range buttons --}}
        <div style="display:flex;gap:6px;margin-right:auto">
            @foreach([
                ['label'=>'আজ',         'from'=>now()->toDateString(),                'to'=>now()->toDateString()],
                ['label'=>'এই মাস',     'from'=>now()->startOfMonth()->toDateString(),'to'=>now()->toDateString()],
                ['label'=>'গত মাস',     'from'=>now()->subMonth()->startOfMonth()->toDateString(),'to'=>now()->subMonth()->endOfMonth()->toDateString()],
                ['label'=>'এই বছর',     'from'=>now()->startOfYear()->toDateString(), 'to'=>now()->toDateString()],
            ] as $q)
            <a href="{{ route('super.reports', ['from'=>$q['from'],'to'=>$q['to']]) }}"
               class="sa-btn sa-btn-ghost sa-btn-sm"
               style="{{ $from===$q['from']&&$to===$q['to'] ? 'background:#f59e0b22;color:#fbbf24;border:1px solid #f59e0b44' : '' }}">
                {{ $q['label'] }}
            </a>
            @endforeach
        </div>
    </form>
</div>

{{-- Grand total summary cards --}}
<div class="sa-stat-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));margin-bottom:22px">
    <div class="sa-stat">
        <div class="sa-stat-icon" style="background:linear-gradient(135deg,#f59e0b,#ef4444)">
            <i class="fas fa-money-bill-wave"></i>
        </div>
        <div>
            <div class="sa-stat-val" style="font-size:1.25rem">৳&nbsp;{{ number_format($totals['revenue'],0) }}</div>
            <div class="sa-stat-lbl">মোট বিক্রয়</div>
        </div>
    </div>
    <div class="sa-stat">
        <div class="sa-stat-icon" style="background:linear-gradient(135deg,#3b82f6,#6366f1)">
            <i class="fas fa-truck-ramp-box"></i>
        </div>
        <div>
            <div class="sa-stat-val" style="font-size:1.25rem">৳&nbsp;{{ number_format($totals['purchase_cost'],0) }}</div>
            <div class="sa-stat-lbl">মোট ক্রয় খরচ</div>
        </div>
    </div>
    <div class="sa-stat">
        <div class="sa-stat-icon" style="background:linear-gradient(135deg,#10b981,#059669)">
            <i class="fas fa-chart-line"></i>
        </div>
        <div>
            <div class="sa-stat-val" style="font-size:1.25rem;color:{{ $totals['gross_profit']>=0 ? '#86efac' : '#fca5a5' }}">
                ৳&nbsp;{{ number_format(abs($totals['gross_profit']),0) }}
            </div>
            <div class="sa-stat-lbl">গ্রস মুনাফা</div>
        </div>
    </div>
    <div class="sa-stat">
        <div class="sa-stat-icon" style="background:linear-gradient(135deg,#8b5cf6,#6d28d9)">
            <i class="fas fa-scale-balanced"></i>
        </div>
        <div>
            <div class="sa-stat-val" style="font-size:1.25rem;color:{{ $totals['net_profit']>=0 ? '#86efac' : '#fca5a5' }}">
                ৳&nbsp;{{ number_format(abs($totals['net_profit']),0) }}
            </div>
            <div class="sa-stat-lbl">নেট মুনাফা</div>
        </div>
    </div>
    <div class="sa-stat">
        <div class="sa-stat-icon" style="background:linear-gradient(135deg,#ef4444,#b91c1c)">
            <i class="fas fa-users"></i>
        </div>
        <div>
            <div class="sa-stat-val" style="font-size:1.25rem">৳&nbsp;{{ number_format($totals['customer_due'],0) }}</div>
            <div class="sa-stat-lbl">মোট কাস্টমার বাকী</div>
        </div>
    </div>
    <div class="sa-stat">
        <div class="sa-stat-icon" style="background:linear-gradient(135deg,#f97316,#ea580c)">
            <i class="fas fa-receipt"></i>
        </div>
        <div>
            <div class="sa-stat-val" style="font-size:1.25rem">{{ number_format($totals['sales_count'],0) }}</div>
            <div class="sa-stat-lbl">মোট বিক্রয় সংখ্যা</div>
        </div>
    </div>
</div>

{{-- Per-shop detailed table --}}
<div class="sa-card">
    <h2 style="font-size:1rem;font-weight:700;margin:0 0 18px;color:#f1f5f9;display:flex;align-items:center;gap:10px">
        <i class="fas fa-table" style="color:#f59e0b"></i> শপ ভিত্তিক বিস্তারিত
        <span style="font-size:.74rem;font-weight:400;color:#64748b;background:#0f172a;padding:2px 10px;border-radius:20px">
            {{ $from }} — {{ $to }}
        </span>
    </h2>

    @if($shops->isEmpty())
        <div class="sa-empty"><i class="fas fa-store-slash"></i><div>কোনো শপ নেই</div></div>
    @else
    <div style="overflow-x:auto">
    <table class="sa-table">
        <thead>
            <tr>
                <th style="min-width:140px">শপ</th>
                <th>বিক্রয় সংখ্যা</th>
                <th>মোট বিক্রয়</th>
                <th>পরিশোধিত</th>
                <th>বিক্রয় বাকী</th>
                <th>ক্রয় খরচ</th>
                <th>গ্রস মুনাফা</th>
                <th>খরচ</th>
                <th>নেট মুনাফা</th>
                <th>কাস্টমার বাকী</th>
                <th>স্টাফ</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($shops as $shop)
            @php $s = $shopStats[$shop->id]; @endphp
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:8px">
                        <div style="width:30px;height:30px;border-radius:8px;
                                    background:linear-gradient(135deg,#f59e0b44,#ef444444);
                                    display:flex;align-items:center;justify-content:center;flex-shrink:0">
                            <i class="fas fa-store" style="font-size:.7rem;color:#fbbf24"></i>
                        </div>
                        <div>
                            <div style="font-weight:600;color:#f1f5f9;font-size:.88rem">{{ $shop->name }}</div>
                            <span class="sa-pill {{ $shop->is_active ? 'sa-pill-on' : 'sa-pill-off' }}" style="font-size:.65rem">
                                {{ $shop->is_active ? 'সক্রিয়' : 'নিষ্ক্রিয়' }}
                            </span>
                        </div>
                    </div>
                </td>
                <td style="color:#94a3b8">{{ number_format($s['sales_count']) }}</td>
                <td style="color:#fbbf24;font-weight:600">৳ {{ number_format($s['revenue'],0) }}</td>
                <td style="color:#86efac">৳ {{ number_format($s['collected'],0) }}</td>
                <td style="color:{{ $s['sale_due']>0 ? '#fca5a5' : '#64748b' }}">
                    ৳ {{ number_format($s['sale_due'],0) }}
                </td>
                <td style="color:#93c5fd">৳ {{ number_format($s['purchase_cost'],0) }}</td>
                <td style="font-weight:600;color:{{ $s['gross_profit']>=0 ? '#86efac' : '#fca5a5' }}">
                    {{ $s['gross_profit']<0 ? '−' : '' }}৳ {{ number_format(abs($s['gross_profit']),0) }}
                </td>
                <td style="color:#fda4af">৳ {{ number_format($s['expenses'],0) }}</td>
                <td style="font-weight:700;color:{{ $s['net_profit']>=0 ? '#86efac' : '#fca5a5' }}">
                    {{ $s['net_profit']<0 ? '−' : '' }}৳ {{ number_format(abs($s['net_profit']),0) }}
                </td>
                <td style="color:{{ $s['customer_due']>0 ? '#fca5a5' : '#64748b' }}">
                    ৳ {{ number_format($s['customer_due'],0) }}
                </td>
                <td style="color:#94a3b8">{{ $s['staff_count'] }}</td>
                <td>
                    <a href="{{ route('super.shops.show', $shop) }}" class="sa-btn sa-btn-ghost sa-btn-sm">
                        <i class="fas fa-eye"></i>
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background:#0f172a;font-weight:700;border-top:2px solid #f59e0b44">
                <td style="color:#f59e0b">সর্বমোট</td>
                <td style="color:#94a3b8">{{ number_format($totals['sales_count']) }}</td>
                <td style="color:#fbbf24">৳ {{ number_format($totals['revenue'],0) }}</td>
                <td style="color:#86efac">৳ {{ number_format($totals['collected'],0) }}</td>
                <td style="color:{{ $totals['sale_due']>0?'#fca5a5':'#64748b' }}">
                    ৳ {{ number_format($totals['sale_due'],0) }}
                </td>
                <td style="color:#93c5fd">৳ {{ number_format($totals['purchase_cost'],0) }}</td>
                <td style="color:{{ $totals['gross_profit']>=0?'#86efac':'#fca5a5' }}">
                    {{ $totals['gross_profit']<0 ? '−' : '' }}৳ {{ number_format(abs($totals['gross_profit']),0) }}
                </td>
                <td style="color:#fda4af">৳ {{ number_format($totals['expenses'],0) }}</td>
                <td style="color:{{ $totals['net_profit']>=0?'#86efac':'#fca5a5' }}">
                    {{ $totals['net_profit']<0 ? '−' : '' }}৳ {{ number_format(abs($totals['net_profit']),0) }}
                </td>
                <td style="color:{{ $totals['customer_due']>0?'#fca5a5':'#64748b' }}">
                    ৳ {{ number_format($totals['customer_due'],0) }}
                </td>
                <td></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    </div>
    @endif
</div>

@endsection
