# Ruposhi POS — Project Context for Claude

## Project Overview
**Bengali UI** Laravel 12 POS/Inventory system for a rice wholesale shop.
- **Local path:** `C:\laragon\www\new_pos`
- **Production:** `pos.numaanhussain.com` on Hostinger shared hosting
- **Prod app path:** `~/domains/pos.numaanhussain.com/pos_app`
- **Prod public:** symlinked `public_html → pos_app/public`
- **GitHub:** `https://github.com/tanvirs2/ruposhi.git`
- **Dev server:** `php artisan serve --port=8000`

## Git Branch Strategy
- **`main`** — active branch (v2 multi-shop) — all development happens here
- **`v2-multi-shop`** — older/stale branch, behind main
- **⚠️ RULE:** Only commit locally. NEVER `git push` without explicit user request.
- **⚠️ PUSH RULE (strict):** User must say "push" or "push now" explicitly. Do NOT push after finishing a task, do NOT ask "should I push?", do NOT push during autonomous loop. Wait. Always.

## Deploy Command (ALWAYS use this exact command)
```bash
cd ~/domains/pos.numaanhussain.com/pos_app && git pull origin main && php artisan migrate
```
After pulling, also run (rebuilds framework caches — old clear-only left the app uncached and slow):
```bash
php artisan config:cache && php artisan route:cache && php artisan view:cache
```
- `git pull origin main` — NOT bare `git pull` (doesn't work on production)
- User runs SSH commands themselves — NEVER ask for passwords or initiate SSH
- ⚠️ These cache ONLY framework internals (config files, route table, compiled Blade) —
  never business data. due_amount/stock/prices still hit the DB fresh on every request.
- ⚠️ After config:cache, `.env` changes on the server require re-running `php artisan config:cache`.

## First-Time Production Setup (seeders)
After initial deploy of v2, run once on server:
```bash
php artisan db:seed --class=SuperAdminSeeder   # backfills super_admin_id + shop_id on existing data
php artisan db:seed --class=RootUserSeeder      # creates root@system.com account
```

## Tech Stack
- Laravel 12, PHP 8.2, MySQL 8, Blade templating
- Bengali UI — per-shop font picker (default `Hind Siliguri`), 14 fonts total, all self-hosted woff2 in `public/fonts/`
- CSS: `public/css/app.css` (custom, no Tailwind)
- No npm/Vite build step needed — CSS/JS are static files

### ⚠️ RULE — ALL front-end assets self-hosted (user directive, strict)
- **NEVER add a Google Fonts / CDN / external `<link>` or `<script>`.** Zero runtime requests to external hosts — the app must work offline and print fast on flaky shop internet.
- Fonts → `public/fonts/*.woff2` (subset: bengali+latin only; use fontTools to convert/subset TTF→woff2)
- JS libs → `public/js/vendor/` (turbo.min.js, pusher.min.js, chart.umd.min.js)
- Font Awesome → `public/vendor/fontawesome/` (css + webfonts)
- Font loading: shop pages use `partials/font-loader.blade.php` (per-shop font); root/reseller/super/login/expired/errors use `partials/base-fonts.blade.php` (Hind Siliguri + Inter)
- Inter is latin-subset variable woff2 (`fonts/inter-latin.woff2`) — covers all weights in one face; only renders ASCII digits/labels

---

## ⚡ v2 Multi-Shop / Multi-Tenant Architecture

### Concept
**Role hierarchy:** root → reseller → super_admin → admin → staff

Each **super_admin** is a business owner who can own multiple branches (shops).
- Root and resellers manage super_admin accounts and licenses from separate panels.
- Each shop's data is fully isolated — invisible to other shops.
- One login URL (`/login`) for everyone — role-based redirect after auth.

### Roles
| Role | shop_id | Access | Panel |
|------|---------|--------|-------|
| `root` | `null` | Everything — manage resellers & super_admins | `/root` |
| `reseller` | `null` | Own clients' licenses | `/reseller` |
| `super_admin` | `null` | Own shops — branch control panel | `/super` |
| `admin` | shop ID | Full access to own shop + staff management | `/` |
| `staff` | shop ID | Own shop (limited — no user management) | `/` |

### Multi-Branch (super_admin owns multiple shops)
- `shops.super_admin_id` — FK to `users.id` — each shop has one owner
- `users.myShops()` — hasMany relation: all shops owned by this super_admin
- `licenses.max_shops` — branch limit (1=basic, null=unlimited)
- `shops.is_locked` — system-controlled lock when license limit exceeded
- `users.syncShopLocks()` — called on dashboard visit + license change; oldest shops protected first
- **Data NEVER deleted** — locked shops are inaccessible until license upgraded

### Core Isolation: Global Scope
- **`app/Scopes/ShopScope.php`** — auto-filters every Eloquent query by `auth()->user()->shop_id`
  - `super_admin` with `shop_id = null` bypasses (control panel mode)
  - `super_admin` who "entered" a shop has `shop_id` set in memory → filtered like a real admin
  - Applied via `HasShopScope` trait on all 22 tenant models
- **`app/Traits/HasShopScope.php`** — applied to models; auto-fills `shop_id` on `creating`
- **`app/Models/User.php`** — deliberately has NO ShopScope (powers auth); all User queries must be **manually** scoped by `shop_id`

### ⚠️ Critical Gotchas
1. **Raw `DB::table()` queries bypass global scope** — must manually add `->where('shop_id', ...)` or `->where('sales.shop_id', ...)`
2. **`User` model has NO global scope** — always filter manually: `User::where('shop_id', $shopId)`
3. **Route model binding respects global scope** — cross-shop ID access returns 404 automatically
4. **Per-shop composite unique constraints** — `(shop_id, col)` not global unique (e.g. items.code, store_config.key)
5. **`orWhere` in search must be wrapped** — `$q->where(fn($s) => $s->whereHas(...)->orWhere('id', ...))` to avoid bypassing shop_id scope
6. **`canManageShop()` not `role !== 'admin'`** — super_admin inside a shop must pass admin-only gates; use `User::canManageShop()` everywhere

### Models with `HasShopScope` (24 total)
Item, Sale, Purchase, Customer, Supplier, Category, Brand, Stock, Employee, ExtraExpense, CustomerArea, CustomerPayment, SupplierPayment, SaleLog, SmsLog, ChatMessage, GroupChatMessage, ExtraCostCategory, StoreConfig, SaleItem, PurchaseItem, SaleExtraCost, PurchaseExtraCost, ItemFavorite

### Middleware
| Alias | File | Purpose |
|-------|------|---------|
| `root` | `app/Http/Middleware/RootMiddleware.php` | Aborts 403 if not root |
| `reseller` | `app/Http/Middleware/ResellerMiddleware.php` | Aborts 403 if not reseller |
| `super_admin` | `app/Http/Middleware/SuperAdmin.php` | Aborts 403 if not super_admin |
| `shop.scope` | `app/Http/Middleware/SetShopScope.php` | Redirects super_admin → control panel; checks shop ownership + lock status; aborts 403 if shop-less |
| `shop.admin` | `app/Http/Middleware/ShopAdmin.php` | Aborts 403 if not `canManageShop()` |
| `check.subscription` | `app/Http/Middleware/CheckSubscription.php` | Locks out expired super_admin, shows warning/grace banners |

### SetShopScope middleware — important security checks
1. If super_admin has no `active_shop_id` in session → redirect to `/super/dashboard`
2. If `active_shop_id` shop doesn't belong to this super_admin → clear session, redirect with error
3. If shop `is_locked` → clear session, redirect with error (catches real-time license downgrades)
4. Sets `$user->shop_id = activeShopId` in memory (not saved to DB) so global scope applies

### Route Groups
```
/root/*          → [auth, root]                         — root admin panel
/reseller/*      → [auth, reseller]                     — reseller panel
/super/*         → [auth, super_admin, check.subscription] — super admin control panel
/*               → [auth, shop.scope, check.subscription]  — shop users
/users/*         → + shop.admin                         — admin-only staff management
```

### Login Flow
Single `/login` for all roles. After auth, redirect by role:
- `root` → `/root/dashboard`
- `reseller` → `/reseller/dashboard`
- `super_admin` → check subscription → `/super/dashboard` (or `/subscription-expired`)
- `admin`/`staff` → `/dashboard`

### Key Files
| File | Purpose |
|------|---------|
| `app/Models/Shop.php` | Shops — name, address, phone, logo, is_active, is_locked, super_admin_id |
| `app/Models/License.php` | Licenses — plan, expires_at, grace_ends_at, max_shops; status: active/warning/grace/expired |
| `app/Models/User.php` | Users — role helpers, myShops(), syncShopLocks(), canManageShop(), activeLicense() |
| `app/Scopes/ShopScope.php` | Core isolation — filters queries by shop_id |
| `app/Traits/HasShopScope.php` | Applied to all tenant models |
| `app/Http/Controllers/Root/SuperAdminController.php` | Root: CRUD for super_admins + license management |
| `app/Http/Controllers/Root/ResellerController.php` | Root: CRUD for resellers |
| `app/Http/Controllers/Root/PaymentController.php` | Root: record/list/delete payment logs |
| `app/Http/Controllers/Reseller/ClientController.php` | Reseller: manage clients + extend licenses + show page |
| `app/Models/PaymentLog.php` | Payment records — user_id, license_id, reseller_id, amount, method, trxID |
| `app/Http/Controllers/Super/ShopController.php` | Super admin: CRUD for own shops, enter/exit shop |
| `app/Http/Controllers/Super/DashboardController.php` | Super admin dashboard — calls syncShopLocks() |
| `app/Http/Controllers/Super/ReportController.php` | Super admin reports (scoped to own shops) |
| `app/Http/Controllers/UserController.php` | Shop-scoped staff/user management (manual scope) |
| `resources/views/layouts/super.blade.php` | Dark control panel layout for super admin |
| `resources/views/super/` | Super admin views (dashboard, shops) |
| `resources/views/root/` | Root admin views |
| `resources/views/reseller/` | Reseller views |
| `resources/views/users/` | User management views (index, create, edit) |

### User::canManageShop()
```php
public function canManageShop(): bool
{
    return $this->role === 'admin'
        || ($this->role === 'super_admin' && session('active_shop_id'));
}
```
Use this — NOT `role === 'admin'` — for any admin-only gate inside shop controllers.

### UserController (staff management) — Important
```php
// All methods manually scope — User has no global scope
private function shopId(): int { return (int) auth()->user()->shop_id; }
private function scopedUsers() {
    return User::where('shop_id', $this->shopId())->where('role', '!=', 'super_admin');
}
```
- `store()` — validates role `in(['admin','staff'])`, forces correct shop_id
- `update()` — blocks self-demotion; cross-shop edit → 404
- `destroy()` — blocks self-delete and removing last admin

### Testing Pattern (tinker)
```php
php artisan tinker --execute="Auth::loginUsingId(1); echo Item::count();"
```
Switch user contexts with `Auth::loginUsingId(N)` to verify shop isolation.

### Seeders
- `SuperAdminSeeder` — backfills `super_admin_id` on existing shops, assigns all existing data to shop 1
- `RootUserSeeder` — creates `root@system.com / password` (root role)

---

## Key Business Rules

### Customer/Supplier Due Amount
- **Allows negative values** (credit/advance balance from overpayment)
- Formula: `customer.due = customer.due + net - paid` (NO `max(0,...)` cap — ever)
- Negative due shown as: 🔵 "অগ্রিম ৳X" badge (blue)
- Positive due shown as: 🔴 "৳X" badge (red)
- `SaleController`, `PurchaseController`, `CustomerPaymentController`, `SupplierPaymentController` all use this formula — no cap

### ⚠️ NEVER use max(0, ...) on due amounts
This was a historical bug. Any cap on due_amount destroys the credit balance feature.
- `CustomerPaymentController::store()` → `due = previousDue - amount` (no cap)
- `SupplierPaymentController::store()` → `due = supplierDue - amount` (no cap)

### No-item Sales (Previous Due Payment)
- Users create a "sale" with NO items to pay off previous customer due
- These appear in the daily sales report as a separate section
- `$grandNoItemDueReduction = min(paid, max(0, previous_due))` — only positive previous dues can be reduced
- `sale.previous_due` is captured BEFORE the sale is made

### No-item Purchases (Advance Supplier Payment)
- Users can submit a purchase with NO items — treated as advance payment to supplier
- `items` validation is `nullable` (not required) in both `store()` and `update()`
- `due_amount = total - paid` (allows negative — no `max(0,...)` cap)
- Negative due on purchase = advance/credit for that supplier
- Yellow warning shown in create form when paid > 0 with no items
- Invoice shows "অগ্রিম পরিশোধ" (yellow notice) instead of item table
- Purchase list shows blue "অগ্রিম ৳X" badge for negative due rows

### Due Auto-fix (in ledgers)
- `CustomerController::ledger()` recalculates `due_amount` from all sales — NO `max(0,...)`
- `SupplierController::ledger()` same — NO `max(0,...)` cap
- Formula: `realTotalDue = allTimePurchases/Sales - allTimePaid - allTimePayments`
- Auto-fix runs every time ledger is opened and corrects any stale due_amount

### Purchase Invoice Due Display Logic
- `due > 0` → 🔴 বকেয়া ৳X
- `due = 0` → ✅ সম্পূর্ণ পরিশোধিত
- `due < 0` + has items → ✅ পরিশোধিত + 🔵 অতিরিক্ত পরিশোধ ৳X
- `due < 0` + no items → 🔵 অগ্রিম পরিশোধ ৳X
- Always show supplier net advance below if `supplier.due_amount < 0`

### Sale Invoice Row Order (tfoot)
Order: ছাড় → পূর্বের বাকী → অতিরিক্ত খরচ → শ্রমিক খরচ → বিক্রয় মোট (only if prev_due ≠ 0) → সর্বমোট → পরিশোধ → বাকী

### ⚠️ NEVER Cache Business Data
- `due_amount`, prices, stock, config values update frequently
- Caching any business data causes wrong values — NEVER use `Cache::remember()` for these
- `StoreConfig::get()` hits DB directly every time — intentional

---

## Database Tables (Key)
| Table | Purpose |
|-------|---------|
| `shops` | Shop records — name, address, phone, logo, is_active, is_locked, super_admin_id |
| `licenses` | Subscription licenses — user_id, reseller_id, plan, expires_at, grace_ends_at, max_shops |
| `sales` | Sales with `shop_id`, `total_amount`, `paid_amount`, `due_amount`, `previous_due`, `extra_cost`, `labor_cost`, `is_edited`, `edit_note` |
| `sale_items` | Line items per sale (has `shop_id`) |
| `purchases` | Stock receives with `shop_id`, `extra_cost`, `labor_cost` |
| `purchase_items` | Line items per purchase (has `shop_id`) |
| `customers` | `shop_id`, `due_amount` can be negative (credit), `credit_limit` nullable (null = no limit, warning-only) |
| `suppliers` | `shop_id`, `due_amount` can be negative (credit) |
| `stock` | `shop_id`, one row per item per shop, `quantity` can be negative |
| `sale_logs` | Edit/delete audit log with JSON snapshot |
| `items` | `shop_id`, products with `purchase_price`, `sale_price` |
| `store_config` | `shop_id`, key-value; composite unique `(shop_id, key)` |
| `users` | `shop_id` (nullable for root/reseller/super_admin), role = root/reseller/super_admin/admin/staff |
| `resellers` | Reseller profile — commission, max_clients, can_extend_license |
| `brands` | `shop_id`, `name`, `description` — optional per-item brand tag, mirrors `categories`; unique `(shop_id, name)` |
| `item_favorites` | `user_id`, `item_id` — per-user starred items for the sale-create item picker; unique `(user_id, item_id)` |

---

## Controllers (Key Methods)

### SaleController
- `store()` — creates sale, decrements stock, updates `customer.due += net - paid`
- `update()` — reverses old effects then re-applies, logs before change
- `destroy()` — checks `canManageShop()`, restores stock, reverses due, logs deletion
- `index()` — search with `orWhere('id')` wrapped in sub-closure (prevents shop scope bypass)
- `logSale()` — saves snapshot to `sale_logs` table
- After store: redirects to `sales.show`

### PurchaseController
- `store()` — creates purchase, increments stock, updates `supplier.due += total - paid`
- `update()` — items are `nullable` (advance payments can be edited without items), reverses old then re-applies
- `destroy()` — checks `canManageShop()`, decrements stock, reverses supplier due
- `index()` — search with `orWhere('id')` wrapped in sub-closure
- After store: redirects to `purchases.show` (invoice)
- **Supplier is required** — cannot submit without selecting
- **Items are optional** — no items = advance payment to supplier
- `due_amount = total - paid` — NO `max(0,...)` cap, allows negative credit
- `$request->items ?? []` — handles empty cart gracefully

### CustomerPaymentController
- `store()` — `due = previousDue - amount` (NO cap — allows credit)
- `destroy()` — checks `canManageShop()`, reverses payment

### SupplierPaymentController
- `store()` — `due = supplierDue - amount` (NO cap — allows credit)
- `destroy()` — checks `canManageShop()`, reverses payment

### ReportController
- `salesReport()` — daily sales with standalone payments, no-item sales sections
- `grandNoItemDueReduction` uses `min(paid, max(0, previous_due))` — handles negative previous_due
- `saleLogs()` — audit log of edits and deletions
- All date defaults: `now()->toDateString()` (today, NOT startOfMonth)
- ⚠️ All `DB::table()` raw queries manually filtered with `->where('sales.shop_id', auth()->user()->shop_id)`

### SupplierController
- `ledger()` — auto-fix supplier due (NO max(0) cap — allows negative credit)
- `ledgerSelect()` — shows blue "অগ্রিম" badge for credit suppliers

### UserController (v2)
- Shop-scoped staff management — all queries manually scoped by `shop_id`
- Guards: self-delete blocked, last-admin blocked, cross-shop edit → 404, super_admin role injection rejected

### Super\ShopController
- `enter()` — sets `session('active_shop_id')`, checks ownership and lock status
- `exitShop()` — clears session, returns to super dashboard
- `store()` — checks `license->canAddShops($count)` before creating, sets `super_admin_id`
- `resetPassword()` — resets any staff user's password scoped to super_admin's shops

### Super\DashboardController
- Calls `auth()->user()->syncShopLocks()` on every visit (auto-sync locked shops)
- All data scoped to `super_admin_id = auth()->id()`

---

## Views Structure

### Layouts
- `resources/views/layouts/app.blade.php` — sidebar with JS-based active highlight; shows shop name in brand/breadcrumb
- `resources/views/layouts/super.blade.php` — dark control panel layout for super admin
- Sidebar JS uses `window.location.pathname` for active state
- Admin-only sidebar link: "ব্যবহারকারী" — guarded by `@if(auth()->user()->canManageShop())`

### Sale Invoice (`sales/show.blade.php`)
- Store name as SVG arc (`partials/store-name-arc.blade.php`)
- Header: arc name → owner badge → tagline → address → phones
- CSS class `.memo-under-arch { margin-top: -58px }` tucks content under arc

### Purchase Invoice (`purchases/show.blade.php`)
- Same style as sale invoice — store header with arc

### Key Blade Files
| File | Notes |
|------|-------|
| `sales/create.blade.php` | Cart, customer search, extra/labor toggles, floating submit button; wide-screen (≥1401px) 3-column layout via `.pos-grid-picker` — left "আইটেম পিকার" column (frequent/favorite/all-items grid, grouped by category), middle search+cart, right summary |
| `sales/edit.blade.php` | Same as create but pre-populated, inline item-change button; plain 2-column `.pos-grid` (no picker) |
| `sales/show.blade.php` | Invoice: tfoot order = ছাড় → পূর্বের বাকী → extra/labor → বিক্রয় মোট → সর্বমোট → পরিশোধ → বাকী |
| `purchases/create.blade.php` | Supplier required, items optional, yellow warning for no-item+paid |
| `purchases/edit.blade.php` | Same style as create; items nullable |
| `purchases/index.blade.php` | Blue "অগ্রিম" badge for negative due rows; tfoot shows net credit in blue |
| `purchases/show.blade.php` | Full due display logic (see Due Display Logic above) |
| `items/create.blade.php`, `items/edit.blade.php` | ক্যাটাগরি + ব্র্যান্ড dropdowns (both optional — no validation rule); `sale_price` blank submit defaults to 0 in `ItemController` (DB column has NOT NULL default, only applies on INSERT) |
| `item-meta/brands/{index,create,edit}.blade.php` | Brand CRUD — exact mirror of `item-meta/categories/*` |
| `customers/ledger.blade.php` | Running balance, "নতুন বিক্রয়" button pre-selects customer |
| `suppliers/ledger-select.blade.php` | Blue "অগ্রিম" badge for credit suppliers |
| `reports/sales.blade.php` | 5-card stats, no-item payments section, standalone payments |
| `reports/sale-logs.blade.php` | Audit log with eye modal |
| `stock/index.blade.php` | Zero-stock rows hidden, negative stock in red |
| `users/index.blade.php` | Staff list with role badge, "আপনি" tag for self |
| `users/create.blade.php` | Create staff/admin account |
| `users/edit.blade.php` | Edit; role select disabled for self + hidden input fallback |
| `super/dashboard.blade.php` | Locked shop warning banner; branch count X/max; add-branch button disabled at limit |
| `super/shops/index.blade.php` | Locked rows dimmed, lock icon, no enter/edit buttons for locked shops |
| `super/shops/create.blade.php` | Simplified (no admin creation); shows locked/upgrade page if canAdd=false |
| `super/shops/show.blade.php` | Shop detail with sales, customers, items count |
| `root/super-admins/index.blade.php` | Shows shop_count/max_shops pill |
| `root/super-admins/create.blade.php` | Create super_admin with max_shops field |
| `root/super-admins/show.blade.php` | License info + renew form with max_shops |
| `subscription/expired.blade.php` | Lock page shown when license expired |

---

## CSS Conventions (`public/css/app.css`)
```css
/* Table rows */
.data-table th { height: 40px; padding: 0 20px; }
.data-table td { height: 46px; padding: 0 20px; }
.tfoot-summary td { background: var(--surface-2); border-top: 2px solid var(--border); }

/* Stats grid */
.stats-grid { grid-template-columns: repeat(4, 1fr); }
/* 5-card override in sales report uses .sales-stats-grid */

/* Badges */
.badge-red   { background:#fee2e2; color:#dc2626; }
.badge-green { background:#dcfce7; color:#15803d; }
/* Credit/advance: background:#eff6ff; color:#1d4ed8 */

/* Super admin control panel uses .sa-* prefix classes */
```

---

## Important Patterns

### Bengali digit conversion
All inputs use `toEnglishDigits()` JS helper to convert Bengali numerals to ASCII before parsing.

### `priceEntered` flag
Purchase form: price field starts empty; user sees "আগের: ৳X ব্যবহার" hint. Only included in calc after user types or clicks "ব্যবহার".

### No-cache middleware
`App\Http\Middleware\NoCacheHeaders` — added to web middleware group, prevents browser from serving stale pages.

### Performance — Dropdown Query Optimization
`SaleController` and `PurchaseController` use column selection for large dropdown loads:
```php
Customer::with('area:id,name')->select('id','name','phone','due_amount','area_id')->get()
Item::with('stock:id,item_id,quantity')->select('id','name','sale_price','purchase_price')->get()
Supplier::select('id','name','phone','address','due_amount')->get()
```
Only fetch columns needed by JS — critical for large datasets.

### Performance — Database Indexes
Added via migration `2026_05_31_..._add_performance_indexes_to_all_tables`:
- `sales`: `sale_date`, `(customer_id, sale_date)`, `due_amount`
- `purchases`: `purchase_date`, `(supplier_id, purchase_date)`
- `customers/suppliers`: `name`, `due_amount`
- `items`: `name`
- `sale_logs`: `created_at`, `action`
- `customer/supplier_payments`: `payment_date`, composite with id

### Sale log snapshots
Stored in `sale_logs.snapshot` (JSON) with: id, sale_date, customer_name, total_amount, paid_amount, due_amount, items[], payment_method, status, notes.

### Turbo Drive (Hotwire Turbo 8) — SPA-like navigation
- Turbo intercepts links, AJAX-fetches the page, swaps `<body>`, fires `turbo:load` (NOT `DOMContentLoaded`).
- Self-hosted at `public/js/vendor/turbo.min.js`, loaded in `layouts/app.blade.php`; CSRF token injected on `turbo:before-fetch-request`.
- `data-turbo-permanent` keeps DOM elements across navigations (sidebar `#sidebar`, chat `#miniChatRoot`, backdrop `#sidebarBackdrop`, search dropdown `#gSearchDrop`, info popover).
- All page init moved from `DOMContentLoaded` → `turbo:load`. Per-page one-time setup guarded with flags (`_autoHide`, `_animated`, etc.) since `turbo:load` fires on every visit.
- Form pages (sales/purchases create) have `<meta name="turbo-cache-control" content="no-cache">` via `@push('styles')` to disable Turbo's cached preview.

### ⚠️ Turbo gotcha — page-specific CSS must go in body, NOT `@push('styles')`
- `@push('styles')` injects into `<head>`. Turbo merges `<head>` on navigation and **never removes pushed styles** — they leak to all subsequent pages until hard refresh.
- **Rule:** any CSS that should only apply to one page (e.g. a tinted background) must be an inline `<style>` tag inside `@section('content')` (the body), NOT a `@push('styles')` rule.
- Example: `purchases/create.blade.php` amber background: `<style>.content { background: #fffbeb !important; }</style>` placed at the top of the content section.

### Searchable area combobox (`partials/area-combobox.blade.php`)
Drop-in replacement for `<select>` area fields when there may be hundreds of areas.
```blade
@include('partials.area-combobox', [
    'areas'          => $areas,          // required — collection with id/name
    'acId'           => 'mySelectId',    // optional — id on the hidden input
    'acName'         => 'area_id',       // default
    'acValue'        => old('area_id'),  // pre-selected id
    'acPlaceholder'  => 'খুঁজুন...',
    'acAllLabel'     => '— সব এলাকা —',
])
```
- Renders: visible search `<input>` + hidden `<input name="area_id">` + embedded JSON
- Wired by `initAreaComboboxes()` in `app.js` on every `turbo:load`
- Dispatches native `change` event on the hidden input when selection changes — existing `addEventListener('change', ...)` handlers work unchanged

### Item picker (`sales/create.blade.php` only)
- Wide-screen bonus column (`.pos-picker`, ≥1401px via `.pos-grid-picker` — **do not** apply this breakpoint to the shared `.pos-grid` class, it's also used by `sales/edit`, `purchases/create`, `purchases/edit`, none of which have a picker column and will break if squeezed into a 3-track template)
- Sections top→bottom: প্রায়ই বিক্রি হওয়া (frequent, `SaleController::create()` ranks `SaleItem` by count over last 60 days) → পছন্দের (favorites, `ItemFavorite` per-user) → সব আইটেম (all items, grouped by `category.name`, uncategorized sorted last as "অন্যান্য")
- Every tile has a ⭐ star (`toggleFavorite()`, POST `items/{item}/favorite` → `ItemFavoriteController::toggle`) that toggles without adding to cart (`event.stopPropagation()`); clicking the rest of the tile calls the same `addItem(id)` used by the search dropdown
- `.picker-scroll` inside `.pos-picker` is the only scrolling element (heading stays fixed) — height must subtract topbar height AND `.content`'s 28px top padding, or the box overflows past the viewport bottom

### ⚠️ Turbo gotcha — NEVER use top-level `const`/`let` in inline page `<script>`
- Turbo re-evaluates body `<script>` on every visit. Top-level `const`/`let` survive in global scope, so the 2nd visit re-declares them → `SyntaxError: Identifier already declared` → **entire script aborts** ("JS dead until refresh").
- **Rule:** all column-0 (top-level) declarations in inline page scripts MUST be `var` (redeclarable). `const`/`let` is fine ONLY inside functions/blocks.
- Functions (`function foo(){}`) and inline `onclick=` handlers re-run/stay global fine — only top-level `let`/`const` break.
- Fixed across 17 views (both create/edit, payment create, index pages, reports, chat, ledger-select, app layout).

---

## Store Config Keys
Accessed via `\App\Models\StoreConfig::get('key', 'default')`:
- `store_name`, `store_owner`, `store_tagline`, `store_phone`, `store_phone2`, `store_address`
- ⚠️ In v2: config is per-shop (shop_id column + composite unique key) — each shop has its own store_name etc.

---

## Completed Features (All Sessions)
⚠️ **Full unabridged changelog moved to [`DEVHISTORY.md`](DEVHISTORY.md)** — item-by-item detail,
exact file names, and reasoning for every numbered change below live there. This section keeps only
a short per-session summary so `CLAUDE.md` stays light to load every session.

1. **Session 1** — Reporting/UX foundation: daily sales report (no-item payments), sale edit/delete audit log (`sale_logs`), negative due/credit balance, purchase invoice+edit, stock zero-hide, floating submit button, `NoCacheHeaders`, today-default date filters.
2. **Session 2** — No-item purchase (advance payment) pattern, purchase due allows negative + অগ্রিম badges, DB indexes + dropdown query optimization. Established **"NEVER cache business data"** rule.
3. **Session 3 (v2 Multi-Shop)** — Core multi-tenant architecture: `shops` table, `ShopScope`/`HasShopScope`, shop-scoped middleware, super admin control panel, closed several cross-shop data-leak bugs, per-shop `store_config`.
4. **Session 4 (Reseller/Root/License)** — Root + reseller panels, `License` model with subscription status, multi-branch shop ownership + `syncShopLocks()`.
5. **Session 5 (Bug Fixes)** — `canManageShop()` fix across 13 controllers, purchase items made nullable, `SetShopScope` `is_locked` check, search `orWhere` scope-bypass fix, removed several `max(0,...)` due caps.
6. **Session 6 (Owner-Side/Mobile)** — Demo login panel, sidebar logout, mobile invoice/submit-bar fixes, Payment Log system (root), reseller client show page + commission dashboard.
7. **Session 7 (Turbo Drive)** — Adopted Hotwire Turbo (SPA nav), জমা entries merged into পরিশোধ তালিকা, fixed the Turbo top-level `const`/`let` SyntaxError bug across 17 views (see Turbo gotcha below).
8. **Session 8 (UI Polish)** — Removed item-search result cap, fixed a `@push('styles')` CSS-leak bug, rewrote keyboard shortcuts (`e.code`), sale-logs filter chips, searchable area combobox partial, renamed মাল→পণ্য sitewide.
9. **Session 9 (তাগাদা/ক্রেডিট/দিনশেষ)** — পরিশোধ তালিকা totals reconciliation, searchable supplier dropdown, floating calculator, তাগাদা লিস্ট (collections + aging), credit limit (warning-only), দিনশেষ রিপোর্ট + cash reconciliation, WhatsApp share, daily DB backup, concurrency hardening (`lockForUpdate`), PWA installable.
10. **Session 10 (মেমো প্রিন্ট/ফন্ট/সেলফ-হোস্ট)** — Sidebar nav highlight, 10 new Bangla fonts, cash memo fits ¼ Demy paper with dynamic density scaling, ALL front-end assets self-hosted (zero external requests), profit/price-jump warnings on sale/purchase create.
11. **Session 11 (আইটেম পিকার/ফেভারিট/ব্র্যান্ড)** — see "Item picker" pattern above; print row-height/minus-sign fixes; Bangla words-in-text extended to more fields; `opening_balance` restricted to admin; purchase overpayment-split removed (direct negative due instead); blank `paid_amount`/`sale_price` field fixes; previous-due banner fix on edit-page load; purchase delete/edit SMS; bold invoice names; item picker + per-user favorites; auto-fill area on customer select; new optional **Brand** field (mirrors Category).

---

## Backup
- **Automated:** `php artisan app:backup-db` — daily 03:00 via scheduler → `storage/app/backups/backup_*.sql.gz` (keeps 14). Same-server only; download periodically for off-server safety.
- Pre-clean DB backup: `storage/backup_before_clean_20260530_073214.sql`

## Test Accounts (local dev)
| Email | Password | Role | Notes |
|-------|----------|------|-------|
| `root@system.com` | `password` | root | System root — manage all |
| `super@admin.com` | `password` | super_admin | Owns প্রধান শাখা (id=1) |
| existing admin | (original) | admin | প্রধান শাখা (id=1) |
| `mirpur@shop.com` | `secret123` | admin | মিরপুর শাখা (id=2) |
