<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'রিসেলার প্যানেল')</title>
    @include('partials.base-fonts')
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <style>
        /* Reuse root layout styles with green accent */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Hind Siliguri', sans-serif; background: #060d1a; color: #e2e8f0; }
        .rt-wrap { display: flex; min-height: 100vh; }
        .rt-side {
            width: 240px; background: #0d1b2e; border-right: 1px solid #1e3a5f;
            display: flex; flex-direction: column; flex-shrink: 0;
            position: fixed; top: 0; left: 0; bottom: 0; overflow-y: auto;
        }
        .rt-brand { padding: 20px 18px; border-bottom: 1px solid #1e3a5f; display: flex; align-items: center; gap: 12px; }
        .rt-brand-icon {
            width: 42px; height: 42px; border-radius: 10px;
            background: linear-gradient(135deg, #059669, #0d9488);
            display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.15rem;
        }
        .rt-brand-name { font-weight: 700; font-size: 1rem; color: #f1f5f9; }
        .rt-brand-sub  { font-size: .7rem; color: #64748b; margin-top: 2px; }
        .rt-nav { padding: 14px 10px; flex: 1; }
        .rt-nav a {
            display: flex; align-items: center; gap: 11px; padding: 10px 13px;
            border-radius: 9px; margin-bottom: 3px; color: #94a3b8; text-decoration: none;
            font-size: .88rem; transition: .15s;
        }
        .rt-nav a:hover  { background: #1e3a5f; color: #e2e8f0; }
        .rt-nav a.active { background: linear-gradient(135deg, #059669, #0d9488); color: #fff; }
        .rt-nav a i { width: 18px; text-align: center; }
        .rt-foot { padding: 14px; border-top: 1px solid #1e3a5f; }
        .rt-logout {
            width: 100%; padding: 10px; border: none; border-radius: 8px;
            background: #1e3a5f; color: #6ee7b7; cursor: pointer;
            font-size: .85rem; display: flex; align-items: center; justify-content: center; gap: 8px;
            font-family: inherit; transition: .15s;
        }
        .rt-logout:hover { background: #064e3b; color: #fff; }
        .rt-main { flex: 1; margin-left: 240px; display: flex; flex-direction: column; }
        .rt-top {
            background: #0d1b2e; border-bottom: 1px solid #1e3a5f;
            padding: 16px 26px; display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 10;
        }
        .rt-top h1 { font-size: 1.1rem; font-weight: 700; color: #f1f5f9; }
        .rt-badge {
            background: #05996922; color: #6ee7b7; padding: 5px 12px;
            border-radius: 20px; font-size: .76rem; font-weight: 600;
            display: flex; align-items: center; gap: 6px; border: 1px solid #05996944;
        }
        .rt-body { padding: 26px; flex: 1; }
        .rt-card { background: #0d1b2e; border: 1px solid #1e3a5f; border-radius: 14px; padding: 22px; margin-bottom: 20px; }
        .rt-card-title { font-size: .9rem; font-weight: 700; color: #6ee7b7; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
        .rt-stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 14px; margin-bottom: 22px; }
        .rt-stat { background: #0d1b2e; border: 1px solid #1e3a5f; border-radius: 13px; padding: 18px; display: flex; flex-direction: column; gap: 6px; }
        .rt-stat-val { font-size: 1.8rem; font-weight: 700; line-height: 1; }
        .rt-stat-lbl { font-size: .8rem; color: #64748b; }
        .rt-table { width: 100%; border-collapse: collapse; }
        .rt-table th, .rt-table td { padding: 11px 14px; text-align: right; font-size: .86rem; border-bottom: 1px solid #1e3a5f; }
        .rt-table th { color: #64748b; font-weight: 600; background: #060d1a55; font-size: .78rem; }
        .rt-table th:first-child, .rt-table td:first-child { text-align: left; }
        .rt-table tbody tr:hover { background: #060d1a55; }
        .rt-btn {
            display: inline-flex; align-items: center; gap: 7px; padding: 9px 16px;
            border-radius: 9px; border: none; cursor: pointer; font-size: .86rem;
            font-weight: 600; text-decoration: none; font-family: inherit; transition: .15s;
        }
        .rt-btn-primary { background: linear-gradient(135deg, #059669, #0d9488); color: #fff; }
        .rt-btn-primary:hover { opacity: .9; }
        .rt-btn-ghost { background: #1e3a5f; color: #6ee7b7; }
        .rt-btn-ghost:hover { background: #064e3b; color: #fff; }
        .rt-btn-sm { padding: 6px 11px; font-size: .78rem; }
        .rt-btn-danger { background: #7f1d1d44; color: #fca5a5; border: 1px solid #7f1d1d; }
        .rt-btn-danger:hover { background: #7f1d1d; color: #fff; }
        .rt-field { margin-bottom: 16px; }
        .rt-label { display: block; font-size: .83rem; color: #94a3b8; margin-bottom: 6px; font-weight: 500; }
        .rt-input, .rt-select, .rt-textarea {
            width: 100%; padding: 10px 14px; border-radius: 9px;
            border: 1px solid #1e3a5f; background: #060d1a; color: #e2e8f0; font-size: .9rem; font-family: inherit;
        }
        .rt-input:focus, .rt-select:focus, .rt-textarea:focus { outline: none; border-color: #059669; }
        .rt-pill { padding: 3px 10px; border-radius: 20px; font-size: .74rem; font-weight: 600; }
        .rt-pill-active  { background: #14532d44; color: #86efac; border: 1px solid #14532d66; }
        .rt-pill-warning { background: #78350f44; color: #fde68a; border: 1px solid #78350f66; }
        .rt-pill-grace   { background: #7f1d1d44; color: #fca5a5; border: 1px solid #7f1d1d66; }
        .rt-pill-expired { background: #1e293b; color: #475569; border: 1px solid #334155; }
        .rt-alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; font-size: .88rem; }
        .rt-alert-success { background: #14532d44; color: #86efac; border: 1px solid #14532d; }
        .rt-alert-error   { background: #7f1d1d44; color: #fca5a5; border: 1px solid #7f1d1d; }
        .rt-empty { text-align: center; padding: 50px 20px; color: #475569; }
        .rt-empty i { font-size: 2.5rem; margin-bottom: 12px; display: block; }
        .rt-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        @media (max-width: 640px) { .rt-grid-2 { grid-template-columns: 1fr; } }
    </style>
    @stack('styles')
</head>
<body>
<div class="rt-wrap">
    <aside class="rt-side">
        <div class="rt-brand">
            <div class="rt-brand-icon"><i class="fas fa-handshake"></i></div>
            <div>
                <div class="rt-brand-name">রিসেলার প্যানেল</div>
                <div class="rt-brand-sub">{{ auth()->user()->name }}</div>
            </div>
        </div>
        <nav class="rt-nav">
            @php $r = request()->route()->getName(); @endphp
            <a href="{{ route('reseller.dashboard') }}" class="{{ $r === 'reseller.dashboard' ? 'active' : '' }}">
                <i class="fas fa-gauge-high"></i> ড্যাশবোর্ড
            </a>
            <a href="{{ route('reseller.clients.index') }}" class="{{ str_starts_with($r, 'reseller.clients') ? 'active' : '' }}">
                <i class="fas fa-users"></i> আমার ক্লায়েন্ট
            </a>
            <a href="{{ route('reseller.clients.create') }}" class="{{ $r === 'reseller.clients.create' ? 'active' : '' }}">
                <i class="fas fa-user-plus"></i> নতুন ক্লায়েন্ট
            </a>
        </nav>
        <div class="rt-foot">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="rt-logout"><i class="fas fa-right-from-bracket"></i> লগআউট</button>
            </form>
        </div>
    </aside>

    <div class="rt-main">
        <div class="rt-top">
            <h1>@yield('page-title')</h1>
            <div class="rt-badge"><i class="fas fa-handshake"></i> রিসেলার</div>
        </div>
        <div class="rt-body">
            @if(session('success'))
                <div class="rt-alert rt-alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="rt-alert rt-alert-error"><i class="fas fa-circle-xmark"></i> {{ session('error') }}</div>
            @endif
            @yield('content')
        </div>
    </div>
</div>
@stack('scripts')
</body>
</html>
