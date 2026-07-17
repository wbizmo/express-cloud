<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Operations\CronDigestController;
use App\Http\Controllers\Public\SaleVerificationController;
use App\Http\Controllers\Catalog\ProductLookupController;

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
require __DIR__.'/staff.php';

if (app()->environment(['local', 'testing'])) {
    Route::view('/ui-preview', 'ui.shell-preview')
        ->name('ui.preview');
}

Route::get(
    '/cron/{secret}/end-of-day-digest',
    CronDigestController::class,
)->name('cron.end-of-day-digest');

Route::get(
    '/verify/sales/{sale}/{token}',
    SaleVerificationController::class,
)->name('public.sales.verify');

Route::get('/catalog/products/lookup', ProductLookupController::class)
    ->middleware(['auth', 'account.active', 'session.inactivity'])
    ->name('catalog.products.lookup');
