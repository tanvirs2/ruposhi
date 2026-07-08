<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily DB backup at 03:00 (server time) — keeps last 14 files in storage/app/backups.
// Production needs a single cron entry: * * * * * php artisan schedule:run
Schedule::command('app:backup-db')->dailyAt('03:00');
