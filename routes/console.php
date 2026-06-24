<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| Keep the local database in sync with the live sources. The frequency is
| configurable via NEWS_FETCH_SCHEDULE (defaults to hourly).
*/
Schedule::command('news:fetch')
    ->cron(match (config('news.fetch.schedule')) {
        'everyMinute' => '* * * * *',
        'everyFiveMinutes' => '*/5 * * * *',
        'everyThirtyMinutes' => '*/30 * * * *',
        'daily' => '0 0 * * *',
        default => '0 * * * *', // hourly
    })
    ->withoutOverlapping()
    ->runInBackground();
