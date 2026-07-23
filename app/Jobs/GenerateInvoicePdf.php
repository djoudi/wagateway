<?php

namespace App\Jobs;

use App\Models\Invoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateInvoicePdf implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(private readonly Invoice $invoice) {}

    public function handle(): void
    {
        // Requires barryvdh/laravel-dompdf — add to composer.json:
        //   composer require barryvdh/laravel-dompdf
        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            Log::error('[Invoice PDF] barryvdh/laravel-dompdf is not installed. Run: composer require barryvdh/laravel-dompdf');
            return;
        }

        $this->invoice->loadMissing('user', 'plan');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.invoice', [
            'invoice' => $this->invoice,
        ])->setPaper('a4');

        $path = "invoices/{$this->invoice->invoice_number}.pdf";
        Storage::put($path, $pdf->output());

        $this->invoice->update(['pdf_path' => $path]);
    }
}
