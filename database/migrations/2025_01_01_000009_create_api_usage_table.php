<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('messages_sent')->default(0);
            $table->unsignedInteger('api_requests')->default(0);
            $table->timestamps();

            $table->unique(['user_id','date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_usage');
    }
};
