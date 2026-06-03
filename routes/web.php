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

/* ── Auth ──────────────────────────────────────────────────── */
Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/* ── Protected ─────────────────────────────────────────────── */
Route::middleware('auth')->group(function () {

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

    // SMS
    Route::get('/sms',                          [SmsController::class, 'index'])->name('sms.index');
    Route::post('/sms/send',                    [SmsController::class, 'send'])->name('sms.send');
    Route::post('/sms/send-custom',             [SmsController::class, 'sendCustom'])->name('sms.send-custom');
    Route::post('/sms/settings',                [SmsController::class, 'saveSettings'])->name('sms.settings');
    Route::delete('/sms/log/{smsLog}',          [SmsController::class, 'destroyLog'])->name('sms.log.destroy');

    Route::get('/stock',              [StockController::class, 'index'])->name('stock.index');
    Route::get('/stock/report',       [StockController::class, 'report'])->name('stock.report');
    Route::get('/stock/low',          [StockController::class, 'low'])->name('stock.low');
    Route::patch('/stock/{stock}',    [StockController::class, 'adjust'])->name('stock.adjust');

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

    Route::get('/store-config', [StoreConfigController::class, 'index'])->name('store-config.index');
    Route::put('/store-config', [StoreConfigController::class, 'update'])->name('store-config.update');
    Route::post('/store-config/payment-method',        [StoreConfigController::class, 'addPaymentMethod'])->name('store-config.payment-method.add');
    Route::delete('/store-config/payment-method',      [StoreConfigController::class, 'deletePaymentMethod'])->name('store-config.payment-method.delete');
    Route::post('/store-config/multimedia/toggle',     [StoreConfigController::class, 'toggleMultimedia'])->name('store-config.multimedia.toggle');
    Route::post('/store-config/multimedia/interval',   [StoreConfigController::class, 'updateMultimediaInterval'])->name('store-config.multimedia.interval');
    Route::post('/store-config/multimedia/upload',     [StoreConfigController::class, 'uploadMultimedia'])->name('store-config.multimedia.upload');
    Route::delete('/store-config/multimedia',          [StoreConfigController::class, 'deleteMultimedia'])->name('store-config.multimedia.delete');

    /* Extra Cost Categories */
    Route::get('/extra-cost-categories',                   [ExtraCostCategoryController::class, 'index'])->name('extra-cost-categories.index');
    Route::post('/extra-cost-categories',                  [ExtraCostCategoryController::class, 'store'])->name('extra-cost-categories.store');
    Route::put('/extra-cost-categories/{extraCostCategory}',    [ExtraCostCategoryController::class, 'update'])->name('extra-cost-categories.update');
    Route::delete('/extra-cost-categories/{extraCostCategory}', [ExtraCostCategoryController::class, 'destroy'])->name('extra-cost-categories.destroy');

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
