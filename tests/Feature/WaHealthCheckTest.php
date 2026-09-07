<?php

use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Http;

test('ping sends shared secret to wa health endpoint', function () {
    config([
        'services.wa_node.url' => 'http://127.0.0.1:3000',
        'services.wa_node.secret' => 'test_secret_key_32chars_minimum',
    ]);

    Http::fake([
        'http://127.0.0.1:3000/health' => Http::response(['status' => 'ok'], 200),
    ]);

    expect(app(WhatsAppService::class)->ping())->toBeTrue();

    Http::assertSent(fn ($request) => $request->url() === 'http://127.0.0.1:3000/health'
        && $request->hasHeader('X-WG-Secret', 'test_secret_key_32chars_minimum'));
});

test('ping is false when wa health returns unauthorized', function () {
    config([
        'services.wa_node.url' => 'http://127.0.0.1:3000',
        'services.wa_node.secret' => 'test_secret_key_32chars_minimum',
    ]);

    Http::fake([
        'http://127.0.0.1:3000/health' => Http::response(['success' => false], 401),
    ]);

    expect(app(WhatsAppService::class)->ping())->toBeFalse();
});
