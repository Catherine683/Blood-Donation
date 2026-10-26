<?php

use App\Jobs\RecordQueueHeartbeatJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('donorlink:heartbeat')
    ->everyMinute();

Schedule::job(new RecordQueueHeartbeatJob)
    ->everyFiveMinutes();

Schedule::command('queue:prune-failed --hours=168')
    ->daily();

Schedule::command('donorlink:send-reminders')
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('donorlink:dispatch-announcements')
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->onOneServer();
