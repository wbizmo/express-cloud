<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
|
| Sprint 3 will implement the shared administrator and staff login flow.
|
*/

Route::view('/', 'auth.login-placeholder')->name('login');
