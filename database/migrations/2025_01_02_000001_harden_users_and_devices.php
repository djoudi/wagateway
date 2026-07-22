<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Users: add hash columns alongside existing keys
        // We keep api_key_prefix for display-only (first 12 chars)
        Schema::table('users', function (Blueprint $table) {
            $table->string('api_key_hash', 64)->nullable()->unique()->after('api_key');
            $table->string('api_key_test_hash', 64)->nullable()->unique()->after('api_key_test');
            $table->string('api_key_prefix', 12)->nullable()->after('api_key_hash');
            $table->string('api_key_test_prefix', 12)->nullable()->after('api_key_test_hash');
            $table->timestamp('api_key_last_used_at')->nullable()->after('api_key_test_prefix');
            $table->string('api_key_last_used_ip', 45)->nullable()->after('api_key_last_used_at');
        });

        // Devices: rename session_data to reflect encryption
        Schema::table('devices', function (Blueprint $table) {
            $table->text('session_data_enc')->nullable()->after('session_data');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'api_key_hash','api_key_test_hash',
                'api_key_prefix','api_key_test_prefix',
                'api_key_last_used_at','api_key_last_used_ip',
            ]);
        });
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('session_data_enc');
        });
    }
};
