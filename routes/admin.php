<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\Catalog\ClassificationController;
use App\Http\Controllers\Admin\Catalog\ProductController;
use App\Http\Controllers\Admin\Catalog\SupplierController;
use App\Http\Controllers\Admin\Catalog\TaxRateController;
use App\Http\Controllers\Admin\Imports\ProductImportController;
use App\Http\Controllers\Admin\Inventory\InventoryController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SessionController;
use App\Http\Controllers\Admin\StaffController;
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

});
