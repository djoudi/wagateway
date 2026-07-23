<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Plan;
use App\Services\ChargilyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BillingController extends Controller
{
    public function __construct(private readonly ChargilyService $chargily) {}

    /**
     * Create a Chargily checkout for the given plan and redirect the user
     * to Chargily's hosted payment page.
     */
    public function checkout(Request $request, Plan $plan): RedirectResponse
    {
        $request->validate([
            'cycle' => ['required', 'in:monthly,yearly'],
        ]);

        $user  = $request->user();
        $cycle = $request->input('cycle');
        $amount = $cycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly;

        $invoice = Invoice::create([
            'user_id'       => $user->id,
            'plan_id'       => $plan->id,
            'billing_cycle' => $cycle,
            'amount'        => $amount,
            'currency'      => 'DZD',
            'status'        => 'pending',
        ]);

        try {
            $checkoutUrl = $this->chargily->createCheckout($invoice);
        } catch (\RuntimeException $e) {
            $invoice->update(['status' => 'failed']);
            return redirect()->route('billing')->with('error', $e->getMessage());
        }

        return redirect()->away($checkoutUrl);
    }

    /**
     * Customer returns here after a successful payment on Chargily's page.
     * Note: this is a UX confirmation only — the actual plan activation
     * happens via the signed webhook (ChargilyWebhookController), which is
     * the only trusted source of truth for payment status.
     */
    public function checkoutSuccess(Invoice $invoice): \Illuminate\View\View
    {
        $this->authorizeInvoice($invoice);

        return view('billing.checkout-success', [
            'invoice' => $invoice,
            // Webhooks can arrive a few seconds after redirect — the view
            // polls invoice status via Livewire until it flips to "paid".
        ]);
    }

    public function checkoutFailure(Invoice $invoice): \Illuminate\View\View
    {
        $this->authorizeInvoice($invoice);

        return view('billing.checkout-failure', ['invoice' => $invoice]);
    }

    /**
     * Download a paid invoice as PDF.
     */
    public function downloadInvoice(Invoice $invoice)
    {
        $this->authorizeInvoice($invoice);

        if (! $invoice->pdf_path || ! \Illuminate\Support\Facades\Storage::exists($invoice->pdf_path)) {
            abort(404, 'الفاتورة غير جاهزة بعد — حاول مرة أخرى خلال دقيقة.');
        }

        return \Illuminate\Support\Facades\Storage::download(
            $invoice->pdf_path,
            "{$invoice->invoice_number}.pdf"
        );
    }

    private function authorizeInvoice(Invoice $invoice): void
    {
        if ($invoice->user_id !== auth()->id()) {
            abort(403);
        }
    }
}
