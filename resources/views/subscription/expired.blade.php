<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>সাবস্ক্রিপশন মেয়াদ শেষ</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
            padding: 50px 40px;
            max-width: 480px;
            width: 100%;
            text-align: center;
            box-shadow: 0 25px 60px rgba(0,0,0,.5);
        }
        .icon-wrap {
            width: 90px; height: 90px; border-radius: 50%;
            background: linear-gradient(135deg, #7f1d1d, #dc2626);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 24px;
            font-size: 2.2rem; color: #fff;
            box-shadow: 0 8px 25px rgba(220,38,38,.35);
        }
        h1 { font-size: 1.5rem; font-weight: 700; color: #f1f5f9; margin-bottom: 12px; }
        .subtitle { font-size: .95rem; color: #94a3b8; line-height: 1.7; margin-bottom: 30px; }
        .contact-box {
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 18px 20px;
            margin-bottom: 28px;
            text-align: left;
        }
        .contact-box p { font-size: .85rem; color: #64748b; margin-bottom: 8px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
        .contact-row { display: flex; align-items: center; gap: 10px; color: #e2e8f0; font-size: .92rem; margin-top: 8px; }
        .contact-row i { color: #f59e0b; width: 18px; }
        .btn-logout {
            display: inline-flex; align-items: center; gap: 9px;
            background: #334155; color: #fca5a5;
            border: none; border-radius: 10px;
            padding: 12px 28px; font-size: .92rem; font-weight: 600;
            font-family: inherit; cursor: pointer;
            text-decoration: none; transition: .15s;
        }
        .btn-logout:hover { background: #7f1d1d; color: #fff; }
        .expire-info {
            display: inline-block;
            background: #7f1d1d22;
            border: 1px solid #7f1d1d;
            color: #fca5a5;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: .82rem;
            font-weight: 600;
            margin-bottom: 22px;
        }
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
        $license = $user->activeLicense();
    @endphp

    @if($license)
        <div class="expire-info">
            <i class="fas fa-calendar-xmark"></i>
            মেয়াদ শেষ: {{ $license->expires_at->format('d M Y') }}
        </div>
    @endif

    <p class="subtitle">
        আপনার সফটওয়্যার লাইসেন্সের মেয়াদ শেষ হয়ে গেছে।<br>
        সফটওয়্যার ব্যবহার পুনরায় শুরু করতে সাবস্ক্রিপশন নবায়ন করুন।
    </p>

    <div class="contact-box">
        <p><i class="fas fa-headset"></i> &nbsp;যোগাযোগ করুন</p>
        <div class="contact-row">
            <i class="fas fa-phone"></i>
            <span>সফটওয়্যার সাপোর্ট: <strong>01XXXXXXXXX</strong></span>
        </div>
        <div class="contact-row">
            <i class="fab fa-whatsapp"></i>
            <span>WhatsApp: <strong>01XXXXXXXXX</strong></span>
        </div>
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
