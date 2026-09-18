@extends('layouts.app')
@section('title', 'মেমো পুনঃমুদ্রণ লগ')
@section('page-title', 'মেমো পুনঃমুদ্রণের ইতিহাস')

@section('content')

@push('styles')
<meta name="turbo-cache-control" content="no-cache">
@endpush

{{-- ব্যাখ্যা — কেন প্রথম প্রিন্ট এই তালিকায় নেই --}}
<div class="no-print" style="margin-bottom:16px;padding:12px 14px;background:#eff6ff;
    border:1px solid #bfdbfe;border-left:4px solid #2563eb;border-radius:8px;
    font-size:.84rem;color:#1e40af;line-height:1.7">
    <i class="fas fa-circle-info"></i>
    প্রতিটা মেমোর <strong>প্রথম প্রিন্ট স্বাভাবিক কাজ</strong>, তাই এখানে আসে না।
    একই মেমোর দ্বিতীয় বা তার পরের কপি প্রিন্ট হলেই সেটা এখানে জমা হয় — কে, কখন, কোন কপি।
    ওই কপিগুলোর কাগজের উপরে <strong>"পুনঃমুদ্রণ — কপি নং X"</strong> ছাপা থাকে।
</div>

{{-- Filter --}}
<div class="card no-print" style="margin-bottom:20px">
    <div class="card-filter">
        <form method="GET" class="filter-form" data-date-snap>
            <div class="form-group-field">
                <label>শুরুর তারিখ</label>
                <input type="date" name="from" value="{{ $from }}">
            </div>
            <div class="form-group-field">
                <label>শেষ তারিখ</label>
                <input type="date" name="to" value="{{ $to }}">
            </div>
            @include('partials.date-range-buttons')
            <button type="submit" class="btn btn-primary" style="align-self:flex-end">
                <i class="fas fa-search"></i> খুঁজুন
            </button>
        </form>
    </div>
</div>

{{-- Summary --}}
<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-label">পুনঃমুদ্রণ</div>
        <div class="stat-value" style="color:#dc2626">{{ number_format($summary['reprints']) }} টি</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">যত মেমোতে</div>
        <div class="stat-value">{{ number_format($summary['memos']) }} টি</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">যত ইউজার</div>
        <div class="stat-value">{{ number_format($summary['users']) }} জন</div>
    </div>
</div>

<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <h3><i class="fas fa-print"></i> পুনঃমুদ্রণ তালিকা</h3>
        <span style="font-size:.8rem;color:#94a3b8">{{ $logs->total() }} টি রেকর্ড</span>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="tc">প্রিন্টের সময়</th>
                    <th class="tc">চালান নং</th>
                    <th class="tc">কপি নং</th>
                    <th>কাস্টমার</th>
                    <th class="tr">মেমোর মোট</th>
                    <th class="tc">বিক্রয়ের তারিখ</th>
                    <th class="tc">কে প্রিন্ট করেছেন</th>
                    <th class="tc">IP</th>
                    <th class="tc">মেমো</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td class="tc" style="font-size:.78rem;white-space:nowrap;color:#334155">
                        {{ $log->printed_at->format('d/m/Y h:i a') }}
                    </td>
                    <td class="tc mono">#{{ str_pad($log->sale_id, 6, '0', STR_PAD_LEFT) }}</td>
                    <td class="tc">
                        <span class="badge badge-red">কপি {{ $log->copy_no }}</span>
                    </td>
                    <td>{{ $log->sale?->customer?->name ?? 'ওয়াক-ইন কাস্টমার' }}</td>
                    <td class="tr">৳ {{ number_format($log->sale?->total_amount ?? 0, 0) }}</td>
                    <td class="tc" style="font-size:.78rem;color:#64748b">
                        {{ $log->sale?->sale_date?->format('d/m/Y') ?? '—' }}
                    </td>
                    <td class="tc" style="font-size:.8rem;font-weight:600;color:#334155">
                        {{ $log->user?->name ?? 'অজানা' }}
                    </td>
                    <td class="tc mono" style="font-size:.72rem;color:#94a3b8">{{ $log->ip ?? '—' }}</td>
                    <td class="tc">
                        @if($log->sale)
                        <a href="{{ route('sales.show', $log->sale_id) }}" class="btn-icon-sm"
                           title="মেমো দেখুন" style="color:#0d9488">
                            <i class="fas fa-eye"></i>
                        </a>
                        @else
                        <span style="color:#cbd5e1">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="empty-row">এই সময়কালে কোনো পুনঃমুদ্রণ হয়নি</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">{{ $logs->links() }}</div>
</div>

@endsection
