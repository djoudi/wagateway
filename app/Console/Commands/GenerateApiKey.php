<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateApiKey extends Command
{
    protected $signature   = 'user:generate-api-key {email}';
    protected $description = 'Generate or regenerate API keys for a user';

    public function handle(): void
    {
        $user = User::where('email', $this->argument('email'))->firstOrFail();

        $user->update([
            'api_key'      => 'wg_live_' . Str::random(40),
            'api_key_test' => 'wg_test_' . Str::random(40),
        ]);

        $this->info("Live key: {$user->api_key}");
        $this->info("Test key: {$user->api_key_test}");
    }
}
