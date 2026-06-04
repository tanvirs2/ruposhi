@extends('layouts.reseller')
@section('title', 'আমার ক্লায়েন্ট')
@section('page-title', 'ক্লায়েন্ট তালিকা')

@section('content')
<div class="rt-card">
    <div class="rt-card-title" style="justify-content:space-between">
        <span><i class="fas fa-users"></i> আমার ক্লায়েন্ট</span>
        <a href="{{ route('reseller.clients.create') }}" class="rt-btn rt-btn-primary rt-btn-sm"><i class="fas fa-plus"></i> নতুন ক্লায়েন্ট</a>
    </div>

    @if($clients->isEmpty())
        <div class="rt-empty"><i class="fas fa-users"></i> কোনো ক্লায়েন্ট নেই।</div>
    @else
    <table class="rt-table">
        <thead>
            <tr><th>#</th><th>নাম</th><th>ইমেইল</th><th>মেয়াদ</th><th>অবস্থা</th><th>অ্যাকশন</th></tr>
        </thead>
        <tbody>
            @foreach($clients as $i => $c)
            @php $lic = $c->activeLicense(); @endphp
            <tr>
                <td style="color:#475569">{{ $i + 1 }}</td>
                <td>{{ $c->name }}</td>
                <td style="color:#64748b;font-size:.83rem">{{ $c->email }}</td>
                <td style="font-size:.82rem">
                    {{ $lic?->expires_at->format('d M Y') ?? '—' }}
                    @if($lic?->status === 'warning')
                        <br><small style="color:#fde68a">{{ $lic->daysUntilExpiry() }} দিন বাকি</small>
                    @elseif($lic?->status === 'grace')
                        <br><small style="color:#fca5a5">গ্রেস: {{ $lic->graceDaysLeft() }} দিন</small>
                    @endif
                </td>
                <td>
                    @php $st = $lic?->status ?? 'expired'; @endphp
                    <span class="rt-pill rt-pill-{{ $st }}">
                        {{ ['active'=>'সক্রিয়','warning'=>'সংকট','grace'=>'গ্রেস','expired'=>'শেষ'][$st] }}
                    </span>
                </td>
                <td>
                    {{-- Extend license modal trigger --}}
                    <button onclick="openExtend({{ $c->id }}, '{{ addslashes($c->name) }}')"
                            class="rt-btn rt-btn-ghost rt-btn-sm">
                        <i class="fas fa-key"></i> নবায়ন
                    </button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

{{-- Extend modal --}}
<div id="extendModal" style="display:none;position:fixed;inset:0;background:#000000aa;z-index:100;align-items:center;justify-content:center">
    <div style="background:#0d1b2e;border:1px solid #1e3a5f;border-radius:16px;padding:28px;width:100%;max-width:420px;margin:20px">
        <div style="font-size:1rem;font-weight:700;color:#6ee7b7;margin-bottom:18px">
            <i class="fas fa-key"></i> লাইসেন্স নবায়ন
        </div>
        <div id="extendClientName" style="color:#94a3b8;font-size:.88rem;margin-bottom:16px"></div>

        <form id="extendForm" method="POST">
            @csrf
            <div class="rt-field">
                <label class="rt-label">প্ল্যান</label>
                <select class="rt-select" name="plan">
                    <option value="monthly">মাসিক (৩০ দিন)</option>
                    <option value="quarterly">ত্রৈমাসিক (৯০ দিন)</option>
                    <option value="yearly">বার্ষিক (৩৬৫ দিন)</option>
                </select>
            </div>
            <div class="rt-field">
                <label class="rt-label">কোথা থেকে বাড়াবেন?</label>
                <select class="rt-select" name="from">
                    <option value="expiry">বর্তমান মেয়াদের পর</option>
                    <option value="today">আজ থেকে</option>
                </select>
            </div>
            <div style="display:flex;gap:10px;margin-top:4px">
                <button type="submit" class="rt-btn rt-btn-primary"><i class="fas fa-rotate-right"></i> নবায়ন</button>
                <button type="button" onclick="closeExtend()" class="rt-btn rt-btn-ghost">বাতিল</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openExtend(userId, name) {
    document.getElementById('extendClientName').textContent = 'ক্লায়েন্ট: ' + name;
    document.getElementById('extendForm').action = '/reseller/clients/' + userId + '/extend-license';
    document.getElementById('extendModal').style.display = 'flex';
}
function closeExtend() {
    document.getElementById('extendModal').style.display = 'none';
}
</script>
@endpush
@endsection
