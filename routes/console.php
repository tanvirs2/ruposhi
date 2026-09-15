<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily DB backup at 03:00 (server time) — kept in storage/app/backups.
// Production needs a single cron entry: * * * * * php artisan schedule:run
//
// Retention was 14 (the command's default). Raised to 90: deleting an item
// used to erase its lines out of past চালান, and the damage went unnoticed
// for weeks — by the time anyone looked, the backups holding the lost lines
// had already been pruned. A gz dump is a couple of hundred KB, so three
// months costs a few tens of MB and buys a real recovery window.
Schedule::command('app:backup-db --keep=90')->dailyAt('03:00');
