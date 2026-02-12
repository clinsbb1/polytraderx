<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('subscriptions:check-expired')->hourly();

// Tier 1: Reflexes — Every minute
Schedule::command('bot:scan-markets')->everyMinute()->withoutOverlapping()->runInBackground();
Schedule::command('bot:execute-trades')->everyMinute()->withoutOverlapping()->runInBackground();
Schedule::command('bot:monitor-positions')->everyMinute()->withoutOverlapping()->runInBackground();

// Tier 2: Muscles (Haiku) — Every 5 minutes
Schedule::command('bot:ai-analyze-markets')->everyFiveMinutes()->withoutOverlapping()->runInBackground();

// Tier 3: Brain (Sonnet) — Event-driven + scheduled
Schedule::command('bot:ai-audit-losses')->everyFiveMinutes()->withoutOverlapping()->runInBackground();
Schedule::command('bot:daily-review')->dailyAt('23:55')->withoutOverlapping()->runInBackground();
Schedule::command('bot:weekly-report')->weeklyOn(0, '23:55')->withoutOverlapping()->runInBackground();

// Housekeeping
Schedule::command('bot:snapshot-balance')->everyFifteenMinutes()->withoutOverlapping()->runInBackground();
Schedule::command('bot:daily-summary')->dailyAt('00:05')->withoutOverlapping()->runInBackground();
Schedule::command('bot:cleanup-logs')->daily()->withoutOverlapping()->runInBackground();
