<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * A চালান's stored total_amount is canonical — customers.due_amount,
 * suppliers.due_amount and both সর্বমোট বাকী cards are built from it. The
 * ledgers, by contrast, *reconstruct* each bill from its lines. When the two
 * disagree the ledger's অবশিষ্ট column drifts away from the card.
 *
 * The known cause is item deletion: sale_items.item_id and
 * purchase_items.item_id used to cascade, so deleting a traded item erased its
 * lines from every past চালান while the চালান kept its original total.
 *
 * Shared by pos:audit-bill-totals and pos:recover-bill-lines so both agree on
 * exactly what "drifting" means.
 */
class BillTotals
{
    /** total_amount − (Σ lines [− discount] + extra costs), as a SQL expression. */
    public static function gapExpr(string $table, string $lines, string $extras, bool $hasDiscount): string
    {
        $fk       = $table === 'sales' ? 'sale_id' : 'purchase_id';
        $discount = $hasDiscount ? "- {$table}.discount" : '';

        // Prefer the categorised extra-cost rows; fall back to the legacy
        // scalar column only when a bill has no rows at all — this mirrors how
        // the ledgers render them.
        return "{$table}.total_amount - ("
             . "COALESCE((SELECT SUM(l.subtotal) FROM {$lines} l WHERE l.{$fk} = {$table}.id), 0)"
             . " {$discount}"
             . " + COALESCE((SELECT SUM(x.amount) FROM {$extras} x WHERE x.{$fk} = {$table}.id),"
             . " {$table}.extra_cost))";
    }

    public static function driftingSales(?int $shopId = null): Collection
    {
        $gap = self::gapExpr('sales', 'sale_items', 'sale_extra_costs', true);

        return DB::table('sales')
            ->when($shopId, fn ($q) => $q->where('sales.shop_id', $shopId))
            ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id')
            ->selectRaw("sales.id, sales.sale_date as dt, sales.total_amount,
                         COALESCE(customers.name, 'ওয়াক-ইন') as party, {$gap} as gap")
            ->havingRaw('ABS(gap) > 0.01')
            ->orderBy('sales.id')
            ->get();
    }

    public static function driftingPurchases(?int $shopId = null): Collection
    {
        $gap = self::gapExpr('purchases', 'purchase_items', 'purchase_extra_costs', false);

        return DB::table('purchases')
            ->when($shopId, fn ($q) => $q->where('purchases.shop_id', $shopId))
            ->leftJoin('suppliers', 'purchases.supplier_id', '=', 'suppliers.id')
            ->selectRaw("purchases.id, purchases.purchase_date as dt, purchases.total_amount,
                         COALESCE(suppliers.name, '—') as party, {$gap} as gap")
            ->havingRaw('ABS(gap) > 0.01')
            ->orderBy('purchases.id')
            ->get();
    }
}
