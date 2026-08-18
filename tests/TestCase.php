<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Reset Redis state so rate-limiter / cache counters never leak
        // between tests (tests run against a real local Redis).
        \Illuminate\Support\Facades\Redis::flushdb();

        // Fake events + queue by default in tests
        \Illuminate\Support\Facades\Event::fake([
            \App\Events\QrCodeGenerated::class,
            \App\Events\DeviceStatusChanged::class,
        ]);
    }
}
