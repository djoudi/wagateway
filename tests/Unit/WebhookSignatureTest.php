<?php

use App\Services\WebhookService;

test('webhook signature uses sha256 hmac', function () {
    $secret  = 'my_secret';
    $payload = ['event' => 'test', 'data' => ['key' => 'value']];
    $sig     = WebhookService::sign($secret, $payload);

    expect($sig)->toStartWith('sha256=')
        ->and(strlen($sig))->toBeGreaterThan(10);
});

test('webhook signature is deterministic', function () {
    $secret  = 'my_secret';
    $payload = ['event' => 'test'];

    expect(WebhookService::sign($secret, $payload))
        ->toBe(WebhookService::sign($secret, $payload));
});

test('different secrets produce different signatures', function () {
    $payload = ['event' => 'test'];
    expect(WebhookService::sign('secret_a', $payload))
        ->not->toBe(WebhookService::sign('secret_b', $payload));
});
