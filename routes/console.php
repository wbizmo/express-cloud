use Illuminate\Support\Facades\Schedule;
<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('reports:daily-operations')->dailyAt('00:10')->withoutOverlapping()->onOneServer()->appendOutputTo(storage_path('logs/daily-operations-reports.log'));
