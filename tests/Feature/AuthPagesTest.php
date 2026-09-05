<?php

test('register page is arabic rtl when accept-language is ar', function () {
    $this->withHeaders(['Accept-Language' => 'ar-DZ,ar;q=0.9'])
        ->get('/register')
        ->assertOk()
        ->assertSee('lang="ar"', false)
        ->assertSee('dir="rtl"', false)
        ->assertSee('إنشاء الحساب')
        ->assertSee('الاسم الكامل')
        ->assertDontSee('Get started for free');
});

test('register page is english ltr when accept-language is en', function () {
    $this->withHeaders(['Accept-Language' => 'en-US,en;q=0.9'])
        ->get('/register')
        ->assertOk()
        ->assertSee('lang="en"', false)
        ->assertSee('dir="ltr"', false)
        ->assertSee('Create account')
        ->assertSee('Full name')
        ->assertDontSee('إنشاء الحساب');
});

test('login page follows accept-language', function () {
    $this->withHeaders(['Accept-Language' => 'ar'])
        ->get('/login')
        ->assertOk()
        ->assertSee('تسجيل الدخول')
        ->assertSee('تذكرني');

    $this->withHeaders(['Accept-Language' => 'en'])
        ->get('/login')
        ->assertOk()
        ->assertSee('Sign in')
        ->assertSee('Remember me');
});

test('register page keeps plan hint', function () {
    $this->withHeaders(['Accept-Language' => 'en'])
        ->get('/register?plan=pro')
        ->assertOk()
        ->assertSee('name="plan"', false)
        ->assertSee('value="pro"', false)
        ->assertSee('Pro');
});

test('session cookie path is url root even when SESSION_PATH is a filesystem directory', function () {
    expect(config('session.path'))->toBe('/');
});

test('register shows validation errors instead of a silent refresh', function () {
    $this->from('/register')
        ->withHeaders(['Accept-Language' => 'en'])
        ->post('/register', [
            'name' => '',
            'email' => 'not-an-email',
            'password' => 'short',
            'password_confirmation' => 'different',
        ])
        ->assertRedirect('/register')
        ->assertSessionHasErrors(['name', 'email', 'password']);
});

test('login shows errors instead of a silent refresh', function () {
    $this->from('/login')
        ->post('/login', [
            'email' => 'nobody@example.com',
            'password' => 'wrong-password',
        ])
        ->assertRedirect('/login')
        ->assertSessionHasErrors('email');
});

test('register with valid data authenticates and redirects to dashboard', function () {
    $this->post('/register', [
        'name' => 'New User',
        'email' => 'new@example.com',
        'password' => 'SecurePass1',
        'password_confirmation' => 'SecurePass1',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticated();
    expect(\App\Models\User::query()->where('email', 'new@example.com')->exists())->toBeTrue();
});

test('login with valid credentials redirects to dashboard', function () {
    $user = \App\Models\User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticated();
});

test('unverified user can open the dashboard', function () {
    $user = \App\Models\User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk();
});
