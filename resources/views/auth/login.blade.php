<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>লগইন — Inventory</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Hind Siliguri', 'Inter', system-ui, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #134e4a 50%, #0d9488 100%);
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
        }
        .login-card {
            background: #fff;
            border-radius: 20px;
            padding: 40px 36px;
            width: 100%; max-width: 400px;
            box-shadow: 0 25px 60px rgba(0,0,0,.3);
        }
        .login-brand {
            display: flex; align-items: center; gap: 12px;
            justify-content: center; margin-bottom: 32px;
        }
        .login-brand-icon {
            width: 44px; height: 44px;
            background: #0d9488; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 1.2rem;
        }
        .login-brand span {
            font-family: 'Inter', sans-serif;
            font-size: 1.4rem; font-weight: 700;
            color: #0f172a;
        }
        h2 { font-size: 1.1rem; font-weight: 600; color: #1e293b; margin-bottom: 6px; }
        p.sub  { font-size: 0.83rem; color: #64748b; margin-bottom: 24px; }
        label {
            display: block; font-size: 0.82rem; font-weight: 600;
            color: #374151; margin-bottom: 6px;
        }
        input[type=email], input[type=password] {
            width: 100%;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            padding: 11px 14px;
            font-family: inherit; font-size: 0.9rem;
            color: #0f172a;
            outline: none;
            transition: border-color .2s;
            margin-bottom: 16px;
        }
        input:focus { border-color: #0d9488; box-shadow: 0 0 0 3px rgba(79,70,229,.12); }
        .form-group { position: relative; }
        .toggle-pw {
            position: absolute; right: 12px; top: 11px;
            background: none; border: none; color: #94a3b8;
            cursor: pointer; font-size: 0.9rem;
        }
        .btn-login {
            width: 100%; padding: 12px;
            background: #0d9488; color: #fff;
            border: none; border-radius: 10px;
            font-family: inherit; font-size: 0.95rem; font-weight: 600;
            cursor: pointer;
            transition: background .2s, transform .1s;
            margin-top: 6px;
        }
        .btn-login:hover { background: #0f766e; }
        .btn-login:active { transform: scale(.98); }
        .error-msg {
            background: #fee2e2; color: #991b1b;
            border: 1px solid #fca5a5;
            border-radius: 8px; padding: 10px 14px;
            font-size: 0.82rem; margin-bottom: 16px;
        }
        .remember { display: flex; align-items: center; gap: 8px; margin-bottom: 18px; }
        .remember input { width: auto; margin: 0; }
        .remember label { margin: 0; font-weight: 400; }

        /* ── Dev credentials panel ─────────────────────────────── */
        .dev-creds {
            margin-top: 20px;
            border: 1.5px dashed #f59e0b;
            border-radius: 12px;
            overflow: hidden;
            background: #fffbeb;
        }
        .dev-creds-header {
            background: #f59e0b;
            color: #fff;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: .04em;
            padding: 6px 14px;
            display: flex; align-items: center; gap: 6px;
        }
        .dev-creds-body { padding: 10px 12px; display: flex; flex-direction: column; gap: 7px; }
        .cred-item {
            display: flex; align-items: center; justify-content: space-between;
            background: #fff;
            border: 1px solid #fde68a;
            border-radius: 8px;
            padding: 8px 12px;
            cursor: pointer;
            transition: background .15s, border-color .15s;
            gap: 8px;
        }
        .cred-item:hover { background: #fef3c7; border-color: #f59e0b; }
        .cred-role {
            font-size: 0.72rem; font-weight: 700;
            color: #92400e;
            background: #fde68a;
            border-radius: 5px;
            padding: 2px 7px;
            white-space: nowrap;
        }
        .cred-info { flex: 1; }
        .cred-info .cred-email { font-size: 0.8rem; font-weight: 600; color: #1e293b; }
        .cred-info .cred-pass  { font-size: 0.75rem; color: #64748b; }
        .cred-fill-hint { font-size: 0.7rem; color: #94a3b8; white-space: nowrap; }
        .cred-item:hover .cred-fill-hint { color: #f59e0b; }
    </style>
</head>
<body>
<div class="login-card">
    <div class="login-brand">
        <div class="login-brand-icon"><i class="fas fa-boxes-stacked"></i></div>
        <span>Inventory</span>
    </div>

    <h2>স্বাগতম!</h2>
    <p class="sub">আপনার অ্যাকাউন্টে লগইন করুন</p>

    @if($errors->any())
        <div class="error-msg">
            <i class="fas fa-circle-exclamation"></i>
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <label for="email">ইমেইল ঠিকানা</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}"
               placeholder="example@email.com" required autofocus>

        <label for="password">পাসওয়ার্ড</label>
        <div class="form-group">
            <input type="password" id="password" name="password"
                   placeholder="••••••••" required>
            <button type="button" class="toggle-pw" onclick="togglePw()">
                <i class="fas fa-eye" id="eyeIcon"></i>
            </button>
        </div>

        <label class="remember">
            <input type="checkbox" name="remember"> মনে রাখুন
        </label>

        <button type="submit" class="btn-login">
            <i class="fas fa-right-to-bracket"></i> লগইন
        </button>
    </form>

    {{-- ⚠️ TEMPORARY — REMOVE BEFORE PRODUCTION --}}
    <div class="dev-creds">
        <div class="dev-creds-header">
            <i class="fas fa-triangle-exclamation"></i>
            DEV ONLY — REMOVE BEFORE PRODUCTION
        </div>
        <div class="dev-creds-body">
            <div class="cred-item" onclick="fillCreds('admin@inventory.com','admin123')">
                <span class="cred-role">Admin</span>
                <div class="cred-info">
                    <div class="cred-email">admin@inventory.com</div>
                    <div class="cred-pass">Password: admin123</div>
                </div>
                <span class="cred-fill-hint"><i class="fas fa-arrow-up-right-from-square"></i> ক্লিক করুন</span>
            </div>
            <div class="cred-item" onclick="fillCreds('hasan@inventory.com','hasan123')">
                <span class="cred-role">Staff</span>
                <div class="cred-info">
                    <div class="cred-email">hasan@inventory.com</div>
                    <div class="cred-pass">Password: hasan123</div>
                </div>
                <span class="cred-fill-hint"><i class="fas fa-arrow-up-right-from-square"></i> ক্লিক করুন</span>
            </div>
        </div>
    </div>
    {{-- /TEMPORARY --}}

</div>
<script>
function togglePw() {
    const pw = document.getElementById('password');
    const ic = document.getElementById('eyeIcon');
    if (pw.type === 'password') { pw.type = 'text'; ic.className = 'fas fa-eye-slash'; }
    else { pw.type = 'password'; ic.className = 'fas fa-eye'; }
}
// ── Dev credential auto-fill ──────────────────────────────
function fillCreds(email, password) {
    document.getElementById('email').value    = email;
    document.getElementById('password').value = password;
    // ensure password is hidden after fill
    document.getElementById('password').type  = 'password';
    document.getElementById('eyeIcon').className = 'fas fa-eye';
}
</script>
</body>
</html>
