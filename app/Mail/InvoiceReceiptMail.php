<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Invoice $invoice) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "تأكيد الدفع — فاتورة {$this->invoice->invoice_number}",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.invoice-receipt');
    }

    public function attachments(): array
    {
        if ($this->invoice->pdf_path && \Illuminate\Support\Facades\Storage::exists($this->invoice->pdf_path)) {
            return [
                \Illuminate\Mail\Mailables\Attachment::fromStorage($this->invoice->pdf_path)
                    ->as("{$this->invoice->invoice_number}.pdf")
                    ->withMime('application/pdf'),
            ];
        }
        return [];
    }
}
