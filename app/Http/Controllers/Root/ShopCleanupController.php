<?php

namespace App\Http\Controllers\Root;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class ShopCleanupController extends Controller
{
    /**
     * Transaction tables — wiped in "smart reset".
     * Order matters: children before parents (FK safety even with checks off).
     */
    private const TXN_TABLES = [
        'sale_logs',
        'sale_extra_costs',
        'sale_items',
        'sales',
        'purchase_deposits',
        'purchase_extra_costs',
        'purchase_items',
        'purchases',
        'customer_payments',
        'supplier_payments',
        'extra_expenses',
        'day_closings',
        'sms_logs',
        'chat_messages',
        'group_chat_messages',
        'pending_edits',
    ];

    /**
     * Every shop-scoped table available for the "custom" single-table clean,
     * with Bangla labels. Master tables are included but flagged so the UI can
     * warn before wiping them.
     */
    private const CLEANABLE = [
        // ── লেনদেন (নিরাপদ — মাস্টার ডেটা থাকবে) ──
        'sales'                => ['label' => 'বিক্রয় (sales)',                 'master' => false],
        'sale_items'           => ['label' => 'বিক্রয় আইটেম (sale_items)',       'master' => false],
        'sale_extra_costs'     => ['label' => 'বিক্রয় অতিরিক্ত খরচ',            'master' => false],
        'sale_logs'            => ['label' => 'বিক্রয় লগ (sale_logs)',          'master' => false],
        'purchases'            => ['label' => 'পণ্য গ্রহণ (purchases)',          'master' => false],
        'purchase_items'       => ['label' => 'গ্রহণ আইটেম (purchase_items)',    'master' => false],
        'purchase_extra_costs' => ['label' => 'গ্রহণ অতিরিক্ত খরচ',             'master' => false],
        'purchase_deposits'    => ['label' => 'গ্রহণ জমা (purchase_deposits)',   'master' => false],
        'customer_payments'    => ['label' => 'কাস্টমার পরিশোধ',                'master' => false],
        'supplier_payments'    => ['label' => 'সাপ্লায়ার পরিশোধ',               'master' => false],
        'extra_expenses'       => ['label' => 'অতিরিক্ত খরচ (extra_expenses)',   'master' => false],
        'day_closings'         => ['label' => 'দিনশেষ ক্যাশ (day_closings)',     'master' => false],
        'sms_logs'             => ['label' => 'SMS লগ (sms_logs)',              'master' => false],
        'chat_messages'        => ['label' => 'চ্যাট মেসেজ (chat_messages)',     'master' => false],
        'group_chat_messages'  => ['label' => 'গ্রুপ চ্যাট (group_chat_messages)','master' => false],
        'pending_edits'        => ['label' => 'পেন্ডিং এডিট (pending_edits)',    'master' => false],

        // ── মাস্টার ডেটা (সতর্কতা — মুছলে ফিরবে না) ──
        'customers'            => ['label' => '⚠ কাস্টমার তালিকা (customers)',   'master' => true],
        'suppliers'            => ['label' => '⚠ সাপ্লায়ার তালিকা (suppliers)',  'master' => true],
        'items'                => ['label' => '⚠ পণ্য তালিকা (items)',          'master' => true],
        'stock'                => ['label' => '⚠ স্টক (stock)',                 'master' => true],
        'categories'           => ['label' => '⚠ ক্যাটাগরি (categories)',        'master' => true],
        'customer_areas'       => ['label' => '⚠ এলাকা (customer_areas)',        'master' => true],
        'extra_cost_categories'=> ['label' => '⚠ খরচ ক্যাটাগরি',               'master' => true],
        'deposit_categories'   => ['label' => '⚠ জমা ক্যাটাগরি',               'master' => true],
        'employees'            => ['label' => '⚠ কর্মচারী (employees)',          'master' => true],
    ];

    public function index()
    {
        $shops = Shop::with('superAdmin:id,name')
            ->orderBy('name')
            ->get()
            ->map(function ($shop) {
                return [
                    'id'        => $shop->id,
                    'name'      => $shop->name,
                    'owner'     => $shop->superAdmin->name ?? '—',
                    'is_locked' => $shop->is_locked,
                    'sales'     => DB::table('sales')->where('shop_id', $shop->id)->count(),
                    'purchases' => DB::table('purchases')->where('shop_id', $shop->id)->count(),
                    'customers' => DB::table('customers')->where('shop_id', $shop->id)->count(),
                    'suppliers' => DB::table('suppliers')->where('shop_id', $shop->id)->count(),
                    'items'     => DB::table('items')->where('shop_id', $shop->id)->count(),
                ];
            });

        return view('root.database.cleanup', [
            'shops'     => $shops,
            'cleanable' => self::CLEANABLE,
        ]);
    }

    /**
     * Smart reset — wipe all transactions for one shop, but keep the master
     * data (customers, suppliers, items, categories, areas, employees, config).
     * Customer/supplier dues and stock quantities are reset to zero so no stale
     * balances survive the transactions that produced them.
     */
    public function smartReset(Request $request)
    {
        $shop = $this->validatedShop($request);
        if ($shop instanceof \Illuminate\Http\RedirectResponse) {
            return $shop;
        }

        Artisan::call('app:backup-db');

        DB::transaction(function () use ($shop) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            foreach (self::TXN_TABLES as $table) {
                DB::table($table)->where('shop_id', $shop->id)->delete();
            }

            // Keep the rows, zero the derived balances/stock
            DB::table('customers')->where('shop_id', $shop->id)->update(['due_amount' => 0]);
            DB::table('suppliers')->where('shop_id', $shop->id)->update(['due_amount' => 0]);
            DB::table('stock')->where('shop_id', $shop->id)->update(['quantity' => 0]);

            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        });

        return back()->with('success',
            "\"{$shop->name}\" শাখার সব লেনদেন মুছে ফেলা হয়েছে। কাস্টমার, সাপ্লায়ার ও পণ্য তালিকা অক্ষত আছে; সব বকেয়া ও স্টক শূন্য করা হয়েছে। (ব্যাকআপ স্বয়ংক্রিয়ভাবে নেওয়া হয়েছে।)");
    }

    /**
     * Custom clean — wipe a single chosen table for one shop.
     */
    public function customClean(Request $request)
    {
        $request->validate([
            'table' => 'required|string|in:' . implode(',', array_keys(self::CLEANABLE)),
        ], [
            'table.required' => 'একটি টেবিল নির্বাচন করুন',
            'table.in'       => 'অননুমোদিত টেবিল',
        ]);

        $shop = $this->validatedShop($request);
        if ($shop instanceof \Illuminate\Http\RedirectResponse) {
            return $shop;
        }

        $table = $request->table;

        Artisan::call('app:backup-db');

        $deleted = 0;
        DB::transaction(function () use ($table, $shop, &$deleted) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            $deleted = DB::table($table)->where('shop_id', $shop->id)->delete();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        });

        $label = self::CLEANABLE[$table]['label'];
        return back()->with('success',
            "\"{$shop->name}\" শাখার \"{$label}\" টেবিল থেকে {$deleted}টি সারি মুছে ফেলা হয়েছে। (ব্যাকআপ স্বয়ংক্রিয়ভাবে নেওয়া হয়েছে।)");
    }

    /**
     * Shared validation: shop must exist and the typed confirmation must match
     * the shop's exact name (so nobody wipes the wrong shop by a stray click).
     */
    private function validatedShop(Request $request)
    {
        $request->validate([
            'shop_id' => 'required|integer|exists:shops,id',
            'confirm' => 'required|string',
        ], [
            'shop_id.required' => 'শাখা নির্বাচন করুন',
            'shop_id.exists'   => 'শাখা খুঁজে পাওয়া যায়নি',
            'confirm.required'  => 'নিশ্চিত করতে শাখার নাম টাইপ করুন',
        ]);

        $shop = Shop::find($request->shop_id);

        if (trim($request->confirm) !== $shop->name) {
            return back()
                ->withInput()
                ->with('error', "নিশ্চিতকরণ মেলেনি — হুবহু শাখার নাম \"{$shop->name}\" টাইপ করুন।");
        }

        return $shop;
    }
}
