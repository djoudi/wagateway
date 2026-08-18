<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->extractToken($request);

        // ── 1. Token presence ────────────────────────────────────────────────
        if (! $token) {
            return $this->error(401, 'MISSING_API_KEY',
                'API key required. Pass it as: Authorization: Bearer wg_live_...');
        }

        // ── 2. Brute-force protection: 20 failed attempts / 10 min per IP ───
        $ipKey = 'api_auth_fail:' . $request->ip();
        if (RateLimiter::tooManyAttempts($ipKey, 20)) {
            $seconds = RateLimiter::availableIn($ipKey);
            return $this->error(429, 'TOO_MANY_ATTEMPTS',
                "Too many failed attempts. Try again in {$seconds} seconds.");
        }

        // ── 3. Token format validation (fast reject before DB hit) ───────────
        if (! preg_match('/^wg_(live|test)_[A-Za-z0-9]{40}$/', $token)) {
            RateLimiter::hit($ipKey, 600);
            return $this->error(401, 'INVALID_API_KEY', 'Malformed API key.');
        }

        // ── 4. Hash lookup — no raw keys in DB ───────────────────────────────
        $hash = hash('sha256', $token);
        $user = User::where('api_key_hash', $hash)
            ->orWhere('api_key_test_hash', $hash)
            ->first();

        if (! $user) {
            RateLimiter::hit($ipKey, 600);
            \App\Models\SecurityEvent::log('api_key_invalid', null, ['prefix' => substr($token, 0, 12)]);
            return $this->error(401, 'INVALID_API_KEY', 'API key not found or revoked.');
        }

        // ── 5. Clear failed attempts on success ───────────────────────────────
        RateLimiter::clear($ipKey);

        // ── 6. Account checks ─────────────────────────────────────────────────
        if ($user->is_suspended) {
            return $this->error(403, 'ACCOUNT_SUSPENDED',
                $user->suspension_reason ?? 'Account suspended. Contact support.');
        }

        if ($user->plan_expires_at && $user->plan_expires_at->isPast()) {
            $graceDays = (int) config('wagateway.subscription_grace_days', 3);
            $hardCutoff = $user->plan_expires_at->copy()->addDays($graceDays);

            if (now()->isAfter($hardCutoff)) {
                return $this->error(402, 'SUBSCRIPTION_EXPIRED',
                    'Your subscription has expired. Renew at ' . url('/dashboard/billing'));
            }
            // Within grace period: request proceeds, but flagged so the
            // response carries a renewal warning (see finally block below).
            $request->attributes->set('_grace_period_active', true);
        }

        // ── 7. Record usage (non-blocking) ───────────────────────────────────
        $user->touchApiKeyUsage();

        // ── 8. Inject context ────────────────────────────────────────────────
        $isTestKey = str_starts_with($token, 'wg_test_');
        $request->merge(['_is_test_key' => $isTestKey]);
        auth()->login($user);

        $response = $next($request);

        if ($request->attributes->get('_grace_period_active')) {
            $response->headers->set('X-WG-Subscription-Warning', 'expired-grace-period-active');
            $response->headers->set('X-WG-Renew-Url', url('/dashboard/billing'));
        }

        return $response;
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function extractToken(Request $request): ?string
    {
        // Priority 1: Authorization header (standard)
        if ($bearer = $request->bearerToken()) {
            return $bearer;
        }

        // Priority 2: ?api_key= query param (webhooks / testing only)
        // Only allowed in non-production for convenience
        if (! app()->isProduction() && $request->query('api_key')) {
            return $request->query('api_key');
        }

        return null;
    }

    private function error(int $status, string $code, string $message): Response
    {
        return response()->json([
            'success' => false,
            'error'   => ['code' => $code, 'message' => $message],
        ], $status);
    }
}
