<?php

use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Str;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function createPlan(array $attrs = []): Plan
{
    return Plan::create(array_merge([
        'name'                => 'Test Plan',
        'slug'                => 'test-' . Str::random(5),
        'daily_message_limit' => 1000,
        'max_devices'         => 5,
        'max_webhooks'        => 5,
        'max_templates'       => 20,
        'bulk_batch_limit'    => 100,
        'price_monthly'       => 500,
        'price_yearly'        => 4800,
        'features'            => json_encode(['webhooks','bulk_send','scheduling']),
        'is_active'           => true,
        'sort_order'          => 1,
    ], $attrs));
}

function createUser(array $attrs = []): User
{
    $plan = createPlan();
    return User::factory()->withApiKeys()->create(array_merge([
        'plan_id'      => $plan->id,
        'is_suspended' => false,
    ], $attrs));
}

// ─── Tests ────────────────────────────────────────────────────────────────────

test('health endpoint is public', function () {
    $this->getJson('/api/health')
         ->assertStatus(200)
         ->assertJsonPath('status', 'ok');
});

test('rejects requests with no API key', function () {
    $this->getJson('/api/v1/devices')
         ->assertStatus(401)
         ->assertJsonPath('error.code', 'MISSING_API_KEY');
});

test('rejects invalid API key', function () {
    $this->withToken('wg_live_invalid_key_here')
         ->getJson('/api/v1/devices')
         ->assertStatus(401)
         ->assertJsonPath('error.code', 'INVALID_API_KEY');
});

test('accepts valid live API key', function () {
    $user = createUser();
    $this->withToken($user->raw_api_key)
         ->getJson('/api/v1/devices')
         ->assertStatus(200)
         ->assertJsonPath('success', true);
});

test('accepts valid test API key', function () {
    $user = createUser();
    $this->withToken($user->raw_api_key_test)
         ->getJson('/api/v1/devices')
         ->assertStatus(200);
});

test('rejects suspended user', function () {
    $user = createUser(['is_suspended' => true, 'suspension_reason' => 'Abuse']);
    $this->withToken($user->raw_api_key)
         ->getJson('/api/v1/devices')
         ->assertStatus(403)
         ->assertJsonPath('error.code', 'ACCOUNT_SUSPENDED');
});

test('rejects expired subscription', function () {
    $user = createUser(['plan_expires_at' => now()->subDay()]);
    $this->withToken($user->raw_api_key)
         ->getJson('/api/v1/devices')
         ->assertStatus(402)
         ->assertJsonPath('error.code', 'SUBSCRIPTION_EXPIRED');
});
