# Ruposhi POS — Full Development History

This file holds the complete, unabridged session-by-session changelog that
used to live in full inside `CLAUDE.md`. It was moved here to keep
`CLAUDE.md` lean (loaded into every session's context). `CLAUDE.md` keeps a
short one-paragraph summary per session — come here when you need the exact
implementation detail, file names, or reasoning behind a specific past
change.

---

## Completed Features (All Sessions)

### Session 1
1. Daily sales report — no-item sale payments, standalone CustomerPayments
2. Sale delete/edit audit log (`sale_logs` table)
3. Negative customer/supplier due (credit balance)
4. Customer ledger — "নতুন বিক্রয়" button pre-selects customer
5. Purchase invoice styled like sale invoice
6. Purchase edit functionality
7. Stock page — hide zero-stock rows, negative in red
8. Floating submit button on sale create
9. NoCacheHeaders middleware
10. All date filters default to today (not start of month)
11. Sales list expandable পণ্যের বিবরণ column
12. Customer list with gross/net due tfoot breakdown

### Session 2
13. No-item purchase (advance supplier payment) — same pattern as no-item sale
14. Purchase `due_amount` allows negative (removed `max(0,...)` cap)
15. Purchase create: yellow warning when paying without items
16. Purchase invoice: full due display logic (see above), supplier running total advance
17. Purchase list: blue "অগ্রিম ৳X" badge per row + net credit in tfoot
18. Fixed: `SupplierController::ledger()` had `max(0,...)` bug — was resetting credit to 0
19. Fixed: Old purchases with wrong `due_amount=0` corrected in DB
20. Fixed: Sale invoice tfoot row order (পূর্বের বাকী before extra/labor costs)
21. Supplier ledger-select: blue "অগ্রিম" badge for credit suppliers
22. Performance: DB indexes on all key query columns
23. Performance: Dropdown queries use column selection (not full rows)
24. **Rule: NEVER cache business data** — due_amount, prices, stock change frequently

### Session 3 — v2 Multi-Shop
25. Core multi-tenant architecture: `shops` table, `shop_id` on all tenant tables, role enum
26. `ShopScope` global scope + `HasShopScope` trait — auto-filter + auto-fill shop_id
27. Middleware: `super_admin`, `shop.scope`, `shop.admin`, `check.subscription`
28. Super admin control panel (`/super`) — dark layout, shop CRUD, enter/exit shop
29. Closed data leaks: all `DB::table()` queries in ReportController manually scoped
30. Closed data leaks: DashboardController stock_value query, ChatController user list
31. Per-shop store_config: composite unique `(shop_id, key)`
32. Shop name shown in sidebar brand and topbar breadcrumb
33. Shop-scoped staff/user management: UserController (manual scope), `shop.admin` gate
34. Security guards: cross-shop edit→404, self-delete blocked, last-admin blocked

### Session 4 — Reseller/Root/License system
35. Root admin panel (`/root`) — manage resellers and super_admins
36. Reseller panel (`/reseller`) — manage own clients and extend licenses
37. License model — plan, expires_at, grace_ends_at, max_shops, status (active/warning/grace/expired)
38. Subscription check middleware — warning/grace banners, lock on expired
39. super_admin owns multiple shops: `shops.super_admin_id` FK
40. `max_shops` on license — controls branch limit per super_admin
41. `is_locked` on shops — data preserved but inaccessible when over license limit
42. `syncShopLocks()` — auto-sync on dashboard + license change; oldest shops protected first
43. SetShopScope: checks shop ownership AND lock status on every request
44. Super admin dashboard: locked shop warning banner, branch count X/max, disabled add button at limit
45. Fixed: ReportController showed all super_admins' shops — scoped to `super_admin_id = auth()->id()`

### Session 5 — Bug Fixes
46. Fixed: `destroy()` in Sale/Purchase/CustomerPayment/SupplierPayment + 9 other controllers used `role !== 'admin'` — changed to `canManageShop()` so super_admin inside a shop can perform admin actions
47. Fixed: `PurchaseController::update()` had `items` as `required|array|min:1` — changed to `nullable` so advance-payment purchases can be edited
48. Fixed: `SetShopScope` middleware didn't check `is_locked` — added check so super_admin is kicked out if shop gets locked while they're inside
49. Fixed: `SaleController::index()` and `PurchaseController::index()` — `orWhere('id', X)` without grouping could bypass shop_id scope; wrapped in sub-closure
50. Fixed: `CustomerPaymentController::store()` had `max(0, due - paid)` cap — removed; allows negative credit
51. Fixed: `SupplierPaymentController::store()` same `max(0,...)` bug — removed
52. Fixed: `salesReport()` `grandNoItemDueReduction` used `min(paid, previous_due)` which broke when `previous_due` was negative; fixed with `max(0, previous_due)`

### Session 6 — Owner-Side Features & Mobile Fixes
53. Demo credentials panel on login page — left box, auto-fill on click; Root/Reseller hidden behind vertical pill toggle on right side of login form
54. `DemoSeeder` — creates all demo accounts; safe to re-run (`updateOrCreate`)
55. Sidebar logout button — full-width, separate from user card (root and shop layouts)
56. Fixed: root/reseller hitting `/dashboard` → redirect to proper panel (was 403)
57. Sales report: reordered sections — বাকী পরিশোধ moved before অতিরিক্ত খরচ
58. Fixed: stock/index tfoot colspan misalignment (5→4); stock/low (4→3)
59. Mobile fix: invoice phone numbers no longer overlap store name on small screens (≤480px)
60. Mobile fix: sale create floating submit bar — full-width on mobile (was hardcoded 340px right-only)
61. Mobile fix: `form-actions` flex-wrap so buttons wrap on small screens
62. **Payment Log system** — `payment_logs` table; Root records/views payments per super_admin; Root `/root/payments` index with filters; Root super-admin show page has summary bar + payment form + history
63. Reseller client show page (`/reseller/clients/{id}`) — client info, shop list, license history, payment history, inline renewal form
64. Reseller dashboard: commission summary card — total payments, commission rate, calculated commission, recent 5 payments

### Session 7 — Turbo Drive (SPA navigation) & জমা in পরিশোধ তালিকা
65. **Turbo Drive (Hotwire Turbo 8)** — SPA-like navigation; no full reloads, swaps `<body>`, fires `turbo:load`; `data-turbo-permanent` on sidebar/chat/backdrop/search/popover; all init moved `DOMContentLoaded`→`turbo:load`; `turbo-cache-control: no-cache` on form pages
66. **জমা (deposit) entries in পরিশোধ তালিকা** — `SupplierPaymentController::index()` merges `Purchase` (paid) + `PurchaseDeposit` rows via manual `LengthAwarePaginator`; view shows blue "জমা" badge, category in মন্তব্য, মোট জমা stat card + tfoot total
67. **Fixed: Turbo + top-level `const`/`let` SyntaxError** — Turbo re-evaluates body scripts on every visit; top-level `const`/`let` re-declaration aborted the whole script ("JS dead until refresh"). Converted all column-0 `const`/`let`→`var` across 17 views (see Turbo gotcha above)

### Session 8 — UI Polish, Searchable Area, Keyboard Shortcuts
68. **Item search limit removed** — `sales/edit` and `purchases/edit` item dropdowns now show all matches (was capped at 6/8)
69. **পণ্য গ্রহণ page background leak fixed** — moved `.content { background: #fffbeb }` from `@push('styles')` (head — Turbo preserves forever) into body as inline `<style>` tag (Turbo removes on navigation away)
70. **Keyboard shortcuts rewritten** — use `e.code` (physical key, reliable across layouts) instead of `e.key`; Alt+T for বিক্রয় রিপোর্ট (Alt+R was intercepted by NVIDIA App overlay at OS level); Alt+B handler moved to `app.js` (body-script listener stacked on every Turbo navigation causing double-toggle); canonical fallback paths added so shortcuts work even when sidebar link isn't in DOM
71. **Sale-logs filter chips** — replaced `<select name="action">` dropdown with pill chips (সব / সংশোধিত / মুছে ফেলা) showing per-type counts; `ReportController::saleLogs()` passes `$counts` array
72. **Searchable area combobox** — `partials/area-combobox.blade.php` partial (search input + hidden id input + embedded JSON); `initAreaComboboxes()` in `app.js` wires all `.area-combobox:not([data-ac-ready])` on every `turbo:load`; applied to `customers/index`, `customers/ledger-select`, `customers/create`, `customers/edit`, `customer-payments/create`; `CustomerController::search()` accepts `area_id` filter param
73. **Renamed "মাল রিসিভ" → "পণ্য গ্রহণ"** throughout all views, controllers, and config — "মাল" → "পণ্য" everywhere standalone; "মালামাল" and "মালপত্র" preserved (different compound words); "মালের বিবরণ" → "পণ্যের বিবরণ", "মাল ফেরত" → "পণ্য ফেরত", "ক্রটিপূর্ণ মাল" → "ক্রটিপূর্ণ পণ্য"

### Session 9 — তাগাদা লিস্ট, ক্রেডিট লিমিট, দিনশেষ রিপোর্ট, ক্যালকুলেটর, WhatsApp, ব্যাকআপ
74. **পরিশোধ তালিকা combined totals** — supplier-payments index: new purple "সর্বমোট (ফিল্টার)" stat card + third tfoot row (পরিশোধ + জমা) so it reconciles at a glance with the supplier ledger's জমা figure
75. **Searchable supplier dropdown** — reused `partials/area-combobox.blade.php` on supplier-payments index (`acName: supplier_id`); the partial is generic — works for ANY id/name collection, zero new JS
76. **Floating mini calculator** — `#miniCalcRoot` in app layout (`data-turbo-permanent`); topbar button + Alt+C (works inside inputs); select any number on any page → bubble "যোগ করুন" appends it to the expression; safe evaluator in `app.js` (char whitelist + `Function`), handles Bengali digits/৳/commas/`%`; history 10 rows, click restores; copy button
77. **তাগাদা লিস্ট (collections)** — `/collections` + `CollectionController`; customers with due > 0, area filter, ৩০/৬০/৯০+ aging chips (anchor = last payment activity, fallback first sale; `never_paid` flagged); per-row SMS + WhatsApp + tel: link; checkbox bulk SMS (max 50/batch, `collections.sms`); print-friendly; sidebar link in কাস্টমার group
78. **ক্রেডিট লিমিট** — `customers.credit_limit` (nullable decimal; null = no limit; **warning-only, never blocks a sale**); field in customer create/edit; live amber warning on sales/create (3 states: already-over / would-exceed-after-sale / hidden when transaction settles); red "লিমিট ছাড়ানো" badge in collections + warning triangle in customer search results
79. **দিনশেষ রিপোর্ট** — `/reports/day-close` (shop.admin only); `ReportController::dayCloseData()` aggregates sales/payments/purchases/expenses/deposits for one date; ক্যাশ হিসাব card (cash in − cash out = নীট) + দিনের সারাংশ card; "মালিকের ফোনে SMS" sends compact Bangla summary to `store_phone` (`dayCloseSms()`); sidebar link in রিপোর্ট group
80. **WhatsApp share (free)** — `openWhatsApp(phone, msg)` + `waNormalizeBD()` in `app.js` (normalizes বাংলা digits/+880/01x → 880…, opens `wa.me`); green button on sale invoice (pre-filled memo summary) and per-row WhatsApp তাগাদা icon on collections (needs FA brands — already in all.min.css)
81. **Daily DB backup** — `php artisan app:backup-db` (`app/Console/Commands/BackupDatabase.php`): pure-PHP dump (no mysqldump dependency — shared-hosting safe), all tables structure+data, gzip to `storage/app/backups/`, keeps newest 14 (`--keep=N`); scheduled daily 03:00 in `routes/console.php`; **production needs one cron:** `* * * * * cd ~/domains/pos.numaanhussain.com/pos_app && php artisan schedule:run >> /dev/null 2>&1`; restore verified round-trip (counts + Bengali text intact)
82. **Concurrency hardening** — all money mutations were already `DB::transaction` + atomic `increment()/decrement()`; closed the last gap: `previous_due` reads now use `lockForUpdate()` (SaleController store/update/approveEdit + CustomerPaymentController store) so simultaneous saves on the same customer serialize and can't print the same পূর্বের বাকী
83. **ক্যাশ মেলানো (cash reconciliation)** — `day_closings` table (unique `(shop_id, close_date)`, `HasShopScope`); form on `/reports/day-close`: opening cash (prefilled from previous day's counted) + hand-counted cash → expected = opening + cashNet, discrepancy saved (can be negative = short); `ReportController::dayCloseReconcile()` via `reports.day-close.reconcile` (shop.admin); saved card shows breakdown + "আবার মেলান" re-opens form (updateOrCreate); গরমিল হিস্ট্রি card shows last 7 days with links
84. **PWA (installable app)** — `public/manifest.json` (name Ruposhi POS, standalone, theme #0d9488, bn) + icons `public/icons/icon-192/512.png` (generated via PowerShell GDI+: teal rounded square, white ৳ in Nirmala UI); `public/sw.js` is **install-only — NO caching** (fetch = network passthrough; business data must stay live); SW registered in `app.js`; manifest+theme-color+apple-touch-icon links in app layout head AND login page head

### Session 10 — মেমো প্রিন্ট ফিট, ১০ নতুন ফন্ট, Self-host সব asset, লাভ/দাম সতর্কতা
85. **Sidebar highlight** — "বিক্রয় লিস্ট" nav link gets `.nav-item-highlight` (amber tint + left border; solid amber when active) — classes in `app.css`
86. **10 new Bangla fonts** — Bornomala, Kazi Typo, Potro Sans, FN Shorif Lalon, B52 Udayan, FN Mamun Turio, Ruhul Amin, Shorif Borsha, FN Kornofuli, Sonali Borno; TTF→woff2 via Python fontTools (`f.flavor='woff2'`); only Unicode variants (Bijoy/ANSI skipped — legacy encoding renders broken); files `public/fonts/{slug}-{weight}.woff2`; wired in `font-loader` ('file'+'weights' keys), picker cards in `store-config/index`, whitelist in `StoreConfigController::updateFont()`
87. **Cash memo fits 1/4 Demy paper (165×222mm)** — `sales/show.blade.php` print CSS: `#cashMemo` fixed-position 165mm wide, `padding: 8mm 15mm 15mm`, no fixed `@page size` (Chrome gotcha — only `@page{margin:0}`); header compacted (tagline+address one line, প্রতিষ্ঠান+প্রোপ্রাইটর one line, no "টাকা" suffix); store-name print size 1.45rem; header bottom border removed
88. **Dynamic memo density** — PHP computes `--m-font/--m-pad/--m-lh` CSS vars from `$sale->items->count()` (9 tiers: ≤6 items → 1.00rem/3px/1.3 … >33 → 0.64rem/0/1.0); `@media print` table/tfoot rules consume via `var()`; font capped 1.0rem so long Bengali names never wrap to 2 lines; verified 3–40 items all fit one page
89. **Self-host ALL assets** (see Tech Stack RULE) — Inter 35 Google faces→1 local latin variable; Noto/Baloo/Tiro→local bengali+latin subsets; FA/Turbo/Pusher/Chart.js vendored; new `partials/base-fonts.blade.php` for non-shop layouts; @font-face count on invoice page 60→13, external requests→0; page load AND print-preview much faster (Chrome resolves fewer faces, waits on no network)
90. **"অতিরিক্ত লাভ!" threshold 25%→10%** — sales/create, 3 spots (row badge render, updateRow toggle, submit confirm). ⚠️ PARKED for later: user says rice profit is flat ৳/bag (~200–300) regardless of bag price, so % is wrong at both ends — likely future change to flat per-bag taka threshold (option: store-config per-shop limit)
91. **"দাম বেড়েছে!" warning on পণ্য গ্রহণ** — purchases/create: `priceJump(c)` helper — entered price >5% above `c.lastPrice` (previous purchase price) → amber row badge, title shows %; only warns on increase, hidden when price lowered/equal; toggled in `updateRowTotal()`
92. **"নতুন পণ্য গ্রহণ" button** — purchases/show action row: green btn-primary → `purchases.create` (rapid consecutive receives)
93. **.gitignore reference artifacts** — `/print.html` + `/*_files/` ignored (old-system reference pages kept on disk, never committed)

### Session 11 — আইটেম পিকার, ফেভারিট, ব্র্যান্ড, প্রিন্ট/UX ফিক্স
94. **Print row-height/minus-sign fixes** — customer/supplier print lists: `.print-uniform-rows` (`table-layout:fixed`) + `.print-ellipsis-td` (scoped to নাম/প্রোপ্রাইটর only — NOT badge/money columns) fixes long names wrapping to a 2nd line and breaking row heights; minus sign moved to sit directly before the number (not before ৳) in supplier অগ্রিম badge + ledger row/tfoot balances
95. **Blank print pages (regression) fixed** — `window.print()` was racing ahead of DOM paint/font load right after an innerHTML swap; deferred via `document.fonts.ready.then(...)` + double `requestAnimationFrame()` before printing (customers/suppliers/items print buttons)
96. **Bangla words-in-text extended** — `bnWatchTakaWords()` wired to purchase extra-cost/deposit rows and customer+supplier পুরনো বাকী/দেনা (opening_balance) fields
97. **opening_balance restricted to admin** — customer + supplier create/edit: field hidden from staff in the view (`@if(auth()->user()->canManageShop())`) AND ignored server-side if a staff request tampers with it (`CustomerController`/`SupplierController` `store()`/`update()`)
98. **Purchase overpayment split removed** — previously an overpaid purchase created a second linked "advance" `Purchase` record (`notes = '__advance_for:{id}'`); now the full `paid_amount` is recorded directly on the one purchase and `due_amount` goes negative (existing no-cap credit pattern). Reversal logic for OLD pre-existing linked-advance records deliberately left intact in `update()`/`destroy()`/`approveEdit()` for backward compatibility — only the *creation* of new splits was removed
99. **Blank পরিশোধ (paid_amount) field fixed** — purchase create/edit: removed `required` attribute from the input, validation changed to `nullable` + `$request->merge(['paid_amount' => $request->paid_amount ?? 0])`. Same root-cause pattern later hit `items.sale_price` (blank → NULL → NOT NULL column violation on UPDATE) — fixed identically in `ItemController::store()`/`update()`
100. **Previous-due banner on edit-page load fixed** — `purchases/edit.blade.php` now calls the real `selectSupplier()` on page load (was a hand-rolled incomplete duplicate); `sales/edit.blade.php` builds the same due/advance banner HTML inline but does **not** call `resetPrevDuePay()` (that would wrongly reset the sale's saved `paid_amount`)
101. **Purchase delete/edit-request SMS** — mirrors the sale-side fix from an earlier session: `PurchaseController::requestDelete()` now SMSes the admin the moment staff submits a deletion request (link to `approvals.index`); the pre-existing edit-request SMS (`sendPurchaseEditSms()`) updated to respect the `sms_on_edit` toggle and link to `approvals.index` too (was hardcoded to always-send + linked straight to the purchase page)
102. **Sale invoice bold names** — প্রতিষ্ঠান/প্রোপ্রাইটর values wrapped in `<strong>` on `sales/show.blade.php`
103. **Item picker + favorites** (see "Item picker" pattern in CLAUDE.md) — new wide-screen column on `sales/create.blade.php`; `frequentItemIds` computed in `SaleController::create()`; new `item_favorites` table/model/`ItemFavoriteController`
104. **Customer/supplier area auto-fill on customer select** — `customer-payments/create.blade.php` and `customers/ledger-select.blade.php`: selecting a customer now sets the এলাকা combobox to that customer's area via a `syncAreaDisplay()` helper that sets the hidden input + visible text directly WITHOUT dispatching `change` (dispatching would re-trigger the area-select handler and wipe the just-made customer selection)
105. **Brand** — new optional per-item field, exact mirror of Category (`Brand` model, `BrandController`, `item-meta/brands/*` views, sidebar link, `items.brand_id` nullable FK). Unlike Category's app-level `unique:brands,name` (which category also has, as a latent non-shop-scoped bug), Brand's uniqueness is properly shop-scoped via `Rule::unique(...)->where('shop_id', ...)`
