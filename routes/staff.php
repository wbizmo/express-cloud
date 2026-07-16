<?php

declare(strict_types=1);

use App\Http\Controllers\Profile\ProfileController;
use App\Http\Controllers\Staff\Commercial\SaleReturnController;
use App\Http\Controllers\Staff\Commercial\SaleSettlementController;
use App\Http\Controllers\Staff\StaffDashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth',
    'account.active',
    'session.inactivity',
])->prefix('staff')->name('staff.')->group(function (): void {
    Route::get('/dashboard', StaffDashboardController::class)
        ->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'show'])
        ->name('profile.show');

    Route::patch('/profile/picture', [ProfileController::class, 'updatePicture'])
        ->name('profile.picture.update');

    Route::delete('/profile/picture', [ProfileController::class, 'destroyPicture'])
        ->name('profile.picture.destroy');
    Route::prefix('sales/{sale}')
        ->middleware('sale.visible')
        ->name('sales.')
        ->group(function (): void {
            Route::post('/payments', [SaleSettlementController::class, 'payment'])
                ->middleware('permission:sales.payments.record')
                ->name('payments.store');
            Route::post('/voucher', [SaleSettlementController::class, 'voucher'])
                ->middleware('permission:vouchers.apply')
                ->name('voucher.store');
            Route::get('/returns/create', [SaleReturnController::class, 'create'])
                ->middleware('permission:sales.returns.create')
                ->name('returns.create');
            Route::post('/returns', [SaleReturnController::class, 'store'])
                ->middleware('permission:sales.returns.create')
                ->name('returns.store');
        });

});
