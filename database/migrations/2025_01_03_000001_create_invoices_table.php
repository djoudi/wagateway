<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('invoice_number', 30)->unique(); // WG-2026-000123
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained();

            $table->string('billing_cycle', 10)->default('monthly'); // monthly | yearly
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('DZD');

            $table->string('status', 20)->default('pending');
            // pending | processing | paid | failed | expired | cancelled | refunded

            // Chargily Pay references — see App\Services\ChargilyService
            $table->string('chargily_checkout_id')->nullable()->index();
            $table->string('chargily_payment_id')->nullable();
            $table->string('payment_method', 20)->nullable(); // edahabia | cib | ccp | bank_transfer | coupon
            $table->json('gateway_payload')->nullable(); // raw webhook payload, kept for dispute resolution

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // checkout link expiry, not subscription expiry
            $table->string('pdf_path')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
