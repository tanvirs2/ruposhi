@extends('layouts.app')
@section('title', $label . ' ইমপোর্ট')

@section('content')
<style>
.ci-steps { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:18px }
.ci-step { flex:1; min-width:180px; background:var(--surface-2,#f8fafc); border:1px solid var(--border,#e2e8f0);
           border-radius:10px; padding:12px 14px }
.ci-step b { display:block; font-size:.82rem; color:#0f766e; margin-bottom:3px }
.ci-step span { font-size:.78rem; color:#64748b; line-height:1.5 }
.ci-sum { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:16px }
.ci-pill { padding:8px 14px; border-radius:20px; font-weight:700; font-size:.85rem }
.ci-new  { background:#dcfce7; color:#15803d }
.ci-dupe { background:#fef3c7; color:#92400e }
.ci-err  { background:#fee2e2; color:#b91c1c }
.ci-due  { background:#eff6ff; color:#1d4ed8 }
.ci-tag { padding:2px 8px; border-radius:12px; font-size:.72rem; font-weight:700; white-space:nowrap }
.ci-row-dupe td, .ci-row-error td { background:#fffbeb }
.ci-row-error td { background:#fef2f2 }
</style>

<div class="page-header">
    <h1>{{ $label }} ইমপোর্ট (CSV)</h1>
    <a href="{{ route($type . '.index') }}" class="btn btn-ghost">← তালিকায় ফিরুন</a>
</div>

@if(session('error'))<div class="alert alert-error">{{ session('error') }}</div>@endif

<div class="ci-steps">
    <div class="ci-step"><b>১. টেমপ্লেট নিন</b><span>নিচের বাটনে CSV নামিয়ে Excel-এ খুলুন</span></div>
    <div class="ci-step"><b>২. পূরণ করুন</b><span>প্রতি সারিতে একজন {{ $label }} — {{ $dueLbl }} কলামে খাতার বাকী</span></div>
    <div class="ci-step"><b>৩. আপলোড → প্রিভিউ</b><span>কী যোগ হবে দেখে তবেই নিশ্চিত করুন</span></div>
</div>

<div class="card" style="margin-bottom:18px">
    <div class="card-header"><h3><i class="fas fa-file-csv"></i> ফাইল আপলোড</h3></div>
    <div style="padding:16px">
        <p style="color:#475569;font-size:.85rem;margin:0 0 12px">
            কলামের ক্রম: <strong>{{ implode(' · ', $columns) }}</strong><br>
            <span style="color:#64748b">বাংলা অঙ্ক, ৳ চিহ্ন ও কমা লেখা থাকলেও চলবে। অগ্রিম হলে মাইনাস দিন (যেমন -৫০০)।</span>
        </p>
        <form action="{{ route('contacts.import.preview', $type) }}" method="POST" enctype="multipart/form-data"
              style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
            @csrf
            <input type="file" name="file" accept=".csv" required
                   style="flex:1;min-width:220px;padding:8px;border:1px solid #cbd5e1;border-radius:8px">
            <button class="btn btn-primary"><i class="fas fa-eye"></i> প্রিভিউ দেখুন</button>
            <a href="{{ route('contacts.import.template', $type) }}" class="btn btn-ghost">
                <i class="fas fa-download"></i> টেমপ্লেট
            </a>
        </form>
        @error('file')<div style="color:#dc2626;font-size:.8rem;margin-top:8px">{{ $message }}</div>@enderror
    </div>
</div>

@isset($preview)
<div class="card">
    <div class="card-header"><h3><i class="fas fa-list-check"></i> প্রিভিউ — এখনো কিছু সংরক্ষণ হয়নি</h3></div>
    <div style="padding:16px">
        <div class="ci-sum">
            <span class="ci-pill ci-new">যোগ হবে {{ $preview['new'] }}</span>
            @if($preview['dupe'])<span class="ci-pill ci-dupe">বাদ যাবে {{ $preview['dupe'] }}</span>@endif
            @if($preview['error'])<span class="ci-pill ci-err">ত্রুটি {{ $preview['error'] }}</span>@endif
            <span class="ci-pill ci-due">মোট {{ $dueLbl }} ৳ {{ number_format($preview['sumDue'], 0) }}</span>
        </div>

        @if($preview['dupe'])
        <div class="alert" style="background:#fffbeb;border:1px solid #fde68a;color:#92400e;font-size:.82rem;margin-bottom:14px">
            <i class="fas fa-circle-info"></i> আগে থেকেই আছে এমন {{ $label }} <strong>বাদ দেওয়া হবে</strong> — তাদের বাকী বদলাবে না।
            বদলাতে চাইলে সেই {{ $label }}-এর সম্পাদনা পাতায় গিয়ে হাতে ঠিক করুন।
        </div>
        @endif

        <div class="table-wrap" style="margin-bottom:16px">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:50px">সারি</th>
                        <th>নাম</th>
                        <th>প্রোপ্রাইটর</th>
                        <th>ফোন</th>
                        @unless($type === 'suppliers')<th>এলাকা</th>@endunless
                        <th style="text-align:right">{{ $dueLbl }}</th>
                        <th style="width:150px">অবস্থা</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($preview['rows'] as $r)
                    <tr class="{{ $r['status'] === 'duplicate' ? 'ci-row-dupe' : ($r['status'] === 'error' ? 'ci-row-error' : '') }}">
                        <td class="mono">{{ $r['line'] }}</td>
                        <td><strong>{{ $r['name'] ?: '—' }}</strong></td>
                        <td>{{ $r['proprietor'] ?: '—' }}</td>
                        <td class="mono">{{ $r['phone'] ?: '—' }}</td>
                        @unless($type === 'suppliers')<td>{{ $r['area'] ?: '—' }}</td>@endunless
                        <td style="text-align:right;font-variant-numeric:tabular-nums">
                            ৳ {{ number_format($r['opening_balance'], 0) }}
                        </td>
                        <td>
                            @if($r['status'] === 'new')
                                <span class="ci-tag ci-new">যোগ হবে</span>
                            @elseif($r['status'] === 'duplicate')
                                <span class="ci-tag ci-dupe">বাদ</span>
                                <div style="font-size:.7rem;color:#92400e;margin-top:2px">{{ $r['note'] }}</div>
                            @else
                                <span class="ci-tag ci-err">ত্রুটি</span>
                                <div style="font-size:.7rem;color:#b91c1c;margin-top:2px">{{ $r['note'] }}</div>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        @if($preview['new'] > 0)
        <form action="{{ route('contacts.import.commit', $type) }}" method="POST">
            @csrf
            <button class="btn btn-primary" style="font-size:.95rem;padding:10px 20px">
                <i class="fas fa-check"></i> {{ $preview['new'] }}টি {{ $label }} যোগ করুন
            </button>
        </form>
        @else
        <p style="color:#92400e;font-size:.85rem;margin:0">যোগ করার মতো নতুন সারি নেই।</p>
        @endif
    </div>
</div>
@endisset
@endsection
