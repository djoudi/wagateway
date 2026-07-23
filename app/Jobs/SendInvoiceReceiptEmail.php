<?php

namespace App\Jobs;

use App\Mail\InvoiceReceiptMail;
use App\Models\Invoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendInvoiceReceiptEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public array $backoff = [60, 300, 900];

    public function __construct(private readonly Invoice $invoice) {}

    public function handle(): void
    {
        $this->invoice->loadMissing('user', 'plan');

        try {
            Mail::to($this->invoice->user->email)->send(new InvoiceReceiptMail($this->invoice));
        } catch (\Throwable $e) {
            Log::error("[Invoice Email] Failed to send receipt for {$this->invoice->invoice_number}: {$e->getMessage()}");
            throw $e; // let the queue retry
        }
    }
}
