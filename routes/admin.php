<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Accounting\AccountingReportController;
use App\Http\Controllers\Admin\Accounting\BatchJournalEntryController;
use App\Http\Controllers\Admin\Accounting\ChartOfAccountsController;
use App\Http\Controllers\Admin\Accounting\EnterpriseAccountingController;
use App\Http\Controllers\Admin\Accounting\JournalEntryController;
use App\Http\Controllers\Admin\Accounting\OpeningBalanceController;
use App\Http\Controllers\Admin\AccountingOperations\DocumentBrandingController;
use App\Http\Controllers\Admin\AccountingOperations\FixedAssetController;
use App\Http\Controllers\Admin\AccountingOperations\OperationDocumentController;
use App\Http\Controllers\Admin\AccountingOperations\PurchaseReturnController;
use App\Http\Controllers\Admin\AccountingOperations\StandaloneReceiptController;
use App\Http\Controllers\Admin\Activity\ProductActivityController;
use App\Http\Controllers\Admin\Activity\SystemActivityController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\Catalog\ClassificationController;
use App\Http\Controllers\Admin\Catalog\ProductController;
use App\Http\Controllers\Admin\Catalog\ProductPriceAdjustmentController;
use App\Http\Controllers\Admin\Catalog\SupplierController;
use App\Http\Controllers\Admin\Catalog\TaxRateController;
use App\Http\Controllers\Admin\Commercial\CustomerReceivableController;
use App\Http\Controllers\Admin\Commercial\PurchaseReceiptController;
use App\Http\Controllers\Admin\Commercial\VoucherController;
use App\Http\Controllers\Admin\Customers\CustomerController;
use App\Http\Controllers\Admin\Customers\QuickCustomerController;
use App\Http\Controllers\Admin\Documents\ProductBarcodeController;
use App\Http\Controllers\Admin\Documents\SaleDocumentController;
use App\Http\Controllers\Admin\Governance\AdminChangeController;
use App\Http\Controllers\Admin\Hr\HrAdministrationController;
use App\Http\Controllers\Admin\Imports\ProductImportController;
use App\Http\Controllers\Admin\Insights\LisaChatController;
use App\Http\Controllers\Admin\Insights\LisaInsightController;
use App\Http\Controllers\Admin\Inventory\InventoryController;
use App\Http\Controllers\Admin\Inventory\WarehouseController;
use App\Http\Controllers\Admin\Operations\AdminDashboardController;
use App\Http\Controllers\Admin\Operations\AdminNotificationController;
use App\Http\Controllers\Admin\Operations\AlertRecipientController;
use App\Http\Controllers\Admin\Operations\BackupController;
use App\Http\Controllers\Admin\Operations\BusinessSettingsController;
use App\Http\Controllers\Admin\Operations\StaffPerformanceController;
use App\Http\Controllers\Admin\Payments\PaymentMethodController;
use App\Http\Controllers\Admin\Pos\PosWorkstationController;
use App\Http\Controllers\Admin\Procurement\EnterpriseProcurementController;
use App\Http\Controllers\Admin\Procurement\LowStockReportController;
use App\Http\Controllers\Admin\Procurement\PurchaseOrderController;
use App\Http\Controllers\Admin\Reports\AuditLogController;
use App\Http\Controllers\Admin\Reports\ReportExportController;
use App\Http\Controllers\Admin\Reports\ReportsHubController;
use App\Http\Controllers\Admin\Reports\UniversalExportController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\Sales\SaleController;
use App\Http\Controllers\Admin\Sales\SalesWorkflowController;
use App\Http\Controllers\Admin\Security\LiveSessionController;
use App\Http\Controllers\Admin\SessionController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\SupplierFinance\SupplierBalanceReportController;
use App\Http\Controllers\Admin\SupplierFinance\SupplierBillController;
use App\Http\Controllers\Admin\SupplierFinance\SupplierReturnController;
use App\Http\Controllers\Operations\OperationStatusController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth',
    'account.active',
    'session.inactivity',
    'branch.scope',
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

    Route::post(
        '/staff/{account}/access-key/reveal',
        [StaffController::class, 'revealAccessKey'],
    )
        ->middleware('permission:staff.access-key.reveal')
        ->name('staff.access-key.reveal');

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

        Route::get('/price-adjustments', [ProductPriceAdjustmentController::class, 'index'])
            ->middleware('permission:products.prices.adjust')->name('price-adjustments.index');
        Route::post('/price-adjustments', [ProductPriceAdjustmentController::class, 'store'])
            ->middleware('permission:products.prices.adjust')->name('price-adjustments.store');

        Route::get('/products', [ProductController::class, 'index'])
            ->middleware('permission:products.view')
            ->name('products.index');

        Route::get('/products/create', [ProductController::class, 'create'])
            ->middleware('permission:products.create')
            ->name('products.create');

        Route::post('/products', [ProductController::class, 'store'])
            ->middleware('permission:products.create')
            ->name('products.store');

        Route::get('/products/{product}/edit', [ProductController::class, 'edit'])
            ->middleware('permission:products.update')
            ->name('products.edit');

        Route::put('/products/{product}', [ProductController::class, 'update'])
            ->middleware('permission:products.update')
            ->name('products.update');

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
        Route::post('/quick', QuickCustomerController::class)->middleware('permission:customers.create')->name('quick-store');
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
            ->middleware('permission.any:sales.view,sales.view.own,sales.view.all')
            ->name('index');

        Route::get('/export', [SaleController::class, 'export'])
            ->middleware('permission:sales.export')
            ->name('export');

        Route::get('/create', [SaleController::class, 'create'])
            ->middleware('permission:sales.create')
            ->name('create');

        Route::post('/', [SaleController::class, 'store'])
            ->middleware('permission:sales.create')
            ->name('store');

        Route::get('/{sale}', [SaleController::class, 'show'])
            ->middleware(['permission.any:sales.view,sales.view.own,sales.view.all', 'sale.visible'])
            ->name('show');

        Route::get('/{sale}/edit', [SaleController::class, 'edit'])
            ->middleware(['permission:sales.edit', 'sale.visible'])
            ->name('edit');

        Route::put('/{sale}', [SaleController::class, 'update'])
            ->middleware(['permission:sales.edit', 'sale.visible'])
            ->name('update');

        Route::post('/{sale}/void', [SaleController::class, 'void'])
            ->middleware(['permission:sales.void', 'sale.visible'])
            ->name('void');

        Route::post('/{sale}/payments', [SaleController::class, 'addPayment'])
            ->middleware(['permission:sales.payments.record', 'sale.visible'])
            ->name('payments.store');

        Route::post('/quotes/{quote}/convert', [SaleController::class, 'convert'])
            ->middleware(['permission:quotes.convert', 'sale.visible'])
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

    Route::post('/reports/staff-performance/announcement', [StaffPerformanceController::class, 'announce'])
        ->middleware('permission:reports.staff-performance')
        ->name('reports.staff-performance.announcement');

    Route::get('/reports', ReportsHubController::class)
        ->middleware('permission:reports.hub.view')
        ->name('reports.hub');

    Route::get('/activity-log', [AuditLogController::class, 'index'])
        ->middleware('permission:activity.view')
        ->name('activity-log.index');

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
            ->middleware(['permission:documents.sales.print', 'sale.visible'])
            ->name('sales.thermal');
        Route::get('/sales/{sale}/a4', [SaleDocumentController::class, 'a4'])
            ->middleware(['permission:documents.sales.print', 'sale.visible'])
            ->name('sales.a4');
        Route::get('/sales/{sale}/pdf', [SaleDocumentController::class, 'pdf'])
            ->middleware(['permission:documents.sales.print', 'sale.visible'])
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

    Route::prefix('accounting/chart-of-accounts')
        ->name('accounting.chart-of-accounts.')
        ->group(function (): void {
            Route::get('/', [ChartOfAccountsController::class, 'index'])
                ->middleware('permission:accounting.accounts.view')
                ->name('index');
            Route::post('/', [ChartOfAccountsController::class, 'store'])
                ->middleware('permission:accounting.accounts.manage')
                ->name('store');
            Route::get('/{ledgerAccount}/edit', [ChartOfAccountsController::class, 'edit'])
                ->middleware('permission:accounting.accounts.manage')
                ->name('edit');
            Route::patch('/{ledgerAccount}', [ChartOfAccountsController::class, 'update'])
                ->middleware('permission:accounting.accounts.manage')
                ->name('update');
        });

    // ================== JOURNAL ENTRIES ==================
    Route::prefix('accounting/journal-entries')
        ->name('accounting.journal-entries.')
        ->group(function (): void {
            Route::get('/', [JournalEntryController::class, 'index'])
                ->middleware('permission:accounting.journals.view')
                ->name('index');
            Route::get('/create', [JournalEntryController::class, 'create'])
                ->middleware('permission:accounting.journals.manage')
                ->name('create');
            Route::post('/', [JournalEntryController::class, 'store'])
                ->middleware('permission:accounting.journals.manage')
                ->name('store');
            Route::get('/{journalEntry}', [JournalEntryController::class, 'show'])
                ->middleware('permission:accounting.journals.view')
                ->name('show');
            Route::get('/{journalEntry}/edit', [JournalEntryController::class, 'edit'])
                ->middleware('permission:accounting.journals.manage')
                ->name('edit');
            Route::patch('/{journalEntry}', [JournalEntryController::class, 'update'])
                ->middleware('permission:accounting.journals.manage')
                ->name('update');
        });

    // ================== BATCH JOURNAL ==================
    Route::prefix('accounting/batch-journal')
        ->name('accounting.batch-journal.')
        ->group(function (): void {
            Route::get('/create', [BatchJournalEntryController::class, 'create'])
                ->middleware('permission:accounting.journals.manage')
                ->name('create');
            Route::post('/', [BatchJournalEntryController::class, 'store'])
                ->middleware('permission:accounting.journals.manage')
                ->name('store');
        });

    // ================== OPENING BALANCE ==================
    Route::prefix('accounting/opening-balance')
        ->name('accounting.opening-balance.')
        ->group(function (): void {
            Route::get('/create', [OpeningBalanceController::class, 'create'])
                ->middleware('permission:accounting.journals.manage')
                ->name('create');
            Route::post('/', [OpeningBalanceController::class, 'store'])
                ->middleware('permission:accounting.journals.manage')
                ->name('store');
        });
    // ====================================================

    Route::get(
        '/accounting/reports',
        [AccountingReportController::class, 'index'],
    )
        ->middleware('permission:accounting.reports.view')
        ->name('accounting.reports.index');

    Route::get(
        '/accounting/reports/export',
        [AccountingReportController::class, 'export'],
    )
        ->middleware('permission:accounting.reports.export')
        ->name('accounting.reports.export');

    Route::get('/insights', [LisaInsightController::class, 'index'])
        ->middleware('permission:insights.view')
        ->name('insights.index');
    Route::post('/insights/generate', [LisaInsightController::class, 'generate'])
        ->middleware('permission:insights.generate')
        ->name('insights.generate');
    Route::patch('/insights/{insight}/dismiss', [LisaInsightController::class, 'dismiss'])
        ->middleware('permission:insights.dismiss')
        ->name('insights.dismiss');

    Route::prefix('lisa')->name('insights.chat.')->group(function (): void {
        Route::get('/chat', [LisaChatController::class, 'index'])->middleware('permission:lisa.chat')->name('index');
        Route::post('/chat', [LisaChatController::class, 'store'])->middleware('permission:lisa.chat')->name('store');
        Route::get('/chat/{conversation}', [LisaChatController::class, 'show'])->middleware('permission:lisa.chat')->name('show');
        Route::post('/chat/{conversation}/message', [LisaChatController::class, 'message'])->middleware('permission:lisa.chat')->name('message');
        Route::get('/audit', [LisaChatController::class, 'audit'])->middleware('permission:lisa.audit.view')->name('audit');
        Route::get('/audit/{conversation}', [LisaChatController::class, 'auditShow'])->middleware('permission:lisa.audit.view')->name('audit.show');
    });
    Route::prefix('exports')->name('exports.')->group(function (): void {
        Route::get('/sales/{sale}', [UniversalExportController::class, 'sale'])->middleware(['permission:exports.sales', 'sale.visible'])->name('sales');
        Route::get('/stock/{movement}', [UniversalExportController::class, 'movement'])->middleware('permission:exports.inventory')->name('stock');
        Route::get('/purchases/{order}', [UniversalExportController::class, 'purchase'])->middleware('permission:exports.procurement')->name('purchases');
        Route::get('/audit', [UniversalExportController::class, 'audit'])->middleware('permission:activity.export')->name('audit');
    });

    Route::prefix('accounting/enterprise')
        ->name('accounting.enterprise.')
        ->group(function (): void {
            Route::get('/', [EnterpriseAccountingController::class, 'index'])
                ->middleware('permission:accounting.enterprise.view')
                ->name('index');
            Route::post('/periods/{period}/close', [EnterpriseAccountingController::class, 'close'])
                ->middleware('permission:accounting.close')
                ->name('periods.close');
            Route::post('/treasury/transfers', [EnterpriseAccountingController::class, 'treasuryTransfer'])
                ->middleware('permission:accounting.treasury.manage')
                ->name('treasury.transfer');
            Route::post('/bank-statements', [EnterpriseAccountingController::class, 'importBankStatement'])
                ->middleware('permission:accounting.bank-reconcile')
                ->name('bank-statements.import');
            Route::post('/bank-lines/{statementLine}/match', [EnterpriseAccountingController::class, 'matchBankLine'])
                ->middleware('permission:accounting.bank-reconcile')
                ->name('bank-lines.match');
            Route::post('/bank-statements/{statement}/finalize', [EnterpriseAccountingController::class, 'finalizeBankStatement'])
                ->middleware('permission:accounting.bank-reconcile')
                ->name('bank-statements.finalize');
        });

    Route::prefix('warehouses')
        ->name('warehouses.')
        ->group(function (): void {
            Route::get('/', [WarehouseController::class, 'index'])
                ->middleware('permission:warehouses.view')
                ->name('index');
            Route::post('/', [WarehouseController::class, 'store'])
                ->middleware('permission:warehouses.manage')
                ->name('store');
            Route::post('/transfer', [WarehouseController::class, 'transfer'])
                ->middleware('permission:warehouses.operate')
                ->name('transfer');
            Route::post('/reserve', [WarehouseController::class, 'reserve'])
                ->middleware('permission:inventory.reservations.manage')
                ->name('reserve');
            Route::post('/reservations/{reservation}/release', [WarehouseController::class, 'release'])
                ->middleware('permission:inventory.reservations.manage')
                ->name('reservations.release');
            Route::post('/condition', [WarehouseController::class, 'condition'])
                ->middleware('permission:warehouses.operate')
                ->name('condition');
            Route::post('/count', [WarehouseController::class, 'count'])
                ->middleware('permission:inventory.counts.manage')
                ->name('count');
        });

    Route::prefix('procurement/enterprise')
        ->name('procurement.enterprise.')
        ->group(function (): void {
            Route::get('/', [EnterpriseProcurementController::class, 'index'])
                ->middleware('permission:procurement.requisitions.view')
                ->name('index');
            Route::post('/requisitions', [EnterpriseProcurementController::class, 'requisition'])
                ->middleware('permission:procurement.requisitions.create')
                ->name('requisitions.store');
            Route::post('/requisitions/{requisition}/approve', [EnterpriseProcurementController::class, 'approve'])
                ->middleware('permission:procurement.requisitions.approve')
                ->name('requisitions.approve');
            Route::post('/requisitions/{requisition}/convert', [EnterpriseProcurementController::class, 'convert'])
                ->middleware('permission:procurement.requisitions.approve')
                ->name('requisitions.convert');
            Route::post('/orders/{order}/receive', [EnterpriseProcurementController::class, 'receive'])
                ->middleware('permission:procurement.receipts.create')
                ->name('orders.receive');
            Route::post('/receipts/{receipt}/landed-cost', [EnterpriseProcurementController::class, 'landedCost'])
                ->middleware('permission:procurement.landed-cost.manage')
                ->name('receipts.landed-cost');
        });

    Route::prefix('sales/workflows')
        ->name('sales.workflows.')
        ->group(function (): void {
            Route::get('/', [SalesWorkflowController::class, 'index'])
                ->middleware('permission:sales.view')
                ->name('index');
            Route::get('/export', [SalesWorkflowController::class, 'export'])
                ->middleware('permission:sales.export')
                ->name('export');
            Route::post('/{sale}/convert', [SalesWorkflowController::class, 'convert'])
                ->middleware('permission:sales.edit')
                ->name('convert');
            Route::post('/{sale}/deliver', [SalesWorkflowController::class, 'deliver'])
                ->middleware('permission:sales.edit')
                ->name('deliver');
        });

    Route::prefix('pos')
        ->name('pos.')
        ->group(function (): void {
            Route::get('/', [PosWorkstationController::class, 'index'])
                ->middleware('permission:sales.create')
                ->name('index');
            Route::post('/terminals/{terminal}/open', [PosWorkstationController::class, 'open'])
                ->middleware('permission:sales.create')
                ->name('open');
            Route::post('/shifts/{shift}/complete', [PosWorkstationController::class, 'complete'])
                ->middleware('permission:sales.create')
                ->name('complete');
            Route::post('/shifts/{shift}/cash', [PosWorkstationController::class, 'cash'])
                ->middleware('permission:sales.create')
                ->name('cash');
            Route::post('/shifts/{shift}/hold', [PosWorkstationController::class, 'hold'])
                ->middleware('permission:sales.create')
                ->name('hold');
            Route::post('/shifts/{shift}/held/{heldSale}/resume', [PosWorkstationController::class, 'resume'])
                ->middleware('permission:sales.create')
                ->name('resume');
            Route::post('/shifts/{shift}/close', [PosWorkstationController::class, 'close'])
                ->middleware('permission:sales.create')
                ->name('close');
            Route::post('/sales/{sale}/print', [PosWorkstationController::class, 'print'])
                ->middleware('permission:documents.sales.print')
                ->name('print');
        });

    Route::prefix('hr')
        ->name('hr.')
        ->group(function (): void {
            Route::get('/', [HrAdministrationController::class, 'index'])
                ->middleware('permission:staff.view')
                ->name('index');
            Route::post('/employees', [HrAdministrationController::class, 'employee'])
                ->middleware('permission:staff.create')
                ->name('employees.store');
            Route::post('/employees/{employee}/assign', [HrAdministrationController::class, 'assign'])
                ->middleware('permission:staff.create')
                ->name('employees.assign');
            Route::post('/employees/{employee}/attendance', [HrAdministrationController::class, 'attendance'])
                ->middleware('permission:staff.create')
                ->name('employees.attendance');
            Route::post('/holidays', [HrAdministrationController::class, 'holiday'])
                ->middleware('permission:staff.create')
                ->name('holidays.store');
            Route::post('/employees/{employee}/reviews', [HrAdministrationController::class, 'review'])
                ->middleware('permission:reports.staff-performance')
                ->name('employees.reviews');
            Route::post('/payroll', [HrAdministrationController::class, 'payroll'])
                ->middleware('permission:settings.business.manage')
                ->name('payroll.store');
        });

    Route::prefix('governance/changes')
        ->name('governance.changes.')
        ->group(function (): void {
            Route::get('/', [AdminChangeController::class, 'index'])
                ->middleware('permission:settings.business.manage')
                ->name('index');
            Route::post('/', [AdminChangeController::class, 'store'])
                ->middleware('permission:settings.business.manage')
                ->name('store');
            Route::post('/{change}/decide', [AdminChangeController::class, 'decide'])
                ->middleware('permission:settings.business.manage')
                ->name('decide');
        });

    Route::get('/operations/{operation}', [OperationStatusController::class, 'show'])
        ->name('operations.show');

});
