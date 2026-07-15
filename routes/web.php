<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Operations\CronDigestController;

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
