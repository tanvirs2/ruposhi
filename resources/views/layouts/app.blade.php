<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- PWA: installable app (manifest + icons). SW registered in app.js — no caching, install-only. --}}
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0d9488">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
    {{-- ── Global JS error catcher (loaded first, before any other script) ──
         Captures uncaught script errors — including inline-script SyntaxErrors
         that break Turbo re-navigation ("buttons dead until refresh"). Logs the
         page + script + line to localStorage so the intermittent issue becomes
         diagnosable, and shows a dismissible refresh notice ONLY for the error
         types that actually break the page, so users aren't stuck mid-task. --}}
    <script>
    (function () {
        function logErr(info) {
            try {
                var arr = JSON.parse(localStorage.getItem('jsErrors') || '[]');
                arr.unshift(info);
                localStorage.setItem('jsErrors', JSON.stringify(arr.slice(0, 25)));
            } catch (e) {}
            try { console.error('[JSERR]', info); } catch (e) {}
        }
        function showNotice() {
            if (document.getElementById('jsErrNotice')) return;
            var host = document.body || document.documentElement;
            var d = document.createElement('div');
            d.id = 'jsErrNotice';
            d.style.cssText = 'position:fixed;bottom:16px;left:50%;transform:translateX(-50%);z-index:99999;' +
                'background:#fee2e2;color:#991b1b;border:1.5px solid #fca5a5;border-radius:10px;' +
                'padding:10px 16px;font-family:Hind Siliguri,sans-serif;font-size:.86rem;font-weight:600;' +
                'box-shadow:0 8px 24px rgba(0,0,0,.18);display:flex;align-items:center;gap:12px';
            d.innerHTML = '<span>পেজে একটি সমস্যা হয়েছে — রিফ্রেশ করুন</span>' +
                '<button onclick="location.reload()" style="background:#dc2626;color:#fff;border:none;' +
                'border-radius:6px;padding:5px 14px;font-weight:700;cursor:pointer;font-family:inherit">রিফ্রেশ</button>' +
                '<button onclick="this.parentNode.remove()" style="background:none;border:none;color:#991b1b;' +
                'font-size:1.2rem;cursor:pointer;line-height:1;padding:0 2px">&times;</button>';
            host.appendChild(d);
        }
        // Error messages that actually leave the page broken until a full reload
        var breakers = /already been declared|already declared|is not defined|Unexpected (token|identifier|end)/i;
        window.addEventListener('error', function (e) {
            if (!e || !e.message) return; // ignore resource (img/script 404) load errors
            logErr({ t: new Date().toISOString(), url: location.pathname,
                     msg: String(e.message), src: String(e.filename || ''),
                     line: e.lineno || 0, col: e.colno || 0 });
            if (breakers.test(e.message)) showNotice();
        });
        window.addEventListener('unhandledrejection', function (e) {
            var m = e && e.reason ? (e.reason.message || String(e.reason)) : 'unhandledrejection';
            logErr({ t: new Date().toISOString(), url: location.pathname, msg: 'PROMISE: ' + m });
        });
    })();
    </script>
    <title>@yield('title', 'ড্যাশবোর্ড') — Inventory</title>
    @include('partials.font-loader')
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <script src="{{ asset('js/vendor/pusher.min.js') }}" defer></script>
    {{-- Turbo Drive — SPA-like navigation without full page reloads (self-hosted) --}}
    <script src="{{ asset('js/vendor/turbo.min.js') }}"></script>
    <script>
    // Pass CSRF token on every Turbo fetch so Laravel doesn't reject non-GET requests
    document.addEventListener('turbo:before-fetch-request', function(e) {
        e.detail.fetchOptions.headers['X-CSRF-TOKEN'] =
            document.querySelector('meta[name=csrf-token]').content;
    });
    </script>
    @stack('styles')
</head>
<body class="role-{{ auth()->user()->role }}">

<!-- Sidebar -->
<aside class="sidebar" id="sidebar" data-turbo-permanent>
    <div class="sidebar-header">
        <div class="brand">
            <div class="brand-icon"><i class="fas fa-boxes-stacked"></i></div>
            <span class="brand-name">{{ auth()->user()->shop?->name ?? 'Inventory' }}</span>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
    </div>

    <nav class="sidebar-nav">
        @php
            $_path = request()->path();  // e.g. 'item-types', 'reports/sales'
            $_seg  = explode('/', $_path)[0];  // first segment only

            // Accordion open states
            $inCustomer = in_array($_seg, ['customers','customer-payments','customer-areas','collections']) || $_path === 'customers-ledger';
            $inItems    = in_array($_seg, ['items','categories']);
            $inStock    = $_seg === 'stock';
            $inSupplier = in_array($_seg, ['suppliers','supplier-payments','purchases']) || in_array($_path, ['suppliers-due-report','suppliers-ledger']);
            $inReports  = $_seg === 'reports';
        @endphp

        <div class="nav-section">
            <span class="nav-section-label">প্রধান মেনু</span>
            <a href="{{ route('dashboard') }}" class="nav-item {{ $_path==='dashboard' ? 'active' : '' }}">
                <span class="nav-icon"><i class="fas fa-gauge-high"></i></span>
                <span class="nav-label">ড্যাশবোর্ড</span>
                <button type="button" class="info-btn" data-info="ব্যবসার সামগ্রিক চিত্র — আজকের বিক্রয়, মোট স্টক, কাস্টমার বাকী ও সরবরাহকারী বকেয়া এক নজরে দেখুন।">i</button>
            </a>

            {{-- ── Customer accordion ────────────────────────── --}}
            <div class="nav-group {{ $inCustomer ? 'open' : '' }}" id="navGroupCustomer">
                <div class="nav-group-toggle" onclick="toggleNavGroup('navGroupCustomer')">
                    <span class="nav-icon"><i class="fas fa-users"></i></span>
                    <span class="nav-label">কাস্টমার</span>
                    <button type="button" class="info-btn" data-info="কাস্টমারদের তথ্য, বাকী হিসাব ও পরিশোধ ব্যবস্থাপনা। নতুন কাস্টমার যোগ করুন ও বাকী ট্র্যাক করুন।">i</button>
                    <span class="nav-arrow"><i class="fas fa-chevron-down"></i></span>
                </div>
                <div class="nav-group-children">
                    <a href="{{ route('customers.index') }}" class="nav-item nav-child {{ $_seg==='customers' && !str_ends_with($_path,'ledger') ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fas fa-list"></i></span>
                        <span class="nav-label">কাস্টমার তালিকা</span>
                        <button type="button" class="info-btn" data-info="সকল কাস্টমারের তথ্য দেখুন — নাম, ফোন, মোট বাকী। নতুন কাস্টমার যোগ করুন বা তথ্য সম্পাদনা করুন।">i</button>
                    </a>
                    <a href="{{ route('collections.index') }}" class="nav-item nav-child {{ $_seg==='collections' ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fas fa-hand-holding-dollar"></i></span>
                        <span class="nav-label">তাগাদা লিস্ট</span>
                        <button type="button" class="info-btn" data-info="বাকী আদায়ের কর্মতালিকা — এলাকা ধরে বাকীদার কাস্টমার, বাকীর বয়স (৩০/৬০/৯০+ দিন) ও SMS তাগাদা এক জায়গায়।">i</button>
                    </a>
                    <a href="{{ route('customer-payments.create') }}" class="nav-item nav-child {{ $_path==='customer-payments/create' ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fas fa-plus-circle"></i></span>
                        <span class="nav-label">কাস্টমার পরিশোধ</span>
                        <button type="button" class="info-btn" data-info="কাস্টমারের বকেয়া পরিশোধ রেকর্ড করুন। পরিশোধের পরিমাণ বকেয়া থেকে স্বয়ংক্রিয়ভাবে বাদ যাবে।">i</button>
                    </a>
                    <a href="{{ route('customer-payments.index') }}" class="nav-item nav-child {{ $_seg==='customer-payments' && $_path!=='customer-payments/create' ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fas fa-money-bill-wave"></i></span>
                        <span class="nav-label">পরিশোধ তালিকা</span>
                        <button type="button" class="info-btn" data-info="সকল কাস্টমার পরিশোধের ইতিহাস — কে কত টাকা কখন দিয়েছে।">i</button>
                    </a>
                    <a href="{{ route('reports.daily-payments') }}" class="nav-item nav-child {{ $_path==='reports/daily-payments' ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fas fa-calendar-check"></i></span>
                        <span class="nav-label">দৈনিক পরিশোধ</span>
                        <button type="button" class="info-btn" data-info="নির্দিষ্ট তারিখে কাস্টমার কত টাকা পরিশোধ করেছে তার দৈনিক সারসংক্ষেপ।">i</button>
                    </a>
                    <a href="{{ route('customers.ledger-select') }}" class="nav-item nav-child {{ $_path==='customers-ledger' || ($_seg==='customers' && str_ends_with($_path,'ledger')) ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fas fa-book-open"></i></span>
                        <span class="nav-label">লেজার রিপোর্ট</span>
                        <button type="button" class="info-btn" data-info="কাস্টমারের সকল ক্রয় ও পরিশোধের বিস্তারিত হিসাব।">i</button>
                    </a>
                    <a href="{{ route('customer-areas.index') }}" class="nav-item nav-child {{ $_seg==='customer-areas' ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fas fa-map-location-dot"></i></span>
                        <span class="nav-label">কাস্টমার এরিয়া</span>
                        <button type="button" class="info-btn" data-info="কাস্টমারদের এলাকাভিত্তিক গ্রুপ তৈরি করুন। যেমন: ঢাকা, চট্টগ্রাম ইত্যাদি।">i</button>
                    </a>
                </div>
            </div>

            {{-- ── Items accordion ──────────────────────────── --}}
            <div class="nav-group {{ $inItems ? 'open' : '' }}" id="navGroupItems">
                <div class="nav-group-toggle" onclick="toggleNavGroup('navGroupItems')">
                    <span class="nav-icon"><i class="fas fa-box-open"></i></span>
                    <span class="nav-label">আইটেমস</span>
                    <button type="button" class="info-btn" data-info="পণ্য ও আইটেম ব্যবস্থাপনা — নতুন পণ্য যোগ করুন, ক্রয় ও বিক্রয় মূল্য সেট করুন, ক্যাটাগরি ও ব্র্যান্ড পরিচালনা করুন।">i</button>
                    <span class="nav-arrow"><i class="fas fa-chevron-down"></i></span>
                </div>
                <div class="nav-group-children">
                    <a href="{{ route('items.index') }}" class="nav-item nav-child {{ $_seg==='items' ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fas fa-list"></i></span>
                        <span class="nav-label">আইটেম তালিকা</span>
                        <button type="button" class="info-btn" data-info="সকল পণ্যের তালিকা — ক্রয়মূল্য, বিক্রয়মূল্য ও বর্তমান স্টক দেখুন। নতুন পণ্য যোগ করুন।">i</button>
                    </a>
                    <a href="{{ route('categories.index') }}" class="nav-item nav-child {{ $_seg==='categories' ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fas fa-tags"></i></span>
                        <span class="nav-label">আইটেম ক্যাটাগরি</span>
                        <button type="button" class="info-btn" data-info="পণ্যের ক্যাটাগরি তৈরি করুন। যেমন: চাল, ডাল, তেল। ক্যাটাগরি দিয়ে পণ্য ফিল্টার করা যাবে।">i</button>
                    </a>
                    <a href="{{ route('brands.index') }}" class="nav-item nav-child {{ $_seg==='brands' ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fas fa-copyright"></i></span>
                        <span class="nav-label">ব্র্যান্ড</span>
                        <button type="button" class="info-btn" data-info="পণ্যের ব্র্যান্ড তৈরি করুন। যেমন: আকিজ, সিটি, ফ্রেশ। ঐচ্ছিক — না দিলেও চলবে।">i</button>
                    </a>
                </div>
            </div>
            {{-- ── Stock accordion ──────────────────────────── --}}
            <div class="nav-group {{ $inStock ? 'open' : '' }}" id="navGroupStock">
                <div class="nav-group-toggle" onclick="toggleNavGroup('navGroupStock')">
                    <span class="nav-icon"><i class="fas fa-warehouse"></i></span>
                    <span class="nav-label">স্টক</span>
                    <button type="button" class="info-btn" data-info="গুদামের মালামাল ব্যবস্থাপনা — কোন পণ্য কত আছে, কোনটি শেষ হয়ে যাচ্ছে তা ট্র্যাক করুন।">i</button>
                    <span class="nav-arrow"><i class="fas fa-chevron-down"></i></span>
                </div>
                <div class="nav-group-children">
                    <a href="{{ route('stock.report') }}" class="nav-item nav-child {{ $_path==='stock/report' ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fas fa-magnifying-glass-chart"></i></span>
                        <span class="nav-label">স্টক রিপোর্ট</span>
                        <button type="button" class="info-btn" data-info="সকল পণ্যের বর্তমান স্টক পরিমাণ ও মোট মূল্য। কোন পণ্য কতটুকু আছে তার সারসংক্ষেপ।">i</button>
                    </a>
                    <a href="{{ route('stock.index') }}" class="nav-item nav-child {{ $_path==='stock' ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fas fa-boxes-stacked"></i></span>
                        <span class="nav-label">স্টক তথ্য</span>
                        <button type="button" class="info-btn" data-info="স্টক সমন্বয় করুন — প্রয়োজনে ম্যানুয়ালি স্টক পরিমাণ আপডেট করুন। ক্ষতি বা হিসাব মেলাতে ব্যবহার করুন।">i</button>
                    </a>
                    <a href="{{ route('stock.low') }}" class="nav-item nav-child {{ $_path==='stock/low' ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fas fa-triangle-exclamation"></i></span>
                        <span class="nav-label">স্টক শেষ</span>
                        <button type="button" class="info-btn" data-info="সতর্কবার্তা — যেসব পণ্যের স্টক নির্ধারিত সীমার নিচে নেমে গেছে। দ্রুত অর্ডার দিন।">i</button>
                    </a>
                </div>
            </div>
            {{-- ── Supplier accordion ───────────────────────── --}}
            <div class="nav-group {{ $inSupplier ? 'open' : '' }}" id="navGroupSupplier">
                <div class="nav-group-toggle" onclick="toggleNavGroup('navGroupSupplier')">
                    <span class="nav-icon"><i class="fas fa-truck"></i></span>
                    <span class="nav-label">সরবরাহকারী</span>
                    <button type="button" class="info-btn" data-info="মালামাল সরবরাহকারীদের তথ্য ও বকেয়া হিসাব। কে কত টাকা পাবেন তা ট্র্যাক করুন।">i</button>
                    <span class="nav-arrow"><i class="fas fa-chevron-down"></i></span>
                </div>
                <div class="nav-group-children">
                    <a href="{{ route('suppliers.index') }}" class="nav-item nav-child {{ $_seg==='suppliers' && !str_ends_with($_path,'ledger') ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fas fa-list"></i></span>
                        <span class="nav-label">সরবরাহকারী তালিকা</span>
                        <button type="button" class="info-btn" data-info="সকল সরবরাহকারীর তথ্য — নাম, ফোন, ঠিকানা ও মোট বকেয়া। নতুন সরবরাহকারী যোগ করুন।">i</button>
                    </a>
                    <a href="{{ route('purchases.index') }}" class="nav-item nav-child {{ $_seg==='purchases' ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fas fa-truck-ramp-box"></i></span>
                        <span class="nav-label">পণ্য রিসিভ লিস্ট</span>
                        <button type="button" class="info-btn" data-info="সরবরাহকারীর কাছ থেকে মালামাল গ্রহণ করুন। পরিমাণ ও মূল্য লিখলে স্টক স্বয়ংক্রিয়ভাবে আপডেট হবে এবং বকেয়া হিসাব হবে।">i</button>
                    </a>
                    <a href="{{ route('supplier-payments.create') }}" class="nav-item nav-child {{ $_path==='supplier-payments/create' ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fas fa-plus-circle"></i></span>
                        <span class="nav-label">সরবরাহকারী পরিশোধ</span>
                        <button type="button" class="info-btn" data-info="সরবরাহকারীর বকেয়া পরিশোধ রেকর্ড করুন। পরিশোধের পরিমাণ বকেয়া থেকে স্বয়ংক্রিয়ভাবে বাদ যাবে।">i</button>
                    </a>
                    <a href="{{ route('supplier-payments.index') }}" class="nav-item nav-child {{ $_seg==='supplier-payments' && $_path!=='supplier-payments/create' ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fas fa-money-bill-wave"></i></span>
                        <span class="nav-label">পরিশোধ তালিকা</span>
                        <button type="button" class="info-btn" data-info="সকল সরবরাহকারী পরিশোধের ইতিহাস — কাকে কত টাকা কখন দেওয়া হয়েছে।">i</button>
                    </a>
                    <a href="{{ route('reports.daily-supplier-payments') }}" class="nav-item nav-child {{ $_path==='reports/daily-supplier-payments' ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fas fa-calendar-check"></i></span>
                        <span class="nav-label">দৈনিক পরিশোধ</span>
                        <button type="button" class="info-btn" data-info="নির্দিষ্ট তারিখে সরবরাহকারীকে কত টাকা পরিশোধ করা হয়েছে তার দৈনিক সারসংক্ষেপ।">i</button>
                    </a>
                    <a href="{{ route('suppliers.ledger-select') }}" class="nav-item nav-child {{ $_path==='suppliers-ledger' || ($_seg==='suppliers' && str_ends_with($_path,'ledger')) ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fas fa-book-open"></i></span>
                        <span class="nav-label">লেজার রিপোর্ট</span>
                        <button type="button" class="info-btn" data-info="সরবরাহকারীর সকল পণ্য গ্রহণ ও পরিশোধের বিস্তারিত হিসাব।">i</button>
                    </a>
                    <a href="{{ route('suppliers.due-report') }}" class="nav-item nav-child {{ $_path==='suppliers-due-report' ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fas fa-file-invoice-dollar"></i></span>
                        <span class="nav-label">বাকী রিপোর্ট</span>
                        <button type="button" class="info-btn" data-info="বকেয়া আছে এমন সকল সরবরাহকারীর তালিকা — মোট বকেয়া পরিমাণ সহ।">i</button>
                    </a>
                </div>
            </div>
        </div>

        <div class="nav-section">
            <span class="nav-section-label">লেনদেন</span>
            <a href="{{ route('sales.index') }}" class="nav-item nav-item-highlight {{ $_seg==='sales' ? 'active' : '' }}">
                <span class="nav-icon"><i class="fas fa-receipt"></i></span>
                <span class="nav-label">বিক্রয় লিস্ট</span>
                <button type="button" class="info-btn" data-info="নতুন বিক্রয় এন্ট্রি করুন। পণ্য নির্বাচন করুন, কাস্টমার সেট করুন, পরিশোধ ও বাকী রেকর্ড করুন। বিক্রয়ে স্টক স্বয়ংক্রিয়ভাবে কমবে।">i</button>
            </a>
        </div>

        <div class="nav-section">
            <span class="nav-section-label">ব্যবস্থাপনা</span>
            <a href="{{ route('employees.index') }}" class="nav-item {{ $_seg==='employees' ? 'active' : '' }}">
                <span class="nav-icon"><i class="fas fa-id-badge"></i></span>
                <span class="nav-label">কর্মচারী</span>
                <button type="button" class="info-btn" data-info="কর্মচারীদের তথ্য ব্যবস্থাপনা — নাম, পদবি, বেতন ও যোগাযোগ তথ্য সংরক্ষণ করুন।">i</button>
            </a>
            <a href="{{ route('expenses.index') }}" class="nav-item {{ $_seg==='expenses' ? 'active' : '' }}">
                <span class="nav-icon"><i class="fas fa-money-bill-transfer"></i></span>
                <span class="nav-label">খরচ ও জমা</span>
                <button type="button" class="info-btn" data-info="ব্যবসার দৈনন্দিন খরচ ও নগদ জমা রেকর্ড করুন। যেমন: বিদ্যুৎ বিল, ভাড়া, মালিকের জমা ইত্যাদি।">i</button>
            </a>

            {{-- ── Reports accordion ───────────────────────────── --}}
            <div class="nav-group {{ $inReports ? 'open' : '' }}" id="navGroupReports">
                <div class="nav-group-toggle" onclick="toggleNavGroup('navGroupReports')">
                    <span class="nav-icon"><i class="fas fa-chart-bar"></i></span>
                    <span class="nav-label">রিপোর্ট</span>
                    <button type="button" class="info-btn" data-info="ব্যবসার বিভিন্ন বিশ্লেষণ ও প্রতিবেদন — বিক্রয়, আয়-ব্যয়, বাকী ও স্টকের বিস্তারিত রিপোর্ট।">i</button>
                    <span class="nav-arrow"><i class="fas fa-chevron-down"></i></span>
                </div>
                <div class="nav-group-children">
                    <a href="{{ route('reports.index') }}" class="nav-item nav-child {{ $_path==='reports' ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fas fa-house-chimney"></i></span>
                        <span class="nav-label">হোম / সারসংক্ষেপ</span>
                        <button type="button" class="info-btn" data-info="সামগ্রিক ব্যবসার হিসাব — মোট আয়, ব্যয়, লাভ ও বাকীর সারসংক্ষেপ এক পেজে।">i</button>
                    </a>
                    <a href="{{ route('reports.sales') }}" class="nav-item nav-child {{ $_path==='reports/sales' ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fas fa-receipt"></i></span>
                        <span class="nav-label">দৈনিক বিক্রয় রিপোর্ট</span>
                        <button type="button" class="info-btn" data-info="তারিখ ভিত্তিক বিক্রয়ের বিস্তারিত ইতিহাস — কোন পণ্য কত বিক্রি হয়েছে, লাভ কত।">i</button>
                    </a>
                    <a href="{{ route('reports.daily-payments') }}" class="nav-item nav-child {{ $_path==='reports/daily-payments' ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fas fa-users"></i></span>
                        <span class="nav-label">দৈনিক কাস্টমার পরিশোধ</span>
                        <button type="button" class="info-btn" data-info="প্রতিদিন কাস্টমার কত টাকা পরিশোধ করেছে তার তালিকা।">i</button>
                    </a>
                    <a href="{{ route('reports.daily-supplier-payments') }}" class="nav-item nav-child {{ $_path==='reports/daily-supplier-payments' ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fas fa-truck"></i></span>
                        <span class="nav-label">দৈনিক সরবরাহকারী পরিশোধ</span>
                        <button type="button" class="info-btn" data-info="প্রতিদিন সরবরাহকারীকে কত টাকা পরিশোধ করা হয়েছে তার তালিকা।">i</button>
                    </a>
                    <a href="{{ route('reports.daily-receive') }}" class="nav-item nav-child {{ $_path==='reports/daily-receive' ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fas fa-truck-ramp-box"></i></span>
                        <span class="nav-label">দৈনিক রিসিভ রিপোর্ট</span>
                        <button type="button" class="info-btn" data-info="প্রতিদিন কত পণ্য গ্রহণ হয়েছে, কোন সরবরাহকারী থেকে, মোট মূল্য কত।">i</button>
                    </a>
                    <a href="{{ route('reports.daily-sales-stock') }}" class="nav-item nav-child {{ $_path==='reports/daily-sales-stock' ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fas fa-chart-line"></i></span>
                        <span class="nav-label">বিক্রয় + স্টক রিপোর্ট</span>
                        <button type="button" class="info-btn" data-info="বিক্রয় ও স্টকের সমন্বিত তুলনামূলক প্রতিবেদন — কী বিক্রি হয়েছে ও বর্তমান স্টক কত।">i</button>
                    </a>
                    <a href="{{ route('reports.daily-sales-ledger') }}" class="nav-item nav-child {{ $_path==='reports/daily-sales-ledger' ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fas fa-book-open"></i></span>
                        <span class="nav-label">দৈনিক বিক্রয় লেজার</span>
                        <button type="button" class="info-btn" data-info="প্রতিটি বিক্রয় আইটেমের বিস্তারিত — পরিমাণ, দর, মোট ও ক্রমচলমান যোগফল।">i</button>
                    </a>
                    <a href="{{ route('reports.customer-due') }}" class="nav-item nav-child {{ $_path==='reports/customer-due' ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fas fa-file-invoice-dollar"></i></span>
                        <span class="nav-label">কাস্টমার বাকী রিপোর্ট</span>
                        <button type="button" class="info-btn" data-info="বাকী আছে এমন সকল কাস্টমারের তালিকা — কে কত টাকা বাকী, মোট বাকীর পরিমাণ।">i</button>
                    </a>
                    @if(auth()->user()->canManageShop())
                    <a href="{{ route('reports.profit-loss') }}" class="nav-item nav-child {{ $_path==='reports/profit-loss' ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fas fa-scale-balanced"></i></span>
                        <span class="nav-label">লাভ-লোকসান</span>
                        <button type="button" class="info-btn" data-info="নির্দিষ্ট সময়কালের বিক্রয় আয়, পণ্য খরচ, পরিচালনা ব্যয় ও নিট লাভ-লোকসানের পূর্ণ বিবরণ।">i</button>
                    </a>
                    <a href="{{ route('reports.day-close') }}" class="nav-item nav-child {{ $_path==='reports/day-close' ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fas fa-cash-register"></i></span>
                        <span class="nav-label">দিনশেষ রিপোর্ট</span>
                        <button type="button" class="info-btn" data-info="দিনের সারাংশ এক নজরে — বিক্রয়, নগদ আদায়, খরচ, নতুন বাকী ও ক্যাশ হিসাব। মালিকের ফোনে SMS-ও পাঠানো যায়।">i</button>
                    </a>
                    @endif
                    <a href="{{ route('reports.sale-logs') }}" class="nav-item nav-child {{ $_path==='reports/sale-logs' ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fas fa-clock-rotate-left"></i></span>
                        <span class="nav-label">সংশোধন / মুছে ফেলার লগ</span>
                    </a>
                    <a href="{{ route('reports.growth') }}" class="nav-item nav-child {{ $_path==='reports/growth' ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fas fa-chart-line"></i></span>
                        <span class="nav-label">ব্যবসার প্রবৃদ্ধি</span>
                    </a>
                </div>
            </div>
        </div>

        @if(auth()->user()->canManageShop())
        @php $pendingApprovalCount = \App\Models\Sale::whereNotNull('delete_requested_at')->count()
            + \App\Models\Purchase::whereNotNull('delete_requested_at')->count()
            + \App\Models\PendingEdit::where('status','pending')->count(); @endphp
        <div class="nav-section">
            <span class="nav-section-label">অনুমোদন</span>
            <a href="{{ route('approvals.index') }}" class="nav-item {{ $_seg==='approvals' ? 'active' : '' }}">
                <span class="nav-icon"><i class="fas fa-clipboard-check"></i></span>
                <span class="nav-label">অনুমোদন কেন্দ্র
                    @if($pendingApprovalCount > 0)
                        <span style="background:#ef4444;color:#fff;font-size:.65rem;font-weight:700;
                                     padding:1px 6px;border-radius:20px;margin-left:4px">{{ $pendingApprovalCount }}</span>
                    @endif
                </span>
            </a>
        </div>
        @endif

        <div class="nav-section">
            <span class="nav-section-label">সেটিংস</span>
            @if(auth()->user()->canManageShop())
            <a href="{{ route('users.index') }}" class="nav-item {{ $_seg==='users' ? 'active' : '' }}">
                <span class="nav-icon"><i class="fas fa-users-gear"></i></span>
                <span class="nav-label">ব্যবহারকারী</span>
                <button type="button" class="info-btn" data-info="এই শপের স্টাফ ও অ্যাডমিন লগইন অ্যাকাউন্ট তৈরি ও পরিচালনা করুন।">i</button>
            </a>
            @endif
            @if(auth()->user()->canManageShop())
            <a href="{{ route('store-config.index') }}" class="nav-item {{ $_seg==='store-config' ? 'active' : '' }}">
                <span class="nav-icon"><i class="fas fa-store"></i></span>
                <span class="nav-label">স্টোর কনফিগ</span>
                <button type="button" class="info-btn" data-info="ব্যবসার নাম, ঠিকানা, ফোন ও অন্যান্য তথ্য সেটআপ করুন। এই তথ্য চালান ও রিপোর্টে দেখা যাবে।">i</button>
            </a>
            <a href="{{ route('extra-cost-categories.index') }}" class="nav-item {{ $_seg==='extra-cost-categories' ? 'active' : '' }}">
                <span class="nav-icon"><i class="fas fa-coins"></i></span>
                <span class="nav-label">খরচের ক্যাটাগরি</span>
            </a>
            <a href="{{ route('deposit-categories.index') }}" class="nav-item {{ $_seg==='deposit-categories' ? 'active' : '' }}">
                <span class="nav-icon"><i class="fas fa-piggy-bank"></i></span>
                <span class="nav-label">জমার ক্যাটাগরি</span>
            </a>
            @endif
            <a href="{{ route('sms.index') }}" class="nav-item {{ $_seg==='sms' ? 'active' : '' }}">
                <span class="nav-icon"><i class="fas fa-comment-sms"></i></span>
                <span class="nav-label">SMS পাঠান</span>
                @if(isset($notifTotal))
                    {{-- badge if needed in future --}}
                @endif
            </a>
        </div>
    </nav>

    <div class="sidebar-footer">
        <div class="user-card">
            <a href="{{ route('profile.edit') }}" class="user-avatar" title="প্রোফাইল"
               style="text-decoration:none;color:#fff;flex-shrink:0">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </a>
            <div class="user-info">
                <a href="{{ route('profile.edit') }}" style="text-decoration:none">
                    <span class="user-name">{{ auth()->user()->name }}</span>
                </a>
                <span class="user-role">{{ auth()->user()->role === 'super_admin' ? 'সুপার অ্যাডমিন' : (auth()->user()->role === 'admin' ? 'অ্যাডমিন' : 'স্টাফ') }}</span>
            </div>
            <a href="{{ route('profile.edit') }}" class="logout-btn" title="প্রোফাইল" style="color:#94a3b8">
                <i class="fas fa-user-pen"></i>
            </a>
        </div>
        <form method="POST" action="{{ route('logout') }}" style="margin-top:8px">
            @csrf
            <button type="submit" class="sidebar-logout-btn">
                <i class="fas fa-right-from-bracket"></i> লগআউট
            </button>
        </form>
    </div>
</aside>

<!-- Main -->
<div class="main-wrapper" id="mainWrapper">

    @include('partials.subscription-banner')

    @if(auth()->user()->role === 'super_admin' && session('active_shop_id'))
    <div style="background:linear-gradient(90deg,#b45309,#d97706);color:#fff;padding:9px 20px;display:flex;align-items:center;justify-content:space-between;gap:12px;font-size:.88rem;font-weight:600">
        <span>
            <i class="fas fa-user-shield"></i>
            সুপার অ্যাডমিন মোড — আপনি <strong>{{ session('active_shop_name') }}</strong> শপে কাজ করছেন
        </span>
        <form method="POST" action="{{ route('super.shops.exit') }}" style="margin:0">
            @csrf
            <button type="submit" style="background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.55);color:#fff;padding:5px 16px;border-radius:7px;cursor:pointer;font-weight:600;font-size:.84rem;font-family:inherit">
                <i class="fas fa-arrow-left"></i> কন্ট্রোল প্যানেলে ফিরুন
            </button>
        </form>
    </div>
    @endif

    <header class="topbar">
        <div class="topbar-left">
            <div class="page-title">
                <h1>@yield('page-title', 'ড্যাশবোর্ড')</h1>
                <span class="breadcrumb">@hasSection('breadcrumb') @yield('breadcrumb') @else স্বাগতম, <strong>{{ auth()->user()->name }}</strong>&nbsp;<span style="display:inline-block;padding:1px 8px;border-radius:20px;font-size:.66rem;font-weight:700;vertical-align:middle;background:{{ in_array(auth()->user()->role, ['admin','super_admin']) ? '#dcfce7' : '#fef3c7' }};color:{{ in_array(auth()->user()->role, ['admin','super_admin']) ? '#15803d' : '#b45309' }}">{{ auth()->user()->role === 'super_admin' ? 'সুপার অ্যাডমিন' : (auth()->user()->role === 'admin' ? 'অ্যাডমিন' : 'স্টাফ') }}</span>@if(auth()->user()->shop) &nbsp;·&nbsp; <i class="fas fa-store" style="font-size:.72rem;opacity:.7"></i> {{ auth()->user()->shop->name }} @endif @endif</span>
            </div>
        </div>
        <div class="topbar-right">

            {{-- Font size controls --}}
            <div class="ui-controls">
                <button class="ctrl-btn ctrl-btn-fs-sm" data-size="sm" onclick="setFontSize('sm')" title="ছোট লেখা">A</button>
                <button class="ctrl-btn ctrl-btn-fs-md" data-size="md" onclick="setFontSize('md')" title="স্বাভাবিক লেখা">A</button>
                <button class="ctrl-btn ctrl-btn-fs-lg" data-size="lg" onclick="setFontSize('lg')" title="বড় লেখা">A</button>
            </div>

            <div class="topbar-divider"></div>

            {{-- Phonetic Bengali toggle + help --}}
            <div class="ui-controls" style="position:relative">
                <button class="ctrl-btn" id="phoneticBtn" onclick="togglePhonetic()" title="বাংলা ফোনেটিক টাইপিং চালু/বন্ধ (Alt+B)" style="font-family:'Hind Siliguri',sans-serif;font-weight:700;font-size:.95rem">ক</button>
                <button class="ctrl-btn" onclick="togglePhoneticHelp(event)" title="কিভাবে লিখবেন?" style="font-size:.7rem;padding:0 6px">?</button>
                {{-- Cheat sheet panel --}}
                <div id="phoneticHelpPanel" style="display:none;position:absolute;top:calc(100% + 10px);right:0;
                    width:340px;background:var(--surface);border:1.5px solid var(--border);
                    border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,.18);z-index:9999;padding:14px 16px">
                    <div style="font-weight:700;font-size:.88rem;margin-bottom:10px;color:var(--text-primary)">
                        <i class="fas fa-keyboard" style="color:var(--accent);margin-right:6px"></i>কিভাবে বাংলা লিখবেন
                    </div>
                    <table style="width:100%;border-collapse:collapse;font-size:.8rem">
                        <thead>
                            <tr style="color:var(--text-secondary);font-size:.72rem;text-transform:uppercase">
                                <th style="padding:3px 6px;text-align:left">টাইপ করুন</th>
                                <th style="padding:3px 6px;text-align:left">বাংলা</th>
                                <th style="padding:3px 6px;text-align:left">টাইপ করুন</th>
                                <th style="padding:3px 6px;text-align:left">বাংলা</th>
                            </tr>
                        </thead>
                        <tbody style="color:var(--text-primary)">
                            @foreach([
                                ['a','আ','k','ক'],['i','ই','kh','খ'],['I','ঈ','g','গ'],
                                ['u','উ','gh','ঘ'],['U','ঊ','ch','ছ'],['e','এ','j','জ'],
                                ['o','অ','t','ত'],['O','ও','T','ট'],['OI','ঐ','d','দ'],
                                ['OU','ঔ','D','ড'],['rri','ঋ','n','ন'],['','','N','ণ'],
                                ['sh','শ','p','প'],['Sh','ষ','b','ব'],['s','স','bh','ভ'],
                                ['h','হ','m','ম'],['r','র','l','ল'],['R','ড়','ng','ং'],
                                ['^','ঁ','..','।'],
                            ] as $row)
                            <tr style="border-top:1px solid var(--border)">
                                <td style="padding:4px 6px"><code style="background:var(--surface-2);padding:1px 5px;border-radius:4px;color:var(--accent);font-size:.78rem">{{ $row[0] }}</code></td>
                                <td style="padding:4px 6px;font-family:'Hind Siliguri',sans-serif;font-size:1rem">{{ $row[1] }}</td>
                                <td style="padding:4px 6px"><code style="background:var(--surface-2);padding:1px 5px;border-radius:4px;color:var(--accent);font-size:.78rem">{{ $row[2] }}</code></td>
                                <td style="padding:4px 6px;font-family:'Hind Siliguri',sans-serif;font-size:1rem">{{ $row[3] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div style="margin-top:10px;padding-top:8px;border-top:1px solid var(--border);font-size:.75rem;color:var(--text-secondary)">
                        দুটো ব্যঞ্জনবর্ণ একসাথে লিখলে যুক্তবর্ণ হয় — যেমন <code style="color:var(--accent)">kt</code> → <span style="font-family:'Hind Siliguri',sans-serif">ক্ত</span>,
                        <code style="color:var(--accent)">ng</code> → <span style="font-family:'Hind Siliguri',sans-serif">ং</span>
                    </div>
                </div>
            </div>

            <div class="topbar-divider"></div>

            {{-- Dark mode + shortcuts --}}
            <div class="ui-controls">
                <button class="ctrl-btn ctrl-btn-dark" id="darkModeBtn" onclick="toggleDarkMode()" title="ডার্ক মোড">
                    <i class="fas fa-moon" id="darkModeIcon"></i>
                </button>
                <button class="ctrl-btn ctrl-btn-highlight" onclick="toggleShortcutsHelp()" title="কীবোর্ড শর্টকাট (Alt+/)">
                    <i class="fas fa-keyboard"></i>
                </button>
            </div>

            <div class="topbar-divider"></div>

            {{-- Offline indicator --}}
            <div class="ui-controls" id="offlineIndicatorWrap" style="position:relative;display:none">
                <div id="offlineDot" style="display:none;align-items:center;gap:5px;padding:0 8px;
                     background:#fee2e2;border-radius:20px;height:32px;cursor:default" title="ইন্টারনেট সংযোগ নেই">
                    <i class="fas fa-wifi" style="color:#dc2626;font-size:.75rem;text-decoration:line-through"></i>
                    <span style="font-size:.7rem;font-weight:700;color:#dc2626">অফলাইন</span>
                </div>
                <button class="ctrl-btn" id="offlinePendingBtn" onclick="syncOfflineSales()"
                        title="অফলাইনে সংরক্ষিত বিক্রয় sync করুন" style="display:none;position:relative">
                    <i class="fas fa-cloud-arrow-up" style="color:#1d4ed8"></i>
                    <span id="offlinePendingBadge" style="display:none;position:absolute;top:-4px;right:-4px;
                          background:#dc2626;color:#fff;border-radius:50%;width:16px;height:16px;
                          font-size:.58rem;align-items:center;justify-content:center;font-weight:700">0</span>
                </button>
            </div>

            <div class="topbar-divider" id="offlineIndicatorDivider" style="display:none"></div>

            {{-- Quick actions --}}
            <div class="quick-actions">
                <a href="{{ route('sales.create') }}" class="btn-quick btn-quick-sale" title="নতুন বিক্রয় (Alt+S)">
                    <i class="fas fa-plus"></i><span>নতুন বিক্রয়</span>
                </a>
            </div>

            <div class="topbar-divider"></div>

            {{-- What's New --}}
            @php
                $whatsNew = config('whats_new', []);
            @endphp
            <div class="notif-wrap" id="whatsNewWrap" data-latest-version="{{ $whatsNew[0]['version'] ?? '' }}">
                <button class="ctrl-btn" id="whatsNewBtn" onclick="toggleWhatsNew(event)" title="নতুন কী আছে">
                    <i class="fas fa-gift"></i>
                    <span class="whatsnew-dot" id="whatsNewDot" style="display:none"></span>
                </button>
                <div class="notif-dropdown" id="whatsNewDropdown">
                    <div class="notif-header">
                        <span><i class="fas fa-gift" style="margin-right:6px;color:var(--accent)"></i>নতুন কী আছে</span>
                    </div>
                    <div class="whatsnew-body">
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
                        <div class="notif-empty"><i class="fas fa-gift"></i><div>কোনো আপডেট নেই</div></div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="topbar-divider"></div>

            {{-- Notification Bell --}}
            <div class="notif-wrap" id="notifWrap">
                <button class="ctrl-btn notif-bell-btn" id="notifBtn" onclick="toggleNotif(event)" title="বিজ্ঞপ্তি">
                    <i class="fas fa-bell"></i>
                    @if($notifTotal > 0)
                        <span class="notif-badge">{{ $notifTotal > 9 ? '9+' : $notifTotal }}</span>
                    @endif
                </button>
                <div class="notif-dropdown" id="notifDropdown">
                    <div class="notif-header">
                        <span><i class="fas fa-bell" style="margin-right:6px;color:var(--accent)"></i>বিজ্ঞপ্তি</span>
                        @if($notifTotal > 0)
                            <span class="notif-count-badge">{{ $notifTotal }} টি সতর্কতা</span>
                        @endif
                    </div>
                    <div class="notif-body">
                        @if($notifOutOfStock > 0)
                        <a href="{{ route('stock.low') }}" class="notif-item notif-danger" onclick="closeNotif()">
                            <div class="notif-icon-wrap notif-icon-red"><i class="fas fa-circle-xmark"></i></div>
                            <div class="notif-content">
                                <div class="notif-title">স্টক শেষ</div>
                                <div class="notif-desc">{{ $notifOutOfStock }}টি পণ্যের স্টক শেষ হয়ে গেছে</div>
                            </div>
                            <i class="fas fa-chevron-right notif-arrow"></i>
                        </a>
                        @endif

                        @if($notifLowStock > 0)
                        <a href="{{ route('stock.low') }}" class="notif-item notif-warning" onclick="closeNotif()">
                            <div class="notif-icon-wrap notif-icon-yellow"><i class="fas fa-triangle-exclamation"></i></div>
                            <div class="notif-content">
                                <div class="notif-title">কম স্টক</div>
                                <div class="notif-desc">{{ $notifLowStock }}টি পণ্যের স্টক সীমার নিচে</div>
                            </div>
                            <i class="fas fa-chevron-right notif-arrow"></i>
                        </a>
                        @endif

                        @if($notifCustomersDue > 0)
                        <a href="{{ route('reports.customer-due') }}" class="notif-item notif-info" onclick="closeNotif()">
                            <div class="notif-icon-wrap notif-icon-blue"><i class="fas fa-users"></i></div>
                            <div class="notif-content">
                                <div class="notif-title">কাস্টমার বাকী</div>
                                <div class="notif-desc">{{ $notifCustomersDue }}জনের মোট ৳{{ number_format($notifTotalCustomerDue, 0) }} বাকী</div>
                            </div>
                            <i class="fas fa-chevron-right notif-arrow"></i>
                        </a>
                        @endif

                        @if($notifSuppliersDue > 0)
                        <a href="{{ route('suppliers.due-report') }}" class="notif-item notif-orange" onclick="closeNotif()">
                            <div class="notif-icon-wrap notif-icon-orange"><i class="fas fa-truck"></i></div>
                            <div class="notif-content">
                                <div class="notif-title">সরবরাহকারী বকেয়া</div>
                                <div class="notif-desc">{{ $notifSuppliersDue }}জনকে মোট ৳{{ number_format($notifTotalSupplierDue, 0) }} দেওয়ার বাকী</div>
                            </div>
                            <i class="fas fa-chevron-right notif-arrow"></i>
                        </a>
                        @endif

                        @if(isset($notifPendingApprovals) && $notifPendingApprovals > 0)
                        <a href="{{ route('approvals.index') }}" class="notif-item notif-danger" onclick="closeNotif()">
                            <div class="notif-icon-wrap notif-icon-red"><i class="fas fa-clipboard-check"></i></div>
                            <div class="notif-content">
                                <div class="notif-title">অনুমোদন অপেক্ষায়</div>
                                <div class="notif-desc">{{ $notifPendingApprovals }}টি অনুরোধ আপনার অনুমোদনের অপেক্ষায়</div>
                            </div>
                            <i class="fas fa-chevron-right notif-arrow"></i>
                        </a>
                        @endif

                        @if($notifTotal === 0)
                        <div class="notif-empty">
                            <i class="fas fa-circle-check"></i>
                            <div>সব ঠিকঠাক আছে!</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="topbar-divider"></div>

            {{-- Chat Icon --}}
            <div class="chat-topbar-wrap" id="chatTopbarWrap">
                <a href="{{ route('chat.index') }}" class="ctrl-btn chat-icon-btn" id="chatIconBtn" title="চ্যাট">
                    <i class="fas fa-comments"></i>
                    @if($chatUnread > 0)
                        <span class="chat-badge" id="chatBadge">{{ $chatUnread > 9 ? '9+' : $chatUnread }}</span>
                    @else
                        <span class="chat-badge" id="chatBadge" style="display:none">0</span>
                    @endif
                </a>
            </div>

            <div class="topbar-divider"></div>

            {{-- Calculator --}}
            <button class="ctrl-btn" onclick="miniCalcToggle()" title="ক্যালকুলেটর (Alt+C)">
                <i class="fas fa-calculator"></i>
            </button>

            <div class="topbar-divider"></div>

            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="অনুসন্ধান করুন..." id="globalSearch">
            </div>
            <div class="topbar-time" id="clock">
                <span class="time">--:--</span>
                <span class="date">---</span>
            </div>
        </div>
    </header>

    {{-- Keyboard shortcuts overlay --}}
    <div class="shortcuts-overlay" id="shortcutsOverlay">
        <div class="shortcuts-card">
            <h3><i class="fas fa-keyboard" style="color:var(--accent)"></i> কীবোর্ড শর্টকাট</h3>

            <div class="shortcut-group-title">লেনদেন</div>
            <div class="shortcut-row">
                <a href="{{ route('sales.create') }}" onclick="toggleShortcutsHelp()" style="color:var(--accent);text-decoration:none;font-weight:600">
                    <i class="fas fa-plus" style="font-size:.7rem;margin-right:4px"></i>নতুন বিক্রয়
                </a>
                <span><kbd class="kbd">Alt</kbd> + <kbd class="kbd">S</kbd></span>
            </div>
            <div class="shortcut-row">
                <a href="{{ route('purchases.create') }}" onclick="toggleShortcutsHelp()" style="color:var(--accent);text-decoration:none;font-weight:600">
                    <i class="fas fa-plus" style="font-size:.7rem;margin-right:4px"></i>পণ্য গ্রহণ
                </a>
                <span><kbd class="kbd">Alt</kbd> + <kbd class="kbd">P</kbd></span>
            </div>
            <div class="shortcut-row">
                <span><i class="fas fa-search" style="font-size:.7rem;margin-right:4px;color:var(--text-secondary)"></i>আইটেম খোঁজার ঘরে যান</span>
                <span><kbd class="kbd">Alt</kbd> + <kbd class="kbd">F</kbd></span>
            </div>

            <div class="shortcut-group-title">তালিকা</div>
            <div class="shortcut-row">
                <a href="{{ route('purchases.index') }}" onclick="toggleShortcutsHelp()" style="color:var(--text-primary);text-decoration:none">
                    <i class="fas fa-truck-ramp-box" style="font-size:.7rem;margin-right:4px;color:var(--text-secondary)"></i>পণ্য রিসিভ লিস্ট
                </a>
                <span><kbd class="kbd">Alt</kbd> + <kbd class="kbd">L</kbd></span>
            </div>
            <div class="shortcut-row">
                <a href="{{ route('supplier-payments.index') }}" onclick="toggleShortcutsHelp()" style="color:var(--text-primary);text-decoration:none">
                    <i class="fas fa-money-bill-wave" style="font-size:.7rem;margin-right:4px;color:var(--text-secondary)"></i>পরিশোধ তালিকা
                </a>
                <span><kbd class="kbd">Alt</kbd> + <kbd class="kbd">M</kbd></span>
            </div>

            <div class="shortcut-group-title">নেভিগেশন</div>
            <div class="shortcut-row">
                <a href="{{ route('dashboard') }}" onclick="toggleShortcutsHelp()" style="color:var(--text-primary);text-decoration:none">
                    <i class="fas fa-gauge" style="font-size:.7rem;margin-right:4px;color:var(--text-secondary)"></i>ড্যাশবোর্ড
                </a>
                <span><kbd class="kbd">Alt</kbd> + <kbd class="kbd">D</kbd></span>
            </div>
            <div class="shortcut-row">
                <a href="{{ route('stock.index') }}" onclick="toggleShortcutsHelp()" style="color:var(--text-primary);text-decoration:none">
                    <i class="fas fa-warehouse" style="font-size:.7rem;margin-right:4px;color:var(--text-secondary)"></i>স্টক
                </a>
                <span><kbd class="kbd">Alt</kbd> + <kbd class="kbd">K</kbd></span>
            </div>
            <div class="shortcut-row">
                <a href="{{ route('reports.sales') }}" onclick="toggleShortcutsHelp()" style="color:var(--text-primary);text-decoration:none">
                    <i class="fas fa-chart-line" style="font-size:.7rem;margin-right:4px;color:var(--text-secondary)"></i>দৈনিক বিক্রয় রিপোর্ট
                </a>
                <span><kbd class="kbd">Alt</kbd> + <kbd class="kbd">T</kbd></span>
            </div>

            <div class="shortcut-group-title">সিস্টেম</div>
            <div class="shortcut-row">
                <span>ডার্ক/লাইট মোড</span>
                <span style="color:var(--text-secondary);font-size:.8rem">টপবারে 🌙 বোতাম</span>
            </div>
            <div class="shortcut-row">
                <a href="#" onclick="toggleShortcutsHelp();togglePhonetic();return false;" style="color:var(--text-primary);text-decoration:none">
                    <span style="font-family:'Hind Siliguri',sans-serif;font-weight:700;margin-right:4px">ক</span>বাংলা ফোনেটিক টাইপিং
                </a>
                <span><kbd class="kbd">Alt</kbd> + <kbd class="kbd">B</kbd></span>
            </div>
            <div class="shortcut-row">
                <a href="#" onclick="toggleShortcutsHelp();miniCalcToggle();return false;" style="color:var(--text-primary);text-decoration:none">
                    <i class="fas fa-calculator" style="font-size:.7rem;margin-right:4px;color:var(--text-secondary)"></i>ক্যালকুলেটর
                </a>
                <span><kbd class="kbd">Alt</kbd> + <kbd class="kbd">C</kbd></span>
            </div>
            <div class="shortcut-row">
                <span>এই উইন্ডো</span>
                <span><kbd class="kbd">Alt</kbd> + <kbd class="kbd">/</kbd></span>
            </div>
            <div style="margin-top:16px;text-align:right">
                <button onclick="toggleShortcutsHelp()" class="btn btn-ghost" style="font-size:.82rem">বন্ধ করুন</button>
            </div>
        </div>
    </div>

    {{-- ══ Mini Calculator — persists across Turbo navigations ══ --}}
    <div id="miniCalcRoot" data-turbo-permanent>
        {{-- Selection bubble: appears near any selected number on the page --}}
        <button type="button" id="calcSelBubble" style="display:none">
            <i class="fas fa-calculator"></i> <span id="calcSelNum"></span> যোগ করুন
        </button>

        <div id="miniCalcWin" style="display:none">
            <div class="calc-head">
                <span style="font-weight:700;font-size:.86rem;display:flex;align-items:center;gap:7px">
                    <i class="fas fa-calculator" style="color:var(--accent)"></i> ক্যালকুলেটর
                </span>
                <div style="display:flex;gap:4px">
                    <button type="button" class="calc-head-btn" onclick="calcCopy(this)" title="ফলাফল কপি"><i class="fas fa-copy"></i></button>
                    <button type="button" class="calc-head-btn" onclick="miniCalcToggle()" title="বন্ধ করুন"><i class="fas fa-xmark"></i></button>
                </div>
            </div>
            <input type="text" id="calcExpr" placeholder="যেমন: 50000 + 14000 - 500" autocomplete="off" spellcheck="false">
            <div id="calcResult">&nbsp;</div>
            <div class="calc-keys">
                <button type="button" class="calc-k calc-k-fn" onclick="calcClear()">C</button>
                <button type="button" class="calc-k calc-k-fn" onclick="calcKey('(')">(</button>
                <button type="button" class="calc-k calc-k-fn" onclick="calcKey(')')">)</button>
                <button type="button" class="calc-k calc-k-fn" onclick="calcBack()"><i class="fas fa-delete-left"></i></button>
                <button type="button" class="calc-k" onclick="calcKey('7')">7</button>
                <button type="button" class="calc-k" onclick="calcKey('8')">8</button>
                <button type="button" class="calc-k" onclick="calcKey('9')">9</button>
                <button type="button" class="calc-k calc-k-op" onclick="calcKey('÷')">÷</button>
                <button type="button" class="calc-k" onclick="calcKey('4')">4</button>
                <button type="button" class="calc-k" onclick="calcKey('5')">5</button>
                <button type="button" class="calc-k" onclick="calcKey('6')">6</button>
                <button type="button" class="calc-k calc-k-op" onclick="calcKey('×')">×</button>
                <button type="button" class="calc-k" onclick="calcKey('1')">1</button>
                <button type="button" class="calc-k" onclick="calcKey('2')">2</button>
                <button type="button" class="calc-k" onclick="calcKey('3')">3</button>
                <button type="button" class="calc-k calc-k-op" onclick="calcKey('-')">−</button>
                <button type="button" class="calc-k" onclick="calcKey('0')">0</button>
                <button type="button" class="calc-k" onclick="calcKey('.')">.</button>
                <button type="button" class="calc-k calc-k-op" onclick="calcKey('%')">%</button>
                <button type="button" class="calc-k calc-k-op" onclick="calcKey('+')">+</button>
                <button type="button" class="calc-k calc-k-eq" onclick="calcEquals()">=</button>
            </div>
            <div class="calc-hint"><i class="fas fa-hand-pointer" style="font-size:.65rem"></i> যেকোনো পেজে সংখ্যা সিলেক্ট করলে "যোগ করুন" বাবল আসবে</div>
            <div id="calcHist"></div>
        </div>
    </div>

    <style>
    /* ══ Mini Calculator ══════════════════════════════════════════ */
    #calcSelBubble {
        position: fixed; z-index: 9500; display: none;
        background: var(--accent, #0f9489); color: #fff; border: none; cursor: pointer;
        border-radius: 999px; padding: 6px 14px; font-size: .78rem; font-weight: 700;
        font-family: 'Hind Siliguri', sans-serif;
        box-shadow: 0 4px 16px rgba(15,148,137,.4);
        display: none; align-items: center; gap: 6px; white-space: nowrap;
    }
    #calcSelBubble:hover { filter: brightness(1.08); }
    #miniCalcWin {
        position: fixed; bottom: 86px; right: 88px; width: 288px; z-index: 8000;
        background: var(--surface, #fff); border: 1.5px solid var(--border, #e2e8f0);
        border-radius: 14px; box-shadow: 0 12px 40px rgba(0,0,0,.18);
        display: none; flex-direction: column; padding: 12px;
        font-family: 'Hind Siliguri', sans-serif;
    }
    .calc-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
    .calc-head-btn {
        background: var(--surface-2, #f1f5f9); border: none; cursor: pointer;
        width: 26px; height: 26px; border-radius: 8px; color: var(--text-secondary, #64748b);
        font-size: .75rem; display: flex; align-items: center; justify-content: center;
    }
    .calc-head-btn:hover { background: var(--border, #e2e8f0); color: var(--text-primary, #1e293b); }
    #calcExpr {
        width: 100%; border: 1.5px solid var(--border, #e2e8f0); border-radius: 10px;
        padding: 9px 12px; font-size: 1rem; font-weight: 600; text-align: right;
        background: var(--surface-2, #f8fafc); color: var(--text-primary, #1e293b);
        font-family: inherit; outline: none;
    }
    #calcExpr:focus { border-color: var(--accent, #0f9489); }
    #calcResult {
        text-align: right; font-size: 1.2rem; font-weight: 800; color: var(--accent, #0f9489);
        padding: 6px 4px 8px; min-height: 34px;
    }
    .calc-keys { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; }
    .calc-k {
        border: none; border-radius: 9px; padding: 9px 0; font-size: .95rem; font-weight: 700;
        background: var(--surface-2, #f1f5f9); color: var(--text-primary, #1e293b);
        cursor: pointer; font-family: inherit;
    }
    .calc-k:hover { background: var(--border, #e2e8f0); }
    .calc-k-op { background: #e0f2f1; color: #0f766e; }
    .calc-k-fn { background: #fef3c7; color: #92400e; font-size: .85rem; }
    .calc-k-eq { grid-column: 1 / -1; background: var(--accent, #0f9489); color: #fff; font-size: 1.05rem; }
    .calc-k-eq:hover { filter: brightness(1.06); }
    .calc-hint { font-size: .68rem; color: var(--text-secondary, #94a3b8); margin: 9px 2px 0; }
    #calcHist { margin-top: 6px; max-height: 120px; overflow-y: auto; }
    .calc-hist-row {
        display: flex; justify-content: space-between; gap: 8px; cursor: pointer;
        font-size: .76rem; padding: 5px 6px; border-radius: 7px; color: var(--text-secondary, #64748b);
    }
    .calc-hist-row:hover { background: var(--surface-2, #f1f5f9); }
    .calc-hist-row b { color: var(--text-primary, #1e293b); white-space: nowrap; }
    [data-theme="dark"] .calc-k-op { background: #134e4a; color: #5eead4; }
    [data-theme="dark"] .calc-k-fn { background: #451a03; color: #fbbf24; }
    @media (max-width: 640px) {
        #miniCalcWin { right: 12px; left: 12px; width: auto; bottom: 92px; }
    }
    </style>

    {{-- Offline / Sync banner — persists across Turbo navigations --}}
    <div id="offlineSyncBanner" data-turbo-permanent style="display:none;
         align-items:center;gap:12px;padding:10px 20px;
         background:linear-gradient(90deg,#eff6ff,#dbeafe);
         border-bottom:1.5px solid #93c5fd;color:#1e40af;
         font-size:.82rem;font-weight:600;flex-wrap:wrap">
        <i class="fas fa-wifi-slash" style="font-size:.9rem;color:#1d4ed8"></i>
        <span id="offlineBannerMsg">ইন্টারনেট নেই</span>
        <button id="offlineSyncBtn" onclick="syncOfflineSales()"
                style="display:none;margin-left:auto;background:#1d4ed8;color:#fff;
                       border:none;border-radius:8px;padding:6px 16px;font-size:.78rem;
                       font-weight:700;cursor:pointer;align-items:center;gap:6px">
            <i class="fas fa-rotate"></i> Sync করুন
        </button>
        <button onclick="this.closest('#offlineSyncBanner').style.display='none'"
                style="background:none;border:none;color:#93c5fd;cursor:pointer;
                       font-size:1.1rem;padding:0 4px;line-height:1">×</button>
    </div>

    <main class="content">
        @if(session('success'))
            <div class="alert alert-success" role="alert">
                <i class="fas fa-circle-check" style="flex-shrink:0"></i>
                <span>{{ session('success') }}</span>
                <button class="alert-close" onclick="this.closest('.alert').remove()" aria-label="বন্ধ করুন"><i class="fas fa-xmark"></i></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-error" role="alert">
                <i class="fas fa-circle-exclamation" style="flex-shrink:0"></i>
                <span>{{ session('error') }}</span>
                <button class="alert-close" onclick="this.closest('.alert').remove()" aria-label="বন্ধ করুন"><i class="fas fa-xmark"></i></button>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-error" role="alert">
                <i class="fas fa-circle-exclamation" style="flex-shrink:0;margin-top:2px"></i>
                <ul style="margin:0;padding-left:.8rem;flex:1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button class="alert-close" onclick="this.closest('.alert').remove()" aria-label="বন্ধ করুন"><i class="fas fa-xmark"></i></button>
            </div>
        @endif

        {{-- Print-only header: store name, report title, print date (topbar is hidden on paper).
             Invoices have their own arc header, so they set @section('no-print-header') to skip this. --}}
        @hasSection('no-print-header')
        @else
        <div class="print-only app-print-header">
            <div class="app-print-header-store">{{ \App\Models\StoreConfig::get('store_name', auth()->user()->shop->name ?? 'রিপোর্ট') }}</div>
            <div class="app-print-header-row">
                <span class="app-print-header-title">@yield('page-title', 'রিপোর্ট')</span>
                <span class="app-print-header-date">প্রিন্ট: {{ now()->format('d/m/Y h:i A') }}</span>
            </div>
        </div>
        @endif

        @yield('content')
    </main>
</div>

{{-- ── Delete Confirmation Modal ─────────────────────────────────── --}}
<div class="confirm-modal" id="deleteConfirmModal">
    <div class="confirm-card">
        <div class="confirm-icon">
            <i class="fas fa-trash-can"></i>
        </div>
        <h3 class="confirm-title">মুছে ফেলবেন?</h3>
        <p class="confirm-msg">এই তথ্যটি স্থায়ীভাবে মুছে যাবে এবং আর ফিরিয়ে আনা যাবে না।</p>
        <div class="confirm-actions">
            <button class="btn-confirm-cancel" onclick="_cancelDelete()">
                <i class="fas fa-xmark"></i> বাতিল করুন
            </button>
            <button class="btn-confirm-delete" onclick="_confirmDelete()">
                <i class="fas fa-trash-can"></i> হ্যাঁ, মুছুন
            </button>
        </div>
    </div>
</div>

<script src="{{ asset('js/app.js') }}" data-turbo-eval="false"></script>
<script src="{{ asset('js/offline-sales.js') }}" data-turbo-eval="false"></script>

<script>
/* ── Notification Bell ── */
function toggleNotif(e) {
    e.stopPropagation();
    document.getElementById('notifDropdown').classList.toggle('open');
}
function closeNotif() {
    document.getElementById('notifDropdown').classList.remove('open');
}
document.addEventListener('click', function(e) {
    var wrap = document.getElementById('notifWrap');
    if (wrap && !wrap.contains(e.target)) closeNotif();
});

/* ── What's New panel ── */
function toggleWhatsNew(e) {
    e.stopPropagation();
    var dd = document.getElementById('whatsNewDropdown');
    dd.classList.toggle('open');
    if (dd.classList.contains('open')) {
        var wrap = document.getElementById('whatsNewWrap');
        localStorage.setItem('whatsNewSeen', wrap.getAttribute('data-latest-version'));
        var dot = document.getElementById('whatsNewDot');
        if (dot) dot.style.display = 'none';
    }
}
function closeWhatsNew() {
    var dd = document.getElementById('whatsNewDropdown');
    if (dd) dd.classList.remove('open');
}
document.addEventListener('click', function(e) {
    var wrap = document.getElementById('whatsNewWrap');
    if (wrap && !wrap.contains(e.target)) closeWhatsNew();
});
function _syncWhatsNewDot() {
    var wrap = document.getElementById('whatsNewWrap');
    var dot  = document.getElementById('whatsNewDot');
    if (!wrap || !dot) return;
    var latest = wrap.getAttribute('data-latest-version');
    var seen   = localStorage.getItem('whatsNewSeen');
    dot.style.display = (latest && latest !== seen) ? '' : 'none';
}
document.addEventListener('turbo:load', _syncWhatsNewDot);
_syncWhatsNewDot();

/* ── Sidebar active-link highlighter (runs on every Turbo navigation) ── */
function _syncSidebarActive() {
    var path = window.location.pathname;

    /* Close all accordions, then open only the active one */
    document.querySelectorAll('.sidebar-nav .nav-group').forEach(function(g) {
        g.classList.remove('open');
    });

    /* Strip active classes */
    document.querySelectorAll('.sidebar-nav .nav-item').forEach(function(el) {
        el.classList.remove('active');
    });

    /* Special cases: /customers/{id}/ledger and /suppliers/{id}/ledger */
    var specialTarget = null;
    if (/^\/customers\/\d+\/ledger/.test(path)) {
        specialTarget = '/customers-ledger';
    } else if (/^\/suppliers\/\d+\/ledger/.test(path)) {
        specialTarget = '/suppliers-ledger';
    }

    var bestEl = null, bestLen = -1;
    document.querySelectorAll('.sidebar-nav a.nav-item').forEach(function(a) {
        try {
            var aPath = new URL(a.href, window.location.origin).pathname;
            if (specialTarget) {
                if (aPath === specialTarget) { bestEl = a; bestLen = aPath.length; }
                return;
            }
            var isExact  = (path === aPath);
            var isPrefix = (!isExact && path.startsWith(aPath + '/'));
            if (isExact || isPrefix) {
                if (aPath.length > bestLen) { bestLen = aPath.length; bestEl = a; }
            }
        } catch(e) {}
    });

    if (bestEl) {
        bestEl.classList.add('active');
        var group = bestEl.closest('.nav-group');
        if (group) group.classList.add('open');
    }
}

_syncSidebarActive(); // initial page load
document.addEventListener('turbo:load', _syncSidebarActive);
</script>

@stack('scripts')

<script>
/* ── Global date-range quick-filter helper ─────────────────────
   Usage: drRange(fromInputName, toInputName, formSelector, type)
   type: 'this_month' | 'last_month' | 'this_year' | 'all'
──────────────────────────────────────────────────────────────── */
function drRange(fromName, toName, formSel, type) {
    const today = new Date();
    let from, to;
    if (type === 'this_month') {
        from = new Date(today.getFullYear(), today.getMonth(), 1);
        to   = today;
    } else if (type === 'last_month') {
        from = new Date(today.getFullYear(), today.getMonth() - 1, 1);
        to   = new Date(today.getFullYear(), today.getMonth(), 0);
    } else if (type === 'this_year') {
        from = new Date(today.getFullYear(), 0, 1);
        to   = today;
    } else {
        from = new Date('2000-01-01');
        to   = today;
    }
    // Local date — avoids UTC timezone shift for UTC+6
    const fmt = d => {
        const y   = d.getFullYear();
        const m   = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    };
    document.querySelector(`input[name="${fromName}"]`).value = fmt(from);
    document.querySelector(`input[name="${toName}"]`).value   = fmt(to);
    const f = document.querySelector(formSel);
    if (f.requestSubmit) f.requestSubmit(); else f.submit();
}
</script>

<script>
// ── Bengali Phonetic Typing Engine (Avro-style) ────────────────────────
(function () {
    const HASANTA = '্';
    const VOWELS = {
        "rri":{ ind:"ঋ", kar:"ৃ" },
        "OI": { ind:"ঐ", kar:"ৈ" },
        "OU": { ind:"ঔ", kar:"ৌ" },
        "aa": { ind:"আ", kar:"া" },
        "a":  { ind:"আ", kar:"া" },
        "i":  { ind:"ই", kar:"ি" },
        "I":  { ind:"ঈ", kar:"ী" },
        "u":  { ind:"উ", kar:"ু" },
        "U":  { ind:"ঊ", kar:"ূ" },
        "e":  { ind:"এ", kar:"ে" },
        "O":  { ind:"ও", kar:"ো" },
        "o":  { ind:"অ", kar:"" }
    };
    const CONSONANTS = {
        "kh":"খ","gh":"ঘ","Ng":"ঙ","ch":"ছ","jh":"ঝ","NG":"ঞ",
        "Th":"ঠ","Dh":"ঢ","ph":"ফ","bh":"ভ","dh":"ধ","th":"থ",
        "sh":"শ","Sh":"ষ","Rh":"ঢ়",
        "ng":"ং",
        "k":"ক","g":"গ","c":"চ","j":"জ","T":"ট","D":"ড","N":"ণ",
        "t":"ত","d":"দ","n":"ন","p":"প","f":"ফ","b":"ব","v":"ভ",
        "m":"ম","z":"য","r":"র","l":"ল","S":"শ","s":"স","h":"হ",
        "R":"ড়","y":"য়"
    };
    const SPECIALS = { ",,":"্", "..":"।", "^":"ঁ", ":":"ঃ" };

    const ALL_KEYS = [...Object.keys(VOWELS),...Object.keys(CONSONANTS),...Object.keys(SPECIALS)]
        .sort((a,b)=>b.length-a.length);
    const MAX_LEN = ALL_KEYS.reduce((m,k)=>Math.max(m,k.length),1);

    function lookup(s) {
        for (let len=Math.min(MAX_LEN,s.length); len>=1; len--) {
            const seg=s.slice(0,len);
            if (CONSONANTS[seg]!==undefined) return {type:'cons',key:seg};
            if (VOWELS[seg]!==undefined)     return {type:'vowel',key:seg};
            if (SPECIALS[seg]!==undefined)   return {type:'special',key:seg};
        }
        return null;
    }

    function translitPart(text) {
        let out='', prevCons=false, i=0;
        while (i<text.length) {
            const m=lookup(text.slice(i));
            if (m) {
                if (m.type==='cons') {
                    if (prevCons) out+=HASANTA;
                    out+=CONSONANTS[m.key]; prevCons=true;
                } else if (m.type==='vowel') {
                    out+=prevCons ? VOWELS[m.key].kar : VOWELS[m.key].ind;
                    prevCons=false;
                } else {
                    out+=SPECIALS[m.key]; prevCons=false;
                }
                i+=m.key.length;
            } else { out+=text[i]; prevCons=false; i++; }
        }
        return out;
    }

    // ── Per-input state attached directly to DOM element ──
    const BREAK = /[\s,।!?;'"()\[\]]/;

    function attachPhonetic(el) {
        if (el._ph) return;
        el._phBuf    = '';    // phonetic buffer for current word
        el._phStart  = 0;     // value-index where current word started
        el._phAnchor = 0;     // cursor pos expected right after our last edit

        function commit(buf) {
            const before = el.value.slice(0, el._phStart);
            // slice from selectionEnd so any selected text is consumed/replaced,
            // matching normal typing behaviour (select "নাবিল" + type → replaces it)
            const after  = el.value.slice(el.selectionEnd);
            const conv   = translitPart(buf);
            el.value     = before + conv + after;
            const p      = el._phStart + conv.length;
            el.setSelectionRange(p, p);
            el._phAnchor = p;
            el.dispatchEvent(new Event('input', { bubbles: true }));
        }

        el._phKey = function(e) {
            if (!window._phoneticActive) return;
            if (e.ctrlKey||e.metaKey||e.altKey) return;

            if (e.key.length===1) {
                if (BREAK.test(e.key)) {
                    // word-break char: commit buffer (already converted in value), reset
                    el._phBuf='';
                    // after char is inserted, advance wordStart
                    setTimeout(()=>{ el._phStart=el.selectionStart; el._phAnchor=el.selectionStart; },0);
                } else {
                    e.preventDefault();
                    // active text selection → this keystroke replaces it: drop the
                    // stale buffer and start a fresh word at the selection start
                    if (el.selectionStart !== el.selectionEnd) {
                        el._phBuf='';
                        el._phStart=el.selectionStart;
                    }
                    el._phBuf+=e.key;
                    commit(el._phBuf);
                }
            } else if (e.key==='Backspace') {
                if (el.selectionStart !== el.selectionEnd) {
                    // selection delete — let the browser remove the range, just
                    // reset our word tracking so the next keystroke starts clean
                    el._phBuf='';
                    setTimeout(()=>{ el._phStart=el.selectionStart; el._phAnchor=el.selectionStart; },0);
                } else if (el._phBuf.length>0) {
                    e.preventDefault();
                    el._phBuf=el._phBuf.slice(0,-1);
                    commit(el._phBuf);
                }
            } else if (['ArrowLeft','ArrowRight','ArrowUp','ArrowDown','Home','End'].includes(e.key)) {
                el._phBuf='';
                setTimeout(()=>{ el._phStart=el.selectionStart; el._phAnchor=el.selectionStart; },0);
            }
        };
        el._phReset = function() {
            const cur = el.selectionStart ?? el.value.length;
            // Cursor is exactly where our last edit left it — this focus/click is
            // spurious (e.g. a dropdown re-render stealing focus mid-word), not a
            // real reposition by the user. Keep the in-progress buffer intact so we
            // don't re-insert the conversion on top of itself (e.g. "না" → "ননা").
            if (cur === el._phAnchor && el._phBuf) return;
            el._phBuf    = '';
            el._phStart  = cur;
            el._phAnchor = cur;
        };
        el.addEventListener('keydown', el._phKey);
        el.addEventListener('focus',   el._phReset);
        el.addEventListener('click',   el._phReset);
        el._ph=true;
    }

    function attachAll() {
        document.querySelectorAll('input[type="text"],input:not([type]),textarea')
            .forEach(el => {
                const im = el.getAttribute('inputmode');
                if (im === 'decimal' || im === 'numeric') return;
                attachPhonetic(el);
            });
    }

    // Toggle — preserve state across Turbo re-runs of this body script;
    // restore the saved preference on first init (was unconditionally reset
    // to false on every navigation, fighting the localStorage restore).
    if (typeof window._phoneticActive === 'undefined') {
        window._phoneticActive = localStorage.getItem('phoneticMode') === '1';
    }

    function applyPhoneticUI(active) {
        attachAll();
        const btn=document.getElementById('phoneticBtn');
        if (btn) {
            btn.style.background = active ? 'var(--accent)' : '';
            btn.style.color      = active ? '#fff' : '';
            btn.title = active
                ? 'বাংলা ফোনেটিক চালু — বন্ধ করতে ক্লিক করুন'
                : 'বাংলা ফোনেটিক টাইপিং চালু করুন (Alt+B)';
        }
        document.querySelectorAll('input[type="text"],input:not([type]),textarea')
            .forEach(el=>{
                if (el.getAttribute('inputmode') === 'decimal' ||
                    el.getAttribute('inputmode') === 'numeric') return;
                el.style.borderColor = active ? 'var(--accent)' : '';
            });
    }

    window.togglePhonetic = function() {
        window._phoneticActive = !window._phoneticActive;
        localStorage.setItem('phoneticMode', window._phoneticActive ? '1' : '0');
        applyPhoneticUI(window._phoneticActive);
    };

    // Restore saved state + re-attach to new inputs on every Turbo navigation.
    // Bind ONCE — this body script re-runs on every Turbo visit, so an unguarded
    // addEventListener here stacked a fresh listener on every navigation. Over a
    // long session (many page changes) dozens piled up, making the ক toggle
    // erratic and unresponsive. A single persistent listener still fires on every
    // turbo:load, which is all we need.
    if (!window._phoneticBound) {
        window._phoneticBound = true;
        document.addEventListener('turbo:load', function() {
            window._phoneticActive = localStorage.getItem('phoneticMode') === '1';
            applyPhoneticUI(window._phoneticActive);
            attachAll();
        });
        document.addEventListener('click', function(e) {
            const panel = document.getElementById('phoneticHelpPanel');
            if (panel && !panel.contains(e.target)) panel.style.display = 'none';
        });
    }

    // Alt+B shortcut is bound once in app.js (data-turbo-eval="false") — NOT
    // here, because this body script re-runs on every Turbo navigation and a
    // keydown listener added here would stack into duplicates.

    // Cheat sheet panel toggle
    window.togglePhoneticHelp = function(e) {
        e.stopPropagation();
        const panel = document.getElementById('phoneticHelpPanel');
        panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
    };
    // (outside-click handler to close the panel is now bound once, above)

    // Also attach when new inputs appear (modals, dynamic rows)
    const obs=new MutationObserver(()=>{ if(window._phoneticActive) attachAll(); });
    if (!window._phoneticObsStarted) {
        window._phoneticObsStarted = true;
        document.addEventListener('turbo:load', ()=>{
            obs.observe(document.body,{childList:true,subtree:true});
        }, { once: true });
    }
})();

// ── Compact table toggle — re-runs on every Turbo navigation.
//    Exposed as window.setupCompactTables(root) so pages that toggle between
//    hidden/visible sections (e.g. a view switcher with display:none) can
//    re-run it on a subtree AFTER making it visible — tables measured while
//    hidden report offsetHeight=0 and never get the expand button otherwise. ──
window.setupCompactTables = function (root) {
    (root || document).querySelectorAll('.card').forEach(function (card) {
        const header = card.querySelector('.card-header');
        const wrap   = card.querySelector('.table-wrap');
        if (!header || !wrap) return;
        const table = wrap.querySelector('table');
        if (!table) return;
        // Already processed (re-invocation on the same subtree) — skip.
        if (wrap.querySelector(':scope > .tbl-y-scroll')) return;

        const COMPACT_MAX = 340; // keep in sync with .tbl-y-scroll.tbl-compact max-height

        // Only apply compact behaviour to tables that actually overflow.
        // Small tables that already fit stay normal — no scroll, no button.
        // IMPORTANT: check BEFORE wrapping — a table inside a display:none
        // ancestor reports offsetHeight=0 here, so it's left unwrapped
        // (no marker), meaning a later re-run (after it becomes visible)
        // still gets a fair, correctly-measured pass instead of being
        // permanently skipped by the "already processed" guard above.
        if (table.offsetHeight <= COMPACT_MAX) return;

        // Wrap table in inner scroll container.
        // When compact: wrap.overflow=visible so it's not a scroll container,
        // letting position:sticky anchor correctly to inner (the real scroller).
        const inner = document.createElement('div');
        inner.className = 'tbl-y-scroll';
        wrap.insertBefore(inner, table);
        inner.appendChild(table);

        function setCompact(compact) {
            if (compact) {
                inner.classList.add('tbl-compact');
                wrap.style.overflow = 'visible';
            } else {
                inner.classList.remove('tbl-compact');
                wrap.style.overflow = '';
            }
        }

        // Start compact
        setCompact(true);

        const btn = document.createElement('button');
        btn.type      = 'button';
        btn.className = 'tbl-toggle-btn no-print';
        btn.title     = 'প্রসারিত করুন';
        btn.innerHTML = '<i class="fas fa-expand-alt"></i>';

        btn.addEventListener('click', function () {
            const nowCompact = !inner.classList.contains('tbl-compact');
            setCompact(nowCompact);
            btn.querySelector('i').className = nowCompact
                ? 'fas fa-expand-alt'
                : 'fas fa-compress-alt';
            btn.title = nowCompact ? 'প্রসারিত করুন' : 'সংকুচিত করুন';
        });

        header.appendChild(btn);
    });
};
document.addEventListener('turbo:load', function () { window.setupCompactTables(document); });
</script>

{{-- ══ Multimedia Popup Player ══════════════════════════════ --}}
@php
    $mmEnabled  = \App\Models\StoreConfig::get('multimedia_enabled', '0') === '1';
    $mmInterval = (int) \App\Models\StoreConfig::get('multimedia_interval', '5');
    $mmFiles    = $mmEnabled ? \App\Http\Controllers\StoreConfigController::getMultimediaFiles() : [];
@endphp
@if($mmEnabled && count($mmFiles) > 0)
<div id="mmPopup">
    <div id="mmPopupHeader">
        <span id="mmPopupTitle"><i class="fas fa-photo-film"></i> মিডিয়া</span>
        <div style="display:flex;gap:6px">
            <button class="mm-popup-btn" id="mmPlayPauseBtn" onclick="togglePlayPause()" title="চালু/বন্ধ"><i class="fas fa-pause" id="mmPlayPauseIcon"></i></button>
            <button class="mm-popup-btn" onclick="toggleFullscreen()" title="ফুলস্ক্রিন"><i class="fas fa-expand" id="mmFullIcon"></i></button>
            <button class="mm-popup-btn" onclick="minimizePopup()" title="ছোট করুন"><i class="fas fa-minus"></i></button>
            <button class="mm-popup-btn" onclick="closePopup()" title="বন্ধ করুন"><i class="fas fa-times"></i></button>
        </div>
    </div>
    <div id="mmMediaWrap">
        @foreach($mmFiles as $i => $f)
        @if($f['type'] === 'video')
        <video id="mmSlide_{{ $i }}" class="mm-slide" style="{{ $i > 0 ? 'display:none' : '' }}"
            src="{{ $f['url'] }}" playsinline></video>
        @elseif($f['type'] === 'audio')
        <div id="mmSlide_{{ $i }}" class="mm-slide mm-audio-slide" style="{{ $i > 0 ? 'display:none' : '' }}" data-src="{{ $f['url'] }}">
            <div class="mm-audio-vis">
                <div class="mm-audio-wave">
                    <span></span><span></span><span></span><span></span><span></span>
                    <span></span><span></span><span></span><span></span><span></span>
                </div>
                <i class="fas fa-music mm-audio-note"></i>
                <div class="mm-audio-fname">{{ Str::limit($f['filename'], 30) }}</div>
            </div>
            <audio id="mmAudio_{{ $i }}" src="{{ $f['url'] }}"></audio>
        </div>
        @else
        <img id="mmSlide_{{ $i }}" class="mm-slide" style="{{ $i > 0 ? 'display:none' : '' }}"
            src="{{ $f['url'] }}" alt="">
        @endif
        @endforeach
        {{-- Navigation overlay --}}
        <button class="mm-nav mm-nav-prev" onclick="prevSlide()"><i class="fas fa-chevron-left"></i></button>
        <button class="mm-nav mm-nav-next" onclick="nextSlide()"><i class="fas fa-chevron-right"></i></button>
        {{-- Dots --}}
        <div id="mmDots">
            @foreach($mmFiles as $i => $f)
            <span class="mm-dot {{ $i === 0 ? 'active' : '' }}" onclick="goToSlide({{ $i }})"></span>
            @endforeach
        </div>
    </div>
</div>
{{-- Minimized button --}}
<button id="mmRestoreBtn" style="display:none" onclick="restorePopup()" title="মিডিয়া খুলুন">
    <i class="fas fa-photo-film"></i>
</button>

<style>
#mmPopup {
    position: fixed;
    bottom: 24px;
    right: 24px;
    width: 320px;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 12px 40px rgba(0,0,0,.35);
    z-index: 8888;
    background: #000;
    transition: all .3s;
    user-select: none;
}
#mmPopup.fullscreen {
    bottom: 0; right: 0;
    width: 100vw; height: 100vh;
    border-radius: 0;
    z-index: 9990;
}
#mmPopupHeader {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 12px;
    background: rgba(0,0,0,.85);
    cursor: move;
    gap: 8px;
}
#mmPopupTitle { font-size: .78rem; font-weight: 600; color: #e2e8f0; display:flex; align-items:center; gap:6px; }
.mm-popup-btn {
    background: rgba(255,255,255,.12);
    border: none;
    color: #e2e8f0;
    width: 24px; height: 24px;
    border-radius: 6px;
    cursor: pointer;
    font-size: .75rem;
    display: flex; align-items: center; justify-content: center;
    transition: background .15s;
}
.mm-popup-btn:hover { background: rgba(255,255,255,.25); }
#mmMediaWrap {
    position: relative;
    width: 100%;
    aspect-ratio: 16/9;
    background: #000;
    overflow: hidden;
}
#mmPopup.fullscreen #mmMediaWrap {
    height: calc(100vh - 42px);
    aspect-ratio: unset;
}
.mm-slide {
    width: 100%;
    height: 100%;
    object-fit: contain;
    display: block;
}
/* Navigation */
.mm-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(0,0,0,.45);
    border: none;
    color: #fff;
    width: 34px; height: 34px;
    border-radius: 50%;
    cursor: pointer;
    font-size: .9rem;
    display: flex; align-items: center; justify-content: center;
    opacity: 0;
    transition: opacity .2s;
    z-index: 2;
}
#mmMediaWrap:hover .mm-nav { opacity: 1; }
.mm-nav-prev { left: 8px; }
.mm-nav-next { right: 8px; }
/* Dots */
#mmDots {
    position: absolute;
    bottom: 8px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 5px;
    z-index: 2;
}
.mm-dot {
    width: 7px; height: 7px;
    border-radius: 50%;
    background: rgba(255,255,255,.45);
    cursor: pointer;
    transition: background .2s, transform .2s;
}
.mm-dot.active { background: #fff; transform: scale(1.25); }
/* Restore button */
#mmRestoreBtn {
    position: fixed;
    bottom: 24px;
    right: 24px;
    width: 48px; height: 48px;
    border-radius: 50%;
    background: var(--accent, #0d9488);
    color: #fff;
    border: none;
    cursor: pointer;
    font-size: 1.1rem;
    box-shadow: 0 4px 16px rgba(0,0,0,.25);
    z-index: 8888;
    display: flex; align-items: center; justify-content: center;
    transition: transform .2s;
}
#mmRestoreBtn:hover { transform: scale(1.1); }
/* Drag cursor */
#mmPopup.dragging { opacity: .92; cursor: grabbing; }
</style>

<script>
var mmSlides   = @json($mmFiles);
var mmInterval = {{ $mmInterval * 1000 }};
var mmCurrent    = 0;
var mmTimer      = null;
var mmDragging   = false;

var MM_KEY      = 'mm_slide_idx';
var MM_TIME_KEY = 'mm_slide_time';
var mmPaused = false;

function setPlayPauseIcon(paused) {
    const icon = document.getElementById('mmPlayPauseIcon');
    if (icon) icon.className = paused ? 'fas fa-play' : 'fas fa-pause';
}

function togglePlayPause() {
    mmPaused = !mmPaused;
    setPlayPauseIcon(mmPaused);
    const current = document.getElementById('mmSlide_' + mmCurrent);
    if (!current) return;

    if (mmPaused) {
        // Pause whatever is playing
        if (current.tagName === 'VIDEO') { current.pause(); }
        const aud = current.querySelector('audio');
        if (aud) { aud.pause(); }
        clearTimeout(mmTimer);
    } else {
        // Resume
        if (current.tagName === 'VIDEO') { current.play().catch(() => {}); }
        else if (current.classList.contains('mm-audio-slide')) {
            const aud = current.querySelector('audio');
            if (aud) { aud.play().catch(() => {}); }
        } else {
            mmTimer = setTimeout(nextSlide, mmInterval);
        }
    }
}

// Seek media to `time` then play — waits for metadata + seeked confirmation
function mmSeekAndPlay(media, time) {
    media.ontimeupdate = () => localStorage.setItem(MM_TIME_KEY, media.currentTime);
    media.onended      = () => { localStorage.removeItem(MM_TIME_KEY); nextSlide(); };

    const doSeek = () => {
        if (time > 0) {
            media.currentTime = time;
            // Wait for seek to complete before playing
            media.addEventListener('seeked', () => media.play().catch(() => {}), { once: true });
        } else {
            media.play().catch(() => {});
        }
    };

    if (media.readyState >= 1) {   // metadata already available — seek immediately
        doSeek();
    } else {
        media.addEventListener('loadedmetadata', doSeek, { once: true });
    }
}

function showSlide(idx, resumeTime) {
    document.querySelectorAll('.mm-slide').forEach((el, i) => {
        el.style.display = i === idx ? 'block' : 'none';
        if (el.tagName === 'VIDEO') { el.pause(); }
        const aud = el.querySelector('audio');
        if (aud) { aud.pause(); }
    });
    document.querySelectorAll('.mm-dot').forEach((d, i) => d.classList.toggle('active', i === idx));
    mmCurrent = idx;
    localStorage.setItem(MM_KEY, idx);
    if (resumeTime === undefined) localStorage.removeItem(MM_TIME_KEY);
    clearTimeout(mmTimer);

    // If manually paused, just show the frame — don't auto-play
    if (mmPaused) { setPlayPauseIcon(true); return; }

    const current = document.getElementById('mmSlide_' + idx);
    if (current && current.tagName === 'VIDEO') {
        mmSeekAndPlay(current, resumeTime > 0 ? resumeTime : 0);
    } else if (current && current.classList.contains('mm-audio-slide')) {
        const aud = current.querySelector('audio');
        if (aud) mmSeekAndPlay(aud, resumeTime > 0 ? resumeTime : 0);
    } else {
        mmTimer = setTimeout(nextSlide, mmInterval);
    }
    setPlayPauseIcon(false);
}

function nextSlide() { showSlide((mmCurrent + 1) % mmSlides.length); }
function prevSlide() { showSlide((mmCurrent - 1 + mmSlides.length) % mmSlides.length); }
function goToSlide(i) { showSlide(i); }

function closePopup() {
    document.getElementById('mmPopup').style.display = 'none';
    document.getElementById('mmRestoreBtn').style.display = 'flex';
    clearTimeout(mmTimer);
    // Stop any playing video or audio
    document.querySelectorAll('.mm-slide video, .mm-slide audio').forEach(m => m.pause());
}
function restorePopup() {
    document.getElementById('mmPopup').style.display = 'block';
    document.getElementById('mmRestoreBtn').style.display = 'none';
    mmPaused = false;
    setPlayPauseIcon(false);
    showSlide(mmCurrent);
}
function minimizePopup() { closePopup(); }

var isFullscreen = false;
function toggleFullscreen() {
    const popup = document.getElementById('mmPopup');
    isFullscreen = !isFullscreen;
    popup.classList.toggle('fullscreen', isFullscreen);
    document.getElementById('mmFullIcon').className = isFullscreen ? 'fas fa-compress' : 'fas fa-expand';
}

// Draggable
(function() {
    const popup  = document.getElementById('mmPopup');
    const header = document.getElementById('mmPopupHeader');
    let ox = 0, oy = 0, startX = 0, startY = 0;

    header.addEventListener('mousedown', e => {
        if (isFullscreen) return;
        e.preventDefault();
        const rect = popup.getBoundingClientRect();
        // Switch to absolute positioning for drag
        popup.style.bottom = 'auto';
        popup.style.right  = 'auto';
        popup.style.left   = rect.left + 'px';
        popup.style.top    = rect.top  + 'px';
        startX = e.clientX; startY = e.clientY;
        popup.classList.add('dragging');

        function onMove(e) {
            popup.style.left = (rect.left + e.clientX - startX) + 'px';
            popup.style.top  = (rect.top  + e.clientY - startY) + 'px';
        }
        function onUp() {
            popup.classList.remove('dragging');
            document.removeEventListener('mousemove', onMove);
            document.removeEventListener('mouseup', onUp);
        }
        document.addEventListener('mousemove', onMove);
        document.addEventListener('mouseup', onUp);
    });
})();

// Save position before Turbo navigates away (beforeunload doesn't fire for Turbo)
document.addEventListener('turbo:before-visit', () => {
    const current = document.getElementById('mmSlide_' + mmCurrent);
    if (!current) return;
    if (current.tagName === 'VIDEO' && current.currentTime > 0) {
        localStorage.setItem(MM_TIME_KEY, current.currentTime);
    }
    const aud = current.querySelector('audio');
    if (aud && aud.currentTime > 0) {
        localStorage.setItem(MM_TIME_KEY, aud.currentTime);
    }
});

// Start slideshow — resume from last known slide + audio/video position
document.addEventListener('turbo:load', () => {
    if (!mmSlides.length) return;
    const savedIdx  = parseInt(localStorage.getItem(MM_KEY)  ?? '0', 10);
    const savedTime = parseFloat(localStorage.getItem(MM_TIME_KEY) ?? '0');
    const start     = (savedIdx >= 0 && savedIdx < mmSlides.length) ? savedIdx : 0;
    // Pass savedTime only when > 0 so video/audio resumes at exact position
    showSlide(start, savedTime > 0 ? savedTime : undefined);
});
</script>
@endif

{{-- ══ WebSocket Bootstrap (Reverb locally / Pusher on production) ══ --}}
@auth
@php
    $wsBroadcaster = config('broadcasting.default', 'log');
    $wsUseReverb   = $wsBroadcaster === 'reverb';
    $wsUsePusher   = $wsBroadcaster === 'pusher';
@endphp
@if($wsUseReverb || $wsUsePusher)
<script>
(function waitForPusher() {
    if (typeof Pusher === 'undefined') { return setTimeout(waitForPusher, 50); }

    @if($wsUseReverb)
    // ── Local: Laravel Reverb ────────────────────────────────
    var pusher = new Pusher('{{ env('REVERB_APP_KEY') }}', {
        wsHost:            '{{ env('REVERB_HOST', 'localhost') }}',
        wsPort:             {{ env('REVERB_PORT', 8080) }},
        wssPort:            {{ env('REVERB_PORT', 8080) }},
        forceTLS:           {{ env('REVERB_SCHEME', 'http') === 'https' ? 'true' : 'false' }},
        enabledTransports:  ['ws', 'wss'],
        cluster:            'mt1',
        authEndpoint:       '/broadcasting/auth',
        auth: { headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content } },
    });
    @else
    // ── Production: Pusher Cloud ─────────────────────────────
    var pusher = new Pusher('{{ env('PUSHER_APP_KEY') }}', {
        cluster:      '{{ env('PUSHER_APP_CLUSTER', 'mt1') }}',
        forceTLS:     true,
        authEndpoint: '/broadcasting/auth',
        auth: { headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content } },
    });
    @endif

    var channel = pusher.subscribe('private-chat.user.{{ auth()->id() }}');

    channel.bind('pusher:subscription_error', function(err) {
        console.warn('[WS] subscription error:', err);
    });
    channel.bind('message.sent', function(data) {
        window.dispatchEvent(new CustomEvent('ws:message', { detail: data }));
    });

    window._ws = pusher;
})();
</script>
@endif
@endauth

{{-- ══ Mini Chat Widget ═══════════════════════════════════════════ --}}
<div id="miniChatRoot" class="mc-collapsed" data-turbo-permanent>

    {{-- Collapsed FAB — tucked as a thin tab at the right edge; hover slides it
         out, click opens the chat --}}
    <button id="miniChatFab" onclick="miniChatToggle()" title="চ্যাট খুলুন">
        <i class="fas fa-comments" id="miniChatFabIcon"></i>
        <span class="mc-fab-badge" id="mcFabBadge" style="{{ $chatUnread > 0 ? '' : 'display:none' }}">
            {{ $chatUnread > 9 ? '9+' : $chatUnread }}
        </span>
    </button>

    {{-- Mini chat window --}}
    <div id="miniChatWin" style="display:none">
        {{-- === USER LIST panel === --}}
        <div id="mcPanelUsers">
            <div class="mc-head">
                <span style="font-weight:700;font-size:.86rem;display:flex;align-items:center;gap:7px">
                    <i class="fas fa-comments" style="color:var(--accent)"></i> চ্যাট
                </span>
                <div style="display:flex;gap:4px">
                    <a href="{{ route('chat.index') }}" class="mc-head-btn" title="পূর্ণ পর্দা" style="text-decoration:none">
                        <i class="fas fa-expand"></i>
                    </a>
                    <button class="mc-head-btn" onclick="miniChatToggle()" title="বন্ধ করুন">
                        <i class="fas fa-xmark"></i>
                    </button>
                </div>
            </div>
            <div id="mcUserList" class="mc-list">
                <div class="mc-loading"><i class="fas fa-spinner fa-spin"></i></div>
            </div>
        </div>

        {{-- === CONVERSATION panel === --}}
        <div id="mcPanelConv" style="display:none">
            <div class="mc-head">
                <button class="mc-head-btn" onclick="mcBackToUsers()" title="ফিরে যান" style="margin-right:4px">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <div class="mc-avatar-sm" id="mcConvAvatar">?</div>
                <span id="mcConvName" style="font-weight:700;font-size:.85rem;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"></span>
                <button class="mc-head-btn" onclick="miniChatToggle()" title="বন্ধ করুন">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>
            <div id="mcMessages" class="mc-messages"></div>
            <div class="mc-input-bar">
                <textarea id="mcInput" class="mc-input" rows="1" placeholder="বার্তা লিখুন..."
                    maxlength="2000" onkeydown="mcKeyDown(event)"></textarea>
                <button class="mc-send-btn" onclick="mcSend()" id="mcSendBtn">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
            <div class="mc-hint-row">
                <span id="mcHintText" style="font-size:.68rem;color:#94a3b8">Enter → পাঠান</span>
                <span class="chat-inline-loader" id="mcSendLoader">
                    <span></span><span></span><span></span>
                    <span style="margin-left:3px;font-size:.68rem;color:var(--accent)">পাঠানো হচ্ছে</span>
                </span>
            </div>
        </div>
    </div>
</div>

<style>
/* ══ FAB ══════════════════════════════════════════════════════ */
#miniChatFab {
    position: fixed;
    bottom: 24px; right: 24px;
    width: 52px; height: 52px;
    border-radius: 50%;
    background: var(--accent, #0f9489);
    color: #fff;
    border: none; cursor: pointer;
    font-size: 1.15rem;
    box-shadow: 0 4px 20px rgba(15,148,137,.45);
    z-index: 8000;
    display: flex; align-items: center; justify-content: center;
    transition: right .22s ease, border-radius .22s ease, padding .22s ease, transform .15s, box-shadow .15s;
}
#miniChatFab:hover { transform: scale(1.08); box-shadow: 0 6px 28px rgba(15,148,137,.55); }

/* ══ Collapsed = thin tab tucked against the right browser edge ═══
   Keeps the bubble out of the content's way; hover slides it fully out,
   click opens the chat. `.mc-open` (set while the chat window is up) forces
   the full bubble regardless of hover. ── */
#miniChatRoot.mc-collapsed #miniChatFab {
    right: -30px;                       /* ~30px hides past the edge → thin peeking tab */
    justify-content: flex-start;
    padding-left: 6px;
    border-radius: 26px 0 0 26px;       /* round the left side only — tab look */
    box-shadow: -3px 4px 16px rgba(15,148,137,.42);
}
#miniChatRoot.mc-collapsed #miniChatFab:hover,
#miniChatRoot.mc-collapsed #miniChatFab:focus-visible,
#miniChatRoot.mc-open #miniChatFab {
    right: 16px;
    justify-content: center;
    padding-left: 0;
    border-radius: 50%;
    box-shadow: 0 6px 28px rgba(15,148,137,.55);
}
/* Push FAB above fixed submit bars so it doesn't overlap */
body:has(.sale-submit-bar) #miniChatFab { bottom: 82px; transition: bottom .2s; }
body.txn-summary-active  #miniChatFab { bottom: 158px; }
.mc-fab-badge {
    position: absolute; top: -3px; right: -3px;
    min-width: 18px; height: 18px;
    background: #ef4444; color: #fff;
    border-radius: 999px; font-size: .65rem; font-weight: 800;
    display: flex; align-items: center; justify-content: center;
    padding: 0 3px;
    border: 2px solid var(--surface, #fff);
    transition: left .22s ease, right .22s ease, top .22s ease;
}
/* When collapsed, the right side is off-screen — move the unread badge to the
   visible (left) sliver so the count still shows. Hover/open restores it. */
#miniChatRoot.mc-collapsed #miniChatFab .mc-fab-badge { right: auto; left: -4px; top: -5px; }
#miniChatRoot.mc-collapsed #miniChatFab:hover .mc-fab-badge,
#miniChatRoot.mc-collapsed #miniChatFab:focus-visible .mc-fab-badge,
#miniChatRoot.mc-open #miniChatFab .mc-fab-badge { left: auto; right: -3px; top: -3px; }

/* ══ Window ═══════════════════════════════════════════════════ */
#miniChatWin {
    position: fixed;
    bottom: 86px; right: 24px;
    width: 320px;
    height: 440px;
    border-radius: 14px;
    background: var(--surface, #fff);
    border: 1.5px solid var(--border, #e2e8f0);
    box-shadow: 0 12px 40px rgba(0,0,0,.18);
    z-index: 7999;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: mcSlideUp .2s ease;
}
@keyframes mcSlideUp {
    from { opacity:0; transform: translateY(12px) scale(.97); }
    to   { opacity:1; transform: translateY(0) scale(1); }
}

/* Panels fill the window */
#mcPanelUsers, #mcPanelConv {
    display: flex; flex-direction: column;
    height: 100%;
}

/* ══ Header ═══════════════════════════════════════════════════ */
.mc-head {
    display: flex; align-items: center; gap: 6px;
    padding: 10px 12px;
    border-bottom: 1.5px solid var(--border, #e2e8f0);
    background: var(--bg, #f8fafc);
    flex-shrink: 0;
}
.mc-head-btn {
    width: 26px; height: 26px;
    border: none; background: transparent;
    border-radius: 6px; cursor: pointer;
    color: #94a3b8; font-size: .78rem;
    display: flex; align-items: center; justify-content: center;
    transition: background .1s, color .1s;
    flex-shrink: 0;
}
.mc-head-btn:hover { background: var(--border); color: var(--text-primary); }

/* ══ User list ════════════════════════════════════════════════ */
.mc-list { flex: 1; overflow-y: auto; }
.mc-loading { padding: 24px; text-align: center; color: #94a3b8; }
.mc-user-row {
    display: flex; align-items: center; gap: 9px;
    padding: 9px 12px;
    border-bottom: 1px solid var(--border);
    cursor: pointer;
    transition: background .1s;
}
.mc-user-row:hover { background: var(--bg); }
.mc-user-row:last-child { border-bottom: none; }
.mc-avatar {
    width: 34px; height: 34px;
    border-radius: 50%;
    color: #fff; font-weight: 700; font-size: .82rem;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.mc-avatar-sm {
    width: 26px; height: 26px;
    border-radius: 50%;
    background: #3b82f6;
    color: #fff; font-weight: 700; font-size: .68rem;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.mc-user-meta { flex: 1; min-width: 0; }
.mc-user-name { font-size: .82rem; font-weight: 700; color: var(--text-primary); }
.mc-user-last {
    font-size: .72rem; color: #94a3b8;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    margin-top: 1px;
}
.mc-unread {
    min-width: 18px; height: 18px;
    background: var(--accent); color: #fff;
    border-radius: 999px; font-size: .64rem; font-weight: 800;
    display: flex; align-items: center; justify-content: center;
    padding: 0 3px; flex-shrink: 0;
}

/* ══ Messages ═════════════════════════════════════════════════ */
.mc-messages {
    flex: 1; overflow-y: auto;
    padding: 10px 10px 4px;
    display: flex; flex-direction: column;
    gap: 3px;
    background: var(--bg);
}
.mc-date-div {
    text-align: center;
    font-size: .66rem; color: #94a3b8;
    margin: 6px 0 4px;
    position: relative;
}
.mc-date-div::before {
    content:''; position:absolute; top:50%; left:0; right:0;
    height:1px; background:var(--border);
}
.mc-date-div span { background: var(--bg); padding: 0 8px; position: relative; }
.mc-bubble-row {
    display: flex; align-items: flex-end; gap: 5px;
    margin-bottom: 2px;
}
.mc-bubble-row.mine  { flex-direction: row-reverse; }
.mc-bubble {
    max-width: 75%;
    padding: 7px 11px;
    border-radius: 14px;
    font-size: .81rem;
    line-height: 1.5;
    word-break: break-word;
    white-space: pre-wrap;
}
.mine  .mc-bubble { background: var(--accent); color: #fff; border-bottom-right-radius: 4px; }
.theirs .mc-bubble { background: var(--surface); color: var(--text-primary); border: 1.5px solid var(--border); border-bottom-left-radius: 4px; }
.mc-time { font-size: .62rem; color: #94a3b8; margin-top: 2px; }
.mine .mc-time { text-align: right; }
.mc-empty-conv { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; color:#94a3b8; font-size:.78rem; gap:6px; }
.mc-empty-conv i { font-size: 1.5rem; color: #cbd5e1; }

/* ══ Input ════════════════════════════════════════════════════ */
.mc-input-bar {
    display: flex; align-items: flex-end; gap: 6px;
    padding: 8px 10px;
    border-top: 1.5px solid var(--border);
    background: var(--surface);
    flex-shrink: 0;
}
.mc-input {
    flex: 1;
    border: 1.5px solid var(--border);
    border-radius: 10px;
    padding: 7px 10px;
    font-family: inherit; font-size: .82rem;
    color: var(--text-primary);
    background: var(--bg);
    resize: none; outline: none;
    max-height: 80px;
    line-height: 1.45;
    transition: border-color .15s;
}
.mc-input:focus { border-color: var(--accent); }
.mc-send-btn {
    width: 32px; height: 32px;
    border-radius: 8px;
    background: var(--accent); color: #fff;
    border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: .8rem; flex-shrink: 0;
    transition: background .12s;
}
.mc-send-btn:hover { filter: brightness(1.1); }
.mc-send-btn:disabled { opacity: .7; cursor: not-allowed; }
.mc-hint-row {
    display: flex; align-items: center; gap: 5px;
    padding: 3px 10px 6px;
    min-height: 16px;
}
</style>

<script>
(function () {
    const ME       = {{ auth()->id() }};
    const CSRF     = document.querySelector('meta[name=csrf-token]').content;
    const CHAT_URL = '{{ route('chat.index') }}';
    const SEND_URL = '{{ route('chat.send') }}';
    const POLL_URL = '{{ route('chat.poll') }}';
    const UNRD_URL = '{{ route('chat.unread') }}';

    let mcOpen       = false;
    let mcActiveUser = null;   // {id, name}
    let mcUsers      = [];

    // ── FAB toggle ────────────────────────────────────────────
    window.miniChatToggle = function () {
        mcOpen = !mcOpen;
        document.getElementById('miniChatWin').style.display = mcOpen ? 'flex' : 'none';
        document.getElementById('miniChatFab').style.background = mcOpen ? '#475569' : '';
        document.getElementById('miniChatFabIcon').className = mcOpen ? 'fas fa-xmark' : 'fas fa-comments';
        // While the chat window is open, force the full bubble out (not the tab)
        document.getElementById('miniChatRoot').classList.toggle('mc-open', mcOpen);
        if (mcOpen && mcUsers.length === 0) mcLoadUsers();
    };

    // ── Back to user list ─────────────────────────────────────
    window.mcBackToUsers = function () {
        clearInterval(mcPollTimer); clearInterval(mcGroupPollTimer);
        mcActiveUser = null; mcLastId = 0; mcGroupLastId = 0;
        document.getElementById('mcInput').placeholder = 'বার্তা লিখুন...';
        document.getElementById('mcPanelConv').style.display  = 'none';
        document.getElementById('mcPanelUsers').style.display = 'flex';
        mcLoadUsers();
    };

    // ── Load user list ────────────────────────────────────────
    function mcLoadUsers() {
        fetch(CHAT_URL + '?mc=1', { headers: { 'Accept': 'application/json' } })
        .then(r => r.json()).then(data => { mcUsers = data; renderUserList(data); })
        .catch(() => {});
    }

    function mcAvatarColor(id) { return id % 2 === 0 ? '#3b82f6' : '#7c3aed'; }
    function mcInitial(name)   { return name ? name.charAt(0).toUpperCase() : '?'; }

    function renderUserList(users) {
        const el = document.getElementById('mcUserList');
        const groupRow = `
            <div class="mc-user-row" onclick="mcOpenGroup()">
                <div class="mc-avatar" style="background:linear-gradient(135deg,#f59e0b,#ef4444);font-size:.75rem">
                    <i class="fas fa-users"></i>
                </div>
                <div class="mc-user-meta">
                    <div class="mc-user-name">📢 সবাই (গ্রুপ)</div>
                    <div class="mc-user-last">সকল ব্যবহারকারী</div>
                </div>
            </div>`;
        if (!users.length) { el.innerHTML = groupRow + '<div class="mc-loading" style="font-size:.8rem">কোনো ব্যবহারকারী নেই</div>'; return; }
        el.innerHTML = groupRow + users.map(u => `
            <div class="mc-user-row" onclick="mcOpenConvById(${u.id})">
                <div class="mc-avatar" style="background:${mcAvatarColor(u.id)}">${mcInitial(u.name)}</div>
                <div class="mc-user-meta">
                    <div class="mc-user-name">${escH(u.name)}</div>
                    <div class="mc-user-last">${u.last_msg ? escH(u.last_msg) : '<em style="color:#cbd5e1">কোনো বার্তা নেই</em>'}</div>
                </div>
                ${u.unread > 0 ? `<span class="mc-unread">${u.unread}</span>` : ''}
            </div>`).join('');
    }

    // id দিয়ে user খুঁজে mcOpenConv কল করে — onclick attribute-এ name দেওয়া যায় না
    window.mcOpenConvById = function (id) {
        const u = mcUsers.find(x => x.id == id);
        if (u) mcOpenConv(u.id, u.name);
    }

    // ── Group chat in mini window ─────────────────────────────
    let mcGroupLastId = 0;
    let mcGroupPollTimer = null;
    const GROUP_SEND_URL = '{{ route('chat.group.send') }}';
    const GROUP_POLL_URL = '{{ route('chat.group.poll') }}';

    window.mcOpenGroup = function () {
        mcActiveUser = { id: 'group', name: 'সবাই (গ্রুপ)', group: true };
        document.getElementById('mcPanelUsers').style.display = 'none';
        document.getElementById('mcPanelConv').style.display  = 'flex';
        const av = document.getElementById('mcConvAvatar');
        av.innerHTML = '<i class="fas fa-users" style="font-size:.6rem"></i>';
        av.style.background = 'linear-gradient(135deg,#f59e0b,#ef4444)';
        document.getElementById('mcConvName').textContent = '📢 সবাই — গ্রুপ';
        document.getElementById('mcInput').placeholder = 'সবাইকে বার্তা লিখুন...';
        document.getElementById('mcMessages').innerHTML = '<div class="mc-loading"><i class="fas fa-spinner fa-spin"></i></div>';
        mcFetchGroupHistory();
        clearInterval(mcGroupPollTimer);
        mcGroupPollTimer = setInterval(mcGroupPoll, 8000);
        setTimeout(() => document.getElementById('mcInput').focus(), 100);
    };

    function mcFetchGroupHistory() {
        fetch(`${GROUP_POLL_URL}?last_id=0`, { headers:{ 'Accept':'application/json', 'X-CSRF-TOKEN': CSRF } })
        .then(r => r.json()).then(data => {
            const box = document.getElementById('mcMessages');
            if (!data.messages.length) {
                box.innerHTML = `<div class="mc-empty-conv"><i class="fas fa-users"></i><div>গ্রুপ চ্যাট শুরু করুন</div></div>`;
            } else {
                box.innerHTML = '';
                data.messages.forEach(m => appendMcGroupBubble(m));
                mcScrollBottom();
                mcGroupLastId = data.messages[data.messages.length - 1].id;
            }
        });
    }

    function mcGroupPoll() {
        if (document.hidden) return; // tab in background — skip the server hit
        fetch(`${GROUP_POLL_URL}?last_id=${mcGroupLastId}`, { headers:{ 'Accept':'application/json', 'X-CSRF-TOKEN': CSRF } })
        .then(r => r.json()).then(data => {
            data.messages.forEach(m => {
                if (m.sender_id != ME && !document.querySelector(`#mcMessages [data-id="${m.id}"]`)) {
                    appendMcGroupBubble(m); mcScrollBottom(true);
                }
                mcGroupLastId = Math.max(mcGroupLastId, m.id);
            });
        }).catch(()=>{});
    }

    function appendMcGroupBubble(msg) {
        const box = document.getElementById('mcMessages');
        const empty = box.querySelector('.mc-empty-conv');
        if (empty) empty.remove();
        const isMine = msg.sender_id == ME;
        const row = document.createElement('div');
        row.className = 'mc-bubble-row ' + (isMine ? 'mine' : 'theirs');
        row.dataset.id = msg.id;
        const senderHtml = !isMine ? `<div style="font-size:.65rem;font-weight:700;color:#f59e0b;margin-bottom:2px">${escH(msg.sender_name)}</div>` : '';
        row.innerHTML = `<div>${senderHtml}<div class="mc-bubble">${escH(msg.message).replace(/\n/g,'<br>')}</div><div class="mc-time">${msg.created_at}</div></div>`;
        box.appendChild(row);
    }

    // ── Open conversation ─────────────────────────────────────
    window.mcOpenConv = function (userId, userName) {
        mcActiveUser = { id: userId, name: userName };
        document.getElementById('mcPanelUsers').style.display = 'none';
        document.getElementById('mcPanelConv').style.display  = 'flex';
        const av = document.getElementById('mcConvAvatar');
        av.textContent = mcInitial(userName); av.style.background = mcAvatarColor(userId);
        document.getElementById('mcConvName').textContent = userName;
        document.getElementById('mcMessages').innerHTML = '<div class="mc-loading"><i class="fas fa-spinner fa-spin"></i></div>';
        mcFetchHistory();
        mcStartPoll();
        setTimeout(() => document.getElementById('mcInput').focus(), 100);
    };

    function mcFetchHistory() {
        fetch(`${POLL_URL}?with=${mcActiveUser.id}&last_id=0`, {
            headers: { 'Accept':'application/json', 'X-CSRF-TOKEN': CSRF }
        })
        .then(r => r.json())
        .then(data => {
            const box = document.getElementById('mcMessages');
            if (!data.messages.length) {
                box.innerHTML = `<div class="mc-empty-conv"><i class="fas fa-comments"></i><div>${escH(mcActiveUser.name)}-এর সাথে কথোপকথন শুরু করুন</div></div>`;
            } else {
                box.innerHTML = '';
                data.messages.forEach(m => appendMcBubble(m));
                mcScrollBottom();
                mcLastId = data.messages[data.messages.length - 1].id;
            }
            updateBadge(data.total_unread);
        });
    }

    // ── WebSocket: receive incoming message (when Reverb/Pusher running) ──
    window.addEventListener('ws:message', function (e) {
        const msg = e.detail;

        if (msg.is_group) {
            // Group message
            if (mcOpen && mcActiveUser && mcActiveUser.group) {
                if (!document.querySelector(`#mcMessages [data-id="${msg.id}"]`)) {
                    appendMcGroupBubble(msg); mcScrollBottom(true);
                }
                mcGroupLastId = Math.max(mcGroupLastId, msg.id);
            }
            return;
        }

        // Private message
        updateBadge_fetch();
        if (mcOpen && mcActiveUser && !mcActiveUser.group && msg.sender_id == mcActiveUser.id) {
            if (!document.querySelector(`#mcMessages [data-id="${msg.id}"]`)) {
                appendMcBubble(msg); mcScrollBottom(true);
            }
            mcLastId = Math.max(mcLastId, msg.id);
        }
        if (mcOpen && !mcActiveUser) mcLoadUsers();
    });

    // ── Polling fallback (works even without Reverb/Pusher) ──
    let mcLastId   = 0;
    let mcPollTimer = null;

    function mcStartPoll() {
        clearInterval(mcPollTimer);
        if (!mcActiveUser) return;
        mcPollTimer = setInterval(function () {
            if (document.hidden) return; // tab in background — skip the server hit
            fetch(`${POLL_URL}?with=${mcActiveUser.id}&last_id=${mcLastId}`, {
                headers: { 'Accept':'application/json', 'X-CSRF-TOKEN': CSRF }
            })
            .then(r => r.json())
            .then(function (data) {
                data.messages.forEach(function (m) {
                    if (m.sender_id != ME && !document.querySelector(`#mcMessages [data-id="${m.id}"]`)) {
                        appendMcBubble(m); mcScrollBottom(true);
                    }
                    mcLastId = Math.max(mcLastId, m.id);
                });
                if (data.messages.length) updateBadge(data.total_unread);
            }).catch(function(){});
        }, 8000);
    }

    function updateBadge_fetch() {
        fetch(UNRD_URL, { headers:{ 'Accept':'application/json' } })
            .then(r => r.json()).then(d => updateBadge(d.count)).catch(()=>{});
    }

    // ── Append bubble ─────────────────────────────────────────
    function appendMcBubble(msg) {
        const box = document.getElementById('mcMessages');
        const empty = box.querySelector('.mc-empty-conv');
        if (empty) empty.remove();
        const isMine = msg.sender_id == ME;
        const row = document.createElement('div');
        row.className = 'mc-bubble-row ' + (isMine ? 'mine' : 'theirs');
        row.dataset.id = msg.id;
        row.innerHTML = `<div><div class="mc-bubble">${escH(msg.message).replace(/\n/g,'<br>')}</div><div class="mc-time">${msg.created_at}</div></div>`;
        box.appendChild(row);
    }

    function mcScrollBottom(smooth = false) {
        const el = document.getElementById('mcMessages');
        if (el) el.scrollTo({ top: el.scrollHeight, behavior: smooth ? 'smooth' : 'instant' });
    }

    // ── Send ──────────────────────────────────────────────────
    window.mcKeyDown = function (e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); mcSend(); } };
    window.mcSend = function () {
        const input = document.getElementById('mcInput');
        const text  = input.value.trim();
        if (!text || !mcActiveUser) return;
        const btn        = document.getElementById('mcSendBtn');
        const mcLoad     = document.getElementById('mcSendLoader');
        const mcHintText = document.getElementById('mcHintText');
        btn.disabled = true;
        mcHintText.style.display = 'none';
        mcLoad.classList.add('active');

        const isGroup = mcActiveUser.group === true;
        const url  = isGroup ? GROUP_SEND_URL : SEND_URL;
        const body = isGroup ? { message: text } : { receiver_id: mcActiveUser.id, message: text };

        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify(body),
        })
        .then(r => r.json())
        .then(msg => {
            input.value = ''; input.style.height = 'auto';
            if (isGroup) { appendMcGroupBubble(msg); mcGroupLastId = msg.id; }
            else         { appendMcBubble(msg); }
            mcScrollBottom(true);
        })
        .finally(() => {
            btn.disabled = false;
            mcLoad.classList.remove('active');
            mcHintText.style.display = '';
            input.focus();
        });
    };

    // ── Badge helpers ─────────────────────────────────────────
    function updateBadge(count) {
        ['mcFabBadge','chatBadge'].forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            el.textContent = count > 9 ? '9+' : count;
            el.style.display = count > 0 ? 'flex' : 'none';
        });
    }

    // Textarea auto-grow
    document.getElementById('mcInput').addEventListener('input', function () {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 80) + 'px';
    });

    function escH(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }
})();
</script>
</body>
</html>
