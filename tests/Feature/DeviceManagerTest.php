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
