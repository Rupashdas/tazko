<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled tasks
|--------------------------------------------------------------------------
*/

// Hourly cleanup of attachments that were uploaded but whose parent (task,
// comment, project description) was never saved within the TTL window.
// withoutOverlapping() stops a slow reap from stacking on the next tick;
// runInBackground() lets the scheduler move on immediately.
Schedule::command('attachments:reap-orphans')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->onOneServer();
