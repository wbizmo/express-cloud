<?php

declare(strict_types=1);

use App\Http\Controllers\Catalog\ProductLookupController;
use App\Http\Controllers\Operations\SignedDailyCloseController;
use App\Http\Controllers\Public\SaleVerificationController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
require __DIR__.'/staff.php';

if (app()->environment(['local', 'testing'])) {
    Route::view('/ui-preview', 'ui.shell-preview')->name('ui.preview');
}

Route::post('/cron/operations/daily-close', SignedDailyCloseController::class)
    ->middleware(['throttle:10,1', 'operations.cron.hmac'])
    ->name('cron.operations.daily-close');

Route::get('/verify/sales/{sale}/{token}', SaleVerificationController::class)
    ->name('public.sales.verify');

Route::get('/catalog/products/lookup', ProductLookupController::class)
    ->middleware(['auth', 'account.active', 'session.inactivity'])
    ->name('catalog.products.lookup');
