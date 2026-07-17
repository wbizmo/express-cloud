<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AccountSearchController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::view('/', 'auth.login')->name('login');

    Route::get('/login/staff-search', AccountSearchController::class)
        ->middleware('throttle:30,1')
        ->name('login.staff-search');

    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');
