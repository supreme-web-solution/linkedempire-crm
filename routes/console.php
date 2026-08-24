<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Prevent scheduler overlaps to avoid multiple instances running simultaneously
// This uses cache locks to ensure only one instance runs even if cron fires multiple times
Schedule::command('app:post-scheduler')->everyMinute()->withoutOverlapping(60); // 60 second lock timeout
Schedule::command('app:fetch-linkedin-feeds')->twiceDailyAt(12, 18, 15)->withoutOverlapping(3600); // 1 hour lock timeout
Schedule::command('calls:send-reminders')->everyFifteenMinutes()->withoutOverlapping(900); // 15 minute lock timeout
Schedule::command('app:process-auto-comments')->hourly()->withoutOverlapping(3600); // 1 hour lock timeout
Schedule::command('email:flush-pending-batches')->everyFiveMinutes()->withoutOverlapping(300); // 5 minute lock timeout
Schedule::command('email:reset-daily-scraping-counts')->dailyAt('00:00')->withoutOverlapping(3600); // 1 hour lock timeout
Schedule::command('campaigns:dispatch-due')->everyFiveMinutes()->withoutOverlapping(300)->runInBackground();

// Running the scheduler
// * * * * * cd /home/tubetargeterapp/app.linkdominator.com && /usr/local/bin/ea-php83 artisan schedule:run >> /dev/null 2>&1