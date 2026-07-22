<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // Starter, Pro, Business
            $table->string('slug')->unique();
            $table->integer('daily_message_limit');
            $table->integer('max_devices');
            $table->integer('max_webhooks')->default(5);
            $table->integer('max_templates')->default(20);
            $table->integer('bulk_batch_limit')->default(100);
            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->decimal('price_yearly', 10, 2)->default(0);
            $table->json('features')->default('[]');        // ["bulk_send","scheduling","webhooks"]
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
