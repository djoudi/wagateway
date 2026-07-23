<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\SecurityEvent;
use App\Services\ChargilyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Receives payment confirmation events from Chargily Pay.
 * Public endpoint, secured by HMAC signature verification (never by secrecy
 * of the URL alone) — same defense-in-depth principle used for the internal
 * WA-service events endpoint.
 */
class ChargilyWebhookController extends Controller
{
    public function __construct(private readonly ChargilyService $chargily) {}

    public function handle(Request $request): JsonResponse
    {
        $payload   = $request->getContent();
        $signature = $request->header('signature'); // ⚠ verify exact header name against live Chargily docs

        if (! $this->chargily->verifyWebhookSignature($payload, $signature)) {
            SecurityEvent::log('webhook_auth_failed', null, ['source' => 'chargily', 'ip' => $request->ip()]);
            Log::warning('[Chargily Webhook] Invalid signature', ['ip' => $request->ip()]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $event = $request->input('type');
        $data  = $request->input('data', []);

        Log::info("[Chargily Webhook] {$event}", ['checkout_id' => $data['id'] ?? null]);

        match ($event) {
            'checkout.paid'    => $this->handlePaid($data),
            'checkout.failed'  => $this->handleFailed($data),
            'checkout.expired' => $this->handleExpired($data),
            default             => Log::notice("[Chargily Webhook] Unhandled event: {$event}"),
        };

        return response()->json(['received' => true]);
    }

    private function handlePaid(array $data): void
    {
        $checkoutId = $data['id'] ?? null;
        if (! $checkoutId) return;

        // Idempotency: webhook delivery can retry — never activate a plan twice
        $invoice = Invoice::where('chargily_checkout_id', $checkoutId)->first();
        if (! $invoice) {
            Log::warning("[Chargily Webhook] Paid event for unknown checkout: {$checkoutId}");
            return;
        }
        if ($invoice->isPaid()) {
            return; // already processed — safe no-op on duplicate delivery
        }

        DB::transaction(function () use ($invoice, $data) {
            $invoice->update([
                'chargily_payment_id' => $data['payment_id'] ?? $data['id'] ?? null,
                'payment_method'      => $data['payment_method'] ?? null,
            ]);
            $invoice->markPaid($data);

            $user = $invoice->user;
            $periodDays = $invoice->billing_cycle === 'yearly' ? 365 : 30;

            $user->update([
                'plan_id'          => $invoice->plan_id,
                'plan_expires_at'  => now()->addDays($periodDays),
                'is_suspended'     => false,
            ]);
        });

        SecurityEvent::log('invoice_paid', $invoice->user_id, [
            'invoice_number' => $invoice->invoice_number,
            'plan'           => $invoice->plan->slug,
            'amount'         => $invoice->amount,
        ]);

        // Generate PDF + email receipt (queued — never block the webhook response)
        \App\Jobs\GenerateInvoicePdf::dispatch($invoice);
        \App\Jobs\SendInvoiceReceiptEmail::dispatch($invoice);
    }

    private function handleFailed(array $data): void
    {
        $checkoutId = $data['id'] ?? null;
        if (! $checkoutId) return;

        Invoice::where('chargily_checkout_id', $checkoutId)
            ->where('status', 'pending')
            ->update(['status' => 'failed', 'gateway_payload' => $data]);
    }

    private function handleExpired(array $data): void
    {
        $checkoutId = $data['id'] ?? null;
        if (! $checkoutId) return;

        Invoice::where('chargily_checkout_id', $checkoutId)
            ->where('status', 'pending')
            ->update(['status' => 'expired']);
    }
}
