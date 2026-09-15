<?php

namespace App\Console\Commands;

use App\Support\BillTotals;
use Illuminate\Console\Command;

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
        $shopId = $this->option('shop') ? (int) $this->option('shop') : null;

        $sales     = BillTotals::driftingSales($shopId);
        $purchases = BillTotals::driftingPurchases($shopId);

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
