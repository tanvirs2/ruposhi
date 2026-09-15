<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Finds চালান whose stored total_amount no longer matches the sum of their
 * lines. The usual cause is an item that was deleted while it still had
 * sale_items / purchase_items rows: those FKs used to cascade, so the lines
 * were erased and the bill stopped adding up.
 */
class AuditBillTotals extends Command
{
    protected $signature = 'pos:audit-bill-totals
                            {--shop= : নির্দিষ্ট shop_id (না দিলে সব শাখা)}';

    protected $description = 'যেসব চালানের মোট অঙ্ক তার লাইনগুলোর যোগফলের সাথে মিলছে না, সেগুলো খুঁজে দেখান';

    public function handle(): int
    {
        // No auth in CLI, so ShopScope never applies — filter shop_id manually.
        $shopId = $this->option('shop');

        $saleGap = $this->gapExpr('sales', 'sale_items', 'sale_extra_costs', true);
        $sales   = DB::table('sales')
            ->when($shopId, fn ($q) => $q->where('shop_id', $shopId))
            ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id')
            ->selectRaw("sales.id, sales.sale_date as dt, sales.total_amount,
                         COALESCE(customers.name, 'ওয়াক-ইন') as party, {$saleGap} as gap")
            ->havingRaw('ABS(gap) > 0.01')
            ->orderBy('sales.id')
            ->get();

        $purchaseGap = $this->gapExpr('purchases', 'purchase_items', 'purchase_extra_costs', false);
        $purchases   = DB::table('purchases')
            ->when($shopId, fn ($q) => $q->where('shop_id', $shopId))
            ->leftJoin('suppliers', 'purchases.supplier_id', '=', 'suppliers.id')
            ->selectRaw("purchases.id, purchases.purchase_date as dt, purchases.total_amount,
                         COALESCE(suppliers.name, '—') as party, {$purchaseGap} as gap")
            ->havingRaw('ABS(gap) > 0.01')
            ->orderBy('purchases.id')
            ->get();

        $this->render('বিক্রয় (চালান)', $sales);
        $this->render('ক্রয় (রিসিট)', $purchases);

        if ($sales->isEmpty() && $purchases->isEmpty()) {
            $this->info('✅ সব চালানের অঙ্ক মিলছে।');
            return self::SUCCESS;
        }

        $this->newLine();
        $this->warn('এই চালানগুলোর লাইন মুছে গেছে (সম্ভবত আইটেম ডিলিটের কারণে)।');
        $this->line('লাইনগুলো ফিরে পেতে হলে ডিলিটের আগের ব্যাকআপ থেকে পুনরুদ্ধার করতে হবে।');
        $this->line('লেজারে এই ফারাক এখন "সমন্বয়" সারিতে দেখানো হয়, লুকানো থাকে না।');

        return self::SUCCESS;
    }

    /** total_amount − (Σ lines [− discount] + extra costs) as a SQL expression. */
    private function gapExpr(string $table, string $lines, string $extras, bool $hasDiscount): string
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

    private function render(string $title, $rows): void
    {
        $this->newLine();
        $this->line("── {$title} — গরমিল: {$rows->count()}টি");

        if ($rows->isEmpty()) {
            return;
        }

        $this->table(
            ['নং', 'তারিখ', 'পার্টি', 'মোট অঙ্ক', 'ফারাক'],
            $rows->map(fn ($r) => [
                str_pad($r->id, 6, '0', STR_PAD_LEFT),
                $r->dt,
                mb_substr(trim($r->party), 0, 30),
                number_format($r->total_amount, 0),
                number_format($r->gap, 0),
            ])
        );
        $this->line('  সর্বমোট ফারাক: ' . number_format($rows->sum('gap'), 0));
    }
}
