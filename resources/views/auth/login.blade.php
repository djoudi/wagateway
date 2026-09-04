@php $isAr = app()->getLocale() === 'ar'; @endphp
<x-layouts.auth :title="$isAr ? 'تسجيل الدخول' : 'Login'">
    <h1>{{ $isAr ? 'تسجيل الدخول' : 'Sign in to your account' }}</h1>
    <p class="auth-lead">{{ $isAr ? 'أدخل بريدك وكلمة المرور للمتابعة.' : 'Enter your email and password to continue.' }}</p>

    @if (session('status'))
        <div class="status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}" data-loading>
        @csrf

        <div class="field">
            <label for="email">{{ $isAr ? 'البريد الإلكتروني' : 'Email address' }}</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                   class="@error('email') is-invalid @enderror" />
            @error('email') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="field">
            <div class="row-between" style="margin-bottom:6px">
                <label for="password" style="margin:0">{{ $isAr ? 'كلمة المرور' : 'Password' }}</label>
                @if (Route::has('password.request'))
                    <a class="link" href="{{ route('password.request') }}">{{ $isAr ? 'نسيت كلمة المرور؟' : 'Forgot password?' }}</a>
                @endif
            </div>
            <div class="password-wrap">
                <input id="password" type="password" name="password" required autocomplete="current-password"
                       class="@error('password') is-invalid @enderror" />
                <button type="button" class="toggle-pass" data-toggle-password="password"
                        aria-label="{{ $isAr ? 'إظهار كلمة المرور' : 'Show password' }}" aria-pressed="false">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
            @error('password') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <label class="remember">
            <input type="checkbox" name="remember" id="remember" />
            {{ $isAr ? 'تذكرني' : 'Remember me' }}
        </label>

        <button type="submit" class="btn-submit">
            <span class="spinner" aria-hidden="true"></span>
            {{ $isAr ? 'دخول' : 'Sign in' }}
        </button>
    </form>

    @if (Route::has('register'))
        <p class="auth-foot">
            {{ $isAr ? 'ليس لديك حساب؟' : "Don't have an account?" }}
            <a class="link" href="{{ route('register') }}">{{ $isAr ? 'أنشئ حساباً' : 'Create one' }}</a>
        </p>
    @endif
</x-layouts.auth>
