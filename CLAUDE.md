# Ruposhi POS — Project Context for Claude

## Project Overview
**Bengali UI** Laravel 12 POS/Inventory system for a rice wholesale shop.
- **Local path:** `C:\laragon\www\new_pos`
- **Production:** `pos.numaanhussain.com` on Hostinger shared hosting
- **Prod app path:** `~/domains/pos.numaanhussain.com/pos_app`
- **Prod public:** symlinked `public_html → pos_app/public`
- **GitHub:** `https://github.com/tanvirs2/ruposhi.git`
- **Dev server:** `php artisan serve --port=8899`

## Git Branch Strategy
- **`main`** — v1 (single-shop, stable production backup — do NOT touch)
- **`v2-multi-shop`** — active development branch (multi-tenant)
- **⚠️ RULE:** Only commit locally. NEVER `git push` without explicit user request.

## Deploy Command (ALWAYS use this exact command)
```bash
cd ~/domains/pos.numaanhussain.com/pos_app && git pull origin main && php artisan migrate
```
- `git pull origin main` — NOT bare `git pull` (doesn't work on production)
- User runs SSH commands themselves — NEVER ask for passwords or initiate SSH

## Tech Stack
- Laravel 12, PHP 8.2, MySQL 8, Blade templating
- Bengali UI (`Hind Siliguri` font)
- CSS: `public/css/app.css` (custom, no Tailwind)
- No npm/Vite build step needed — CSS/JS are static files

---

## ⚡ v2 Multi-Shop Architecture (branch: `v2-multi-shop`)

### Concept
One super admin manages multiple isolated shops. Each shop's data (products, sales, customers, etc.) is fully scoped to that shop and invisible to other shops.

### Roles
| Role | shop_id | Access |
|------|---------|--------|
| `super_admin` | `null` | All shops — control panel at `/super` |
| `admin` | shop ID | Full access to own shop + staff management |
| `staff` | shop ID | Own shop (limited — no user management) |

### Core Isolation: Global Scope
- **`app/Scopes/ShopScope.php`** — auto-filters every Eloquent query by `auth()->user()->shop_id`
  - `super_admin` bypasses (no filter)
  - Applied via `HasShopScope` trait on all 22 tenant models
- **`app/Traits/HasShopScope.php`** — applied to models; auto-fills `shop_id` on `creating`
- **`app/Models/User.php`** — deliberately has NO ShopScope (powers auth); all User queries must be **manually** scoped by `shop_id`

### ⚠️ Critical Gotchas
1. **Raw `DB::table()` queries bypass global scope** — must manually add `->where('shop_id', ...)` or `->where('sales.shop_id', ...)`
2. **`User` model has NO global scope** — always filter manually: `User::where('shop_id', $shopId)`
3. **Route model binding respects global scope** — cross-shop ID access returns 404 automatically
4. **Per-shop composite unique constraints** — `(shop_id, col)` not global unique (e.g. items.code, store_config.key)

### Models with `HasShopScope` (22 total)
Item, Sale, Purchase, Customer, Supplier, Category, Stock, Employee, ExtraExpense, CustomerArea, CustomerPayment, SupplierPayment, SaleLog, SmsLog, ChatMessage, GroupChatMessage, ExtraCostCategory, StoreConfig, SaleItem, PurchaseItem, SaleExtraCost, PurchaseExtraCost

### Middleware
| Alias | File | Purpose |
|-------|------|---------|
| `super_admin` | `app/Http/Middleware/SuperAdmin.php` | Aborts 403 if not super_admin |
| `shop.scope` | `app/Http/Middleware/SetShopScope.php` | Redirects super_admin → control panel; aborts 403 if shop-less user |
| `shop.admin` | `app/Http/Middleware/ShopAdmin.php` | Aborts 403 if not admin (protects user management) |

### Route Groups
```
/super/*         → [auth, super_admin]  — super admin control panel
/*               → [auth, shop.scope]   — all shop users
/users/*         → + shop.admin         — admin-only user management
```

### Key Files (v2)
| File | Purpose |
|------|---------|
| `app/Models/Shop.php` | Shops table — name, address, phone, logo, is_active |
| `app/Scopes/ShopScope.php` | Core isolation — filters queries by shop_id |
| `app/Traits/HasShopScope.php` | Applied to all tenant models |
| `app/Http/Controllers/Super/ShopController.php` | CRUD for shops + provision admin account |
| `app/Http/Controllers/Super/DashboardController.php` | Super admin dashboard |
| `app/Http/Controllers/UserController.php` | Shop-scoped staff/user management (manual scope) |
| `resources/views/layouts/super.blade.php` | Dark control panel layout for super admin |
| `resources/views/super/` | Super admin views (dashboard, shops) |
| `resources/views/users/` | User management views (index, create, edit) |

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

### Seeder
`database/seeders/SuperAdminSeeder.php` — creates `super@admin.com/password` (super_admin), default shop "প্রধান শাখা", assigns all existing data to shop 1.

---

## Key Business Rules

### Customer/Supplier Due Amount
- **Allows negative values** (credit/advance balance from overpayment)
- Formula: `customer.due = customer.due + net - paid` (no max(0) cap)
- Negative due shown as: 🔵 "অগ্রিম ৳X" badge (blue)
- Positive due shown as: 🔴 "৳X" badge (red)
- `SaleController` and `PurchaseController` use this formula consistently

### No-item Sales (Previous Due Payment)
- Users create a "sale" with NO items to pay off previous customer due
- These appear in the daily sales report as a separate section
- `$grandNoItemDueReduction = min(paid, previous_due)` — only effective due reduction counts
- `sale.previous_due` is captured BEFORE the sale is made

### No-item Purchases (Advance Supplier Payment)
- Users can submit a purchase with NO items — treated as advance payment to supplier
- `items` validation is `nullable` (not required) in PurchaseController
- `due_amount = total - paid` (allows negative — no `max(0,...)` cap)
- Negative due on purchase = advance/credit for that supplier
- Yellow warning shown in create form when paid > 0 with no items
- Invoice shows "অগ্রিম পরিশোধ" (yellow notice) instead of item table
- Purchase list shows blue "অগ্রিম ৳X" badge for negative due rows

### Due Auto-fix (in ledgers)
- `CustomerController::ledger()` recalculates `due_amount` from all sales — NO `max(0,...)`
- `SupplierController::ledger()` same — NO `max(0,...)` cap (was a bug, now fixed)
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
| `shops` | Shop records — name, address, phone, logo, is_active |
| `sales` | Sales with `shop_id`, `total_amount`, `paid_amount`, `due_amount`, `previous_due`, `extra_cost`, `labor_cost`, `is_edited`, `edit_note` |
| `sale_items` | Line items per sale (has `shop_id`) |
| `purchases` | Stock receives with `shop_id`, `extra_cost`, `labor_cost` |
| `purchase_items` | Line items per purchase (has `shop_id`) |
| `customers` | `shop_id`, `due_amount` can be negative (credit) |
| `suppliers` | `shop_id`, `due_amount` can be negative (credit) |
| `stock` | `shop_id`, one row per item per shop, `quantity` can be negative |
| `sale_logs` | Edit/delete audit log with JSON snapshot |
| `items` | `shop_id`, products with `purchase_price`, `sale_price` |
| `store_config` | `shop_id`, key-value; composite unique `(shop_id, key)` |
| `users` | `shop_id` (nullable for super_admin), role = super_admin/admin/staff |

---

## Controllers (Key Methods)

### SaleController
- `store()` — creates sale, decrements stock, updates `customer.due += net - paid`
- `update()` — reverses old effects then re-applies, logs before change
- `destroy()` — restores stock, reverses due, logs deletion
- `logSale()` — saves snapshot to `sale_logs` table
- After store: redirects to `sales.show`

### PurchaseController
- `store()` — creates purchase, increments stock, updates `supplier.due += total - paid`
- `update()` — reverses old then re-applies
- `destroy()` — decrements stock, reverses supplier due
- After store: redirects to `purchases.show` (invoice)
- **Supplier is required** — cannot submit without selecting
- **Items are optional** — no items = advance payment to supplier
- `due_amount = total - paid` — NO `max(0,...)` cap, allows negative credit
- `$request->items ?? []` — handles empty cart gracefully

### ReportController
- `salesReport()` — daily sales with standalone payments, no-item sales sections
- `saleLogs()` — audit log of edits and deletions
- All date defaults: `now()->toDateString()` (today, NOT startOfMonth)
- ⚠️ All 16+ `DB::table()` raw queries manually filtered with `->where('sales.shop_id', auth()->user()->shop_id)`

### SupplierController
- `ledger()` — auto-fix supplier due (NO max(0) cap — allows negative credit)
- `ledgerSelect()` — shows blue "অগ্রিম" badge for credit suppliers

### UserController (v2)
- Shop-scoped staff management — all queries manually scoped by `shop_id`
- Guards: self-delete blocked, last-admin blocked, cross-shop edit → 404, super_admin role injection rejected

---

## Views Structure

### Layouts
- `resources/views/layouts/app.blade.php` — sidebar with JS-based active highlight; shows shop name in brand/breadcrumb
- `resources/views/layouts/super.blade.php` — dark control panel layout for super admin
- Sidebar JS uses `window.location.pathname` for active state
- Admin-only sidebar link: "ব্যবহারকারী" (user management) — guarded by `@if(auth()->user()->role === 'admin')`

### Sale Invoice (`sales/show.blade.php`)
- Store name as SVG arc (`partials/store-name-arc.blade.php`)
- Header: arc name → owner badge → tagline → address → phones
- CSS class `.memo-under-arch { margin-top: -58px }` tucks content under arc

### Purchase Invoice (`purchases/show.blade.php`)
- Same style as sale invoice — store header with arc

### Key Blade Files
| File | Notes |
|------|-------|
| `sales/create.blade.php` | Cart, customer search, extra/labor toggles, floating submit button |
| `sales/edit.blade.php` | Same as create but pre-populated, inline item-change button |
| `sales/show.blade.php` | Invoice: tfoot order = ছাড় → পূর্বের বাকী → extra/labor → বিক্রয় মোট → সর্বমোট → পরিশোধ → বাকী |
| `purchases/create.blade.php` | Supplier required, items optional, yellow warning for no-item+paid |
| `purchases/edit.blade.php` | Same style as create |
| `purchases/index.blade.php` | Blue "অগ্রিম" badge for negative due rows; tfoot shows net credit in blue |
| `purchases/show.blade.php` | Full due display logic (see Due Display Logic above) |
| `customers/ledger.blade.php` | Running balance, "নতুন বিক্রয়" button pre-selects customer |
| `suppliers/ledger-select.blade.php` | Blue "অগ্রিম" badge for credit suppliers |
| `reports/sales.blade.php` | 5-card stats, no-item payments section, standalone payments |
| `reports/sale-logs.blade.php` | Audit log with eye modal |
| `stock/index.blade.php` | Zero-stock rows hidden, negative stock in red |
| `users/index.blade.php` | Staff list with role badge, "আপনি" tag for self |
| `users/create.blade.php` | Create staff/admin account |
| `users/edit.blade.php` | Edit; role select disabled for self + hidden input fallback |
| `super/shops/index.blade.php` | All shops table with status |
| `super/shops/create.blade.php` | Provision new shop + admin account in one form |

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

---

## Store Config Keys
Accessed via `\App\Models\StoreConfig::get('key', 'default')`:
- `store_name`, `store_owner`, `store_tagline`, `store_phone`, `store_phone2`, `store_address`
- ⚠️ In v2: config is per-shop (shop_id column + composite unique key) — each shop has its own store_name etc.

---

## Recently Completed Features

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
11. Sales list expandable মালের বিবরণ column
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

### Session 3 — v2 Multi-Shop (branch: `v2-multi-shop`)
25. Core multi-tenant architecture: `shops` table, `shop_id` on all tenant tables, role enum (super_admin/admin/staff)
26. `ShopScope` global scope + `HasShopScope` trait — auto-filter + auto-fill shop_id
27. Three middleware: `super_admin`, `shop.scope`, `shop.admin`
28. Super admin control panel (`/super`) — dark layout, shop CRUD, provision shop + admin account together
29. Closed data leaks: 16 raw DB::table() queries in ReportController manually scoped
30. Closed data leaks: DashboardController stock_value query, ChatController user list, GroupMessageSent broadcast
31. Per-shop store_config: composite unique `(shop_id, key)`
32. Shop name shown in sidebar brand and topbar breadcrumb
33. Shop-scoped staff/user management: UserController (manual scope), index/create/edit views, `shop.admin` gate
34. Security guards: cross-shop edit→404, self-delete blocked, last-admin blocked, super_admin role injection rejected

---

## Backup
Pre-clean DB backup: `storage/backup_before_clean_20260530_073214.sql`

## Test Accounts (local dev)
| Email | Password | Role | Shop |
|-------|----------|------|------|
| `super@admin.com` | `password` | super_admin | — |
| existing admin | (original) | admin | প্রধান শাখা (id=1) |
| `mirpur@shop.com` | `secret123` | admin | মিরপুর শাখা (id=2) |
