<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('alerts:no-backup')->daily();
Schedule::command('alerts:storage-usage')->hourly();
Schedule::command('alerts:send')->everyFiveMinutes();





Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
