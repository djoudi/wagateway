<?php

use App\Models\User;

function dashboardUser(): User
{
    return User::factory()->create();
}

test('dashboard is arabic rtl when accept-language is ar', function () {
    $this->actingAs(dashboardUser())
        ->withHeaders(['Accept-Language' => 'ar-DZ,ar;q=0.9'])
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('lang="ar"', false)
        ->assertSee('dir="rtl"', false)
        ->assertSee('لوحة التحكم')
        ->assertSee('الأجهزة')
        ->assertDontSee('>Dashboard</', false);
});

test('dashboard is english ltr when accept-language is en', function () {
    $this->actingAs(dashboardUser())
        ->withHeaders(['Accept-Language' => 'en-US,en;q=0.9'])
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('lang="en"', false)
        ->assertSee('dir="ltr"', false)
        ->assertSee('Dashboard')
        ->assertSee('Devices')
        ->assertDontSee('لوحة التحكم');
});

test('dashboard shell has a mobile drawer hamburger', function () {
    $html = $this->actingAs(dashboardUser())
        ->get('/dashboard')
        ->assertOk()
        ->getContent();

    expect($html)
        ->toContain('aria-controls="app-sidebar"')
        ->toContain('aria-expanded');
});

test('dashboard shell does not use old brand tokens or dead chrome', function () {
    $html = $this->actingAs(dashboardUser())
        ->get('/dashboard')
        ->assertOk()
        ->getContent();

    expect($html)->not->toContain('family=Inter')
        ->and($html)->not->toContain('#25D366')
        ->and($html)->not->toContain('ti-bell')
        ->and($html)->not->toContain('placeholder="Search');
});
