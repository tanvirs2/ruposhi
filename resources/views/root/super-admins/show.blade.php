@extends('layouts.root')
@section('title', $superAdmin->name . ' — লাইসেন্স')
@section('page-title', $superAdmin->name)

@section('content')

@php $currentLic = $superAdmin->activeLicense(); @endphp

<div style="display:grid;grid-template-columns:1fr 380px;gap:20px;align-items:start">

    {{-- License history --}}
    <div class="rt-card">
        <div class="rt-card-title"><i class="fas fa-file-contract"></i> লাইসেন্স ইতিহাস</div>

        @if($licenses->isEmpty())
            <div class="rt-empty"><i class="fas fa-file-slash"></i> কোনো লাইসেন্স নেই।</div>
        @else
        <table class="rt-table">
            <thead>
                <tr>
                    <th>শুরু</th>
                    <th>মেয়াদ</th>
                    <th>গ্রেস শেষ</th>
                    <th>প্ল্যান</th>
                    <th>অবস্থা</th>
                </tr>
            </thead>
            <tbody>
                @foreach($licenses as $lic)
                <tr>
                    <td style="font-size:.82rem">{{ $lic->starts_at->format('d M Y') }}</td>
                    <td style="font-size:.82rem">{{ $lic->expires_at->format('d M Y') }}</td>
                    <td style="font-size:.82rem">{{ $lic->grace_ends_at->format('d M Y') }}</td>
                    <td style="font-size:.8rem">{{ $lic->plan }}</td>
                    <td>
                        @php $st = $lic->status; @endphp
                        <span class="rt-pill rt-pill-{{ $st }}">
                            {{ ['active'=>'সক্রিয়','warning'=>'সংকট','grace'=>'গ্রেস','expired'=>'শেষ'][$st] }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    {{-- Extend License Form --}}
    <div class="rt-card">
        <div class="rt-card-title"><i class="fas fa-key"></i> লাইসেন্স নবায়ন</div>

        @if($currentLic)
        <div style="background:#060d1a;border-radius:9px;padding:14px;margin-bottom:18px;font-size:.85rem">
            <div style="color:#64748b;margin-bottom:6px">বর্তমান মেয়াদ</div>
            <div style="font-size:1.1rem;font-weight:700;color:#f1f5f9">{{ $currentLic->expires_at->format('d M Y') }}</div>
            @php $st = $currentLic->status; @endphp
            <span class="rt-pill rt-pill-{{ $st }}" style="margin-top:8px;display:inline-block">
                {{ ['active'=>'সক্রিয়','warning'=>'সংকট — '.$currentLic->daysUntilExpiry().' দিন বাকি','grace'=>'গ্রেস — '.$currentLic->graceDaysLeft().' দিন বাকি','expired'=>'মেয়াদ শেষ'][$st] }}
            </span>
        </div>
        @endif

        <form method="POST" action="{{ route('root.super-admins.extend-license', $superAdmin) }}">
            @csrf

            <div class="rt-field">
                <label class="rt-label">বাড়ানোর ধরন</label>
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:.88rem">
                        <input type="radio" name="extend_type" value="plan" checked onchange="toggleExtendType()"> প্ল্যান অনুযায়ী
                    </label>
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:.88rem">
                        <input type="radio" name="extend_type" value="days" onchange="toggleExtendType()"> নির্দিষ্ট দিন
                    </label>
                </div>
            </div>

            <div class="rt-field" id="planField">
                <label class="rt-label">প্ল্যান</label>
                <select class="rt-select" name="plan">
                    <option value="monthly">মাসিক (৩০ দিন)</option>
                    <option value="quarterly">ত্রৈমাসিক (৯০ দিন)</option>
                    <option value="yearly">বার্ষিক (৩৬৫ দিন)</option>
                </select>
            </div>

            <div class="rt-field" id="daysField" style="display:none">
                <label class="rt-label">দিনের সংখ্যা</label>
                <input class="rt-input" type="number" name="days" min="1" max="3650" placeholder="যেমন: 45">
            </div>

            <div class="rt-field">
                <label class="rt-label">কোথা থেকে বাড়াবেন?</label>
                <select class="rt-select" name="from">
                    <option value="expiry">বর্তমান মেয়াদ শেষের পর থেকে</option>
                    <option value="today">আজ থেকে</option>
                </select>
            </div>

            <div class="rt-field">
                <label class="rt-label">নোট (ঐচ্ছিক)</label>
                <textarea class="rt-textarea" name="notes" rows="2"></textarea>
            </div>

            <button type="submit" class="rt-btn rt-btn-primary" style="width:100%">
                <i class="fas fa-rotate-right"></i> লাইসেন্স নবায়ন করুন
            </button>
        </form>
    </div>

</div>

<div style="margin-top:14px">
    <a href="{{ route('root.super-admins.index') }}" class="rt-btn rt-btn-ghost">
        <i class="fas fa-arrow-left"></i> তালিকায় ফিরুন
    </a>
</div>

@push('scripts')
<script>
function toggleExtendType() {
    const type = document.querySelector('input[name="extend_type"]:checked').value;
    document.getElementById('planField').style.display = type === 'plan' ? 'block' : 'none';
    document.getElementById('daysField').style.display = type === 'days' ? 'block' : 'none';
}
</script>
@endpush
@endsection
