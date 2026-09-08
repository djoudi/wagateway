<?php

namespace App\Console\Commands;

use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class WaHealthCheck extends Command
{
    protected $signature   = 'wa:health-check';
    protected $description = 'Ping the WA Node service and log health status';

    public function handle(WhatsAppService $wa): int
    {
        $ok = $wa->ping();

        if ($ok) {
            $this->info('WA service: healthy');
            return self::SUCCESS;
        }

        Log::error('[WA HealthCheck] WA service is unreachable!', [
            'url' => rtrim((string) config('services.wa_node.url'), '/') . '/health',
        ]);
        $this->error('WA service: UNREACHABLE — check wa-service process and WA_SERVICE_URL');

        return self::FAILURE;
    }
}
