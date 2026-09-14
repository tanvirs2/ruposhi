<?php

namespace App\Console\Commands;

use App\Models\Shop;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetStock extends Command
{
    protected $signature = 'pos:reset-stock
                            {--shop= : নির্দিষ্ট shop_id (না দিলে সব শাখা)}
                            {--force : কনফার্মেশন ছাড়াই চালান}';

    protected $description = 'বর্তমান স্টক শূন্য (০) করে দিন — বিক্রয়/ক্রয়ের রেকর্ড অপরিবর্তিত থাকে';

    public function handle(): int
    {
        // No auth in CLI, so ShopScope never applies — filter shop_id manually.
        $shopId = $this->option('shop');

        $query = DB::table('stock');

        if ($shopId !== null) {
            if (!Shop::withoutGlobalScopes()->whereKey($shopId)->exists()) {
                $this->error("❌  shop_id {$shopId} — এই শাখা পাওয়া যায়নি।");
                return 1;
            }
            $query->where('shop_id', $shopId);
        }

        $rows    = (clone $query)->count();
        $nonZero = (clone $query)->where('quantity', '!=', 0)->count();
        $scope   = $shopId !== null ? "শাখা #{$shopId}" : 'সব শাখা';

        if ($rows === 0) {
            $this->warn("কোনো স্টক রেকর্ড নেই ({$scope})।");
            return 0;
        }

        $this->newLine();
        $this->line("  স্কোপ          : {$scope}");
        $this->line("  স্টক রেকর্ড     : {$rows}");
        $this->line("  শূন্য নয় এমন   : {$nonZero}");
        $this->newLine();
        $this->warn('⚠️  এটি অপরিবর্তনীয় — চালানোর আগে ব্যাকআপ নিন (php artisan app:backup-db)।');

        if (!$this->option('force')
            && !$this->confirm("উপরের সব স্টক ০ করে দেবেন?", false)) {
            $this->line('বাতিল করা হয়েছে।');
            return 0;
        }

        // updated_at is left untouched on purpose — the "সর্বশেষ আপডেট" column should
        // keep showing the real last movement, not the day of the reset.
        $affected = $query->update(['quantity' => 0]);

        $this->newLine();
        $this->info("✅  {$affected} টি স্টক রেকর্ড ০ করা হয়েছে ({$scope})।");

        return 0;
    }
}
