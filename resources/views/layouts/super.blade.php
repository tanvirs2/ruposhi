<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'সুপার অ্যাডমিন') — Control Panel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <style>
        .sa-wrap { display:flex; min-height:100vh; background:#0f172a; }
        .sa-side {
            width:250px; background:#1e293b; color:#e2e8f0;
            display:flex; flex-direction:column; flex-shrink:0;
            border-right:1px solid #334155;
        }
        .sa-brand {
            padding:20px 18px; display:flex; align-items:center; gap:12px;
            border-bottom:1px solid #334155;
        }
        .sa-brand-icon {
            width:40px; height:40px; border-radius:10px;
            background:linear-gradient(135deg,#f59e0b,#ef4444);
            display:flex; align-items:center; justify-content:center;
            color:#fff; font-size:1.1rem;
        }
        .sa-brand-name { font-weight:700; font-size:1.02rem; }
        .sa-brand-sub  { font-size:.7rem; color:#94a3b8; }
        .sa-nav { padding:14px 10px; flex:1; }
        .sa-nav a {
            display:flex; align-items:center; gap:12px;
            padding:11px 14px; border-radius:9px; margin-bottom:4px;
            color:#cbd5e1; text-decoration:none; font-size:.9rem;
            transition:.15s;
        }
        .sa-nav a:hover  { background:#334155; color:#fff; }
        .sa-nav a.active { background:linear-gradient(135deg,#f59e0b,#ef4444); color:#fff; }
        .sa-nav a i { width:18px; text-align:center; }
        .sa-foot { padding:14px; border-top:1px solid #334155; }
        .sa-foot form { margin:0; }
        .sa-logout {
            width:100%; padding:10px; border:none; border-radius:8px;
            background:#334155; color:#fca5a5; cursor:pointer;
            font-size:.85rem; display:flex; align-items:center; justify-content:center; gap:8px;
            font-family:inherit;
        }
        .sa-logout:hover { background:#7f1d1d; color:#fff; }
        .sa-main { flex:1; display:flex; flex-direction:column; overflow:hidden; }
        .sa-top {
            background:#1e293b; padding:16px 26px; border-bottom:1px solid #334155;
            display:flex; align-items:center; justify-content:space-between;
        }
        .sa-top h1 { font-size:1.15rem; font-weight:700; color:#f1f5f9; margin:0; }
        .sa-badge {
            background:#f59e0b22; color:#fbbf24; padding:5px 12px;
            border-radius:20px; font-size:.78rem; font-weight:600;
            display:flex; align-items:center; gap:6px;
        }
        .sa-body { padding:26px; overflow-y:auto; flex:1; color:#e2e8f0; }
        .sa-card {
            background:#1e293b; border:1px solid #334155; border-radius:14px;
            padding:22px; margin-bottom:20px;
        }
        .sa-stat-grid {
            display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
            gap:16px; margin-bottom:22px;
        }
        .sa-stat {
            background:#1e293b; border:1px solid #334155; border-radius:14px;
            padding:20px; display:flex; align-items:center; gap:16px;
        }
        .sa-stat-icon {
            width:50px; height:50px; border-radius:12px; flex-shrink:0;
            display:flex; align-items:center; justify-content:center;
            font-size:1.3rem; color:#fff;
        }
        .sa-stat-val { font-size:1.7rem; font-weight:700; color:#f1f5f9; line-height:1; }
        .sa-stat-lbl { font-size:.82rem; color:#94a3b8; margin-top:4px; }
        .sa-table { width:100%; border-collapse:collapse; }
        .sa-table th, .sa-table td {
            padding:12px 14px; text-align:right; font-size:.88rem;
            border-bottom:1px solid #334155;
        }
        .sa-table th { color:#94a3b8; font-weight:600; background:#0f172a55; }
        .sa-table th:first-child, .sa-table td:first-child { text-align:left; }
        .sa-table tbody tr:hover { background:#0f172a55; }
        .sa-btn {
            display:inline-flex; align-items:center; gap:7px;
            padding:9px 16px; border-radius:9px; border:none; cursor:pointer;
            font-size:.86rem; font-weight:600; text-decoration:none;
            font-family:inherit; transition:.15s;
        }
        .sa-btn-primary { background:linear-gradient(135deg,#f59e0b,#ef4444); color:#fff; }
        .sa-btn-primary:hover { opacity:.9; }
        .sa-btn-ghost { background:#334155; color:#e2e8f0; }
        .sa-btn-ghost:hover { background:#475569; }
        .sa-btn-sm { padding:6px 11px; font-size:.78rem; }
        .sa-btn-danger { background:#7f1d1d; color:#fecaca; }
        .sa-btn-danger:hover { background:#991b1b; color:#fff; }
        .sa-input, .sa-textarea {
            width:100%; padding:11px 14px; border-radius:9px;
            border:1px solid #334155; background:#0f172a; color:#e2e8f0;
            font-size:.9rem; font-family:inherit;
        }
        .sa-input:focus, .sa-textarea:focus { outline:none; border-color:#f59e0b; }
        .sa-label { display:block; font-size:.84rem; color:#cbd5e1; margin-bottom:6px; font-weight:500; }
        .sa-field { margin-bottom:16px; }
        .sa-pill { padding:3px 10px; border-radius:20px; font-size:.74rem; font-weight:600; }
        .sa-pill-on  { background:#14532d; color:#86efac; }
        .sa-pill-off { background:#7f1d1d; color:#fca5a5; }
        .sa-alert {
            padding:13px 18px; border-radius:10px; margin-bottom:20px;
            font-size:.88rem; background:#14532d; color:#bbf7d0; border:1px solid #166534;
        }
        .sa-error { background:#7f1d1d; color:#fecaca; border-color:#991b1b; }
        .sa-empty { text-align:center; padding:50px 20px; color:#64748b; }
        .sa-empty i { font-size:2.5rem; margin-bottom:12px; display:block; }
    </style>
    @stack('styles')
</head>
<body>
<div class="sa-wrap">

    {{-- Sidebar --}}
    <aside class="sa-side">
        <div class="sa-brand">
            <div class="sa-brand-icon"><i class="fas fa-shield-halved"></i></div>
            <div>
                <div class="sa-brand-name">Control Panel</div>
                <div class="sa-brand-sub">সুপার অ্যাডমিন</div>
            </div>
        </div>
        <nav class="sa-nav">
            @php $r = request()->route()->getName(); @endphp
            <a href="{{ route('super.dashboard') }}" class="{{ $r==='super.dashboard' ? 'active' : '' }}">
                <i class="fas fa-gauge-high"></i> ড্যাশবোর্ড
            </a>
            <a href="{{ route('super.shops.index') }}" class="{{ str_starts_with($r,'super.shops') ? 'active' : '' }}">
                <i class="fas fa-store"></i> সকল শপ
            </a>
            <a href="{{ route('super.shops.create') }}" class="{{ $r==='super.shops.create' ? 'active' : '' }}">
                <i class="fas fa-plus-circle"></i> নতুন শপ
            </a>
        </nav>
        <div class="sa-foot">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="sa-logout"><i class="fas fa-right-from-bracket"></i> লগআউট</button>
            </form>
        </div>
    </aside>

    {{-- Main --}}
    <div class="sa-main">
        <div class="sa-top">
            <h1>@yield('page-title', 'সুপার অ্যাডমিন')</h1>
            <div class="sa-badge">
                <i class="fas fa-user-shield"></i> {{ auth()->user()->name }}
            </div>
        </div>
        <div class="sa-body">
            @if(session('success'))
                <div class="sa-alert"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="sa-alert sa-error">
                    <i class="fas fa-exclamation-circle"></i>
                    {{ $errors->first() }}
                </div>
            @endif
            @yield('content')
        </div>
    </div>

</div>
@stack('scripts')
</body>
</html>
