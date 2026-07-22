<?php

use App\Http\Controllers\Internal\WaEventsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Internal routes — accessible only within Docker network
| Authenticated by X-WG-Secret header, never exposed publicly
|--------------------------------------------------------------------------
*/
Route::prefix('internal')
    ->middleware('throttle:1000,1')
    ->group(function () {
        Route::post('/wa-events', [WaEventsController::class, 'handle'])
            ->name('internal.wa-events');
    });
