<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature');

pest()->extend(TestCase::class)->in('Unit');

expect()->extend('toBeUuid', function () {
    return $this->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
});
