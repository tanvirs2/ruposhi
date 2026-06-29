@extends('layouts.root')
@section('title', 'ডেটাবেজ ব্যাকআপ')
@section('page-title', 'ডেটাবেজ ব্যাকআপ / রিস্টোর')

@section('content')

<div class="rt-grid-2" style="margin-bottom:20px">

    {{-- Export --}}
    <div class="rt-card" style="margin:0;border-top:3px solid #22c55e">
        <div class="rt-card-title">
            <i class="fas fa-download" style="color:#22c55e"></i> ডেটাবেজ Export
        </div>
        <p style="font-size:.85rem;color:#94a3b8;margin-bottom:18px;line-height:1.6">
            সম্পূর্ণ ডেটাবেজ একটি <code style="background:#060d1a;padding:1px 6px;border-radius:4px;color:#86efac">.sql</code>
            ফাইলে ডাউনলোড হবে। সমস্ত টেবিল ও ডেটা অন্তর্ভুক্ত থাকবে।
        </p>

        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:20px">
            <div style="background:#060d1a;border:1px solid #1e3a5f;border-radius:10px;padding:14px;text-align:center">
                <div style="font-size:1.4rem;font-weight:800;color:#86efac">{{ count($stats) }}</div>
                <div style="font-size:.72rem;color:#64748b;margin-top:3px">টেবিল</div>
            </div>
            <div style="background:#060d1a;border:1px solid #1e3a5f;border-radius:10px;padding:14px;text-align:center">
                <div style="font-size:1.4rem;font-weight:800;color:#93c5fd">{{ number_format($totalRows) }}</div>
                <div style="font-size:.72rem;color:#64748b;margin-top:3px">মোট সারি</div>
            </div>
            <div style="background:#060d1a;border:1px solid #1e3a5f;border-radius:10px;padding:14px;text-align:center">
                <div style="font-size:1.4rem;font-weight:800;color:#fbbf24">{{ number_format($totalKb, 0) }} KB</div>
                <div style="font-size:.72rem;color:#64748b;margin-top:3px">আনুমানিক আকার</div>
            </div>
        </div>

        <a href="{{ route('root.database.export') }}"
           class="rt-btn rt-btn-green" style="width:100%;justify-content:center;font-size:.95rem;padding:13px">
            <i class="fas fa-download"></i> ডেটাবেজ ডাউনলোড করুন (.sql)
        </a>
    </div>

    {{-- Import --}}
    <div class="rt-card" style="margin:0;border-top:3px solid #ef4444">
        <div class="rt-card-title">
            <i class="fas fa-upload" style="color:#f87171"></i> ডেটাবেজ Import
        </div>

        <div style="background:#7f1d1d22;border:1px solid #7f1d1d;border-radius:8px;padding:12px 14px;margin-bottom:18px;font-size:.83rem;color:#fca5a5;line-height:1.6">
            <i class="fas fa-triangle-exclamation"></i>
            <strong>সতর্কতা:</strong> Import করলে বিদ্যমান সমস্ত ডেটা মুছে যাবে এবং আপলোড করা ফাইলের ডেটা দিয়ে প্রতিস্থাপিত হবে।
            আগে অবশ্যই Export করে নিন।
        </div>

        <form method="POST" action="{{ route('root.database.import') }}" enctype="multipart/form-data"
              onsubmit="return confirm('নিশ্চিত? বিদ্যমান সব ডেটা মুছে যাবে!')">
            @csrf
            <div class="rt-field">
                <label class="rt-label">SQL ফাইল নির্বাচন করুন</label>
                <input type="file" name="sql_file" accept=".sql,.txt" required
                       style="width:100%;padding:10px;border:1px solid #1e3a5f;border-radius:9px;
                              background:#060d1a;color:#e2e8f0;font-size:.86rem;font-family:inherit">
                @error('sql_file')
                    <div style="color:#fca5a5;font-size:.8rem;margin-top:6px">{{ $message }}</div>
                @enderror
                <div style="font-size:.75rem;color:#64748b;margin-top:5px">সর্বোচ্চ ৫০ MB · .sql বা .txt ফরম্যাট</div>
            </div>
            <button type="submit" class="rt-btn rt-btn-danger" style="width:100%;justify-content:center;font-size:.95rem;padding:13px">
                <i class="fas fa-upload"></i> Import করুন (ডেটা মুছবে)
            </button>
        </form>
    </div>

</div>

{{-- Table list --}}
<div class="rt-card">
    <div class="rt-card-title">
        <i class="fas fa-table"></i> ডেটাবেজ টেবিল তালিকা
        <span style="font-size:.75rem;color:#475569;font-weight:400;margin-left:auto">{{ count($stats) }}টি টেবিল</span>
    </div>
    <table class="rt-table">
        <thead>
            <tr>
                <th>টেবিল নাম</th>
                <th>সারি সংখ্যা</th>
                <th>আকার (KB)</th>
                <th>Engine</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stats as $t)
            <tr>
                <td style="font-family:monospace;font-size:.82rem;color:#93c5fd">{{ $t['name'] }}</td>
                <td>{{ number_format($t['rows']) }}</td>
                <td>{{ $t['size_kb'] }}</td>
                <td style="font-size:.78rem;color:#64748b">{{ $t['engine'] }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="border-top:2px solid #1e3a5f">
                <td style="font-weight:700;color:#e2e8f0">মোট</td>
                <td style="font-weight:700;color:#93c5fd">{{ number_format($totalRows) }}</td>
                <td style="font-weight:700;color:#fbbf24">{{ number_format($totalKb, 0) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>

@endsection
