<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>সাবস্ক্রিপশন মেয়াদ শেষ</title>
    @include('partials.base-fonts')
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Hind Siliguri', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 20px;
            padding: 44px 36px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            box-shadow: 0 25px 60px rgba(0,0,0,.5);
        }
        .icon-wrap {
            width: 80px; height: 80px; border-radius: 50%;
            background: linear-gradient(135deg, #7f1d1d, #dc2626);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem; color: #fff;
            box-shadow: 0 8px 25px rgba(220,38,38,.35);
        }
        h1 { font-size: 1.45rem; font-weight: 700; color: #f1f5f9; margin-bottom: 10px; }
        .subtitle { font-size: .9rem; color: #94a3b8; line-height: 1.7; margin-bottom: 24px; }
        .expire-info {
            display: inline-block;
            background: #7f1d1d22;
            border: 1px solid #7f1d1d;
            color: #fca5a5;
            padding: 5px 14px;
            border-radius: 20px;
            font-size: .8rem;
            font-weight: 600;
            margin-bottom: 20px;
        }

        /* Software ID box */
        .id-box {
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 14px 18px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        .id-item { text-align: center; }
        .id-label { font-size: .7rem; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px; }
        .id-value {
            font-size: 1.2rem; font-weight: 800; font-family: monospace; letter-spacing: 1px;
            color: #93c5fd; background: #1e3a5f; padding: 4px 14px; border-radius: 8px;
        }
        .id-value.license { color: #fde68a; background: #78350f44; }

        /* Contact box */
        .contact-box {
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 24px;
            text-align: left;
        }
        .contact-box p { font-size: .78rem; color: #64748b; margin-bottom: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
        .contact-row { display: flex; align-items: center; gap: 10px; color: #e2e8f0; font-size: .9rem; margin-top: 8px; }
        .contact-row i { width: 18px; text-align:center; }

        .hint {
            font-size: .76rem; color: #475569; margin-bottom: 22px; line-height: 1.6;
            background: #0f172a; border-radius: 8px; padding: 10px 14px;
            border: 1px dashed #334155;
        }
        .btn-logout {
            display: inline-flex; align-items: center; gap: 9px;
            background: #334155; color: #fca5a5;
            border: none; border-radius: 10px;
            padding: 12px 28px; font-size: .9rem; font-weight: 600;
            font-family: inherit; cursor: pointer;
            text-decoration: none; transition: .15s;
        }
        .btn-logout:hover { background: #7f1d1d; color: #fff; }
    </style>
</head>
<body>
<div class="card">
    <div class="icon-wrap">
        <i class="fas fa-lock"></i>
    </div>

    <h1>সাবস্ক্রিপশন মেয়াদ শেষ</h1>

    @php
        $user    = auth()->user();
        // For super_admin: use their own id; for admin/staff: get the shop owner
        $owner   = ($user->role === 'super_admin') ? $user : \App\Models\User::find(
            optional(\App\Models\Shop::find($user->shop_id))->super_admin_id
        );
        $license = $owner?->activeLicense();

        $supportPhone    = \App\Models\SystemConfig::get('support_phone', '01XXXXXXXXX');
        $supportWhatsapp = \App\Models\SystemConfig::get('support_whatsapp', '01XXXXXXXXX');
        $supportEmail    = \App\Models\SystemConfig::get('support_email');
        $softwareName    = \App\Models\SystemConfig::get('software_name', 'Ruposhi POS');
    @endphp

    @if($license)
        <div class="expire-info">
            <i class="fas fa-calendar-xmark"></i>
            মেয়াদ শেষ: {{ $license->expires_at->format('d M Y') }}
        </div>
    @endif

    <p class="subtitle">
        আপনার সফটওয়্যার লাইসেন্সের মেয়াদ শেষ হয়ে গেছে।<br>
        নিচের নম্বরে কল করে সাবস্ক্রিপশন নবায়ন করুন।
    </p>

    {{-- Software ID box --}}
    @if($owner)
    <div class="id-box">
        <div class="id-item">
            <div class="id-label"><i class="fas fa-id-badge"></i> সফটওয়্যার আইডি</div>
            <div class="id-value">SA-{{ $owner->id }}</div>
        </div>
        @if($license)
        <div class="id-item">
            <div class="id-label"><i class="fas fa-file-contract"></i> লাইসেন্স আইডি</div>
            <div class="id-value license">#{{ $license->id }}</div>
        </div>
        @endif
    </div>

    <div class="hint">
        <i class="fas fa-circle-info" style="color:#818cf8"></i>
        কাস্টমার সার্ভিসে ফোন করলে উপরের <strong style="color:#93c5fd">SA-ID</strong> বলুন —
        এটা দিয়ে আপনার account দ্রুত খোঁজা যাবে।
    </div>
    @endif

    {{-- Contact box --}}
    <div class="contact-box">
        <p><i class="fas fa-headset"></i> &nbsp;যোগাযোগ করুন</p>
        @if($supportPhone && $supportPhone !== '01XXXXXXXXX')
        <div class="contact-row">
            <i class="fas fa-phone" style="color:#f59e0b"></i>
            <span>সফটওয়্যার সাপোর্ট: <strong>{{ $supportPhone }}</strong></span>
        </div>
        @else
        <div class="contact-row">
            <i class="fas fa-phone" style="color:#475569"></i>
            <span style="color:#475569">সাপোর্ট নম্বর এখনো সেট হয়নি।</span>
        </div>
        @endif

        @if($supportWhatsapp && $supportWhatsapp !== '01XXXXXXXXX')
        <div class="contact-row">
            <i class="fab fa-whatsapp" style="color:#4ade80"></i>
            <span>WhatsApp: <strong>{{ $supportWhatsapp }}</strong></span>
        </div>
        @endif

        @if($supportEmail)
        <div class="contact-row">
            <i class="fas fa-envelope" style="color:#fbbf24"></i>
            <span>ইমেইল: <strong>{{ $supportEmail }}</strong></span>
        </div>
        @endif
    </div>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn-logout">
            <i class="fas fa-right-from-bracket"></i>
            লগআউট
        </button>
    </form>
</div>
</body>
</html>
