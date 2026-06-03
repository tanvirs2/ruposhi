<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ড্যাশবোর্ড') — Inventory</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    @stack('styles')
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="brand">
            <div class="brand-icon"><i class="fas fa-boxes-stacked"></i></div>
            <span class="brand-name">Inventory</span>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
    </div>

    <nav class="sidebar-nav">
        @php
            $_path = request()->path();  // e.g. 'item-types', 'reports/sales'
            $_seg  = explode('/', $_path)[0];  // first segment only

            // Accordion open states
            $inCustomer = in_array($_seg, ['customers','customer-payments','customer-areas']) || $_path === 'customers-ledger';
            $inItems    = in_array($_seg, ['items','categories']);
            $inStock    = $_seg === 'stock';
            $inSupplier = in_array($_seg, ['suppliers','supplier-payments']) || in_array($_path, ['suppliers-due-report','suppliers-ledger']);
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
                        <button type="button" class="info-btn" data-info="সরবরাহকারীর সকল মাল গ্রহণ ও পরিশোধের বিস্তারিত হিসাব।">i</button>
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
            <a href="{{ route('purchases.index') }}" class="nav-item {{ $_seg==='purchases' ? 'active' : '' }}">
                <span class="nav-icon"><i class="fas fa-truck-ramp-box"></i></span>
                <span class="nav-label">মাল রিসিভ</span>
                <button type="button" class="info-btn" data-info="সরবরাহকারীর কাছ থেকে মালামাল গ্রহণ করুন। পরিমাণ ও মূল্য লিখলে স্টক স্বয়ংক্রিয়ভাবে আপডেট হবে এবং বকেয়া হিসাব হবে।">i</button>
            </a>
            <a href="{{ route('sales.index') }}" class="nav-item {{ $_seg==='sales' ? 'active' : '' }}">
                <span class="nav-icon"><i class="fas fa-receipt"></i></span>
                <span class="nav-label">বিক্রয়</span>
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
                        <span class="nav-label">বিক্রয় রিপোর্ট</span>
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
                        <button type="button" class="info-btn" data-info="প্রতিদিন কত মাল রিসিভ হয়েছে, কোন সরবরাহকারী থেকে, মোট মূল্য কত।">i</button>
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
                    <a href="{{ route('reports.profit-loss') }}" class="nav-item nav-child {{ $_path==='reports/profit-loss' ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fas fa-scale-balanced"></i></span>
                        <span class="nav-label">লাভ-লোকসান</span>
                        <button type="button" class="info-btn" data-info="নির্দিষ্ট সময়কালের বিক্রয় আয়, পণ্য খরচ, পরিচালনা ব্যয় ও নিট লাভ-লোকসানের পূর্ণ বিবরণ।">i</button>
                    </a>
                    <a href="{{ route('reports.sale-logs') }}" class="nav-item nav-child {{ $_path==='reports/sale-logs' ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fas fa-clock-rotate-left"></i></span>
                        <span class="nav-label">সংশোধন / মুছে ফেলার লগ</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="nav-section">
            <span class="nav-section-label">সেটিংস</span>
            <a href="{{ route('store-config.index') }}" class="nav-item {{ $_seg==='store-config' ? 'active' : '' }}">
                <span class="nav-icon"><i class="fas fa-store"></i></span>
                <span class="nav-label">স্টোর কনফিগ</span>
                <button type="button" class="info-btn" data-info="ব্যবসার নাম, ঠিকানা, ফোন ও অন্যান্য তথ্য সেটআপ করুন। এই তথ্য চালান ও রিপোর্টে দেখা যাবে।">i</button>
            </a>
            <a href="{{ route('extra-cost-categories.index') }}" class="nav-item {{ $_seg==='extra-cost-categories' ? 'active' : '' }}">
                <span class="nav-icon"><i class="fas fa-coins"></i></span>
                <span class="nav-label">খরচের ক্যাটাগরি</span>
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
                <span class="user-role">{{ auth()->user()->role === 'admin' ? 'অ্যাডমিন' : 'স্টাফ' }}</span>
            </div>
            <a href="{{ route('profile.edit') }}" class="logout-btn" title="প্রোফাইল" style="color:#94a3b8">
                <i class="fas fa-user-pen"></i>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="logout-btn" title="লগআউট">
                    <i class="fas fa-right-from-bracket"></i>
                </button>
            </form>
        </div>
    </div>
</aside>

<!-- Main -->
<div class="main-wrapper" id="mainWrapper">

    <header class="topbar">
        <div class="topbar-left">
            <div class="page-title">
                <h1>@yield('page-title', 'ড্যাশবোর্ড')</h1>
                <span class="breadcrumb">@hasSection('breadcrumb') @yield('breadcrumb') @else স্বাগতম, <strong>{{ auth()->user()->name }}</strong> @endif</span>
            </div>
        </div>
        <div class="topbar-right">

            {{-- Quick actions --}}
            <div class="quick-actions">
                <a href="{{ route('sales.create') }}" class="btn-quick btn-quick-sale" title="নতুন বিক্রয় (Alt+S)">
                    <i class="fas fa-plus"></i><span>নতুন বিক্রয়</span>
                </a>
                <a href="{{ route('purchases.create') }}" class="btn-quick btn-quick-purchase" title="মাল রিসিভ (Alt+P)">
                    <i class="fas fa-truck-ramp-box"></i><span>মাল রিসিভ</span>
                </a>
            </div>

            <div class="topbar-divider"></div>

            {{-- Font size controls --}}
            <div class="ui-controls">
                <button class="ctrl-btn" data-size="sm" onclick="setFontSize('sm')" title="ছোট লেখা">A</button>
                <button class="ctrl-btn" data-size="md" onclick="setFontSize('md')" title="স্বাভাবিক লেখা" style="font-size:.92rem">A</button>
                <button class="ctrl-btn" data-size="lg" onclick="setFontSize('lg')" title="বড় লেখা" style="font-size:1.05rem">A</button>
            </div>

            <div class="topbar-divider"></div>

            {{-- Dark mode + shortcuts --}}
            <div class="ui-controls">
                <button class="ctrl-btn ctrl-btn-dark" id="darkModeBtn" onclick="toggleDarkMode()" title="ডার্ক মোড">
                    <i class="fas fa-moon" id="darkModeIcon"></i>
                </button>
                <button class="ctrl-btn" onclick="toggleShortcutsHelp()" title="কীবোর্ড শর্টকাট (Alt+/)">
                    <i class="fas fa-keyboard"></i>
                </button>
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
            <div class="shortcut-row">
                <span>নতুন বিক্রয়</span>
                <span><kbd class="kbd">Alt</kbd> + <kbd class="kbd">S</kbd></span>
            </div>
            <div class="shortcut-row">
                <span>মাল রিসিভ</span>
                <span><kbd class="kbd">Alt</kbd> + <kbd class="kbd">P</kbd></span>
            </div>
            <div class="shortcut-row">
                <span>ড্যাশবোর্ড</span>
                <span><kbd class="kbd">Alt</kbd> + <kbd class="kbd">D</kbd></span>
            </div>
            <div class="shortcut-row">
                <span>ডার্ক/লাইট মোড</span>
                <span style="color:var(--text-secondary);font-size:.8rem">টপবারে 🌙 বোতাম</span>
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

<script src="{{ asset('js/app.js') }}"></script>

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

/* ── Sidebar active-link highlighter (JS, bypasses Blade/cache) ── */
(function () {
    var path = window.location.pathname;          // e.g. /customers/3/ledger

    /* 1. Strip PHP-generated active classes (clean slate) */
    document.querySelectorAll('.sidebar-nav .nav-item').forEach(function (el) {
        el.classList.remove('active');
    });

    /* 2. Special cases: /customers/{id}/ledger → /customers-ledger
                         /suppliers/{id}/ledger → /suppliers-ledger */
    var specialTarget = null;
    if (/^\/customers\/\d+\/ledger/.test(path)) {
        specialTarget = '/customers-ledger';
    } else if (/^\/suppliers\/\d+\/ledger/.test(path)) {
        specialTarget = '/suppliers-ledger';
    }

    /* 3. Find the best-matching anchor */
    var bestEl  = null;
    var bestLen = -1;

    document.querySelectorAll('.sidebar-nav a.nav-item').forEach(function (a) {
        try {
            var aPath = new URL(a.href, window.location.origin).pathname;

            if (specialTarget) {
                // Only match the special target link exactly
                if (aPath === specialTarget) { bestEl = a; bestLen = aPath.length; }
                return;
            }

            // Exact match wins over prefix match; longer prefix wins over shorter
            var isExact  = (path === aPath);
            var isPrefix = (!isExact && path.startsWith(aPath + '/'));
            if (isExact || isPrefix) {
                if (aPath.length > bestLen) {
                    bestLen = aPath.length;
                    bestEl  = a;
                }
            }
        } catch (e) {}
    });

    /* 4. Apply active + open parent accordion */
    if (bestEl) {
        bestEl.classList.add('active');
        var group = bestEl.closest('.nav-group');
        if (group) { group.classList.add('open'); }
    }
})();
</script>

@stack('scripts')

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
const mmSlides   = @json($mmFiles);
const mmInterval = {{ $mmInterval * 1000 }};
let mmCurrent    = 0;
let mmTimer      = null;
let mmDragging   = false;

const MM_KEY      = 'mm_slide_idx';
const MM_TIME_KEY = 'mm_slide_time';
let mmPaused = false;

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

let isFullscreen = false;
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

// Save position immediately before navigating away (most reliable)
window.addEventListener('beforeunload', () => {
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

// Start slideshow — resume from last known slide + audio/video position (survives page refresh)
document.addEventListener('DOMContentLoaded', () => {
    if (!mmSlides.length) return;
    const savedIdx  = parseInt(localStorage.getItem(MM_KEY)  ?? '0', 10);
    const savedTime = parseFloat(localStorage.getItem(MM_TIME_KEY) ?? '0');
    const start     = (savedIdx >= 0 && savedIdx < mmSlides.length) ? savedIdx : 0;
    // Pass savedTime only when > 0 so video/audio resumes at exact position
    showSlide(start, savedTime > 0 ? savedTime : undefined);
});
</script>
@endif

</body>
</html>
