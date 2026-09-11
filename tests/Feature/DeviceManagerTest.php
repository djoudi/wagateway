<?php

use App\Livewire\Devices\DeviceManager;
use App\Models\Device;
use App\Models\Plan;
use App\Models\User;
use App\Services\WhatsAppService;
use Livewire\Livewire;

function devicePageUser(): User
{
    $plan = Plan::query()->first() ?? Plan::create([
        'name' => 'Starter',
        'slug' => 'starter-test',
        'daily_message_limit' => 1000,
        'max_devices' => 2,
        'max_webhooks' => 3,
        'max_templates' => 10,
        'bulk_batch_limit' => 100,
        'price_monthly' => 500,
        'price_yearly' => 4800,
        'features' => ['webhooks'],
        'is_active' => true,
        'sort_order' => 1,
    ]);

    return User::factory()->create(['plan_id' => $plan->id]);
}

test('devices page shows add first device controls', function () {
    $html = $this->actingAs(devicePageUser())
        ->get('/devices')
        ->assertOk()
        ->getContent();

    expect($html)
        ->toContain('wire:click.prevent="openAddModal"')
        ->toContain('add=1');
});

test('devices page opens add modal from query string', function () {
    $html = $this->actingAs(devicePageUser())
        ->get('/devices?add=1')
        ->assertOk()
        ->getContent();

    expect($html)->toContain('إضافة جهاز جديد')->or->toContain('Add new device');
});

test('openAddModal sets showAddModal true', function () {
    Livewire::actingAs(devicePageUser())
        ->test(DeviceManager::class)
        ->call('openAddModal')
        ->assertSet('showAddModal', true);
});

test('createDevice stores a device and opens the qr modal', function () {
    $user = devicePageUser();

    $this->mock(WhatsAppService::class, function ($mock) {
        $mock->shouldReceive('startSession')->once()->andReturn(['qr' => 'data:image/png;base64,abc']);
    });

    Livewire::actingAs($user)
        ->test(DeviceManager::class)
        ->set('newDeviceName', 'Support line')
        ->call('createDevice')
        ->assertSet('showAddModal', false)
        ->assertSet('showQrModal', true)
        ->assertSet('qrDeviceName', 'Support line');

    expect(Device::where('user_id', $user->id)->where('name', 'Support line')->exists())->toBeTrue();
});

test('createDevice opens the qr modal in loading state when startSession has no qr', function () {
    $user = devicePageUser();

    $this->mock(WhatsAppService::class, function ($mock) {
        $mock->shouldReceive('startSession')->once()->andReturn(['success' => true]);
    });

    Livewire::actingAs($user)
        ->test(DeviceManager::class)
        ->set('newDeviceName', 'Sales line')
        ->call('createDevice')
        ->assertSet('showQrModal', true)
        ->assertSet('qrStatus', 'loading')
        ->assertSet('qrCode', null);
});

test('pollQrFromDevice shows qr stored on the device without echo', function () {
    $user = devicePageUser();
    $device = Device::factory()->create([
        'user_id' => $user->id,
        'name' => 'Sales line',
        'status' => 'connecting',
        'qr_code' => 'data:image/png;base64,polled',
        'qr_expires_at' => now()->addSeconds(45),
    ]);

    Livewire::actingAs($user)
        ->test(DeviceManager::class)
        ->set('showQrModal', true)
        ->set('qrDeviceId', $device->uuid)
        ->set('qrDeviceName', $device->name)
        ->set('qrStatus', 'loading')
        ->call('pollQrFromDevice')
        ->assertSet('qrCode', 'data:image/png;base64,polled')
        ->assertSet('qrStatus', 'waiting');
});

test('pollQrFromDevice fetches qr from the wa service when the device has none', function () {
    $user = devicePageUser();
    $device = Device::factory()->create([
        'user_id' => $user->id,
        'status' => 'connecting',
        'qr_code' => null,
        'qr_expires_at' => null,
    ]);

    $this->mock(WhatsAppService::class, function ($mock) {
        $mock->shouldReceive('getQrCode')->once()->andReturn([
            'success' => true,
            'qr' => 'data:image/png;base64,from-node',
        ]);
    });

    Livewire::actingAs($user)
        ->test(DeviceManager::class)
        ->set('showQrModal', true)
        ->set('qrDeviceId', $device->uuid)
        ->set('qrStatus', 'loading')
        ->call('pollQrFromDevice')
        ->assertSet('qrCode', 'data:image/png;base64,from-node')
        ->assertSet('qrStatus', 'waiting');
});

test('pollQrFromDevice marks the modal connected when the device is ready', function () {
    $user = devicePageUser();
    $device = Device::factory()->create([
        'user_id' => $user->id,
        'status' => 'connected',
    ]);

    Livewire::actingAs($user)
        ->test(DeviceManager::class)
        ->set('showQrModal', true)
        ->set('qrDeviceId', $device->uuid)
        ->set('qrStatus', 'waiting')
        ->set('qrCode', 'data:image/png;base64,old')
        ->call('pollQrFromDevice')
        ->assertSet('qrStatus', 'connected');
});
