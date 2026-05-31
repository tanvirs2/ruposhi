# Ruposhi POS — Project Context for Claude

## Project Overview
**Bengali UI** Laravel 12 POS/Inventory system for a rice wholesale shop.
- **Local path:** `C:\laragon\www\new_pos`
- **Production:** `pos.numaanhussain.com` on Hostinger shared hosting
- **Prod app path:** `~/domains/pos.numaanhussain.com/pos_app`
- **Prod public:** symlinked `public_html → pos_app/public`
- **GitHub:** `https://github.com/tanvirs2/ruposhi.git`

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

### Customer Due Auto-fix (in ledger)
- `CustomerController::ledger()` recalculates `due_amount` from all sales
- Does NOT use `max(0,...)` — allows negatives to persist

---

## Database Tables (Key)
| Table | Purpose |
|-------|---------|
| `sales` | Sales with `total_amount`, `paid_amount`, `due_amount`, `previous_due`, `extra_cost`, `labor_cost`, `is_edited`, `edit_note` |
| `sale_items` | Line items per sale |
| `purchases` | Stock receives with `extra_cost`, `labor_cost` |
| `purchase_items` | Line items per purchase |
| `customers` | `due_amount` can be negative (credit) |
| `suppliers` | `due_amount` can be negative (credit) |
| `stock` | One row per item, `quantity` can be negative |
| `sale_logs` | Edit/delete audit log with JSON snapshot |
| `items` | Products with `purchase_price`, `sale_price` |

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

### ReportController
- `salesReport()` — daily sales with standalone payments, no-item sales sections
- `saleLogs()` — audit log of edits and deletions
- All date defaults: `now()->toDateString()` (today, NOT startOfMonth)

---

## Views Structure

### Layouts
- `resources/views/layouts/app.blade.php` — sidebar with JS-based active highlight
- Sidebar JS uses `window.location.pathname` for active state

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
| `purchases/create.blade.php` | Supplier required, price empty by default with hint |
| `purchases/edit.blade.php` | Same style as create |
| `customers/ledger.blade.php` | Running balance, "নতুন বিক্রয়" button pre-selects customer |
| `reports/sales.blade.php` | 5-card stats, no-item payments section, standalone payments |
| `reports/sale-logs.blade.php` | Audit log with eye modal |
| `stock/index.blade.php` | Zero-stock rows hidden, negative stock in red |

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
```

---

## Important Patterns

### Bengali digit conversion
All inputs use `toEnglishDigits()` JS helper to convert Bengali numerals to ASCII before parsing.

### `priceEntered` flag
Purchase form: price field starts empty; user sees "আগের: ৳X ব্যবহার" hint. Only included in calc after user types or clicks "ব্যবহার".

### No-cache middleware
`App\Http\Middleware\NoCacheHeaders` — added to web middleware group, prevents browser from serving stale pages.

### Sale log snapshots
Stored in `sale_logs.snapshot` (JSON) with: id, sale_date, customer_name, total_amount, paid_amount, due_amount, items[], payment_method, status, notes.

---

## Store Config Keys
Accessed via `\App\Models\StoreConfig::get('key', 'default')`:
- `store_name`, `store_owner`, `store_tagline`, `store_phone`, `store_phone2`, `store_address`

---

## Recently Completed Features (this session)
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

---

## Backup
Pre-clean DB backup: `storage/backup_before_clean_20260530_073214.sql`
