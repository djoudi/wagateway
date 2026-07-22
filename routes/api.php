<?php

use App\Http\Controllers\Api\V1\BulkController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\MessageController;
use App\Http\Controllers\Api\V1\WebhookController;
use App\Http\Controllers\Api\V1\TemplateController;
use App\Http\Controllers\Api\V1\ScheduleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/
Route::get('/health', fn () => response()->json([
    'status'    => 'ok',
    'version'   => '1.0.0',
    'timestamp' => now()->toISOString(),
]));

/*
|--------------------------------------------------------------------------
| Authenticated API  v1
|--------------------------------------------------------------------------
*/
Route::prefix('v1')
    ->middleware(['api.key', 'throttle:300,1']) // 300 req/min hard cap
    ->group(function () {

        // ── Devices ─────────────────────────────────────────────────────────
        Route::prefix('devices')->group(function () {
            Route::get('/',              [DeviceController::class, 'index']);
            Route::post('/',             [DeviceController::class, 'store']);
            Route::get('/{device}',      [DeviceController::class, 'show']);
            Route::get('/{device}/qr',   [DeviceController::class, 'qr']);
            Route::post('/{device}/reconnect', [DeviceController::class, 'reconnect']);
            Route::delete('/{device}',   [DeviceController::class, 'destroy']);
        });

        // ── Messaging ────────────────────────────────────────────────────────
        Route::prefix('messages')->group(function () {
            Route::get('/',                  [MessageController::class, 'index']);
            Route::get('/{message}',         [MessageController::class, 'show']);

            Route::post('/send/text',        [MessageController::class, 'sendText']);
            Route::post('/send/image',       [MessageController::class, 'sendImage']);
            Route::post('/send/document',    [MessageController::class, 'sendDocument']);
            Route::post('/send/location',    [MessageController::class, 'sendLocation']);
            Route::post('/send/audio',       [MessageController::class, 'sendAudio']);
            Route::post('/send/video',       [MessageController::class, 'sendVideo']);
            Route::post('/send/contact',     [MessageController::class, 'sendContact']);

            // Bulk — requires plan feature
            Route::middleware('feature:bulk_send')->group(function () {
                Route::post('/bulk',         [BulkController::class, 'send']);
                Route::get('/bulk/{bulkJob}',[BulkController::class, 'status']);
                Route::delete('/bulk/{bulkJob}', [BulkController::class, 'cancel']);
            });

            // Scheduling — requires plan feature
            Route::middleware('feature:scheduling')->group(function () {
                Route::post('/schedule',     [ScheduleController::class, 'store']);
                Route::get('/schedule',      [ScheduleController::class, 'index']);
                Route::delete('/schedule/{scheduled}', [ScheduleController::class, 'destroy']);
            });
        });

        // ── Webhooks ─────────────────────────────────────────────────────────
        Route::apiResource('webhooks', WebhookController::class)
            ->parameters(['webhooks' => 'webhook']);

        // ── Templates ────────────────────────────────────────────────────────
        Route::apiResource('templates', TemplateController::class);
    });
