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
            padding: 24px 16px;
        }

        /* ── Page wrapper: login left + dev panel right ── */
        .page-wrapper {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            width: 100%;
            max-width: 860px;
        }

        /* ── Login card ─────────────────────────────────── */
        .login-card {
            background: #fff;
            border-radius: 20px;
            padding: 40px 36px;
            width: 100%;
            max-width: 400px;
            flex-shrink: 0;
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
            font-size: 1.4rem; font-weight: 700; color: #0f172a;
        }
        h2 { font-size: 1.1rem; font-weight: 600; color: #1e293b; margin-bottom: 6px; }
        p.sub { font-size: 0.83rem; color: #64748b; margin-bottom: 24px; }
        label {
            display: block; font-size: 0.82rem; font-weight: 600;
            color: #374151; margin-bottom: 6px;
        }
        input[type=email], input[type=password] {
            width: 100%;
            border: 1.5px solid #e2e8f0; border-radius: 10px;
            padding: 11px 14px;
            font-family: inherit; font-size: 0.9rem; color: #0f172a;
            outline: none; transition: border-color .2s; margin-bottom: 16px;
        }
        input:focus { border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13,148,136,.12); }
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
            cursor: pointer; transition: background .2s, transform .1s; margin-top: 6px;
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

        /* ════════════════════════════════════════════════════
           ⚠️ DEV PANEL — DELETE THIS ENTIRE BLOCK FOR PRODUCTION
           ════════════════════════════════════════════════════ */
        .dev-panel {
            flex: 1;
            min-width: 280px;
            max-width: 420px;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0,0,0,.25);
            border: 2px dashed #f59e0b;
        }
        .dev-panel-header {
            background: #f59e0b;
            color: #fff;
            font-size: 0.73rem; font-weight: 800; letter-spacing: .06em;
            padding: 9px 16px;
            display: flex; align-items: center; gap: 8px;
        }
        .dev-panel-header .dev-tag {
            margin-left: auto;
            background: rgba(0,0,0,.15);
            border-radius: 4px;
            padding: 1px 8px;
            font-size: 0.65rem; letter-spacing: .04em;
        }
        .dev-panel-body {
            background: #fffbeb;
            padding: 10px 10px;
            display: flex; flex-direction: column; gap: 4px;
            max-height: calc(100vh - 100px);
            overflow-y: auto;
        }

        /* Section headings */
        .cred-section {
            font-size: 0.64rem; font-weight: 800; letter-spacing: .08em;
            color: #a78950; text-transform: uppercase;
            padding: 6px 4px 2px;
            border-top: 1px solid #fde68a;
            margin-top: 2px;
        }
        .cred-section:first-child { border-top: none; margin-top: 0; padding-top: 2px; }

        /* Credential rows */
        .cred-item {
            display: flex; align-items: center; gap: 8px;
            background: #fff;
            border: 1px solid #e7e5e4;
            border-radius: 8px;
            padding: 7px 10px;
            cursor: pointer;
            transition: background .15s, border-color .15s, transform .1s;
        }
        .cred-item:hover {
            background: #fef3c7;
            border-color: #fbbf24;
            transform: translateX(2px);
        }
        .cred-role {
            font-size: 0.63rem; font-weight: 700;
            border-radius: 5px; padding: 2px 8px;
            white-space: nowrap; min-width: 66px; text-align: center;
        }
        .role-root     { background: #ede9fe; color: #5b21b6; }
        .role-reseller { background: #dbeafe; color: #1d4ed8; }
        .role-super    { background: #ccfbf1; color: #0f766e; }
        .role-admin    { background: #dcfce7; color: #15803d; }
        .role-staff    { background: #f1f5f9; color: #475569; }

        .cred-info { flex: 1; min-width: 0; }
        .cred-email {
            font-size: 0.77rem; font-weight: 600; color: #1e293b;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .cred-meta { font-size: 0.69rem; color: #a8a29e; margin-top: 1px; }
        .cred-meta b { color: #57534e; font-weight: 600; }

        .cred-arrow { color: #d6d3d1; font-size: 0.68rem; }
        .cred-item:hover .cred-arrow { color: #f59e0b; }

        .dev-panel-footer {
            background: #fef3c7;
            border-top: 1px solid #fde68a;
            padding: 7px 12px;
            font-size: 0.68rem; color: #92400e;
            display: flex; align-items: center; gap: 6px;
        }

        /* System toggle button — vertical pill on the right of login form */
        .system-toggle {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: rgba(245,158,11,.15);
            border: 2px dashed #f59e0b;
            border-radius: 10px;
            padding: 14px 8px;
            cursor: pointer;
            color: #92400e;
            transition: background .15s, border-color .15s;
            writing-mode: vertical-rl;
            text-orientation: mixed;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: .06em;
            align-self: center;
            white-space: nowrap;
        }
        .system-toggle:hover { background: rgba(245,158,11,.28); border-color: #d97706; }
        .system-toggle i { writing-mode: horizontal-tb; font-size: 0.85rem; }
        .system-toggle.open { background: rgba(245,158,11,.3); border-style: solid; }

        /* Hidden system section */
        .system-section {
            display: none;
            flex-direction: column;
            gap: 4px;
        }
        .system-section.open { display: flex; }
        /* ════ END DEV PANEL ════ */

        /* Responsive: stack on small screens */
        @media (max-width: 720px) {
            .page-wrapper { flex-direction: column; align-items: center; }
            .dev-panel { max-width: 400px; width: 100%; }
        }
    </style>
</head>
<body>

<div class="page-wrapper">

    {{-- ════════════════════════════════════════════════════════
         ⚠️  DEV ONLY — DELETE THIS ENTIRE <div class="dev-panel"> BLOCK BEFORE PRODUCTION
         ════════════════════════════════════════════════════════ --}}
    <div class="dev-panel">
        <div class="dev-panel-header">
            <i class="fas fa-flask"></i>
            DEMO ACCOUNTS
            <span class="dev-tag">DEV ONLY</span>
        </div>
        <div class="dev-panel-body">

            {{-- Root + Reseller — hidden by default --}}
            <div class="system-section" id="systemSection">

                <div class="cred-section">⚙ সিস্টেম রুট</div>
                <div class="cred-item" onclick="fillCreds('root@system.com','password')">
                    <span class="cred-role role-root">Root</span>
                    <div class="cred-info">
                        <div class="cred-email">root@system.com</div>
                        <div class="cred-meta">pass: <b>password</b> &nbsp;·&nbsp; সব কিছু নিয়ন্ত্রণ</div>
                    </div>
                    <i class="fas fa-chevron-right cred-arrow"></i>
                </div>

                <div class="cred-section">🤝 রিসেলার</div>
                <div class="cred-item" onclick="fillCreds('resell@a.com','123456')">
                    <span class="cred-role role-reseller">Reseller</span>
                    <div class="cred-info">
                        <div class="cred-email">resell@a.com</div>
                        <div class="cred-meta">pass: <b>123456</b> &nbsp;·&nbsp; নুমান</div>
                    </div>
                    <i class="fas fa-chevron-right cred-arrow"></i>
                </div>

            </div>

            {{-- Super Admin --}}
            <div class="cred-section">🏢 সুপার অ্যাডমিন (বিজনেস মালিক)</div>
            <div class="cred-item" onclick="fillCreds('super@admin.com','password')">
                <span class="cred-role role-super">Super Admin</span>
                <div class="cred-info">
                    <div class="cred-email">super@admin.com</div>
                    <div class="cred-meta">pass: <b>password</b> &nbsp;·&nbsp; প্রধান + মিরপুর শাখা</div>
                </div>
                <i class="fas fa-chevron-right cred-arrow"></i>
            </div>

            {{-- Shop Admins --}}
            <div class="cred-section">🏪 শাখা অ্যাডমিন</div>
            <div class="cred-item" onclick="fillCreds('admin@inventory.com','admin123')">
                <span class="cred-role role-admin">Admin</span>
                <div class="cred-info">
                    <div class="cred-email">admin@inventory.com</div>
                    <div class="cred-meta">pass: <b>admin123</b> &nbsp;·&nbsp; প্রধান শাখা</div>
                </div>
                <i class="fas fa-chevron-right cred-arrow"></i>
            </div>
            <div class="cred-item" onclick="fillCreds('mirpur@shop.com','secret123')">
                <span class="cred-role role-admin">Admin</span>
                <div class="cred-info">
                    <div class="cred-email">mirpur@shop.com</div>
                    <div class="cred-meta">pass: <b>secret123</b> &nbsp;·&nbsp; মিরপুর শাখা</div>
                </div>
                <i class="fas fa-chevron-right cred-arrow"></i>
            </div>
            <div class="cred-item" onclick="fillCreds('uttara.admin@pos.test','password')">
                <span class="cred-role role-admin">Admin</span>
                <div class="cred-info">
                    <div class="cred-email">uttara.admin@pos.test</div>
                    <div class="cred-meta">pass: <b>password</b> &nbsp;·&nbsp; উত্তরা শাখা</div>
                </div>
                <i class="fas fa-chevron-right cred-arrow"></i>
            </div>
            <div class="cred-item" onclick="fillCreds('dhanmondi.admin@pos.test','password')">
                <span class="cred-role role-admin">Admin</span>
                <div class="cred-info">
                    <div class="cred-email">dhanmondi.admin@pos.test</div>
                    <div class="cred-meta">pass: <b>password</b> &nbsp;·&nbsp; ধানমন্ডি শাখা</div>
                </div>
                <i class="fas fa-chevron-right cred-arrow"></i>
            </div>

            {{-- Staff --}}
            <div class="cred-section">👤 স্টাফ</div>
            <div class="cred-item" onclick="fillCreds('hasan@inventory.com','hasan123')">
                <span class="cred-role role-staff">Staff</span>
                <div class="cred-info">
                    <div class="cred-email">hasan@inventory.com</div>
                    <div class="cred-meta">pass: <b>hasan123</b> &nbsp;·&nbsp; প্রধান শাখা</div>
                </div>
                <i class="fas fa-chevron-right cred-arrow"></i>
            </div>
            <div class="cred-item" onclick="fillCreds('uttara.staff1@pos.test','password')">
                <span class="cred-role role-staff">Staff</span>
                <div class="cred-info">
                    <div class="cred-email">uttara.staff1@pos.test</div>
                    <div class="cred-meta">pass: <b>password</b> &nbsp;·&nbsp; উত্তরা শাখা</div>
                </div>
                <i class="fas fa-chevron-right cred-arrow"></i>
            </div>

        </div>
        <div class="dev-panel-footer">
            <i class="fas fa-circle-info"></i>
            ক্লিক করলে email + password auto-fill হবে
        </div>
    </div>
    {{-- ════ END DEV PANEL — DELETE ABOVE DIV BEFORE PRODUCTION ════ --}}

    {{-- ── Login Card ── --}}
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
    </div>
    {{-- ── /Login Card ── --}}

    {{-- ⚠️ DEV ONLY — delete with dev-panel --}}
    <button class="system-toggle" id="systemToggle" onclick="toggleSystem()">
        <i class="fas fa-shield-halved"></i>
        ROOT &amp; RESELLER
        <i class="fas fa-lock" id="toggleLockIcon"></i>
    </button>
    {{-- /DEV ONLY --}}

</div>

<script>
function togglePw() {
    const pw = document.getElementById('password');
    const ic = document.getElementById('eyeIcon');
    if (pw.type === 'password') { pw.type = 'text'; ic.className = 'fas fa-eye-slash'; }
    else { pw.type = 'password'; ic.className = 'fas fa-eye'; }
}
function toggleSystem() {
    const btn  = document.getElementById('systemToggle');
    const sec  = document.getElementById('systemSection');
    const icon = document.getElementById('toggleLockIcon');
    const isOpen = btn.classList.toggle('open');
    sec.classList.toggle('open', isOpen);
    icon.className = isOpen ? 'fas fa-lock-open' : 'fas fa-lock';
}
function fillCreds(email, password) {
    document.getElementById('email').value    = email;
    document.getElementById('password').value = password;
    document.getElementById('password').type  = 'password';
    document.getElementById('eyeIcon').className = 'fas fa-eye';
}
</script>
</body>
</html>
