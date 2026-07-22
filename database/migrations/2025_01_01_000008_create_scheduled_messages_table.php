<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->string('to_number', 20);
            $table->json('message_data');
            $table->timestamp('scheduled_at');
            $table->string('status', 20)->default('pending'); // pending | sent | cancelled | failed
            $table->foreignId('message_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['status','scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_messages');
    }
};
