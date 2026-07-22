<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->string('to_number', 20);
            $table->string('type', 20)->default('text');
            // type: text | image | document | audio | video | location | contact | sticker
            $table->json('content');
            // { body:"...", caption:"...", url:"...", filename:"...", lat:..., lng:... }
            $table->string('status', 20)->default('queued');
            // queued | sending | sent | delivered | read | failed
            $table->string('wa_message_id')->nullable()->index(); // WhatsApp internal ID
            $table->text('error_message')->nullable();
            $table->unsignedTinyInteger('retry_count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->foreignId('bulk_job_id')->nullable();    // if part of bulk send
            $table->boolean('is_test')->default(false);
            $table->timestamps();

            $table->index(['user_id','status','created_at']);
            $table->index(['device_id','created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
