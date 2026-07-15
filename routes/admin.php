<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\BranchController;
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

    Route::patch('/branches/{branch}/deactivate', [BranchController::class, 'deactivate'])
        ->middleware('permission:branches.deactivate')
        ->name('branches.deactivate');

    Route::get('/staff', [StaffController::class, 'index'])
        ->middleware('permission:staff.view')
        ->name('staff.index');

    Route::post('/staff', [StaffController::class, 'store'])
        ->middleware('permission:staff.create')
        ->name('staff.store');

    Route::patch('/staff/{account}/suspend', [StaffController::class, 'suspend'])
        ->middleware('permission:staff.suspend')
        ->name('staff.suspend');

    Route::get('/roles', [RoleController::class, 'index'])
        ->middleware('permission:roles.view')
        ->name('roles.index');

    Route::post('/roles', [RoleController::class, 'store'])
        ->middleware('permission:roles.create')
        ->name('roles.store');

    Route::get('/security/sessions', [SessionController::class, 'index'])
        ->middleware('permission:staff.sessions.view')
        ->name('security.sessions.index');

    Route::patch('/security/sessions/{session}/revoke', [SessionController::class, 'revoke'])
        ->middleware('permission:staff.sessions.revoke')
        ->name('security.sessions.revoke');
});
