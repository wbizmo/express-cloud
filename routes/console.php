<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('operations:daily-close')
    ->dailyAt((string) config('operations.report_time', '23:00'))
    ->timezone((string) config('operations.report_timezone', 'Africa/Lagos'))
    ->withoutOverlapping(120)
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/daily-close.log'));

Schedule::command('operations:prune-state')
    ->dailyAt('02:30')
    ->timezone((string) config('operations.report_timezone', 'Africa/Lagos'))
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('pos:expire-held-sales')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer();
