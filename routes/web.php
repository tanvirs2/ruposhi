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
use App\Http\Controllers\ReceiptProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ExtraCostCategoryController;
use App\Http\Controllers\DepositCategoryController;
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
Route::view('/subscription-expired', 'subscription.expired')
    ->name('subscription.expired')->middleware('auth');

/* ── Root Admin routes ─────────────────────────────────────── */
Route::middleware(['auth', 'root'])->prefix('root')->name('root.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Root\DashboardController::class, 'index'])->name('dashboard');
    Route::resource('super-admins', \App\Http\Controllers\Root\SuperAdminController::class);
    Route::post('/super-admins/{user}/extend-license', [\App\Http\Controllers\Root\SuperAdminController::class, 'extendLicense'])->name('super-admins.extend-license');
    Route::post('/super-admins/{user}/expire-license',  [\App\Http\Controllers\Root\SuperAdminController::class, 'expireLicense'])->name('super-admins.expire-license');
    Route::resource('resellers', \App\Http\Controllers\Root\ResellerController::class)->except('show');
    // Reseller detail + payout tracking
    Route::get('/resellers/{reseller}/show',               [\App\Http\Controllers\Root\ResellerPayoutController::class, 'show'])->name('resellers.show');
    Route::post('/resellers/{reseller}/payouts',           [\App\Http\Controllers\Root\ResellerPayoutController::class, 'store'])->name('resellers.payouts.store');
    Route::delete('/resellers/{reseller}/payouts/{payout}',[\App\Http\Controllers\Root\ResellerPayoutController::class, 'destroy'])->name('resellers.payouts.destroy');
    // Payment logs
    Route::get('/payments', [\App\Http\Controllers\Root\PaymentController::class, 'index'])->name('payments.index');
    Route::post('/payments', [\App\Http\Controllers\Root\PaymentController::class, 'store'])->name('payments.store');
    Route::delete('/payments/{payment}', [\App\Http\Controllers\Root\PaymentController::class, 'destroy'])->name('payments.destroy');
    // System settings
    Route::get('/settings',  [\App\Http\Controllers\Root\SystemConfigController::class, 'edit'])->name('settings.edit');
    Route::put('/settings',  [\App\Http\Controllers\Root\SystemConfigController::class, 'update'])->name('settings.update');
    // Database backup / restore
    Route::get('/database',        [\App\Http\Controllers\Root\DatabaseController::class, 'index'])->name('database.index');
    Route::get('/database/export', [\App\Http\Controllers\Root\DatabaseController::class, 'export'])->name('database.export');
    Route::post('/database/import',[\App\Http\Controllers\Root\DatabaseController::class, 'import'])->name('database.import');
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
    Route::resource('shops', ShopController::class)->except('destroy');
    Route::post('/shops/{shop}/enter', [ShopController::class, 'enter'])->name('shops.enter');
    Route::post('/exit-shop',          [ShopController::class, 'exitShop'])->name('shops.exit');
    Route::get('/reports', [SuperReportController::class, 'index'])->name('reports');
    Route::post('/users/{user}/reset-password', [ShopController::class, 'resetPassword'])->name('users.reset-password');
    Route::post('/shops/{shop}/users',           [ShopController::class, 'storeUser'])->name('shops.users.store');
    Route::delete('/shops/{shop}/users/{user}',  [ShopController::class, 'destroyUser'])->name('shops.users.destroy');
});

/* ── Protected (shop users) ────────────────────────────────── */
Route::middleware(['auth', 'shop.scope', 'check.subscription'])->group(function () {

    Route::redirect('/', '/dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/user-summary', [DashboardController::class, 'userSummary'])->name('dashboard.user-summary');

    /* Customers */
    Route::get('customers-search',            [CustomerController::class, 'search'])->name('customers.search');
    Route::resource('customers', CustomerController::class);
    Route::get('customers/{customer}/ledger',          [CustomerController::class, 'ledger'])->name('customers.ledger');
    Route::post('customers/{customer}/sms-reminder',   [CustomerController::class, 'smsReminder'])->name('customers.sms-reminder');
    Route::get('customers-ledger',                     [CustomerController::class, 'ledgerSelect'])->name('customers.ledger-select');

    /* Collections — তাগাদা লিস্ট */
    Route::get('collections',       [\App\Http\Controllers\CollectionController::class, 'index'])->name('collections.index');
    Route::post('collections/sms',  [\App\Http\Controllers\CollectionController::class, 'bulkSms'])->name('collections.sms');

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
    Route::post('sales/{sale}/request-delete',  [SaleController::class, 'requestDelete'])->name('sales.request-delete');
    Route::post('sales/{sale}/approve-delete',  [SaleController::class, 'approveDelete'])->name('sales.approve-delete');
    Route::post('sales/{sale}/reject-delete',   [SaleController::class, 'rejectDelete'])->name('sales.reject-delete');

    Route::resource('purchases', PurchaseController::class);
    Route::post('purchases/{purchase}/request-delete', [PurchaseController::class, 'requestDelete'])->name('purchases.request-delete');
    Route::post('purchases/{purchase}/approve-delete', [PurchaseController::class, 'approveDelete'])->name('purchases.approve-delete');
    Route::post('purchases/{purchase}/reject-delete',  [PurchaseController::class, 'rejectDelete'])->name('purchases.reject-delete');

    // Pending edit approval (shared for both sales and purchases)
    Route::post('pending-edits/{pendingEdit}/approve', [SaleController::class, 'approveEdit'])->name('pending-edits.approve-sale');
    Route::post('pending-edits/{pendingEdit}/approve-purchase', [PurchaseController::class, 'approveEdit'])->name('pending-edits.approve-purchase');
    Route::post('pending-edits/{pendingEdit}/reject',  [SaleController::class, 'rejectEdit'])->name('pending-edits.reject-sale');
    Route::post('pending-edits/{pendingEdit}/reject-purchase', [PurchaseController::class, 'rejectEdit'])->name('pending-edits.reject-purchase');

    // Approvals page (admin only — all pending delete + edit requests)
    Route::get('approvals', [App\Http\Controllers\ApprovalController::class, 'index'])->name('approvals.index')->middleware('shop.admin');
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

    /* দিনশেষ রিপোর্ট — admin only */
    Route::get('/reports/day-close',       [ReportController::class, 'dayClose'])->name('reports.day-close')->middleware('shop.admin');
    Route::post('/reports/day-close/sms',  [ReportController::class, 'dayCloseSms'])->name('reports.day-close.sms')->middleware('shop.admin');
    Route::post('/reports/day-close/reconcile', [ReportController::class, 'dayCloseReconcile'])->name('reports.day-close.reconcile')->middleware('shop.admin');

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
        Route::post('/store-config/font',                  [StoreConfigController::class, 'updateFont'])->name('store-config.font');
        Route::post('/receipt-profiles',                   [ReceiptProfileController::class, 'store'])->name('receipt-profiles.store');
        Route::put('/receipt-profiles/{receiptProfile}',    [ReceiptProfileController::class, 'update'])->name('receipt-profiles.update');
        Route::delete('/receipt-profiles/{receiptProfile}', [ReceiptProfileController::class, 'destroy'])->name('receipt-profiles.destroy');
    });

    /* Extra Cost Categories — admin only */
    Route::middleware('shop.admin')->group(function () {
        Route::get('/extra-cost-categories',                   [ExtraCostCategoryController::class, 'index'])->name('extra-cost-categories.index');
        Route::post('/extra-cost-categories',                  [ExtraCostCategoryController::class, 'store'])->name('extra-cost-categories.store');
        Route::put('/extra-cost-categories/{extraCostCategory}',    [ExtraCostCategoryController::class, 'update'])->name('extra-cost-categories.update');
        Route::delete('/extra-cost-categories/{extraCostCategory}', [ExtraCostCategoryController::class, 'destroy'])->name('extra-cost-categories.destroy');

        Route::get('/deposit-categories',                      [DepositCategoryController::class, 'index'])->name('deposit-categories.index');
        Route::post('/deposit-categories',                     [DepositCategoryController::class, 'store'])->name('deposit-categories.store');
        Route::put('/deposit-categories/{depositCategory}',    [DepositCategoryController::class, 'update'])->name('deposit-categories.update');
        Route::delete('/deposit-categories/{depositCategory}', [DepositCategoryController::class, 'destroy'])->name('deposit-categories.destroy');
    });

    /* Shop staff/user management — shop admin only */
    Route::resource('users', UserController::class)->except('show')->middleware('shop.admin');

    /* Profile */
    Route::get('/profile',          [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile',          [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'changePassword'])->name('profile.password');

    /* Global search (topbar) */
    Route::get('/search', \App\Http\Controllers\GlobalSearchController::class)->name('search');
});
