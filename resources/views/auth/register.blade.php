@php $isAr = app()->getLocale() === 'ar'; @endphp
<x-layouts.auth :title="$isAr ? 'إنشاء حساب' : 'Register'">
    <h1>{{ $isAr ? 'ابدأ مجاناً' : 'Get started for free' }}</h1>
    <p class="auth-lead">{{ $isAr ? 'أنشئ حسابك خلال دقيقة وابدأ ربط رقم واتساب.' : 'Create your account in a minute and connect a WhatsApp number.' }}</p>

    @if ($errors->any())
        <div class="status status-error">{{ $errors->first() }}</div>
    @endif

    @if (request()->query('plan') === 'pro' || request()->query('plan') === 'business')
        <div class="banner">
            @if ($isAr)
                سننشئ حسابك الآن، ثم نوجّهك مباشرة لتفعيل خطة
                <strong>{{ request()->query('plan') === 'pro' ? 'Pro' : 'Business' }}</strong>
            @else
                We will create your account first, then take you to activate the
                <strong>{{ request()->query('plan') === 'pro' ? 'Pro' : 'Business' }}</strong> plan.
            @endif
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}" data-loading>
        @csrf
        @if (request()->query('plan'))
            <input type="hidden" name="plan" value="{{ request()->query('plan') }}" />
        @endif

        <div class="field">
            <label for="name">{{ $isAr ? 'الاسم الكامل' : 'Full name' }}</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
                   class="@error('name') is-invalid @enderror" />
            @error('name') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="field">
            <label for="email">{{ $isAr ? 'البريد الإلكتروني' : 'Email address' }}</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email"
                   class="@error('email') is-invalid @enderror" />
            @error('email') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="field">
            <label for="password">{{ $isAr ? 'كلمة المرور' : 'Password' }}</label>
            <div class="password-wrap">
                <input id="password" type="password" name="password" required autocomplete="new-password"
                       class="@error('password') is-invalid @enderror" />
                <button type="button" class="toggle-pass" data-toggle-password="password"
                        aria-label="{{ $isAr ? 'إظهار كلمة المرور' : 'Show password' }}" aria-pressed="false">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
            @error('password') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="field">
            <label for="password_confirmation">{{ $isAr ? 'تأكيد كلمة المرور' : 'Confirm password' }}</label>
            <div class="password-wrap">
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
                <button type="button" class="toggle-pass" data-toggle-password="password_confirmation"
                        aria-label="{{ $isAr ? 'إظهار كلمة المرور' : 'Show password' }}" aria-pressed="false">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
        </div>

        <button type="submit" class="btn-submit">
            <span class="spinner" aria-hidden="true"></span>
            {{ $isAr ? 'إنشاء الحساب' : 'Create account' }}
        </button>
    </form>

    <p class="auth-foot">
        {{ $isAr ? 'لديك حساب بالفعل؟' : 'Already have an account?' }}
        <a class="link" href="{{ route('login') }}">{{ $isAr ? 'تسجيل الدخول' : 'Sign in' }}</a>
    </p>
</x-layouts.auth>
