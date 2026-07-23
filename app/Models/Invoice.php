<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Invoice extends Model
{
    protected $fillable = [
        'uuid', 'invoice_number', 'user_id', 'plan_id',
        'billing_cycle', 'amount', 'currency', 'status',
        'chargily_checkout_id', 'chargily_payment_id', 'payment_method',
        'gateway_payload', 'paid_at', 'expires_at', 'pdf_path',
    ];

    protected $casts = [
        'gateway_payload' => 'array',
        'amount'          => 'decimal:2',
        'paid_at'         => 'datetime',
        'expires_at'      => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            $invoice->uuid ??= (string) Str::uuid();
            $invoice->invoice_number ??= static::nextInvoiceNumber();
        });
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function plan(): BelongsTo { return $this->belongsTo(Plan::class); }

    public function isPaid(): bool { return $this->status === 'paid'; }

    public function markPaid(array $gatewayPayload = []): void
    {
        $this->update([
            'status'          => 'paid',
            'paid_at'         => now(),
            'gateway_payload' => $gatewayPayload ?: $this->gateway_payload,
        ]);
    }

    /**
     * Sequential, year-scoped, human-readable invoice numbers: WG-2026-000123
     * Uses a row lock on the last invoice of the year to avoid collisions
     * under concurrent webhook delivery.
     */
    public static function nextInvoiceNumber(): string
    {
        $year = now()->year;

        return \Illuminate\Support\Facades\DB::transaction(function () use ($year) {
            $last = static::where('invoice_number', 'like', "WG-{$year}-%")
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            $seq = $last
                ? ((int) substr($last->invoice_number, -6)) + 1
                : 1;

            return sprintf('WG-%d-%06d', $year, $seq);
        });
    }

    public function getRouteKeyName(): string { return 'uuid'; }
}
