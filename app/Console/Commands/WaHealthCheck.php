<?php

namespace App\Console\Commands;

use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class WaHealthCheck extends Command
{
    protected $signature   = 'wa:health-check';
    protected $description = 'Ping the WA Node service and log health status';

    public function handle(WhatsAppService $wa): void
    {
        $ok = $wa->ping();

        if ($ok) {
            $this->info('WA service: healthy');
        } else {
            Log::error('[WA HealthCheck] WA service is unreachable!');
            $this->error('WA service: UNREACHABLE — check wa-service container');
        }
    }
}
