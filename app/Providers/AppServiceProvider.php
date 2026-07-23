<?php

namespace App\Providers;

use App\Models\BulkJob;
use App\Models\Device;
use App\Models\Message;
use App\Models\Webhook;
use App\Policies\BulkJobPolicy;
use App\Policies\DevicePolicy;
use App\Policies\MessagePolicy;
use App\Policies\WebhookPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // ── Authorization policies ──────────────────────────────────────────
        Gate::policy(Device::class,  DevicePolicy::class);
        Gate::policy(Message::class, MessagePolicy::class);
        Gate::policy(Webhook::class, WebhookPolicy::class);
        Gate::policy(BulkJob::class, BulkJobPolicy::class);

        // ── Named rate limiters ──────────────────────────────────────────────
        // General API throttle — keyed by authenticated user or IP
        RateLimiter::for('api', function ($request) {
            return Limit::perMinute(300)->by($request->user()?->id ?: $request->ip());
        });

        // Stricter limiter for unauthenticated auth endpoints
        RateLimiter::for('auth', function ($request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // Force HTTPS URL generation in production
        if ($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
