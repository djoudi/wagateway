<?php

use App\Enums\DeviceStatus;
use App\Enums\MessageStatus;
use App\Models\Device;
use App\Models\Message;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Str;

function makeTestSetup(int $dailyLimit = 100): array
{
    $plan = Plan::create([
        'name' => 'Test', 'slug' => 'msg-test-' . Str::random(4),
        'daily_message_limit' => $dailyLimit,
        'max_devices' => 10, 'max_webhooks' => 10,
        'max_templates' => 50, 'bulk_batch_limit' => 500,
        'price_monthly' => 0, 'price_yearly' => 0,
        'features' => json_encode(['bulk_send','scheduling']),
        'is_active' => true, 'sort_order' => 0,
    ]);

    $user = User::factory()->withApiKeys()->create([
        'plan_id' => $plan->id, 'is_suspended' => false,
    ]);

    $device = Device::factory()->connected()->create([
        'user_id' => $user->id, 'name' => 'Test Device',
    ]);

    return [$user, $device, $plan];
}

// ─── Send text ────────────────────────────────────────────────────────────────

test('can send a text message on a connected device', function () {
    [$user, $device] = makeTestSetup();

    $this->mock(\App\Services\WhatsAppService::class)
         ->shouldReceive('sendText')->once()
         ->andReturn(['success' => true, 'id' => 'wa_msg_abc123']);

    $this->mock(\App\Services\WebhookService::class)
         ->shouldReceive('dispatch')->once();

    $this->withToken($user->raw_api_key)
         ->postJson('/api/v1/messages/send/text', [
             'device_id' => $device->uuid,
             'to'        => '213700000001',
             'body'      => 'Hello test!',
         ])
         ->assertStatus(201)
         ->assertJsonPath('success', true)
         ->assertJsonPath('data.status', MessageStatus::Sent->value);

    $this->assertDatabaseHas('messages', [
        'user_id'       => $user->id,
        'to_number'     => '213700000001',
        'wa_message_id' => 'wa_msg_abc123',
    ]);
});

test('cannot send to disconnected device', function () {
    [$user, $device] = makeTestSetup();
    $device->update(['status' => DeviceStatus::Disconnected]);

    $this->withToken($user->raw_api_key)
         ->postJson('/api/v1/messages/send/text', [
             'device_id' => $device->uuid,
             'to'        => '213700000001',
             'body'      => 'Hello!',
         ])
         ->assertStatus(422);
});

test('body is required for text messages', function () {
    [$user, $device] = makeTestSetup();

    $this->withToken($user->raw_api_key)
         ->postJson('/api/v1/messages/send/text', [
             'device_id' => $device->uuid,
             'to'        => '213700000001',
         ])
         ->assertStatus(422);
});

test('to number is required', function () {
    [$user, $device] = makeTestSetup();

    $this->withToken($user->raw_api_key)
         ->postJson('/api/v1/messages/send/text', [
             'device_id' => $device->uuid,
             'body'      => 'Hello!',
         ])
         ->assertStatus(422);
});

// ─── Rate limiting ────────────────────────────────────────────────────────────

test('rate limit enforced when daily limit reached', function () {
    [$user, $device] = makeTestSetup(dailyLimit: 2);

    // Simulate limit reached via Redis
    $key = "ratelimit:msg:{$user->id}:" . now()->toDateString();
    \Illuminate\Support\Facades\Redis::set($key, 2);

    $this->withToken($user->raw_api_key)
         ->postJson('/api/v1/messages/send/text', [
             'device_id' => $device->uuid,
             'to'        => '213700000001',
             'body'      => 'Blocked message',
         ])
         ->assertStatus(422);

    \Illuminate\Support\Facades\Redis::del($key);
});

// ─── List messages ────────────────────────────────────────────────────────────

test('can list messages with pagination', function () {
    [$user, $device] = makeTestSetup();
    Message::factory()->count(5)->create([
        'user_id' => $user->id, 'device_id' => $device->id,
    ]);

    $this->withToken($user->raw_api_key)
         ->getJson('/api/v1/messages')
         ->assertStatus(200)
         ->assertJsonPath('success', true)
         ->assertJsonCount(5, 'data')
         ->assertJsonStructure(['meta' => ['total','per_page','current_page','last_page']]);
});

test('can filter messages by status', function () {
    [$user, $device] = makeTestSetup();
    Message::factory()->create(['user_id' => $user->id, 'device_id' => $device->id, 'status' => MessageStatus::Sent]);
    Message::factory()->failed()->create(['user_id' => $user->id, 'device_id' => $device->id]);

    $this->withToken($user->raw_api_key)
         ->getJson('/api/v1/messages?status=failed')
         ->assertStatus(200)
         ->assertJsonCount(1, 'data');
});

test('user cannot see other users messages', function () {
    [$user, $device]   = makeTestSetup();
    [$other, $device2] = makeTestSetup();
    Message::factory()->count(3)->create(['user_id' => $other->id, 'device_id' => $device2->id]);

    $this->withToken($user->raw_api_key)
         ->getJson('/api/v1/messages')
         ->assertStatus(200)
         ->assertJsonCount(0, 'data');
});
