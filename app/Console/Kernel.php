<?php

namespace App\Console;

use App\Jobs\SendScheduledMessages;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Process scheduled messages every minute
        $schedule->job(new SendScheduledMessages, 'default')
            ->everyMinute()
            ->withoutOverlapping();

        // Reset device daily message counters at midnight
        $schedule->command('devices:reset-daily-counters')
            ->dailyAt('00:00')
            ->withoutOverlapping();

        // WA service health check every 2 minutes
        $schedule->command('wa:health-check')
            ->everyTwoMinutes()
            ->withoutOverlapping();

        // Prune old webhook deliveries older than 30 days
        $schedule->command('webhook-deliveries:prune --days=30')
            ->weekly();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
