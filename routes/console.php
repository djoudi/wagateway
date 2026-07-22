<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('devices:reset-daily-counters')->dailyAt('00:00')->withoutOverlapping();
Schedule::command('wa:health-check')->everyTwoMinutes()->withoutOverlapping();
Schedule::command('security:audit-check')->hourly()->withoutOverlapping();
Schedule::command('billing:send-reminders')->dailyAt('09:00')->withoutOverlapping();
Schedule::job(new \App\Jobs\SendScheduledMessages)->everyMinute()->withoutOverlapping();
Schedule::command('model:prune', ['--model' => [\App\Models\WebhookDelivery::class]])->weekly();
