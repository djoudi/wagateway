<?php

namespace App\Jobs;

use App\Models\WebhookDelivery;
use App\Services\WebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeliverWebhook implements ShouldQueue
{
    use Queueable, InteractsWithQueue;

    public int $tries   = 3;
    public int $timeout = 15;

    public function __construct(private readonly WebhookDelivery $delivery) {}

    public function handle(): void
    {
        $webhook = $this->delivery->webhook;

        if (! $webhook || ! $webhook->is_active) {
            $this->delivery->update(['success' => false, 'response_body' => 'Webhook inactive or deleted']);
            return;
        }

        $payload   = $this->delivery->payload;
        $signature = WebhookService::sign($webhook->secret, $payload);
        $start     = microtime(true);

        try {
            $response = Http::withHeaders([
                'Content-Type'    => 'application/json',
                'X-WG-Event'      => $this->delivery->event,
                'X-WG-Signature'  => $signature,
                'X-WG-Delivery'   => $this->delivery->id,
                'User-Agent'      => 'WaGateway-Webhook/1.0',
            ])
            ->timeout(10)
            ->post($webhook->url, $payload);

            $durationMs = (int) ((microtime(true) - $start) * 1000);
            $success    = $response->successful();

            $this->delivery->update([
                'http_status'  => $response->status(),
                'response_body'=> substr($response->body(), 0, 1000),
                'duration_ms'  => $durationMs,
                'success'      => $success,
                'delivered_at' => $success ? now() : null,
            ]);

            if ($success) {
                $webhook->increment('success_count');
                $webhook->update(['last_triggered_at' => now()]);
            } else {
                $webhook->increment('failure_count');
                Log::warning("[Webhook] Non-2xx {$response->status()} → {$webhook->url}");
                $this->retryOrFail();
            }
        } catch (\Exception $e) {
            $this->delivery->update([
                'success'      => false,
                'response_body'=> $e->getMessage(),
                'duration_ms'  => (int) ((microtime(true) - $start) * 1000),
            ]);
            $webhook->increment('failure_count');
            Log::error("[Webhook] Exception → {$webhook->url}: {$e->getMessage()}");
            $this->retryOrFail();
        }
    }

    public function backoff(): array
    {
        return [30, 120, 600]; // 30s → 2min → 10min
    }

    private function retryOrFail(): void
    {
        if ($this->attempts() < $this->tries) {
            $this->release($this->backoff()[$this->attempts() - 1] ?? 600);
        } else {
            $this->fail();
        }
    }

    public function failed(\Throwable $e): void
    {
        $this->delivery->update([
            'success'       => false,
            'next_retry_at' => null,
            'response_body' => 'Max retries reached: ' . $e->getMessage(),
        ]);
    }
}
