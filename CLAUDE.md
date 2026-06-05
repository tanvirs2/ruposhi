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
After pulling, also run:
```bash
php artisan config:clear && php artisan route:clear && php artisan view:clear
```
- `git pull origin main` — NOT bare `git pull` (doesn't work on production)
- User runs SSH commands themselves — NEVER ask for passwords or initiate SSH

## First-Time Production Setup (seeders)
After initial deploy of v2, run once on server:
```bash
php artisan db:seed --class=SuperAdminSeeder   # backfills super_admin_id + shop_id on existing data
php artisan db:seed --class=RootUserSeeder      # creates root@system.com account
```

## Tech Stack
- Laravel 12, PHP 8.2, MySQL 8, Blade templating
- Bengali UI (`Hind Siliguri` font)
- CSS: `public/css/app.css` (custom, no Tailwind)
- No npm/Vite build step needed — CSS/JS are static files

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

### Models with `HasShopScope` (22 total)
Item, Sale, Purchase, Customer, Supplier, Category, Stock, Employee, ExtraExpense, CustomerArea, CustomerPayment, SupplierPayment, SaleLog, SmsLog, ChatMessage, GroupChatMessage, ExtraCostCategory, StoreConfig, SaleItem, PurchaseItem, SaleExtraCost, PurchaseExtraCost

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
| `customers` | `shop_id`, `due_amount` can be negative (credit) |
| `suppliers` | `shop_id`, `due_amount` can be negative (credit) |
| `stock` | `shop_id`, one row per item per shop, `quantity` can be negative |
| `sale_logs` | Edit/delete audit log with JSON snapshot |
| `items` | `shop_id`, products with `purchase_price`, `sale_price` |
| `store_config` | `shop_id`, key-value; composite unique `(shop_id, key)` |
| `users` | `shop_id` (nullable for root/reseller/super_admin), role = root/reseller/super_admin/admin/staff |
| `resellers` | Reseller profile — commission, max_clients, can_extend_license |

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
| `sales/create.blade.php` | Cart, customer search, extra/labor toggles, floating submit button |
| `sales/edit.blade.php` | Same as create but pre-populated, inline item-change button |
| `sales/show.blade.php` | Invoice: tfoot order = ছাড় → পূর্বের বাকী → extra/labor → বিক্রয় মোট → সর্বমোট → পরিশোধ → বাকী |
| `purchases/create.blade.php` | Supplier required, items optional, yellow warning for no-item+paid |
| `purchases/edit.blade.php` | Same style as create; items nullable |
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

---

## Store Config Keys
Accessed via `\App\Models\StoreConfig::get('key', 'default')`:
- `store_name`, `store_owner`, `store_tagline`, `store_phone`, `store_phone2`, `store_address`
- ⚠️ In v2: config is per-shop (shop_id column + composite unique key) — each shop has its own store_name etc.

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

---

## Backup
Pre-clean DB backup: `storage/backup_before_clean_20260530_073214.sql`

## Test Accounts (local dev)
| Email | Password | Role | Notes |
|-------|----------|------|-------|
| `root@system.com` | `password` | root | System root — manage all |
| `super@admin.com` | `password` | super_admin | Owns প্রধান শাখা (id=1) |
| existing admin | (original) | admin | প্রধান শাখা (id=1) |
| `mirpur@shop.com` | `secret123` | admin | মিরপুর শাখা (id=2) |
