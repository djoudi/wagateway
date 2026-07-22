<?php

use App\Enums\DeviceStatus;
use App\Models\Device;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Str;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function makePlan(int $maxDevices = 3): Plan
{
    return Plan::create([
        'name' => 'Pro', 'slug' => 'pro-' . Str::random(4),
        'daily_message_limit' => 10000,
        'max_devices'   => $maxDevices,
        'max_webhooks'  => 10,
        'max_templates' => 50,
        'bulk_batch_limit' => 1000,
        'price_monthly' => 1500,
        'price_yearly'  => 14400,
        'features'      => json_encode(['bulk_send','scheduling']),
        'is_active'     => true,
        'sort_order'    => 1,
    ]);
}

function makeApiUser(int $maxDevices = 3): User
{
    $plan = makePlan($maxDevices);
    return User::factory()->withApiKeys()->create([
        'plan_id'      => $plan->id,
        'is_suspended' => false,
    ]);
}

// ─── List ─────────────────────────────────────────────────────────────────────

test('user can list their devices', function () {
    $user = makeApiUser();
    Device::factory()->count(3)->create(['user_id' => $user->id]);

    $this->withToken($user->raw_api_key)
         ->getJson('/api/v1/devices')
         ->assertStatus(200)
         ->assertJsonPath('success', true)
         ->assertJsonCount(3, 'data');
});

test('user cannot see other users devices', function () {
    $user  = makeApiUser();
    $other = makeApiUser();
    Device::factory()->count(2)->create(['user_id' => $other->id]);

    $this->withToken($user->raw_api_key)
         ->getJson('/api/v1/devices')
         ->assertStatus(200)
         ->assertJsonCount(0, 'data');
});

// ─── Create ───────────────────────────────────────────────────────────────────

test('user can create a device within plan limit', function () {
    $user = makeApiUser(3);

    $this->mock(\App\Services\WhatsAppService::class)
         ->shouldReceive('startSession')->once()
         ->andReturn(['success' => true]);

    $this->withToken($user->raw_api_key)
         ->postJson('/api/v1/devices', ['name' => 'Marketing'])
         ->assertStatus(201)
         ->assertJsonPath('data.name', 'Marketing')
         ->assertJsonPath('data.status', DeviceStatus::Connecting->value);

    $this->assertDatabaseHas('devices', ['user_id' => $user->id, 'name' => 'Marketing']);
});

test('cannot exceed plan device limit', function () {
    $user = makeApiUser(2);
    Device::factory()->count(2)->create(['user_id' => $user->id]);

    $this->withToken($user->raw_api_key)
         ->postJson('/api/v1/devices', ['name' => 'Extra'])
         ->assertStatus(403)
         ->assertJsonPath('error.code', 'DEVICE_LIMIT_REACHED');
});

test('device name is required', function () {
    $user = makeApiUser();

    $this->withToken($user->raw_api_key)
         ->postJson('/api/v1/devices', ['name' => ''])
         ->assertStatus(422);
});

// ─── Delete ───────────────────────────────────────────────────────────────────

test('user can delete their own device', function () {
    $user   = makeApiUser();
    $device = Device::factory()->create(['user_id' => $user->id]);

    $this->mock(\App\Services\WhatsAppService::class)
         ->shouldReceive('terminateSession')->once()
         ->andReturn(['success' => true]);

    $this->withToken($user->raw_api_key)
         ->deleteJson("/api/v1/devices/{$device->uuid}")
         ->assertStatus(200)
         ->assertJsonPath('success', true);

    $this->assertSoftDeleted('devices', ['id' => $device->id]);
});

test('user cannot delete another users device', function () {
    $user   = makeApiUser();
    $other  = makeApiUser();
    $device = Device::factory()->create(['user_id' => $other->id]);

    $this->withToken($user->raw_api_key)
         ->deleteJson("/api/v1/devices/{$device->uuid}")
         ->assertStatus(403);
});
