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
            padding: 8px 8px;
            display: flex; flex-direction: column; gap: 8px;
            max-height: calc(100vh - 100px);
            overflow-y: auto;
        }

        /* Client card box */
        .client-card {
            border: 1.5px solid #e2c97e;
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,.06);
        }
        .client-card-header {
            background: linear-gradient(90deg,#1a1200,#2a1f00);
            padding: 7px 12px;
            display: flex; align-items: center; gap: 7px;
            border-bottom: 1px solid #3d2e00;
        }
        .client-card-header .client-num {
            font-size: .6rem; font-weight: 800; letter-spacing: .05em;
            color: #f59e0b;
        }
        .client-card-header .client-name {
            font-size: .72rem; font-weight: 700; color: #fde68a;
            flex: 1;
        }
        .client-card-header .client-branch-count {
            font-size: .58rem; color: #78716c;
        }
        .client-card-body {
            display: flex; flex-direction: column; gap: 0;
        }
        /* Shop sub-header inside card */
        .shop-header {
            display: flex; align-items: center; gap: 5px;
            padding: 5px 12px;
            border-top: 1px solid #f0f0f0;
        }
        .shop-header.locked { background: #fff5f5; }
        .shop-header.active { background: #f0fdf4; }
        .shop-header .shop-name {
            font-size: .67rem; font-weight: 700; flex: 1;
        }
        /* Indent cred-item inside client card */
        .client-card .cred-item {
            border-radius: 0;
            border: none;
            border-top: 1px solid #f5f5f4;
            padding: 7px 12px 7px 16px;
        }
        .client-card .cred-item:hover { background: #fef9ee; }
        .client-card .cred-item.owner-row {
            background: #fffbeb;
            border-top: none;
            padding-left: 12px;
        }
        .client-card .cred-item.owner-row:hover { background: #fef3c7; }

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

        /* Live stats bar */
        .dev-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0;
            background: #1e1b0e;
            border-bottom: 2px solid #f59e0b;
        }
        .dev-stat {
            padding: 8px 4px;
            text-align: center;
            border-right: 1px solid #2d2a1a;
            cursor: default;
        }
        .dev-stat:last-child { border-right: none; }
        .dev-stat-num {
            font-size: 1.2rem; font-weight: 800;
            line-height: 1;
        }
        .dev-stat-label {
            font-size: 0.58rem; font-weight: 600; letter-spacing: .04em;
            margin-top: 2px; opacity: .75;
        }
        .stat-shop   { color: #34d399; }
        .stat-super  { color: #22d3ee; }
        .stat-admin  { color: #86efac; }
        .stat-staff  { color: #cbd5e1; }

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

        /* ── What's New button + modal ──────────────────────── */
        .whatsnew-btn {
            position: fixed;
            top: 18px;
            right: 18px;
            display: flex;
            align-items: center;
            gap: 7px;
            background: rgba(255,255,255,.12);
            border: 1px solid rgba(255,255,255,.3);
            color: #fff;
            border-radius: 999px;
            padding: 8px 16px;
            font-family: inherit;
            font-size: 0.78rem;
            font-weight: 600;
            cursor: pointer;
            backdrop-filter: blur(4px);
            transition: background .15s;
            z-index: 50;
        }
        .whatsnew-btn:hover { background: rgba(255,255,255,.22); }
        .whatsnew-btn .whatsnew-dot {
            width: 8px; height: 8px;
            border-radius: 999px;
            background: #ef4444;
            border: 2px solid #134e4a;
            margin-left: 2px;
        }
        .whatsnew-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(15,23,42,.55);
            align-items: center; justify-content: center;
            z-index: 100;
            padding: 16px;
        }
        .whatsnew-overlay.open { display: flex; }
        .whatsnew-modal {
            background: #fff;
            border-radius: 16px;
            width: 100%; max-width: 420px;
            max-height: 80vh;
            display: flex; flex-direction: column;
            overflow: hidden;
            box-shadow: 0 25px 60px rgba(0,0,0,.35);
        }
        .whatsnew-modal-header {
            display: flex; align-items: center; gap: 8px;
            padding: 16px 20px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.95rem; font-weight: 700; color: #0f172a;
        }
        .whatsnew-modal-header i { color: #0d9488; }
        .whatsnew-modal-close {
            margin-left: auto;
            background: none; border: none;
            color: #94a3b8; font-size: 1rem;
            cursor: pointer; padding: 4px;
        }
        .whatsnew-modal-body {
            overflow-y: auto;
            padding: 4px 0;
        }
        .whatsnew-entry {
            padding: 12px 20px;
            border-bottom: 1px solid #f1f5f9;
        }
        .whatsnew-entry:last-child { border-bottom: none; }
        .whatsnew-entry-date {
            font-size: .72rem;
            font-weight: 700;
            color: #0d9488;
            margin-bottom: 6px;
        }
        .whatsnew-entry ul { margin: 0; padding-left: 18px; }
        .whatsnew-entry li {
            font-size: .82rem;
            color: #334155;
            line-height: 1.55;
            margin-bottom: 4px;
        }
        .whatsnew-entry li:last-child { margin-bottom: 0; }
    </style>
</head>
@php
    $whatsNew = config('whats_new', []);
@endphp
<body>

<button class="whatsnew-btn" id="whatsNewBtn" data-latest-version="{{ $whatsNew[0]['version'] ?? '' }}" onclick="openWhatsNew()">
    <i class="fas fa-gift"></i> নতুন কী আছে
    <span class="whatsnew-dot" id="whatsNewDot" style="display:none"></span>
</button>

<div class="whatsnew-overlay" id="whatsNewOverlay" onclick="if(event.target===this) closeWhatsNew()">
    <div class="whatsnew-modal">
        <div class="whatsnew-modal-header">
            <i class="fas fa-gift"></i> নতুন কী আছে
            <button class="whatsnew-modal-close" onclick="closeWhatsNew()"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="whatsnew-modal-body">
            @forelse($whatsNew as $entry)
            <div class="whatsnew-entry">
                <div class="whatsnew-entry-date">v{{ $entry['version'] }} &nbsp;·&nbsp; {{ $entry['date'] }}</div>
                <ul>
                    @foreach($entry['items'] as $item)
                    <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
            @empty
            <div class="whatsnew-entry">কোনো আপডেট নেই</div>
            @endforelse
        </div>
    </div>
</div>

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

        {{-- Live DB stats --}}
        @php
            // Load all clients (super_admins) with their shops and each shop's users
            $clients = \App\Models\User::where('role','super_admin')
                ->with([
                    'myShops' => fn($q) => $q->select('id','name','is_locked','super_admin_id')->orderBy('id'),
                    'myShops.users' => fn($q) => $q->whereIn('role',['admin','staff'])
                        ->select('id','name','email','role','shop_id')
                        ->orderByRaw("FIELD(role,'admin','staff')")->orderBy('name'),
                ])
                ->select('id','name','email')
                ->orderBy('id')
                ->get();

            // Stats from loaded data only (matches what's visible in cards)
            $statShops  = $clients->sum(fn($c) => $c->myShops->count());
            $statSuper  = $clients->count();
            $allShopUsers = $clients->flatMap(fn($c) => $c->myShops->flatMap(fn($s) => $s->users));
            $statAdmin  = $allShopUsers->where('role','admin')->count();
            $statStaff  = $allShopUsers->where('role','staff')->count();
        @endphp
        <div class="dev-stats">
            <div class="dev-stat">
                <div class="dev-stat-num stat-shop">{{ $statShops }}</div>
                <div class="dev-stat-label stat-shop">🏪 শাখা</div>
            </div>
            <div class="dev-stat">
                <div class="dev-stat-num stat-super">{{ $statSuper }}</div>
                <div class="dev-stat-label stat-super">🏢 সুপার</div>
            </div>
            <div class="dev-stat">
                <div class="dev-stat-num stat-admin">{{ $statAdmin }}</div>
                <div class="dev-stat-label stat-admin">👔 অ্যাডমিন</div>
            </div>
            <div class="dev-stat">
                <div class="dev-stat-num stat-staff">{{ $statStaff }}</div>
                <div class="dev-stat-label stat-staff">👤 স্টাফ</div>
            </div>
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
                <div class="cred-item" onclick="fillCreds('resell@a.com','password')">
                    <span class="cred-role role-reseller">Reseller</span>
                    <div class="cred-info">
                        <div class="cred-email">resell@a.com</div>
                        <div class="cred-meta">pass: <b>password</b> &nbsp;·&nbsp; নুমান</div>
                    </div>
                    <i class="fas fa-chevron-right cred-arrow"></i>
                </div>
            </div>

            {{-- CLIENT GROUPS — one card per super_admin --}}
            @foreach($clients as $ci => $client)
            <div class="client-card">

                {{-- Card header --}}
                <div class="client-card-header">
                    <i class="fas fa-building" style="color:#f59e0b;font-size:.7rem"></i>
                    <span class="client-num">#{{ $ci+1 }}</span>
                    <span class="client-name">{{ $client->name }}</span>
                    <span class="client-branch-count">{{ $client->myShops->count() }} শাখা</span>
                </div>

                <div class="client-card-body">

                    {{-- Client (মালিক) login row --}}
                    <div class="cred-item owner-row" onclick="fillCreds('{{ $client->email }}','password')">
                        <span class="cred-role role-super" style="font-size:.58rem;min-width:54px;">মালিক</span>
                        <div class="cred-info">
                            <div class="cred-email">{{ $client->email }}</div>
                            <div class="cred-meta">pass: <b>password</b></div>
                        </div>
                        <i class="fas fa-chevron-right cred-arrow"></i>
                    </div>

                    {{-- Each shop under this client --}}
                    @foreach($client->myShops as $shop)

                    {{-- Shop sub-header --}}
                    <div class="shop-header {{ $shop->is_locked ? 'locked' : 'active' }}">
                        <i class="fas fa-store" style="color:{{ $shop->is_locked ? '#ef4444' : '#16a34a' }};font-size:.62rem"></i>
                        <span class="shop-name" style="color:{{ $shop->is_locked ? '#dc2626' : '#15803d' }}">
                            {{ $shop->name }}
                        </span>
                        @if($shop->is_locked)
                        <span style="font-size:.55rem;background:#fee2e2;color:#dc2626;border-radius:3px;padding:1px 6px;font-weight:700;">
                            <i class="fas fa-lock"></i> LOCKED
                        </span>
                        @endif
                    </div>

                    {{-- Admin & staff rows --}}
                    @forelse($shop->users as $u)
                    <div class="cred-item" onclick="fillCreds('{{ $u->email }}','password')">
                        <span class="cred-role {{ $u->role==='admin' ? 'role-admin' : 'role-staff' }}"
                              style="font-size:.58rem;min-width:48px;">
                            {{ $u->role==='admin' ? 'Admin' : 'Staff' }}
                        </span>
                        <div class="cred-info">
                            <div class="cred-email">{{ $u->email }}</div>
                            <div class="cred-meta">pass: <b>password</b> &nbsp;·&nbsp; {{ $u->name }}</div>
                        </div>
                        <i class="fas fa-chevron-right cred-arrow"></i>
                    </div>
                    @empty
                    <div style="padding:6px 16px;font-size:.62rem;color:#a8a29e;font-style:italic;border-top:1px solid #f5f5f4;">
                        কোনো staff/admin নেই
                    </div>
                    @endforelse

                    @endforeach
                    {{-- /shops --}}

                </div>
            </div>
            {{-- /client card --}}
            @endforeach

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

        @if(session('error'))
            <div class="error-msg">
                <i class="fas fa-lock"></i>
                {{ session('error') }}
            </div>
        @endif

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

/* ── What's New modal ── */
function openWhatsNew() {
    document.getElementById('whatsNewOverlay').classList.add('open');
    var btn = document.getElementById('whatsNewBtn');
    localStorage.setItem('whatsNewSeen', btn.getAttribute('data-latest-version'));
    var dot = document.getElementById('whatsNewDot');
    if (dot) dot.style.display = 'none';
}
function closeWhatsNew() {
    document.getElementById('whatsNewOverlay').classList.remove('open');
}
(function() {
    var btn = document.getElementById('whatsNewBtn');
    var dot = document.getElementById('whatsNewDot');
    if (!btn || !dot) return;
    var latest = btn.getAttribute('data-latest-version');
    var seen   = localStorage.getItem('whatsNewSeen');
    dot.style.display = (latest && latest !== seen) ? '' : 'none';
})();
</script>
</body>
</html>
