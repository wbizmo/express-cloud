<?php

declare(strict_types=1);

use App\Http\Controllers\Profile\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth',
    'account.active',
    'session.inactivity',
])->prefix('staff')->name('staff.')->group(function (): void {
    Route::view('/dashboard', 'staff.dashboard')
        ->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'show'])
        ->name('profile.show');

    Route::patch('/profile/picture', [ProfileController::class, 'updatePicture'])
        ->name('profile.picture.update');

    Route::delete('/profile/picture', [ProfileController::class, 'destroyPicture'])
        ->name('profile.picture.destroy');
});
