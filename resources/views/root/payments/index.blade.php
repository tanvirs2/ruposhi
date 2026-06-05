@extends('layouts.root')
@section('title', 'পেমেন্ট ইতিহাস')
@section('page-title', 'সব পেমেন্ট')

@section('content')

{{-- Stats --}}
<div class="rt-stat-grid" style="margin-bottom:20px">
    <div class="rt-stat">
        <div class="rt-stat-val" style="color:#86efac">৳ {{ number_format($totalAmount, 0) }}</div>
        <div class="rt-stat-lbl">মোট আয় (ফিল্টার অনুযায়ী)</div>
    </div>
    <div class="rt-stat">
        <div class="rt-stat-val" style="color:#93c5fd">{{ $payments->total() }}</div>
        <div class="rt-stat-lbl">মোট লেনদেন</div>
    </div>
</div>

{{-- Filters --}}
<div class="rt-card" style="margin-bottom:16px">
    <form method="GET" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end">
        <div>
            <label style="font-size:.78rem;color:#64748b;display:block;margin-bottom:4px">ক্লায়েন্ট</label>
            <select name="user_id" class="rt-select" style="min-width:180px">
                <option value="">— সবাই —</option>
                @foreach($superAdmins as $sa)
                    <option value="{{ $sa->id }}" {{ request('user_id') == $sa->id ? 'selected' : '' }}>
                        {{ $sa->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label style="font-size:.78rem;color:#64748b;display:block;margin-bottom:4px">পদ্ধতি</label>
            <select name="method" class="rt-select">
                <option value="">— সব —</option>
                @foreach(\App\Models\PaymentLog::paymentMethods() as $key => $label)
                    <option value="{{ $key }}" {{ request('method') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label style="font-size:.78rem;color:#64748b;display:block;margin-bottom:4px">তারিখ থেকে</label>
            <input type="date" name="from" class="rt-input" value="{{ request('from') }}" style="width:150px">
        </div>
        <div>
            <label style="font-size:.78rem;color:#64748b;display:block;margin-bottom:4px">তারিখ পর্যন্ত</label>
            <input type="date" name="to" class="rt-input" value="{{ request('to') }}" style="width:150px">
        </div>
        <button type="submit" class="rt-btn rt-btn-primary"><i class="fas fa-search"></i> খুঁজুন</button>
        @if(request()->hasAny(['user_id','method','from','to']))
            <a href="{{ route('root.payments.index') }}" class="rt-btn rt-btn-ghost">পরিষ্কার</a>
        @endif
    </form>
</div>

{{-- Table --}}
<div class="rt-card">
    <div class="rt-card-title" style="justify-content:space-between">
        <span><i class="fas fa-receipt"></i> পেমেন্ট তালিকা</span>
    </div>

    @if($payments->isEmpty())
        <div class="rt-empty"><i class="fas fa-receipt"></i> কোনো পেমেন্ট পাওয়া যায়নি।</div>
    @else
    <table class="rt-table">
        <thead>
            <tr>
                <th>তারিখ</th>
                <th>ক্লায়েন্ট</th>
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
                <td>
                    <a href="{{ route('root.super-admins.show', $p->user) }}"
                       style="color:#93c5fd;text-decoration:none;font-weight:600">
                        {{ $p->user->name }}
                    </a>
                    <div style="font-size:.75rem;color:#475569">{{ $p->user->email }}</div>
                </td>
                <td style="font-weight:800;color:#86efac;font-size:1rem">
                    ৳ {{ number_format($p->amount, 0) }}
                </td>
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
        <tfoot>
            <tr style="border-top:2px solid #1e3a5f">
                <td colspan="2" style="padding:10px 12px;font-size:.85rem;font-weight:700;color:#94a3b8">
                    মোট ({{ $payments->total() }}টি)
                </td>
                <td style="padding:10px 12px;font-weight:800;color:#86efac;font-size:1rem">
                    ৳ {{ number_format($totalAmount, 0) }}
                </td>
                <td colspan="5"></td>
            </tr>
        </tfoot>
    </table>

    <div style="padding:16px">
        {{ $payments->withQueryString()->links() }}
    </div>
    @endif
</div>

@endsection
