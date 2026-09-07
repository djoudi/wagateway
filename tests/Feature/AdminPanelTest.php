<?php

use App\Models\User;
use Filament\Panel;

test('admin email match ignores surrounding spaces and case', function () {
    config(['wagateway.admin_emails' => [' Admin@Example.com ', 'other@x.com']]);

    $admin = User::factory()->make(['email' => 'admin@example.com']);
    $other = User::factory()->make(['email' => 'user@example.com']);

    expect($admin->isAdmin())->toBeTrue()
        ->and($other->isAdmin())->toBeFalse();
});

test('canAccessPanel follows isAdmin', function () {
    config(['wagateway.admin_emails' => ['ops@wagateway.dz']]);

    $admin = User::factory()->make(['email' => 'ops@wagateway.dz']);
    $user = User::factory()->make(['email' => 'member@wagateway.dz']);
    $panel = Mockery::mock(Panel::class);

    expect($admin->canAccessPanel($panel))->toBeTrue()
        ->and($user->canAccessPanel($panel))->toBeFalse();
});

test('guest is redirected away from filament admin', function () {
    $this->get('/admin')->assertRedirect();
});

test('non-admin cannot open filament admin', function () {
    $user = User::factory()->create(['email' => 'member@example.com']);

    $response = $this->actingAs($user)->get('/admin');

    expect($response->isOk())->toBeFalse();
});
