<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Integration with Chargily Pay (https://chargily.com) — an Algerian payment
 * gateway supporting Edahabia and CIB cards.
 *
 * ⚠ IMPORTANT — VERIFY BEFORE PRODUCTION USE:
 * This service is built against Chargily Pay's publicly documented API v2
 * shape (checkout sessions + signed webhooks) as of this codebase's last
 * knowledge update. This sandbox has no live network access to Chargily's
 * docs at build time. Before going live:
 *   1. Confirm the exact endpoint paths against https://dev.chargily.com
 *   2. Confirm field names in the checkout payload match current API version
 *   3. Confirm the webhook signature header name and algorithm
 *   4. Test end-to-end in Chargily's sandbox/test mode before switching
 *      CHARGILY_MODE=live in .env
 */
class ChargilyService
{
    private string $baseUrl;
    private string $apiKey;
    private string $webhookSecret;

    public function __construct()
    {
        $mode = config('services.chargily.mode', 'test');
        $this->baseUrl = $mode === 'live'
            ? 'https://pay.chargily.net/api/v2'
            : 'https://pay.chargily.net/test/api/v2';

        $this->apiKey       = config('services.chargily.api_key', '');
        $this->webhookSecret = config('services.chargily.webhook_secret', '');
    }

    /**
     * Create a hosted checkout session for an invoice and return the URL
     * to redirect the customer to for payment.
     *
     * @throws \RuntimeException on API failure
     */
    public function createCheckout(Invoice $invoice): string
    {
        $response = Http::withToken($this->apiKey)
            ->timeout(15)
            ->post("{$this->baseUrl}/checkouts", [
                'amount'          => (int) round($invoice->amount), // DZD, no decimals
                'currency'        => 'dzd',
                'payment_method'  => null, // let customer choose Edahabia/CIB on Chargily's page
                'success_url'     => route('billing.checkout.success', $invoice),
                'failure_url'     => route('billing.checkout.failure', $invoice),
                'webhook_endpoint'=> route('webhooks.chargily'),
                'description'     => "اشتراك {$invoice->plan->name} — فاتورة {$invoice->invoice_number}",
                'metadata'        => [
                    'invoice_uuid'   => $invoice->uuid,
                    'invoice_number' => $invoice->invoice_number,
                    'user_id'        => $invoice->user_id,
                    'plan_id'        => $invoice->plan_id,
                ],
            ]);

        if ($response->failed()) {
            Log::error('[Chargily] Checkout creation failed', [
                'invoice' => $invoice->invoice_number,
                'status'  => $response->status(),
                'body'    => $response->body(),
            ]);
            throw new \RuntimeException('تعذّر إنشاء جلسة الدفع. حاول مرة أخرى أو استخدم طريقة دفع بديلة.');
        }

        $data = $response->json();

        $invoice->update([
            'chargily_checkout_id' => $data['id'] ?? null,
            'expires_at'           => now()->addHours(2),
        ]);

        return $data['checkout_url'] ?? throw new \RuntimeException('استجابة غير متوقعة من بوابة الدفع.');
    }

    /**
     * Verify the HMAC signature on an incoming webhook request.
     * Uses timing-safe comparison — same principle as the internal
     * WA-service webhook auth (see WaEventsController).
     */
    public function verifyWebhookSignature(string $payload, ?string $signature): bool
    {
        if (! $signature || ! $this->webhookSecret) {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, $this->webhookSecret);

        return hash_equals($expected, $signature);
    }

    /**
     * Fetch a checkout's current status directly from Chargily — used as a
     * fallback reconciliation path if a webhook is ever missed.
     */
    public function getCheckoutStatus(string $checkoutId): ?array
    {
        $response = Http::withToken($this->apiKey)
            ->timeout(10)
            ->get("{$this->baseUrl}/checkouts/{$checkoutId}");

        return $response->successful() ? $response->json() : null;
    }
}
