<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();                  // public-facing ID
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone_number')->nullable();      // filled after QR scan
            $table->string('status', 20)->default('disconnected');
            // status: disconnected | connecting | connected | banned | removed
            $table->text('qr_code')->nullable();             // base64 QR
            $table->timestamp('qr_expires_at')->nullable();
            $table->text('session_data')->nullable();        // encrypted WA session
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->unsignedBigInteger('messages_sent_today')->default(0);
            $table->unsignedBigInteger('messages_sent_total')->default(0);
            $table->date('last_count_reset')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id','status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
