<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Backup schedule
|--------------------------------------------------------------------------
|
| Requires ONE server cron entry (see docs/BACKUPS.md):
|   * * * * * cd /path/to/site && php artisan schedule:run >> /dev/null 2>&1
|
| Times are in the app timezone (Site Settings → General → Timezone).
| Runs execute inline inside the scheduler process, so no queue worker is
| needed for nightly backups; the admin panel's manual runs use the queue.
|
*/
Schedule::command('backups:run database')->dailyAt('01:30')->withoutOverlapping()->onOneServer()->runInBackground();
Schedule::command('backups:run files')->dailyAt('02:00')->withoutOverlapping()->onOneServer()->runInBackground();
Schedule::command('backups:run full')->weeklyOn(0, '03:00')->withoutOverlapping()->onOneServer()->runInBackground();
Schedule::command('backups:clean')->dailyAt('04:00')->withoutOverlapping()->onOneServer();
