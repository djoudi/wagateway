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
        $middleware->statefulApi();

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
