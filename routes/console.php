<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('subscriptions:check-expired')->hourly();
Schedule::command('payments:expire-pending')->hourly();

// Tier 1: Reflexes — Every minute
Schedule::command('sim:scan-markets')->everyMinute()->withoutOverlapping()->runInBackground();
Schedule::command('sim:execute-trades')->everyMinute()->withoutOverlapping()->runInBackground();
Schedule::command('sim:monitor-positions')->everyMinute()->withoutOverlapping()->runInBackground();

// Tier 2: Muscles (Haiku) — Every 5 minutes
Schedule::command('sim:ai-analyze-markets')->everyFiveMinutes()->withoutOverlapping()->runInBackground();

// Tier 3: Brain (Sonnet) — Event-driven + scheduled
Schedule::command('sim:ai-audit-losses')->everyFiveMinutes()->withoutOverlapping()->runInBackground();
Schedule::command('sim:daily-review')->dailyAt('23:55')->withoutOverlapping()->runInBackground();
Schedule::command('sim:weekly-report')->weeklyOn(0, '23:55')->withoutOverlapping()->runInBackground();

// Housekeeping
Schedule::command('sim:snapshot-balance')->everyFifteenMinutes()->withoutOverlapping()->runInBackground();
Schedule::command('sim:daily-summary')->dailyAt('00:05')->withoutOverlapping()->runInBackground();
Schedule::command('sim:cleanup-logs')->daily()->withoutOverlapping()->runInBackground();
