<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Accounting\AccountingReportController;
use App\Http\Controllers\Admin\AccountingOperations\DocumentBrandingController;
use App\Http\Controllers\Admin\AccountingOperations\FixedAssetController;
use App\Http\Controllers\Admin\AccountingOperations\OperationDocumentController;
use App\Http\Controllers\Admin\AccountingOperations\PurchaseReturnController;
use App\Http\Controllers\Admin\AccountingOperations\StandaloneReceiptController;
use App\Http\Controllers\Admin\Activity\ProductActivityController;
use App\Http\Controllers\Admin\Activity\SystemActivityController;
use App\Http\Controllers\Admin\Api\ApiTokenController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\Catalog\ClassificationController;
use App\Http\Controllers\Admin\Catalog\ProductController;
use App\Http\Controllers\Admin\Catalog\SupplierController;
use App\Http\Controllers\Admin\Catalog\TaxRateController;
use App\Http\Controllers\Admin\Commercial\CustomerReceivableController;
use App\Http\Controllers\Admin\Commercial\PurchaseReceiptController;
use App\Http\Controllers\Admin\Commercial\VoucherController;
use App\Http\Controllers\Admin\Customers\CustomerController;
use App\Http\Controllers\Admin\Documents\ProductBarcodeController;
use App\Http\Controllers\Admin\Documents\SaleDocumentController;
use App\Http\Controllers\Admin\Imports\ProductImportController;
use App\Http\Controllers\Admin\Inventory\InventoryController;
use App\Http\Controllers\Admin\Operations\AdminDashboardController;
use App\Http\Controllers\Admin\Operations\AdminNotificationController;
use App\Http\Controllers\Admin\Operations\AlertRecipientController;
use App\Http\Controllers\Admin\Operations\BackupController;
use App\Http\Controllers\Admin\Operations\BusinessSettingsController;
use App\Http\Controllers\Admin\Operations\StaffPerformanceController;
use App\Http\Controllers\Admin\Payments\PaymentMethodController;
use App\Http\Controllers\Admin\Procurement\LowStockReportController;
use App\Http\Controllers\Admin\Procurement\PurchaseOrderController;
use App\Http\Controllers\Admin\Reports\ReportExportController;
use App\Http\Controllers\Admin\Reports\ReportsHubController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\Sales\SaleController;
use App\Http\Controllers\Admin\Security\LiveSessionController;
use App\Http\Controllers\Admin\SessionController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\SupplierFinance\SupplierBalanceReportController;
use App\Http\Controllers\Admin\SupplierFinance\SupplierBillController;
use App\Http\Controllers\Admin\SupplierFinance\SupplierReturnController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth',
    'account.active',
    'session.inactivity',
])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/branches', [BranchController::class, 'index'])
        ->middleware('permission:branches.view')
        ->name('branches.index');

    Route::post('/branches', [BranchController::class, 'store'])
        ->middleware('permission:branches.create')
        ->name('branches.store');

    Route::patch(
        '/branches/{branch}/deactivate',
        [BranchController::class, 'deactivate'],
    )
        ->middleware('permission:branches.deactivate')
        ->name('branches.deactivate');

    Route::get('/staff', [StaffController::class, 'index'])
        ->middleware('permission:staff.view')
        ->name('staff.index');

    Route::post('/staff', [StaffController::class, 'store'])
        ->middleware('permission:staff.create')
        ->name('staff.store');

    Route::patch(
        '/staff/{account}/suspend',
        [StaffController::class, 'suspend'],
    )
        ->middleware('permission:staff.suspend')
        ->name('staff.suspend');

    Route::get('/roles', [RoleController::class, 'index'])
        ->middleware('permission:roles.view')
        ->name('roles.index');

    Route::post('/roles', [RoleController::class, 'store'])
        ->middleware('permission:roles.create')
        ->name('roles.store');

    Route::get(
        '/security/sessions',
        [SessionController::class, 'index'],
    )
        ->middleware('permission:staff.sessions.view')
        ->name('security.sessions.index');

    Route::patch(
        '/security/sessions/{session}/revoke',
        [SessionController::class, 'revoke'],
    )
        ->middleware('permission:staff.sessions.revoke')
        ->name('security.sessions.revoke');

    Route::prefix('catalog')->name('catalog.')->group(function (): void {
        Route::get('/products', [ProductController::class, 'index'])
            ->middleware('permission:products.view')
            ->name('products.index');

        Route::get('/products/create', [ProductController::class, 'create'])
            ->middleware('permission:products.create')
            ->name('products.create');

        Route::post('/products', [ProductController::class, 'store'])
            ->middleware('permission:products.create')
            ->name('products.store');

        Route::get(
            '/categories',
            [ClassificationController::class, 'categories'],
        )
            ->middleware('permission:categories.manage')
            ->name('categories.index');

        Route::post(
            '/categories',
            [ClassificationController::class, 'storeCategory'],
        )
            ->middleware('permission:categories.manage')
            ->name('categories.store');

        Route::get(
            '/brands',
            [ClassificationController::class, 'brands'],
        )
            ->middleware('permission:brands.manage')
            ->name('brands.index');

        Route::post(
            '/brands',
            [ClassificationController::class, 'storeBrand'],
        )
            ->middleware('permission:brands.manage')
            ->name('brands.store');

        Route::get('/tax-rates', [TaxRateController::class, 'index'])
            ->middleware('permission:tax-rates.manage')
            ->name('tax-rates.index');

        Route::post('/tax-rates', [TaxRateController::class, 'store'])
            ->middleware('permission:tax-rates.manage')
            ->name('tax-rates.store');

        Route::get('/suppliers', [SupplierController::class, 'index'])
            ->middleware('permission:suppliers.view')
            ->name('suppliers.index');

        Route::post('/suppliers', [SupplierController::class, 'store'])
            ->middleware('permission:suppliers.create')
            ->name('suppliers.store');
    });
    Route::prefix('imports')->name('imports.')->group(function (): void {
        Route::get('/products', [ProductImportController::class, 'index'])
            ->middleware('permission:products.import-history')
            ->name('products.index');

        Route::get(
            '/products/template',
            [ProductImportController::class, 'template'],
        )
            ->middleware('permission:products.import')
            ->name('products.template');

        Route::post('/products', [ProductImportController::class, 'store'])
            ->middleware('permission:products.import')
            ->name('products.store');

        Route::get(
            '/products/{import}',
            [ProductImportController::class, 'show'],
        )
            ->middleware('permission:products.import-history')
            ->name('products.show');

        Route::post(
            '/products/{import}/process',
            [ProductImportController::class, 'process'],
        )
            ->middleware('permission:products.import')
            ->name('products.process');

        Route::get(
            '/products/{import}/errors',
            [ProductImportController::class, 'errors'],
        )
            ->middleware('permission:products.import-history')
            ->name('products.errors');
    });

    Route::prefix('inventory')->name('inventory.')->group(function (): void {
        Route::get('/', [InventoryController::class, 'index'])
            ->middleware('permission:inventory.view')
            ->name('index');

        Route::get('/movements', [InventoryController::class, 'movements'])
            ->middleware('permission:inventory.movements.view')
            ->name('movements');

        Route::post('/intake', [InventoryController::class, 'intake'])
            ->middleware('permission:inventory.intake')
            ->name('intake');

        Route::post('/transfer', [InventoryController::class, 'transfer'])
            ->middleware('permission:inventory.transfer')
            ->name('transfer');

        Route::post('/adjust', [InventoryController::class, 'adjust'])
            ->middleware('permission:inventory.adjust')
            ->name('adjust');
    });

    Route::prefix('procurement')->name('procurement.')->group(function (): void {
        Route::get('/orders', [PurchaseOrderController::class, 'index'])
            ->middleware('permission:procurement.view')
            ->name('orders.index');

        Route::post('/orders', [PurchaseOrderController::class, 'store'])
            ->middleware('permission:procurement.create')
            ->name('orders.store');

        Route::patch('/orders/{order}/approve', [PurchaseOrderController::class, 'approve'])
            ->middleware('permission:procurement.approve')
            ->name('orders.approve');

        Route::post('/orders/{order}/receive', [PurchaseOrderController::class, 'receive'])
            ->middleware('permission:procurement.receive')
            ->name('orders.receive');
    });

    Route::get('/reports/low-stock', LowStockReportController::class)
        ->middleware('permission:reports.low-stock')
        ->name('reports.low-stock');

    Route::prefix('customers')->name('customers.')->group(function (): void {
        Route::get('/', [CustomerController::class, 'index'])
            ->middleware('permission:customers.view')
            ->name('index');

        Route::get('/search', [CustomerController::class, 'search'])
            ->middleware('permission:customers.view')
            ->name('search');

        Route::post('/', [CustomerController::class, 'store'])
            ->middleware('permission:customers.create')
            ->name('store');
    });

    Route::prefix('payment-methods')->name('payment-methods.')->group(function (): void {
        Route::get('/', [PaymentMethodController::class, 'index'])
            ->middleware('permission:payment-methods.view')
            ->name('index');

        Route::post('/', [PaymentMethodController::class, 'store'])
            ->middleware('permission:payment-methods.manage')
            ->name('store');

        Route::patch('/{method}/default', [PaymentMethodController::class, 'setDefault'])
            ->middleware('permission:payment-methods.manage')
            ->name('default');

        Route::patch('/{method}/toggle', [PaymentMethodController::class, 'toggle'])
            ->middleware('permission:payment-methods.manage')
            ->name('toggle');
    });

    Route::prefix('sales')->name('sales.')->group(function (): void {
        Route::get('/', [SaleController::class, 'index'])
            ->middleware('permission:sales.view')
            ->name('index');

        Route::get('/create', [SaleController::class, 'create'])
            ->middleware('permission:sales.create')
            ->name('create');

        Route::post('/', [SaleController::class, 'store'])
            ->middleware('permission:sales.create')
            ->name('store');

        Route::get('/{sale}', [SaleController::class, 'show'])
            ->middleware('permission:sales.view')
            ->name('show');

        Route::post('/{sale}/payments', [SaleController::class, 'addPayment'])
            ->middleware('permission:sales.payments')
            ->name('payments.store');

        Route::post('/quotes/{quote}/convert', [SaleController::class, 'convert'])
            ->middleware('permission:sales.convert-quotes')
            ->name('quotes.convert');
    });

    Route::prefix('supplier-finance')->name('supplier-finance.')->group(function (): void {
        Route::get('/bills', [SupplierBillController::class, 'index'])
            ->middleware('permission:supplier-bills.view')
            ->name('bills.index');

        Route::post('/bills', [SupplierBillController::class, 'store'])
            ->middleware('permission:supplier-bills.create')
            ->name('bills.store');

        Route::get('/bills/{bill}', [SupplierBillController::class, 'show'])
            ->middleware('permission:supplier-bills.view')
            ->name('bills.show');

        Route::post('/bills/{bill}/payments', [SupplierBillController::class, 'pay'])
            ->middleware('permission:supplier-bills.pay')
            ->name('bills.payments.store');

        Route::get(
            '/bills/{bill}/documents/{document}',
            [SupplierBillController::class, 'downloadDocument'],
        )
            ->middleware('permission:supplier-documents.download')
            ->name('bills.documents.download');

        Route::get('/returns', [SupplierReturnController::class, 'index'])
            ->middleware('permission:supplier-returns.view')
            ->name('returns.index');

        Route::post('/returns', [SupplierReturnController::class, 'store'])
            ->middleware('permission:supplier-returns.create')
            ->name('returns.store');
    });

    Route::get('/reports/supplier-balances', SupplierBalanceReportController::class)
        ->middleware('permission:reports.supplier-balances')
        ->name('reports.supplier-balances');

    Route::get('/dashboard', AdminDashboardController::class)
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    Route::prefix('operations')->name('operations.')->group(function (): void {
        Route::get('/alert-recipients', [AlertRecipientController::class, 'index'])
            ->middleware('permission:alerts.manage-recipients')
            ->name('alert-recipients.index');

        Route::post('/alert-recipients', [AlertRecipientController::class, 'store'])
            ->middleware('permission:alerts.manage-recipients')
            ->name('alert-recipients.store');

        Route::patch('/alert-recipients/{recipient}/toggle', [AlertRecipientController::class, 'toggle'])
            ->middleware('permission:alerts.manage-recipients')
            ->name('alert-recipients.toggle');

        Route::get('/settings', [BusinessSettingsController::class, 'edit'])
            ->middleware('permission:settings.business.manage')
            ->name('settings.edit');

        Route::patch('/settings', [BusinessSettingsController::class, 'update'])
            ->middleware('permission:settings.business.manage')
            ->name('settings.update');

        Route::patch('/notifications/{notification}/read', [AdminNotificationController::class, 'markRead'])
            ->middleware('permission:alerts.view')
            ->name('notifications.read');
    });

    Route::get('/reports/staff-performance', StaffPerformanceController::class)
        ->middleware('permission:reports.staff-performance')
        ->name('reports.staff-performance');

    Route::get('/reports', ReportsHubController::class)
        ->middleware('permission:reports.hub.view')
        ->name('reports.hub');

    Route::prefix('reports/exports')->name('reports.exports.')->group(function (): void {
        Route::get('/sales', [ReportExportController::class, 'sales'])
            ->middleware('permission:reports.export')
            ->name('sales');
        Route::get('/staff', [ReportExportController::class, 'staff'])
            ->middleware('permission:reports.export')
            ->name('staff');
        Route::get('/low-stock', [ReportExportController::class, 'lowStock'])
            ->middleware('permission:reports.export')
            ->name('low-stock');
    });

    Route::prefix('documents')->name('documents.')->group(function (): void {
        Route::get('/sales/{sale}/thermal', [SaleDocumentController::class, 'thermal'])
            ->middleware('permission:documents.sales.print')
            ->name('sales.thermal');
        Route::get('/sales/{sale}/a4', [SaleDocumentController::class, 'a4'])
            ->middleware('permission:documents.sales.print')
            ->name('sales.a4');
        Route::get('/sales/{sale}/pdf', [SaleDocumentController::class, 'pdf'])
            ->middleware('permission:documents.sales.print')
            ->name('sales.pdf');
        Route::get('/products/{product}/label', ProductBarcodeController::class)
            ->middleware('permission:documents.products.labels')
            ->name('products.label');
    });

    Route::get('/activity', SystemActivityController::class)
        ->middleware('permission:activity.view')
        ->name('activity.index');

    Route::get('/products/{product}/activity', ProductActivityController::class)
        ->middleware('permission:activity.products.view')
        ->name('products.activity');

    Route::get('/security/sessions', [LiveSessionController::class, 'index'])
        ->middleware('permission:security.sessions.view')
        ->name('security.sessions.index');

    Route::delete('/security/sessions/{session}', [LiveSessionController::class, 'destroy'])
        ->middleware('permission:security.sessions.terminate')
        ->name('security.sessions.destroy');

    Route::prefix('commercial')->name('commercial.')->group(function (): void {
        Route::get('/vouchers', [VoucherController::class, 'index'])
            ->middleware('permission:vouchers.manage')
            ->name('vouchers.index');
        Route::post('/vouchers', [VoucherController::class, 'store'])
            ->middleware('permission:vouchers.manage')
            ->name('vouchers.store');

        Route::get('/receivables', [CustomerReceivableController::class, 'index'])
            ->middleware('permission:customers.receivables.view')
            ->name('receivables.index');
        Route::get('/receivables/{customer}', [CustomerReceivableController::class, 'show'])
            ->middleware('permission:customers.receivables.view')
            ->name('receivables.show');

        Route::get('/purchases', [PurchaseReceiptController::class, 'index'])
            ->middleware('permission:purchases.record')
            ->name('purchases.index');
        Route::get('/purchases/create', [PurchaseReceiptController::class, 'create'])
            ->middleware('permission:purchases.record')
            ->name('purchases.create');
        Route::post('/purchases', [PurchaseReceiptController::class, 'store'])
            ->middleware('permission:purchases.record')
            ->name('purchases.store');
    });

    Route::prefix('api')->name('api.')->group(function (): void {
        Route::get('/tokens', [ApiTokenController::class, 'index'])
            ->middleware('permission:api.tokens.manage')
            ->name('tokens.index');
        Route::post('/tokens', [ApiTokenController::class, 'store'])
            ->middleware('permission:api.tokens.manage')
            ->name('tokens.store');
        Route::delete('/tokens/{token}', [ApiTokenController::class, 'destroy'])
            ->middleware('permission:api.tokens.manage')
            ->name('tokens.destroy');
    });

    Route::prefix('operations/backups')
        ->name('operations.backups.')
        ->group(function (): void {
            Route::get('/', [BackupController::class, 'index'])
                ->middleware('permission:backups.view')
                ->name('index');
            Route::post('/', [BackupController::class, 'store'])
                ->middleware('permission:backups.create')
                ->name('store');
            Route::post('/{backupRun}/verify', [BackupController::class, 'verify'])
                ->middleware('permission:backups.verify')
                ->name('verify');
        });

    Route::prefix('accounting-operations')
        ->name('accounting-operations.')
        ->group(function (): void {
            Route::get('/branding', [DocumentBrandingController::class, 'edit'])
                ->middleware('permission:documents.branding.manage')
                ->name('branding.edit');
            Route::patch('/branding', [DocumentBrandingController::class, 'update'])
                ->middleware('permission:documents.branding.manage')
                ->name('branding.update');

            Route::get('/receipts', [StandaloneReceiptController::class, 'index'])
                ->middleware('permission:receipts.view')
                ->name('receipts.index');
            Route::get('/receipts/create', [StandaloneReceiptController::class, 'create'])
                ->middleware('permission:receipts.create')
                ->name('receipts.create');
            Route::post('/receipts', [StandaloneReceiptController::class, 'store'])
                ->middleware('permission:receipts.create')
                ->name('receipts.store');

            Route::get('/purchase-returns', [PurchaseReturnController::class, 'index'])
                ->middleware('permission:purchase_returns.view')
                ->name('purchase-returns.index');
            Route::get('/purchase-returns/create', [PurchaseReturnController::class, 'create'])
                ->middleware('permission:purchase_returns.create')
                ->name('purchase-returns.create');
            Route::post('/purchase-returns', [PurchaseReturnController::class, 'store'])
                ->middleware('permission:purchase_returns.create')
                ->name('purchase-returns.store');

            Route::get('/assets', [FixedAssetController::class, 'index'])
                ->middleware('permission:assets.view')
                ->name('assets.index');
            Route::post('/assets', [FixedAssetController::class, 'store'])
                ->middleware('permission:assets.manage')
                ->name('assets.store');

            Route::get('/documents/{type}/{id}/pdf', [OperationDocumentController::class, 'pdf'])
                ->whereIn('type', [
                    'standalone_receipt',
                    'purchase_receipt',
                    'purchase_return',
                    'sale_return',
                    'stock_operation',
                    'fixed_asset',
                ])
                ->middleware('permission:operation_documents.download')
                ->name('documents.pdf');
            Route::get('/documents/{type}/{id}/spreadsheet', [OperationDocumentController::class, 'spreadsheet'])
                ->whereIn('type', [
                    'standalone_receipt',
                    'purchase_receipt',
                    'purchase_return',
                    'sale_return',
                    'stock_operation',
                    'fixed_asset',
                ])
                ->middleware('permission:operation_documents.download')
                ->name('documents.spreadsheet');
        });

    Route::get(
        '/accounting/reports',
        [AccountingReportController::class, 'index'],
    )
        ->middleware('permission:accounting.reports.view')
        ->name('accounting.reports.index');

});
