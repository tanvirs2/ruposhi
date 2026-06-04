<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'রুট প্যানেল') — Root Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Hind Siliguri', sans-serif; background: #060d1a; color: #e2e8f0; }
        .rt-wrap { display: flex; min-height: 100vh; }

        /* Sidebar */
        .rt-side {
            width: 240px; background: #0d1b2e; border-right: 1px solid #1e3a5f;
            display: flex; flex-direction: column; flex-shrink: 0; position: fixed;
            top: 0; left: 0; bottom: 0; overflow-y: auto;
        }
        .rt-brand {
            padding: 20px 18px; border-bottom: 1px solid #1e3a5f;
            display: flex; align-items: center; gap: 12px;
        }
        .rt-brand-icon {
            width: 42px; height: 42px; border-radius: 10px;
            background: linear-gradient(135deg, #1d4ed8, #7c3aed);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 1.15rem;
        }
        .rt-brand-name { font-weight: 700; font-size: 1rem; color: #f1f5f9; }
        .rt-brand-sub  { font-size: .7rem; color: #64748b; margin-top: 2px; }

        .rt-nav { padding: 14px 10px; flex: 1; }
        .rt-nav-section { font-size: .7rem; color: #475569; font-weight: 700;
            text-transform: uppercase; letter-spacing: 1px; padding: 12px 8px 6px; }
        .rt-nav a {
            display: flex; align-items: center; gap: 11px;
            padding: 10px 13px; border-radius: 9px; margin-bottom: 3px;
            color: #94a3b8; text-decoration: none; font-size: .88rem; transition: .15s;
        }
        .rt-nav a:hover  { background: #1e3a5f; color: #e2e8f0; }
        .rt-nav a.active { background: linear-gradient(135deg, #1d4ed8, #7c3aed); color: #fff; }
        .rt-nav a i { width: 18px; text-align: center; }

        .rt-foot { padding: 14px; border-top: 1px solid #1e3a5f; }
        .rt-logout {
            width: 100%; padding: 10px; border: none; border-radius: 8px;
            background: #1e3a5f; color: #93c5fd; cursor: pointer;
            font-size: .85rem; display: flex; align-items: center; justify-content: center; gap: 8px;
            font-family: inherit; transition: .15s;
        }
        .rt-logout:hover { background: #1e3a8a; color: #fff; }

        /* Main */
        .rt-main { flex: 1; margin-left: 240px; display: flex; flex-direction: column; min-height: 100vh; }
        .rt-top {
            background: #0d1b2e; border-bottom: 1px solid #1e3a5f;
            padding: 16px 26px; display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 10;
        }
        .rt-top h1 { font-size: 1.1rem; font-weight: 700; color: #f1f5f9; }
        .rt-badge {
            background: #1d4ed822; color: #93c5fd; padding: 5px 12px;
            border-radius: 20px; font-size: .76rem; font-weight: 600;
            display: flex; align-items: center; gap: 6px; border: 1px solid #1d4ed844;
        }
        .rt-body { padding: 26px; flex: 1; }

        /* Cards & Tables */
        .rt-card { background: #0d1b2e; border: 1px solid #1e3a5f; border-radius: 14px; padding: 22px; margin-bottom: 20px; }
        .rt-card-title { font-size: .9rem; font-weight: 700; color: #93c5fd; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
        .rt-stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 14px; margin-bottom: 22px; }
        .rt-stat {
            background: #0d1b2e; border: 1px solid #1e3a5f; border-radius: 13px;
            padding: 18px; display: flex; flex-direction: column; gap: 6px;
        }
        .rt-stat-val { font-size: 1.8rem; font-weight: 700; line-height: 1; }
        .rt-stat-lbl { font-size: .8rem; color: #64748b; }

        .rt-table { width: 100%; border-collapse: collapse; }
        .rt-table th, .rt-table td { padding: 11px 14px; text-align: right; font-size: .86rem; border-bottom: 1px solid #1e3a5f; }
        .rt-table th { color: #64748b; font-weight: 600; background: #060d1a55; font-size: .78rem; }
        .rt-table th:first-child, .rt-table td:first-child { text-align: left; }
        .rt-table tbody tr:hover { background: #060d1a55; }

        /* Buttons */
        .rt-btn {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 9px 16px; border-radius: 9px; border: none; cursor: pointer;
            font-size: .86rem; font-weight: 600; text-decoration: none;
            font-family: inherit; transition: .15s;
        }
        .rt-btn-primary { background: linear-gradient(135deg, #1d4ed8, #7c3aed); color: #fff; }
        .rt-btn-primary:hover { opacity: .9; }
        .rt-btn-ghost { background: #1e3a5f; color: #93c5fd; }
        .rt-btn-ghost:hover { background: #1e3a8a; color: #fff; }
        .rt-btn-sm { padding: 6px 11px; font-size: .78rem; }
        .rt-btn-danger { background: #7f1d1d44; color: #fca5a5; border: 1px solid #7f1d1d; }
        .rt-btn-danger:hover { background: #7f1d1d; color: #fff; }
        .rt-btn-green { background: #14532d44; color: #86efac; border: 1px solid #14532d; }
        .rt-btn-green:hover { background: #14532d; color: #fff; }

        /* Form elements */
        .rt-field { margin-bottom: 16px; }
        .rt-label { display: block; font-size: .83rem; color: #94a3b8; margin-bottom: 6px; font-weight: 500; }
        .rt-input, .rt-select, .rt-textarea {
            width: 100%; padding: 10px 14px; border-radius: 9px;
            border: 1px solid #1e3a5f; background: #060d1a; color: #e2e8f0;
            font-size: .9rem; font-family: inherit;
        }
        .rt-input:focus, .rt-select:focus, .rt-textarea:focus { outline: none; border-color: #1d4ed8; }
        .rt-select option { background: #0d1b2e; }

        /* Pills */
        .rt-pill { padding: 3px 10px; border-radius: 20px; font-size: .74rem; font-weight: 600; }
        .rt-pill-active  { background: #14532d44; color: #86efac; border: 1px solid #14532d66; }
        .rt-pill-warning { background: #78350f44; color: #fde68a; border: 1px solid #78350f66; }
        .rt-pill-grace   { background: #7f1d1d44; color: #fca5a5; border: 1px solid #7f1d1d66; }
        .rt-pill-expired { background: #1e293b; color: #475569; border: 1px solid #334155; }

        /* Alerts */
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

    {{-- Sidebar --}}
    <aside class="rt-side">
        <div class="rt-brand">
            <div class="rt-brand-icon"><i class="fas fa-crown"></i></div>
            <div>
                <div class="rt-brand-name">Root Admin</div>
                <div class="rt-brand-sub">সফটওয়্যার মালিক</div>
            </div>
        </div>

        <nav class="rt-nav">
            @php $r = request()->route()->getName(); @endphp
            <div class="rt-nav-section">প্রধান</div>
            <a href="{{ route('root.dashboard') }}" class="{{ $r === 'root.dashboard' ? 'active' : '' }}">
                <i class="fas fa-gauge-high"></i> ড্যাশবোর্ড
            </a>

            <div class="rt-nav-section">ম্যানেজমেন্ট</div>
            <a href="{{ route('root.super-admins.index') }}" class="{{ str_starts_with($r, 'root.super-admins') ? 'active' : '' }}">
                <i class="fas fa-user-shield"></i> সুপার অ্যাডমিন
            </a>
            <a href="{{ route('root.super-admins.create') }}" class="{{ $r === 'root.super-admins.create' ? 'active' : '' }}">
                <i class="fas fa-user-plus"></i> নতুন সুপার অ্যাডমিন
            </a>
            <a href="{{ route('root.resellers.index') }}" class="{{ str_starts_with($r, 'root.resellers') ? 'active' : '' }}">
                <i class="fas fa-handshake"></i> রিসেলার
            </a>
            <a href="{{ route('root.resellers.create') }}" class="{{ $r === 'root.resellers.create' ? 'active' : '' }}">
                <i class="fas fa-user-plus"></i> নতুন রিসেলার
            </a>
        </nav>

        <div class="rt-foot">
            <div style="font-size:.8rem;color:#475569;padding:8px 0 10px;text-align:center">
                {{ auth()->user()->name }}
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="rt-logout"><i class="fas fa-right-from-bracket"></i> লগআউট</button>
            </form>
        </div>
    </aside>

    {{-- Main --}}
    <div class="rt-main">
        <div class="rt-top">
            <h1>@yield('page-title', 'ড্যাশবোর্ড')</h1>
            <div class="rt-badge">
                <i class="fas fa-crown"></i> Root Admin
            </div>
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
