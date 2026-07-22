<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Fake events + queue by default in tests
        \Illuminate\Support\Facades\Event::fake([
            \App\Events\QrCodeGenerated::class,
            \App\Events\DeviceStatusChanged::class,
        ]);
    }
}
