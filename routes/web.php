<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerAreaController;
use App\Http\Controllers\CustomerPaymentController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierPaymentController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\ExtraExpenseController;
use App\Http\Controllers\StoreConfigController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ExtraCostCategoryController;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Super\DashboardController as SuperDashboard;
use App\Http\Controllers\Super\ShopController;
use App\Http\Controllers\Super\ReportController as SuperReportController;

/* ── Error page preview (local dev only) ──────────────────── */
if (app()->environment('local')) {
    Route::get('/test-error/{code}', function ($code) {
        return response()->view("errors.{$code}", [], (int) $code);
    })->where('code', '404|403|500|419|503');
}

/* ── Auth ──────────────────────────────────────────────────── */
Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/* ── Subscription expired page ─────────────────────────────── */
Route::get('/subscription-expired', function () {
    return view('subscription.expired');
})->name('subscription.expired')->middleware('auth');

/* ── Root Admin routes ─────────────────────────────────────── */
Route::middleware(['auth', 'root'])->prefix('root')->name('root.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Root\DashboardController::class, 'index'])->name('dashboard');
    Route::resource('super-admins', \App\Http\Controllers\Root\SuperAdminController::class);
    Route::post('/super-admins/{user}/extend-license', [\App\Http\Controllers\Root\SuperAdminController::class, 'extendLicense'])->name('super-admins.extend-license');
    Route::resource('resellers', \App\Http\Controllers\Root\ResellerController::class);
    // Reseller detail + payout tracking
    Route::get('/resellers/{reseller}/show',               [\App\Http\Controllers\Root\ResellerPayoutController::class, 'show'])->name('resellers.show');
    Route::post('/resellers/{reseller}/payouts',           [\App\Http\Controllers\Root\ResellerPayoutController::class, 'store'])->name('resellers.payouts.store');
    Route::delete('/resellers/{reseller}/payouts/{payout}',[\App\Http\Controllers\Root\ResellerPayoutController::class, 'destroy'])->name('resellers.payouts.destroy');
    // Payment logs
    Route::get('/payments', [\App\Http\Controllers\Root\PaymentController::class, 'index'])->name('payments.index');
    Route::post('/payments', [\App\Http\Controllers\Root\PaymentController::class, 'store'])->name('payments.store');
    Route::delete('/payments/{payment}', [\App\Http\Controllers\Root\PaymentController::class, 'destroy'])->name('payments.destroy');
});

/* ── Reseller routes ───────────────────────────────────────── */
Route::middleware(['auth', 'reseller'])->prefix('reseller')->name('reseller.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Reseller\DashboardController::class, 'index'])->name('dashboard');
    Route::resource('clients', \App\Http\Controllers\Reseller\ClientController::class);
    Route::post('/clients/{user}/extend-license', [\App\Http\Controllers\Reseller\ClientController::class, 'extendLicense'])->name('clients.extend-license');
});

/* ── Super Admin routes ────────────────────────────────────── */
Route::middleware(['auth', 'super_admin', 'check.subscription'])->prefix('super')->name('super.')->group(function () {
    Route::get('/dashboard', [SuperDashboard::class, 'index'])->name('dashboard');
    Route::resource('shops', ShopController::class);
    Route::post('/shops/{shop}/enter', [ShopController::class, 'enter'])->name('shops.enter');
    Route::post('/exit-shop',          [ShopController::class, 'exitShop'])->name('shops.exit');
    Route::get('/reports', [SuperReportController::class, 'index'])->name('reports');
    Route::post('/users/{user}/reset-password', [ShopController::class, 'resetPassword'])->name('users.reset-password');
});

/* ── Protected (shop users) ────────────────────────────────── */
Route::middleware(['auth', 'shop.scope', 'check.subscription'])->group(function () {

    Route::get('/', fn() => redirect()->route('dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /* Customers */
    Route::resource('customers', CustomerController::class);
    Route::get('customers/{customer}/ledger', [CustomerController::class, 'ledger'])->name('customers.ledger');
    Route::get('customers-ledger',            [CustomerController::class, 'ledgerSelect'])->name('customers.ledger-select');

    /* Customer Areas */
    Route::resource('customer-areas', CustomerAreaController::class)->except('show');

    /* Customer Payments */
    Route::resource('customer-payments', CustomerPaymentController::class)->except('edit', 'update');

    Route::resource('items',       ItemController::class);
    Route::resource('categories',  CategoryController::class)->except('show');
    /* Suppliers */
    Route::resource('suppliers', SupplierController::class);
    Route::get('suppliers/{supplier}/ledger', [SupplierController::class, 'ledger'])->name('suppliers.ledger');
    Route::get('suppliers-ledger',            [SupplierController::class, 'ledgerSelect'])->name('suppliers.ledger-select');
    Route::get('suppliers-due-report',        [SupplierController::class, 'dueReport'])->name('suppliers.due-report');

    /* Supplier Payments */
    Route::resource('supplier-payments', SupplierPaymentController::class)->only('index', 'create', 'store', 'destroy');
    Route::resource('sales',     SaleController::class);
    Route::resource('purchases', PurchaseController::class);
    Route::resource('employees', EmployeeController::class);
    Route::resource('expenses',  ExtraExpenseController::class);

    // Chat (private)
    Route::get('/chat',               [ChatController::class, 'index'])->name('chat.index');
    Route::post('/chat/send',         [ChatController::class, 'send'])->name('chat.send');
    Route::get('/chat/poll',          [ChatController::class, 'poll'])->name('chat.poll');
    Route::get('/chat/unread',        [ChatController::class, 'unread'])->name('chat.unread');
    // Chat (group)
    Route::get('/chat/group',         [ChatController::class, 'groupIndex'])->name('chat.group');
    Route::post('/chat/group/send',   [ChatController::class, 'groupSend'])->name('chat.group.send');
    Route::get('/chat/group/poll',    [ChatController::class, 'groupPoll'])->name('chat.group.poll');

    // SMS
    Route::get('/sms',                          [SmsController::class, 'index'])->name('sms.index');
    Route::post('/sms/send',                    [SmsController::class, 'send'])->name('sms.send');
    Route::post('/sms/send-custom',             [SmsController::class, 'sendCustom'])->name('sms.send-custom');
    Route::post('/sms/settings',                [SmsController::class, 'saveSettings'])->name('sms.settings')->middleware('shop.admin');
    Route::delete('/sms/log/{smsLog}',          [SmsController::class, 'destroyLog'])->name('sms.log.destroy');

    Route::get('/stock',              [StockController::class, 'index'])->name('stock.index');
    Route::get('/stock/report',       [StockController::class, 'report'])->name('stock.report');
    Route::get('/stock/low',          [StockController::class, 'low'])->name('stock.low');
    Route::patch('/stock/{stock}',    [StockController::class, 'adjust'])->name('stock.adjust');

    Route::get('/reports/growth',                        [ReportController::class, 'growth'])->name('reports.growth');
    Route::get('/reports',                            [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/daily-payments',             [ReportController::class, 'dailyPayments'])->name('reports.daily-payments');
    Route::get('/reports/daily-supplier-payments',    [ReportController::class, 'dailySupplierPayments'])->name('reports.daily-supplier-payments');
    Route::get('/reports/sales',                      [ReportController::class, 'salesReport'])->name('reports.sales');
    Route::get('/reports/daily-receive',              [ReportController::class, 'dailyReceive'])->name('reports.daily-receive');
    Route::get('/reports/customer-due',               [ReportController::class, 'customerDue'])->name('reports.customer-due');
    Route::get('/reports/daily-sales-stock',          [ReportController::class, 'dailySalesStock'])->name('reports.daily-sales-stock');
    Route::get('/reports/daily-sales-ledger',         [ReportController::class, 'dailySalesLedger'])->name('reports.daily-sales-ledger');
    Route::get('/reports/profit-loss',                [ReportController::class, 'profitLoss'])->name('reports.profit-loss');
    Route::get('/reports/export/profit-loss',         [ReportController::class, 'exportProfitLoss'])->name('reports.export.profit-loss');

    /* Report CSV exports */
    Route::get('/reports/export/sales',                   [ReportController::class, 'exportSales'])->name('reports.export.sales');
    Route::get('/reports/export/daily-payments',          [ReportController::class, 'exportDailyPayments'])->name('reports.export.daily-payments');
    Route::get('/reports/export/daily-supplier-payments', [ReportController::class, 'exportDailySupplierPayments'])->name('reports.export.daily-supplier-payments');
    Route::get('/reports/export/customer-due',            [ReportController::class, 'exportCustomerDue'])->name('reports.export.customer-due');
    Route::get('/reports/export/daily-receive',           [ReportController::class, 'exportDailyReceive'])->name('reports.export.daily-receive');
    Route::get('/reports/export/daily-sales-stock',       [ReportController::class, 'exportDailySalesStock'])->name('reports.export.daily-sales-stock');
    Route::get('/reports/export/daily-sales-ledger',      [ReportController::class, 'exportDailySalesLedger'])->name('reports.export.daily-sales-ledger');

    Route::get('/reports/sale-logs', [ReportController::class, 'saleLogs'])->name('reports.sale-logs');

    /* Store Config — admin only */
    Route::middleware('shop.admin')->group(function () {
        Route::get('/store-config', [StoreConfigController::class, 'index'])->name('store-config.index');
        Route::put('/store-config', [StoreConfigController::class, 'update'])->name('store-config.update');
        Route::post('/store-config/payment-method',        [StoreConfigController::class, 'addPaymentMethod'])->name('store-config.payment-method.add');
        Route::delete('/store-config/payment-method',      [StoreConfigController::class, 'deletePaymentMethod'])->name('store-config.payment-method.delete');
        Route::post('/store-config/multimedia/toggle',     [StoreConfigController::class, 'toggleMultimedia'])->name('store-config.multimedia.toggle');
        Route::post('/store-config/multimedia/interval',   [StoreConfigController::class, 'updateMultimediaInterval'])->name('store-config.multimedia.interval');
        Route::post('/store-config/multimedia/upload',     [StoreConfigController::class, 'uploadMultimedia'])->name('store-config.multimedia.upload');
        Route::delete('/store-config/multimedia',          [StoreConfigController::class, 'deleteMultimedia'])->name('store-config.multimedia.delete');
    });

    /* Extra Cost Categories — admin only */
    Route::middleware('shop.admin')->group(function () {
        Route::get('/extra-cost-categories',                   [ExtraCostCategoryController::class, 'index'])->name('extra-cost-categories.index');
        Route::post('/extra-cost-categories',                  [ExtraCostCategoryController::class, 'store'])->name('extra-cost-categories.store');
        Route::put('/extra-cost-categories/{extraCostCategory}',    [ExtraCostCategoryController::class, 'update'])->name('extra-cost-categories.update');
        Route::delete('/extra-cost-categories/{extraCostCategory}', [ExtraCostCategoryController::class, 'destroy'])->name('extra-cost-categories.destroy');
    });

    /* Shop staff/user management — shop admin only */
    Route::resource('users', UserController::class)->except('show')->middleware('shop.admin');

    /* Profile */
    Route::get('/profile',          [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile',          [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'changePassword'])->name('profile.password');

    /* JSON API for POS dropdowns */
    Route::get('/api/items',     fn() => \App\Models\Item::with('stock')->get(['id','name','sale_price','purchase_price','unit']));
    Route::get('/api/customers', fn() => \App\Models\Customer::get(['id','name','phone','due_amount']));
    Route::get('/api/suppliers', fn() => \App\Models\Supplier::get(['id','name','phone','due_amount']));

    /* Global search */
    Route::get('/search', function (\Illuminate\Http\Request $r) {
        $q = trim($r->get('q', ''));
        if (mb_strlen($q) < 1) return response()->json(['items' => [], 'customers' => [], 'suppliers' => []]);
        return response()->json([
            'items'     => \App\Models\Item::where('name', 'like', "%{$q}%")->limit(6)->get(['id', 'name']),
            'customers' => \App\Models\Customer::where('name', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%")->limit(5)->get(['id', 'name', 'phone']),
            'suppliers' => \App\Models\Supplier::where('name', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%")->limit(5)->get(['id', 'name', 'phone']),
        ]);
    })->name('search');
});
