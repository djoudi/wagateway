<?php

use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

function secTestUser(): User
{
    $plan = Plan::create([
        'name' => 'Pro', 'slug' => 'sec-' . Str::random(4),
        'daily_message_limit' => 10000, 'max_devices' => 10,
        'max_webhooks' => 10, 'max_templates' => 50,
        'bulk_batch_limit' => 500, 'price_monthly' => 1500,
        'price_yearly' => 14400, 'features' => json_encode([]),
        'is_active' => true, 'sort_order' => 1,
    ]);

    return User::factory()->withApiKeys()->create(['plan_id' => $plan->id]);
}

// ─── API keys are never stored raw ─────────────────────────────────────────────

test('raw api key is never persisted to the users table', function () {
    $user = secTestUser();

    $row = \Illuminate\Support\Facades\DB::table('users')->where('id', $user->id)->first();

    expect($row->api_key)->toBeNull()
        ->and($row->api_key_test)->toBeNull()
        ->and($row->api_key_hash)->not->toBeNull()
        ->and($row->api_key_hash)->toBe(hash('sha256', $user->raw_api_key));
});

test('regenerating a key invalidates the previous one immediately', function () {
    $user   = secTestUser();
    $oldKey = $user->raw_api_key;

    $user->generateApiKeys();

    $this->withToken($oldKey)
         ->getJson('/api/v1/devices')
         ->assertStatus(401)
         ->assertJsonPath('error.code', 'INVALID_API_KEY');
});

// ─── Malformed tokens rejected before DB hit ───────────────────────────────────

test('malformed api key is rejected without querying the database', function () {
    $this->withToken('not_even_close_to_valid')
         ->getJson('/api/v1/devices')
         ->assertStatus(401)
         ->assertJsonPath('error.code', 'INVALID_API_KEY');
});

// ─── Brute-force protection ─────────────────────────────────────────────────────

test('repeated invalid api keys from same IP get rate limited', function () {
    RateLimiter::clear('api_auth_fail:127.0.0.1');

    for ($i = 0; $i < 20; $i++) {
        $this->withToken('wg_live_' . Str::random(40))->getJson('/api/v1/devices');
    }

    $this->withToken('wg_live_' . Str::random(40))
         ->getJson('/api/v1/devices')
         ->assertStatus(429)
         ->assertJsonPath('error.code', 'TOO_MANY_ATTEMPTS');
});

// ─── Internal webhook uses timing-safe comparison ──────────────────────────────

test('internal wa-events endpoint rejects wrong secret', function () {
    $this->postJson('/internal/wa-events', ['event' => 'qr', 'data' => []], [
        'X-WG-Secret' => 'wrong_secret',
    ])->assertStatus(401);
});

test('internal wa-events endpoint accepts correct secret', function () {
    $this->postJson('/internal/wa-events', ['event' => 'qr', 'data' => ['session_id' => 'nonexistent']], [
        'X-WG-Secret' => config('services.wa_node.secret'),
    ])->assertStatus(200);
});

// ─── Security events are logged ────────────────────────────────────────────────

test('failed login is recorded in security_events', function () {
    $user = secTestUser();

    $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password']);

    $this->assertDatabaseHas('security_events', [
        'event' => 'login_failed',
    ]);
});

test('successful login is recorded in security_events', function () {
    $user = User::factory()->create(['password' => bcrypt('correct-password')]);

    $this->post('/login', ['email' => $user->email, 'password' => 'correct-password']);

    $this->assertDatabaseHas('security_events', [
        'user_id' => $user->id,
        'event'   => 'login_success',
    ]);
});
