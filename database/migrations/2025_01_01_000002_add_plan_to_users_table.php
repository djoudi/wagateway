<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete()->after('id');
            $table->string('api_key', 64)->unique()->nullable()->after('plan_id');
            $table->string('api_key_test', 64)->unique()->nullable()->after('api_key');
            $table->timestamp('plan_expires_at')->nullable()->after('api_key_test');
            $table->boolean('is_suspended')->default(false)->after('plan_expires_at');
            $table->string('suspension_reason')->nullable()->after('is_suspended');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn(['plan_id','api_key','api_key_test','plan_expires_at','is_suspended','suspension_reason']);
        });
    }
};
