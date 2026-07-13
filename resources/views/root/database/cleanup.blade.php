@extends('layouts.root')
@section('title', 'ডেটা ক্লিনআপ')
@section('page-title', 'ডেটা ক্লিনআপ (শাখাভিত্তিক)')

@section('content')

{{-- Intro / how it works --}}
<div class="rt-card" style="border-top:3px solid #6366f1">
    <div class="rt-card-title"><i class="fas fa-circle-info" style="color:#818cf8"></i> এটা কী করে</div>
    <p style="font-size:.86rem;color:#94a3b8;line-height:1.7;margin:0">
        নতুন ক্লায়েন্টকে সফটওয়্যার বুঝিয়ে দেওয়ার আগে (বা ট্রায়াল শেষে) তার
        <strong style="color:#c7d2fe">টেস্ট লেনদেন</strong> পরিষ্কার করতে এই টুল ব্যবহার করুন।
        <strong style="color:#86efac">স্মার্ট রিসেট</strong> শুধু বিক্রয়-ক্রয়-পরিশোধ-খরচ মুছে ফেলে —
        কাস্টমার, সাপ্লায়ার ও পণ্য তালিকা <strong style="color:#86efac">অক্ষত থাকে</strong>, এবং সব বকেয়া ও স্টক শূন্য হয়ে যায়।
        প্রতিটি অপারেশনের আগে <strong style="color:#fbbf24">স্বয়ংক্রিয় ব্যাকআপ</strong> নেওয়া হয়।
    </p>
</div>

{{-- Shop overview --}}
<div class="rt-card">
    <div class="rt-card-title">
        <i class="fas fa-store"></i> শাখাসমূহের ডেটা
        <span style="font-size:.75rem;color:#475569;font-weight:400;margin-left:auto">{{ $shops->count() }}টি শাখা</span>
    </div>
    <table class="rt-table">
        <thead>
            <tr>
                <th>শাখা</th>
                <th>মালিক</th>
                <th style="text-align:right">বিক্রয়</th>
                <th style="text-align:right">গ্রহণ</th>
                <th style="text-align:right">কাস্টমার</th>
                <th style="text-align:right">সাপ্লায়ার</th>
                <th style="text-align:right">পণ্য</th>
            </tr>
        </thead>
        <tbody>
            @forelse($shops as $s)
            <tr>
                <td style="font-weight:600;color:#e2e8f0">
                    {{ $s['name'] }}
                    @if($s['is_locked'])<i class="fas fa-lock" style="color:#f87171;font-size:.72rem;margin-left:5px" title="লক করা"></i>@endif
                </td>
                <td style="font-size:.82rem;color:#94a3b8">{{ $s['owner'] }}</td>
                <td style="text-align:right;color:#93c5fd">{{ number_format($s['sales']) }}</td>
                <td style="text-align:right;color:#93c5fd">{{ number_format($s['purchases']) }}</td>
                <td style="text-align:right;color:#86efac">{{ number_format($s['customers']) }}</td>
                <td style="text-align:right;color:#86efac">{{ number_format($s['suppliers']) }}</td>
                <td style="text-align:right;color:#86efac">{{ number_format($s['items']) }}</td>
            </tr>
            @empty
            <tr><td colspan="7" style="text-align:center;color:#64748b;padding:20px">কোনো শাখা নেই</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="rt-grid-2" style="margin-top:20px">

    {{-- ── Smart reset ─────────────────────────────────────── --}}
    <div class="rt-card" style="margin:0;border-top:3px solid #22c55e">
        <div class="rt-card-title">
            <i class="fas fa-wand-magic-sparkles" style="color:#4ade80"></i> স্মার্ট রিসেট
        </div>
        <p style="font-size:.82rem;color:#94a3b8;line-height:1.6;margin-bottom:14px">
            নির্বাচিত শাখার <strong style="color:#86efac">সব লেনদেন</strong> মুছে ফেলবে (বিক্রয়, গ্রহণ, পরিশোধ,
            খরচ, লগ, চ্যাট)। <strong style="color:#86efac">রাখবে:</strong> কাস্টমার, সাপ্লায়ার, পণ্য, ক্যাটাগরি, এলাকা,
            কর্মচারী, কনফিগ। বকেয়া ও স্টক শূন্য হবে।
        </p>

        <form method="POST" action="{{ route('root.cleanup.smart') }}"
              onsubmit="return confirm('নিশ্চিত? এই শাখার সব লেনদেন মুছে যাবে (মাস্টার ডেটা থাকবে)।')">
            @csrf
            <div class="rt-field">
                <label class="rt-label">শাখা নির্বাচন</label>
                <select name="shop_id" class="rt-select" required data-shop-select onchange="clHint(this,'smartName')">
                    <option value="">— শাখা বাছুন —</option>
                    @foreach($shops as $s)
                        <option value="{{ $s['id'] }}" data-name="{{ $s['name'] }}">{{ $s['name'] }} — {{ $s['owner'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="rt-field">
                <label class="rt-label">
                    নিশ্চিত করতে শাখার নাম হুবহু টাইপ করুন
                    <span id="smartName" style="color:#4ade80;font-weight:700"></span>
                </label>
                <input type="text" name="confirm" class="rt-input" autocomplete="off"
                       placeholder="শাখার নাম" required value="{{ old('confirm') }}">
            </div>
            <button type="submit" class="rt-btn rt-btn-green" style="width:100%;justify-content:center;padding:12px">
                <i class="fas fa-wand-magic-sparkles"></i> লেনদেন রিসেট করুন (মাস্টার রাখবে)
            </button>
        </form>
    </div>

    {{-- ── Custom single-table clean ────────────────────────── --}}
    <div class="rt-card" style="margin:0;border-top:3px solid #ef4444">
        <div class="rt-card-title">
            <i class="fas fa-eraser" style="color:#f87171"></i> কাস্টম টেবিল ক্লিন
        </div>
        <p style="font-size:.82rem;color:#94a3b8;line-height:1.6;margin-bottom:14px">
            নির্দিষ্ট একটি টেবিলের ডেটা নির্বাচিত শাখার জন্য মুছে ফেলবে।
            <strong style="color:#fca5a5">⚠ চিহ্নিত</strong> মাস্টার টেবিল মুছলে ফিরবে না — সাবধান।
        </p>

        <form method="POST" action="{{ route('root.cleanup.custom') }}"
              onsubmit="return confirm('নিশ্চিত? নির্বাচিত টেবিলের ডেটা এই শাখা থেকে মুছে যাবে।')">
            @csrf
            <div class="rt-field">
                <label class="rt-label">শাখা নির্বাচন</label>
                <select name="shop_id" class="rt-select" required data-shop-select onchange="clHint(this,'customName')">
                    <option value="">— শাখা বাছুন —</option>
                    @foreach($shops as $s)
                        <option value="{{ $s['id'] }}" data-name="{{ $s['name'] }}">{{ $s['name'] }} — {{ $s['owner'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="rt-field">
                <label class="rt-label">টেবিল নির্বাচন</label>
                <select name="table" class="rt-select" required>
                    <option value="">— টেবিল বাছুন —</option>
                    @foreach($cleanable as $tbl => $meta)
                        <option value="{{ $tbl }}">{{ $meta['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="rt-field">
                <label class="rt-label">
                    নিশ্চিত করতে শাখার নাম হুবহু টাইপ করুন
                    <span id="customName" style="color:#fca5a5;font-weight:700"></span>
                </label>
                <input type="text" name="confirm" class="rt-input" autocomplete="off"
                       placeholder="শাখার নাম" required>
            </div>
            <button type="submit" class="rt-btn rt-btn-danger" style="width:100%;justify-content:center;padding:12px">
                <i class="fas fa-eraser"></i> টেবিল ক্লিন করুন
            </button>
        </form>
    </div>

</div>

<script>
function clHint(sel, targetId) {
    var opt  = sel.options[sel.selectedIndex];
    var name = opt ? (opt.getAttribute('data-name') || '') : '';
    var el   = document.getElementById(targetId);
    if (el) el.textContent = name ? '→ ' + name : '';
}
</script>

@endsection
