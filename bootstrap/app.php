<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/internal.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'api.key' => \App\Http\Middleware\ApiKeyAuthenticate::class,
            'feature' => \App\Http\Middleware\EnsurePlanFeature::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\SetLocaleFromAcceptLanguage::class,
        ]);
        $middleware->statefulApi();

        // Trust proxy headers (X-Forwarded-Proto, -Host, -Port) from the
        // internal Docker network. Required for correct HTTPS URL
        // generation and secure cookies when running behind nginx alone
        // (standalone deploy) or nginx + Coolify's Traefik (Coolify deploy)
        // — without this, Laravel never sees the request as HTTPS even
        // though the public-facing connection is, and secure cookies won't
        // be sent by the browser at all.
        $middleware->trustProxies(
            at: ['172.16.0.0/12', '10.0.0.0/8', '192.168.0.0/16'],
            headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR
                   | \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST
                   | \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT
                   | \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO,
        );

        // These endpoints receive POSTs from non-browser clients (the Node.js
        // WA service, and Chargily's payment gateway) authenticated by their
        // own signed secrets — not by a Laravel session — so they cannot
        // carry a CSRF token and must be exempted rather than rejected.
        $middleware->validateCsrfTokens(except: [
            'internal/wa-events',
            'webhooks/chargily',
        ]);

        // Security headers on every response
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json(['success' => false, 'error' => ['code' => 'NOT_FOUND', 'message' => 'Resource not found.']], 404);
            }
        });
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json(['success' => false, 'error' => $e->errors()], 422);
            }
        });
        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json(['success' => false, 'error' => ['code' => 'FORBIDDEN', 'message' => 'You do not own this resource.']], 403);
            }
        });
    })->create();
