@php $isAr = app()->getLocale() === 'ar'; @endphp
<x-layouts.auth :title="$isAr ? 'دخول الإدارة' : 'Admin login'">
    <h1>{{ $isAr ? 'دخول الإدارة' : 'Admin sign in' }}</h1>
    <p class="auth-lead">{{ $isAr ? 'أدخل بريد المشرف وكلمة المرور.' : 'Enter your admin email and password.' }}</p>

    @if ($errors->any())
        <div class="status status-error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="/admin/login" data-loading>
        @csrf

        <div class="field">
            <label for="email">{{ $isAr ? 'البريد الإلكتروني' : 'Email address' }}</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                   class="@error('email') is-invalid @enderror" />
            @error('email') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="field">
            <label for="password">{{ $isAr ? 'كلمة المرور' : 'Password' }}</label>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                   class="@error('password') is-invalid @enderror" />
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
</x-layouts.auth>
