<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Administrator Routes
|--------------------------------------------------------------------------
|
| Protected administrator routes will be registered here from Sprint 3.
|
*/

Route::prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        //
    });
