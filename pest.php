<?php

uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class,
)->in('tests/Feature');

uses(Tests\TestCase::class)->in('tests/Unit');

expect()->extend('toBeUuid', function () {
    return $this->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
});
