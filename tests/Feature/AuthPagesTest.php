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
