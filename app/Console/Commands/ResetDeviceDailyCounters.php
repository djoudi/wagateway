<?php

namespace App\Console\Commands;

use App\Models\Device;
use Illuminate\Console\Command;

class ResetDeviceDailyCounters extends Command
{
    protected $signature   = 'devices:reset-daily-counters';
    protected $description = 'Reset daily message counters for all devices';

    public function handle(): void
    {
        $count = Device::whereDate('last_count_reset', '<', today())
            ->orWhereNull('last_count_reset')
            ->update(['messages_sent_today' => 0, 'last_count_reset' => today()]);

        $this->info("Reset daily counters for {$count} device(s).");
    }
}
