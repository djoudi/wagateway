<?php

use App\Models\User;
use Filament\Panel;

test('admin email match ignores surrounding spaces and case', function () {
    config(['wagateway.admin_emails' => [' Admin@Example.com ', 'other@x.com']]);

    $admin = User::factory()->make(['email' => 'admin@example.com', 'is_admin' => false]);
    $other = User::factory()->make(['email' => 'user@example.com', 'is_admin' => false]);

    expect($admin->isAdmin())->toBeTrue()
        ->and($other->isAdmin())->toBeFalse();
});

test('is_admin column grants access without env list', function () {
    config(['wagateway.admin_emails' => []]);

    $admin = User::factory()->make(['email' => 'ops@wagateway.dz', 'is_admin' => true]);
    $user = User::factory()->make(['email' => 'member@wagateway.dz', 'is_admin' => false]);

    expect($admin->isAdmin())->toBeTrue()
        ->and($user->isAdmin())->toBeFalse();
});

test('canAccessPanel follows isAdmin', function () {
    config(['wagateway.admin_emails' => []]);

    $admin = User::factory()->make(['email' => 'ops@wagateway.dz', 'is_admin' => true]);
    $user = User::factory()->make(['email' => 'member@wagateway.dz', 'is_admin' => false]);
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

test('admin login form posts relatively with a hidden password field', function () {
    $html = $this->get('/admin/login')->assertOk()->getContent();

    expect($html)
        ->toContain('method="POST"')
        ->toContain('action="/admin/sign-in"')
        ->toContain('type="password"')
        ->not->toContain('wire:submit')
        ->not->toContain('x-bind:type');
});

test('admin can sign in via post using is_admin flag', function () {
    config(['wagateway.admin_emails' => []]);
    $admin = User::factory()->admin()->create(['email' => 'ops@example.com']);

    $this->post('/admin/sign-in', [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect('/admin');

    $this->assertAuthenticated();
});

test('admin login matches email case-insensitively', function () {
    config(['wagateway.admin_emails' => []]);
    User::factory()->admin()->create(['email' => 'admin@wagateway.dz']);

    $this->post('/admin/sign-in', [
        'email' => 'Admin@WaGateway.DZ',
        'password' => 'password',
    ])->assertRedirect('/admin');

    $this->assertAuthenticated();
});

test('non-admin cannot sign in via admin login', function () {
    $user = User::factory()->create(['email' => 'member@example.com']);

    $this->from('/admin/login')
        ->post('/admin/sign-in', [
            'email' => $user->email,
            'password' => 'password',
        ])
        ->assertRedirect('/admin/login')
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});
