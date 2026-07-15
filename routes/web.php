<?php

declare(strict_types=1);

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
require __DIR__.'/staff.php';

if (app()->environment(['local', 'testing'])) {
    Route::view('/ui-preview', 'ui.shell-preview')
        ->name('ui.preview');
}
