<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerArea;
use App\Models\StoreConfig;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CollectionController extends Controller
{
    // ── তাগাদা লিস্ট — area-wise due collection worklist with aging ────
    public function index(Request $request)
    {
        $shopId = auth()->user()->shop_id;
        $areaId = $request->get('area_id');
        $age    = in_array($request->get('age'), ['30', '60', '90']) ? (int) $request->get('age') : null;

        $customers = Customer::with('area:id,name')
            ->where('due_amount', '>', 0)
            ->when($areaId, fn ($q) => $q->where('area_id', $areaId))
            ->orderByDesc('due_amount')
            ->get(['id', 'name', 'proprietor', 'phone', 'address', 'area_id', 'due_amount', 'credit_limit']);

        // Debt age = days since the customer last paid anything (standalone
        // payment or paid_amount on a sale). Never paid → age counts from the
        // first sale. Raw DB::table() bypasses ShopScope — manual shop filter.
        $lastPayments = DB::table('customer_payments')
            ->where('shop_id', $shopId)
            ->groupBy('customer_id')
            ->selectRaw('customer_id, MAX(payment_date) as d')
            ->pluck('d', 'customer_id');

        $saleDates = DB::table('sales')
            ->where('shop_id', $shopId)
            ->whereNotNull('customer_id')
            ->groupBy('customer_id')
            ->selectRaw('customer_id,
                         MAX(CASE WHEN paid_amount > 0 THEN sale_date END) as last_paid,
                         MIN(sale_date) as first_sale')
            ->get()
            ->keyBy('customer_id');

        $today = now()->startOfDay();
        $customers->each(function ($c) use ($lastPayments, $saleDates, $today) {
            $lastPaid = collect([
                $lastPayments[$c->id] ?? null,
                optional($saleDates->get($c->id))->last_paid,
            ])->filter()->max();

            $c->never_paid   = $lastPaid === null;
            $anchor          = $lastPaid ?? optional($saleDates->get($c->id))->first_sale;
            $c->last_paid_at = $lastPaid;
            $c->days_since   = $anchor ? Carbon::parse($anchor)->startOfDay()->diffInDays($today) : null;
        });

        // Aging buckets — computed after the area filter, before the age filter
        $buckets = [
            'all' => ['count' => $customers->count(), 'total' => $customers->sum('due_amount')],
        ];
        foreach ([30, 60, 90] as $b) {
            $set = $customers->filter(fn ($c) => $c->days_since !== null && $c->days_since >= $b);
            $buckets[(string) $b] = ['count' => $set->count(), 'total' => $set->sum('due_amount')];
        }

        if ($age) {
            $customers = $customers->filter(fn ($c) => $c->days_since !== null && $c->days_since >= $age)->values();
        }

        $areas = CustomerArea::orderBy('name')->get(['id', 'name']);

        return view('collections.index', compact('customers', 'areas', 'buckets', 'areaId', 'age'));
    }

    // ── Bulk due-reminder SMS to selected customers ────────────────────
    public function bulkSms(Request $request, SmsService $sms)
    {
        $ids = array_filter((array) $request->input('customer_ids', []));
        if (empty($ids)) {
            return back()->with('error', 'কোনো কাস্টমার নির্বাচন করা হয়নি।');
        }
        // Shared hosting: each SMS is a synchronous gateway call — cap the batch
        if (count($ids) > 50) {
            return back()->with('error', 'একবারে সর্বোচ্চ ৫০ জনকে SMS পাঠানো যায়। কম নির্বাচন করুন।');
        }

        $customers = Customer::whereIn('id', $ids)
            ->where('due_amount', '>', 0)
            ->whereNotNull('phone')->where('phone', '!=', '')
            ->get(['id', 'name', 'phone', 'due_amount']);

        if ($customers->isEmpty()) {
            return back()->with('error', 'নির্বাচিত কাস্টমারদের ফোন নম্বর বা বাকী নেই।');
        }

        $storeName  = StoreConfig::get('store_name', 'দোকান');
        $storePhone = StoreConfig::get('store_phone', '');

        $sent = 0;
        $failed = 0;
        foreach ($customers as $c) {
            $msg = "প্রিয় {$c->name},\nআপনার কাছে {$storeName}-এ ৳" . number_format($c->due_amount, 0) . " বাকী আছে।\nঅনুগ্রহ করে দ্রুত পরিশোধ করুন।";
            if ($storePhone) $msg .= "\nযোগাযোগ: {$storePhone}";
            $msg .= "\nধন্যবাদ।";

            $sms->send($c->phone, $msg, $c->name)['success'] ? $sent++ : $failed++;
        }

        $summary = "তাগাদা SMS: {$sent}টি পাঠানো হয়েছে" . ($failed ? ", {$failed}টি ব্যর্থ" : '') . '।';

        return back()->with($sent > 0 ? 'success' : 'error', $summary);
    }
}
