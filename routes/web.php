<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Express Cloud entry route
|--------------------------------------------------------------------------
|
| Sprint 3 will replace this foundation screen with the shared admin/staff
| login page. The production root route remains the authentication entry.
|
*/

Route::view('/', 'welcome')->name('home');
