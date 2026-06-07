@extends('layouts.root')
@section('title', 'সিস্টেম সেটিংস')
@section('page-title', 'সিস্টেম সেটিংস')

@section('content')
<div style="max-width:560px">

<div class="rt-card">
    <div class="rt-card-title"><i class="fas fa-gear"></i> সফটওয়্যার ও সাপোর্ট তথ্য</div>
    <p style="font-size:.82rem;color:#64748b;margin-bottom:20px;line-height:1.6">
        এই তথ্যগুলো সাবস্ক্রিপশন মেয়াদ শেষের পেজে দেখানো হবে।
        কাস্টমার ফোন করলে এখানের নম্বরে যোগাযোগ করতে বলুন।
    </p>

    <form method="POST" action="{{ route('root.settings.update') }}">
        @csrf @method('PUT')

        <div class="rt-field">
            <label class="rt-label"><i class="fas fa-tag" style="color:#93c5fd"></i> সফটওয়্যারের নাম</label>
            <input class="rt-input" type="text" name="software_name"
                   value="{{ old('software_name', $settings['software_name']) }}"
                   placeholder="যেমন: Ruposhi POS">
        </div>

        <div class="rt-field">
            <label class="rt-label"><i class="fas fa-phone" style="color:#86efac"></i> সাপোর্ট ফোন নম্বর</label>
            <input class="rt-input" type="text" name="support_phone"
                   value="{{ old('support_phone', $settings['support_phone']) }}"
                   placeholder="যেমন: 01700000000">
        </div>

        <div class="rt-field">
            <label class="rt-label"><i class="fab fa-whatsapp" style="color:#4ade80"></i> WhatsApp নম্বর</label>
            <input class="rt-input" type="text" name="support_whatsapp"
                   value="{{ old('support_whatsapp', $settings['support_whatsapp']) }}"
                   placeholder="যেমন: 01700000000">
        </div>

        <div class="rt-field">
            <label class="rt-label"><i class="fas fa-envelope" style="color:#fbbf24"></i> সাপোর্ট ইমেইল (ঐচ্ছিক)</label>
            <input class="rt-input" type="email" name="support_email"
                   value="{{ old('support_email', $settings['support_email']) }}"
                   placeholder="যেমন: support@yourcompany.com">
        </div>

        <button type="submit" class="rt-btn rt-btn-primary" style="width:100%">
            <i class="fas fa-save"></i> সেটিংস সেভ করুন
        </button>
    </form>
</div>

{{-- Preview --}}
<div class="rt-card" style="margin-top:0">
    <div class="rt-card-title"><i class="fas fa-eye"></i> প্রিভিউ — কাস্টমার যা দেখবে</div>
    <div style="background:#0f172a;border:1px solid #334155;border-radius:12px;padding:18px 20px;font-size:.88rem">
        <div style="color:#64748b;font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px">
            <i class="fas fa-headset"></i> &nbsp;যোগাযোগ করুন
        </div>
        <div style="color:#94a3b8;margin-bottom:8px">
            <i class="fas fa-id-badge" style="color:#818cf8;width:18px"></i>
            সফটওয়্যার আইডি: <strong style="color:#e2e8f0">SA-3</strong>
            &nbsp;|&nbsp; লাইসেন্স: <strong style="color:#e2e8f0">#7</strong>
        </div>
        <div style="color:#e2e8f0;margin-bottom:6px">
            <i class="fas fa-phone" style="color:#f59e0b;width:18px"></i>
            সফটওয়্যার সাপোর্ট:
            <strong>{{ $settings['support_phone'] ?: '(এখনো সেট হয়নি)' }}</strong>
        </div>
        <div style="color:#e2e8f0">
            <i class="fab fa-whatsapp" style="color:#4ade80;width:18px"></i>
            WhatsApp:
            <strong>{{ $settings['support_whatsapp'] ?: '(এখনো সেট হয়নি)' }}</strong>
        </div>
        @if($settings['support_email'])
        <div style="color:#e2e8f0;margin-top:6px">
            <i class="fas fa-envelope" style="color:#fbbf24;width:18px"></i>
            ইমেইল: <strong>{{ $settings['support_email'] }}</strong>
        </div>
        @endif
    </div>
    <p style="font-size:.76rem;color:#475569;margin-top:10px;text-align:center">
        SA-ID ও License ID কাস্টমারের account অনুযায়ী automatically পরিবর্তন হবে।
    </p>
</div>

</div>
@endsection
