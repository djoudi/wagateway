<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // ── Brute-force: 5 attempts per email+IP per minute ──────────────────
        $key = 'login:' . Str::lower($request->email) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => "Too many login attempts. Try again in {$seconds} seconds.",
            ]);
        }

        if (! Auth::attempt($request->only('email','password'), $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);

            \App\Models\SecurityEvent::log('login_failed', null, ['email' => $request->email]);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        // ── Success ───────────────────────────────────────────────────────────
        RateLimiter::clear($key);
        $request->session()->regenerate();

        \App\Models\SecurityEvent::log('login_success', Auth::id());

        // Generate API keys if first login
        $user = Auth::user();
        if (! $user->api_key_hash) {
            $user->generateApiKeys();
            // Keys are shown on the API Keys page — user is directed there
        }

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
