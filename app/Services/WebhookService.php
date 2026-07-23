<?php

namespace App\Services;

use App\Models\User;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use App\Jobs\DeliverWebhook;

class WebhookService
{
    /**
     * Dispatch webhook deliveries for all of a user's active webhooks
     * that subscribe to the given event.
     */
    public function dispatch(User $user, string $event, array $payload): void
    {
        $user->webhooks()
            ->active()
            ->get()
            ->filter(fn (Webhook $w) => $w->listensTo($event))
            ->each(function (Webhook $webhook) use ($event, $payload) {
                $delivery = WebhookDelivery::create([
                    'webhook_id' => $webhook->id,
                    'event'      => $event,
                    'payload'    => $payload,
                    'attempt'    => 1,
                ]);
                DeliverWebhook::dispatch($delivery)->onQueue('webhooks');
            });
    }

    /**
     * Build a signed HMAC-SHA256 signature for a payload.
     * Consumers verify: hash_equals($expected, $received)
     */
    public static function sign(string $secret, array $payload): string
    {
        return 'sha256=' . hash_hmac('sha256', json_encode($payload), $secret);
    }
}
