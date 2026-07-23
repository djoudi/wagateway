<?php

namespace App\Livewire\Billing;

use App\Models\Plan;
use Livewire\Component;

class BillingPage extends Component
{
    public bool   $showUpgradeModal = false;
    public ?int   $selectedPlanId  = null;
    public string $paymentMethod   = 'card'; // card (Chargily) | ccp | bank_transfer | coupon
    public string $billingCycle    = 'monthly'; // monthly | yearly
    public bool   $isProcessing    = false;

    public function mount(): void
    {
        // Honor the plan the user picked on the landing page pricing CTA,
        // captured at registration time (see RegisterController).
        $intended = session()->pull('intended_plan');
        if (! $intended) return;

        $plan = Plan::where('slug', $intended)->where('is_active', true)->first();
        if ($plan && $plan->id !== auth()->user()->plan_id) {
            $this->selectedPlanId  = $plan->id;
            $this->showUpgradeModal = true;
        }
    }

    public function selectPlan(int $planId): void
    {
        $user = auth()->user();

        // No action if already on this plan
        if ($user->plan_id === $planId) return;

        $this->selectedPlanId  = $planId;
        $this->showUpgradeModal = true;
    }

    public function closeModal(): void
    {
        $this->showUpgradeModal = false;
        $this->selectedPlanId  = null;
        $this->isProcessing    = false;
    }

    /**
     * Submit upgrade request.
     *
     * - "card"  → creates a pending Invoice, then redirects to Chargily's
     *             hosted checkout. Plan activation happens ONLY via the
     *             signed webhook (ChargilyWebhookController), never here —
     *             this method never trusts client-side payment confirmation.
     * - "ccp" / "bank_transfer" → creates a pending Invoice for manual
     *             reconciliation by an admin in Filament (see InvoiceResource).
     * - "coupon" → TODO: validate against a coupons table (not yet built).
     */
    public function requestUpgrade()
    {
        $plan = \App\Models\Plan::findOrFail($this->selectedPlanId);
        $user = auth()->user();
        $this->isProcessing = true;

        // Card payments: BillingController::checkout() is the single source
        // of truth for invoice creation on this path — creating one here
        // too would produce a duplicate, orphaned "pending" invoice.
        if ($this->paymentMethod === 'card') {
            return $this->redirect(route('billing.checkout', [
                'plan'  => $plan->id,
                'cycle' => $this->billingCycle,
            ]), navigate: false);
        }

        // Manual payment methods (CCP / bank transfer): create the invoice
        // directly here — it stays "pending" until an admin confirms
        // receipt of payment in the Filament InvoiceResource.
        $amount = $this->billingCycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly;

        $invoice = \App\Models\Invoice::create([
            'user_id'       => $user->id,
            'plan_id'       => $plan->id,
            'billing_cycle' => $this->billingCycle,
            'amount'        => $amount,
            'currency'      => 'DZD',
            'status'        => 'pending',
            'payment_method'=> $this->paymentMethod,
        ]);

        $this->dispatch('notify',
            type: 'success',
            message: "تم إنشاء طلب الترقية (فاتورة {$invoice->invoice_number}). سنُفعّل خطتك خلال 24 ساعة من تأكيد استلام الدفع."
        );
        $this->closeModal();
    }

    public function render()
    {
        $user  = auth()->user();
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();

        $usageStats = [
            'messages_today' => \App\Models\Message::where('user_id', $user->id)->whereDate('created_at', today())->count(),
            'daily_limit'    => $user->plan?->daily_message_limit ?? 0,
            'devices_used'   => \App\Models\Device::where('user_id', $user->id)->count(),
            'devices_max'    => $user->plan?->max_devices ?? 0,
            'webhooks_used'  => $user->webhooks()->count(),
            'webhooks_max'   => $user->plan?->max_webhooks ?? 0,
        ];

        return view('livewire.billing.billing-page', compact('plans', 'usageStats'));
    }
}
