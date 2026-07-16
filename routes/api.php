<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\OpenApiController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\SaleController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class)
    ->name('api.health');

Route::get('/openapi.json', OpenApiController::class)
    ->name('api.openapi');

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::middleware('api.token:products.read')->group(function (): void {
        Route::get('/products', [ProductController::class, 'index'])
            ->name('products.index');
        Route::get('/products/{product}', [ProductController::class, 'show'])
            ->name('products.show');
    });

    Route::middleware('api.token:customers.read')->group(function (): void {
        Route::get('/customers', [CustomerController::class, 'index'])
            ->name('customers.index');
        Route::get('/customers/{customer}', [CustomerController::class, 'show'])
            ->name('customers.show');
    });

    Route::middleware('api.token:sales.read')->group(function (): void {
        Route::get('/sales', [SaleController::class, 'index'])
            ->name('sales.index');
        Route::get('/sales/{sale}', [SaleController::class, 'show'])
            ->name('sales.show');
    });

    Route::middleware('api.token:reports.read')->group(function (): void {
        Route::get(
            '/reports/sales-by-branch',
            [ReportController::class, 'salesByBranch'],
        )->name('reports.sales-by-branch');

        Route::get(
            '/reports/low-stock',
            [ReportController::class, 'lowStock'],
        )->name('reports.low-stock');
    });
});
