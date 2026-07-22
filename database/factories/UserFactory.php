<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name'              => fake()->name(),
            'email'             => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => static::$password ??= Hash::make('password'),
            'remember_token'    => Str::random(10),
            'is_suspended'      => false,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    public function suspended(string $reason = 'Policy violation'): static
    {
        return $this->state(fn () => ['is_suspended' => true, 'suspension_reason' => $reason]);
    }

    /**
     * Attach hashed API keys and expose raw keys via a transient attribute
     * accessible in tests as $user->raw_api_key / $user->raw_api_key_test.
     */
    public function withApiKeys(): static
    {
        return $this->afterMaking(function (User $user) {
            $liveRaw = 'wg_live_' . Str::random(40);
            $testRaw = 'wg_test_' . Str::random(40);

            $user->api_key_hash        = hash('sha256', $liveRaw);
            $user->api_key_prefix      = substr($liveRaw, 0, 12);
            $user->api_key_test_hash   = hash('sha256', $testRaw);
            $user->api_key_test_prefix = substr($testRaw, 0, 12);

            // Stash raw keys for test assertions (not persisted)
            $user->setAttribute('raw_api_key', $liveRaw);
            $user->setAttribute('raw_api_key_test', $testRaw);
        })->afterCreating(function (User $user) {
            // afterMaking doesn't persist — re-save to ensure hash columns are stored
            $user->saveQuietly();
        });
    }

    public function expired(): static
    {
        return $this->state(fn () => ['plan_expires_at' => now()->subDay()]);
    }
}
