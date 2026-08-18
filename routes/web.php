<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth routes — throttled against credential stuffing
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login',    fn () => view('auth.login'))->name('login');
    Route::post('/login',   [\App\Http\Controllers\Auth\LoginController::class, 'store'])
        ->middleware('throttle:auth')->name('login.store');

    Route::get('/register', fn () => view('auth.register'))->name('register');
    Route::post('/register',[\App\Http\Controllers\Auth\RegisterController::class, 'store'])
        ->middleware('throttle:auth')->name('register');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [\App\Http\Controllers\Auth\LoginController::class, 'destroy'])->name('logout');
});

/*
|--------------------------------------------------------------------------
| Broadcasting auth — required for private channels (Reverb)
|--------------------------------------------------------------------------
*/
\Illuminate\Support\Facades\Broadcast::routes(['middleware' => ['auth']]);

/*
|--------------------------------------------------------------------------
| Dashboard routes (Livewire pages)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->prefix('dashboard')->group(function () {
    Route::get('/',          fn () => view('dashboard'))->name('dashboard');
    Route::get('/devices',   fn () => view('devices'))->name('devices');
    Route::get('/messages',  fn () => view('messages'))->name('messages');
    Route::get('/bulk',      fn () => view('bulk'))->name('bulk');
    Route::get('/schedule',  fn () => view('schedule'))->name('schedule');
    Route::get('/webhooks',  fn () => view('webhooks'))->name('webhooks');
    Route::get('/templates', fn () => view('templates'))->name('templates');
    Route::get('/api-keys',  fn () => view('api-keys'))->name('api-keys');
    Route::get('/billing',   fn () => view('billing'))->name('billing');
    Route::get('/docs',      fn () => view('docs'))->name('docs');
});

/*
|--------------------------------------------------------------------------
| Billing — Chargily Pay checkout flow
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->prefix('billing')->group(function () {
    Route::get('/checkout/{plan}', [\App\Http\Controllers\BillingController::class, 'checkout'])
        ->name('billing.checkout');
    Route::get('/checkout/{invoice}/success', [\App\Http\Controllers\BillingController::class, 'checkoutSuccess'])
        ->name('billing.checkout.success');
    Route::get('/checkout/{invoice}/failure', [\App\Http\Controllers\BillingController::class, 'checkoutFailure'])
        ->name('billing.checkout.failure');
    Route::get('/invoices/{invoice}/download', [\App\Http\Controllers\BillingController::class, 'downloadInvoice'])
        ->name('billing.invoice.download');
});

// Public — hit by Chargily's servers, not the browser. Secured by HMAC
// signature verification inside the controller, not by route middleware.
Route::post('/webhooks/chargily', [\App\Http\Controllers\Webhooks\ChargilyWebhookController::class, 'handle'])
    ->name('webhooks.chargily');

/*
|--------------------------------------------------------------------------
| Legal documents — public, no auth required
|--------------------------------------------------------------------------
*/
Route::get('/legal/terms',          fn () => view('legal.terms'))->name('legal.terms');
Route::get('/legal/privacy',        fn () => view('legal.privacy'))->name('legal.privacy');
Route::get('/legal/acceptable-use', fn () => view('legal.acceptable-use'))->name('legal.aup');

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return view('welcome');
})->name('home');
