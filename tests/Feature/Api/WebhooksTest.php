<?php

use App\Models\Plan;
use App\Models\User;
use App\Models\Webhook;
use App\Services\WebhookService;
use Illuminate\Support\Str;

function makeWhUser(int $maxWebhooks = 3): User
{
    $plan = Plan::create([
        'name' => 'Pro', 'slug' => 'wh-' . Str::random(4),
        'daily_message_limit' => 10000,
        'max_devices'   => 10,
        'max_webhooks'  => $maxWebhooks,
        'max_templates' => 50,
        'bulk_batch_limit' => 500,
        'price_monthly' => 1500, 'price_yearly' => 14400,
        'features' => json_encode(['webhooks']),
        'is_active' => true, 'sort_order' => 1,
    ]);

    return User::factory()->withApiKeys()->create(['plan_id' => $plan->id]);
}

// ─── CRUD ─────────────────────────────────────────────────────────────────────

test('user can create a webhook', function () {
    $user = makeWhUser();

    $this->withToken($user->raw_api_key)
         ->postJson('/api/v1/webhooks', [
             'name'   => 'My CRM',
             'url'    => 'https://myapp.com/webhook/wa',
             'events' => ['message.received', 'device.connected'],
         ])
         ->assertStatus(201)
         ->assertJsonPath('data.name', 'My CRM');

    $this->assertDatabaseHas('webhooks', ['user_id' => $user->id, 'name' => 'My CRM']);
});

test('webhook url must be valid', function () {
    $user = makeWhUser();

    $this->withToken($user->raw_api_key)
         ->postJson('/api/v1/webhooks', [
             'name' => 'Bad', 'url' => 'not-a-url', 'events' => ['message.received'],
         ])
         ->assertStatus(422);
});

test('events must be from allowed list', function () {
    $user = makeWhUser();

    $this->withToken($user->raw_api_key)
         ->postJson('/api/v1/webhooks', [
             'name' => 'W', 'url' => 'https://app.com/hook', 'events' => ['invalid.event'],
         ])
         ->assertStatus(422);
});

test('cannot exceed plan webhook limit', function () {
    $user = makeWhUser(2);
    Webhook::factory()->count(2)->create(['user_id' => $user->id]);

    $this->withToken($user->raw_api_key)
         ->postJson('/api/v1/webhooks', [
             'name' => 'Extra', 'url' => 'https://app.com/hook3', 'events' => ['message.received'],
         ])
         ->assertStatus(403)
         ->assertJsonPath('error.code', 'WEBHOOK_LIMIT_REACHED');
});

test('user can update their webhook', function () {
    $user    = makeWhUser();
    $webhook = Webhook::factory()->create(['user_id' => $user->id]);

    $this->withToken($user->raw_api_key)
         ->patchJson("/api/v1/webhooks/{$webhook->uuid}", ['is_active' => false])
         ->assertStatus(200)
         ->assertJsonPath('data.is_active', false);
});

test('user can delete their webhook', function () {
    $user    = makeWhUser();
    $webhook = Webhook::factory()->create(['user_id' => $user->id]);

    $this->withToken($user->raw_api_key)
         ->deleteJson("/api/v1/webhooks/{$webhook->uuid}")
         ->assertStatus(200);

    $this->assertDatabaseMissing('webhooks', ['id' => $webhook->id]);
});

test('user cannot access another users webhook', function () {
    $user1   = makeWhUser();
    $user2   = makeWhUser();
    $webhook = Webhook::factory()->create(['user_id' => $user2->id]);

    $this->withToken($user1->raw_api_key)
         ->deleteJson("/api/v1/webhooks/{$webhook->uuid}")
         ->assertStatus(403);
});

// ─── Signature ────────────────────────────────────────────────────────────────

test('webhook HMAC signature is correctly computed', function () {
    $secret  = 'test_webhook_secret_key';
    $payload = ['event' => 'message.received', 'data' => ['from' => '213700000001']];

    $signature = WebhookService::sign($secret, $payload);

    expect($signature)->toStartWith('sha256=');

    $expected = 'sha256=' . hash_hmac('sha256', json_encode($payload), $secret);
    expect($signature)->toBe($expected);
});
