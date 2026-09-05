@props(['title'])
@php
    $isAr = app()->getLocale() === 'ar';
    $dir  = $isAr ? 'rtl' : 'ltr';
    $lang = $isAr ? 'ar' : 'en';
@endphp
<!DOCTYPE html>
<html lang="{{ $lang }}" dir="{{ $dir }}">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ $title }} — {{ config('app.name', 'WaGateway') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@700;800;900&family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #0B1512;
            --ink-soft: #132019;
            --ink-line: #223028;
            --paper: #F1F3EE;
            --card: #FFFFFF;
            --line: #DDE3DC;
            --signal: #2FA66B;
            --signal-deep: #1B7A4D;
            --signal-dim: #E7F3EC;
            --amber: #E2A63D;
            --text: #16211C;
            --muted: #5B6660;
            --danger: #B42318;
            --danger-dim: #FDECEC;
            --f-display: 'Cairo', 'IBM Plex Sans Arabic', sans-serif;
            --f-body: 'IBM Plex Sans Arabic', 'Cairo', sans-serif;
            --f-mono: 'IBM Plex Mono', ui-monospace, monospace;
            --radius: 14px;
        }

        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; min-height: 100%; }
        body {
            font-family: var(--f-body);
            background: var(--paper);
            color: var(--text);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }
        a { color: inherit; text-decoration: none; }
        a:focus-visible, button:focus-visible, input:focus-visible {
            outline: 2.5px solid var(--signal);
            outline-offset: 3px;
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.001ms !important;
                transition-duration: 0.001ms !important;
            }
        }

        .auth-shell {
            min-height: 100dvh;
            display: grid;
            grid-template-columns: 1fr minmax(280px, 46%);
        }

        .auth-brand {
            background: var(--ink);
            color: #E9EEE9;
            padding: 48px 44px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }
        .auth-brand::after {
            content: "";
            position: absolute;
            width: 420px;
            height: 420px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(47,166,107,0.22), transparent 68%);
            inset-inline-end: -140px;
            bottom: -160px;
            pointer-events: none;
        }
        .brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-family: var(--f-display);
            font-weight: 800;
            font-size: 20px;
            color: #fff;
            position: relative;
            z-index: 1;
            width: fit-content;
        }
        .brand .mark { width: 32px; height: 32px; flex: none; }
        .brand-copy { position: relative; z-index: 1; margin-top: 48px; }
        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: var(--f-mono);
            font-size: 12.5px;
            letter-spacing: .04em;
            color: #9FE8C1;
            background: rgba(47,166,107,0.12);
            border: 1px solid rgba(47,166,107,0.35);
            padding: 6px 14px;
            border-radius: 100px;
            font-weight: 500;
            margin-bottom: 18px;
        }
        .eyebrow .dot { width: 6px; height: 6px; border-radius: 50%; background: var(--signal); }
        .brand-copy h2 {
            font-family: var(--f-display);
            font-weight: 800;
            font-size: clamp(26px, 3vw, 36px);
            color: #fff;
            margin: 0 0 14px;
            letter-spacing: -0.01em;
            line-height: 1.25;
        }
        .brand-copy h2 .accent { color: var(--signal); }
        .benefits { list-style: none; margin: 28px 0 0; padding: 0; display: flex; flex-direction: column; gap: 12px; }
        .benefits li {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14.5px;
            color: #D7ECDF;
        }
        .benefits svg { width: 18px; height: 18px; flex: none; color: var(--signal); }

        .auth-form-col {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 32px 20px;
        }
        .auth-mobile-brand {
            display: none;
            align-items: center;
            gap: 10px;
            font-family: var(--f-display);
            font-weight: 800;
            font-size: 20px;
            margin-bottom: 20px;
            color: var(--text);
        }
        .auth-mobile-brand .mark { width: 32px; height: 32px; }

        .auth-card {
            width: 100%;
            max-width: 420px;
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            padding: 32px 28px;
            box-shadow: 0 8px 30px -18px rgba(11,21,18,0.35);
        }
        .auth-card h1 {
            font-family: var(--f-display);
            font-size: 22px;
            font-weight: 800;
            margin: 0 0 6px;
        }
        .auth-lead { margin: 0 0 24px; color: var(--muted); font-size: 14.5px; }

        .field { margin-bottom: 16px; }
        .field label {
            display: block;
            font-size: 13.5px;
            font-weight: 600;
            margin-bottom: 6px;
        }
        .field input[type="text"],
        .field input[type="email"],
        .field input[type="password"] {
            width: 100%;
            min-height: 48px;
            border: 1.5px solid var(--line);
            border-radius: 11px;
            padding: 12px 14px;
            font: 400 16px/1.5 var(--f-body);
            color: var(--text);
            background: #fff;
            outline: none;
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .field input:focus {
            border-color: var(--signal);
            box-shadow: 0 0 0 3px rgba(47,166,107,0.18);
        }
        .field input.is-invalid { border-color: var(--danger); }
        .field-error { color: var(--danger); font-size: 13px; margin: 6px 0 0; }
        .password-wrap { position: relative; }
        .password-wrap input { padding-inline-end: 48px; }
        .toggle-pass {
            position: absolute;
            inset-inline-end: 8px;
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 40px;
            border: 0;
            background: transparent;
            color: var(--muted);
            cursor: pointer;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .toggle-pass:hover { color: var(--text); }
        .toggle-pass svg { width: 20px; height: 20px; }

        .row-between { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px; }
        .remember { display: flex; align-items: center; gap: 8px; font-size: 14px; color: var(--muted); cursor: pointer; min-height: 44px; }
        .remember input { width: 18px; height: 18px; accent-color: var(--signal); }
        .link { color: var(--signal-deep); font-weight: 600; font-size: 13.5px; }
        .link:hover { text-decoration: underline; }

        .btn-submit {
            width: 100%;
            min-height: 48px;
            border: 0;
            border-radius: 11px;
            background: var(--signal);
            color: #06170F;
            font-family: var(--f-body);
            font-weight: 700;
            font-size: 15.5px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 8px 20px -8px rgba(31,122,78,0.55);
            transition: background .15s ease, transform .15s ease;
            touch-action: manipulation;
        }
        .btn-submit:hover { background: #37B879; }
        .btn-submit:active { transform: translateY(1px); }
        .btn-submit:disabled { opacity: 0.7; cursor: not-allowed; }
        .spinner {
            width: 18px; height: 18px; border-radius: 50%;
            border: 2px solid rgba(6,23,15,0.25);
            border-top-color: #06170F;
            animation: spin .7s linear infinite;
            display: none;
        }
        .btn-submit.is-loading .spinner { display: inline-block; }
        @keyframes spin { to { transform: rotate(360deg); } }

        .auth-foot { text-align: center; margin-top: 18px; color: var(--muted); font-size: 14px; }
        .banner {
            display: flex;
            gap: 8px;
            align-items: flex-start;
            padding: 12px 14px;
            border-radius: 11px;
            background: var(--signal-dim);
            border: 1px solid #CBE9D7;
            color: var(--signal-deep);
            font-size: 13.5px;
            margin-bottom: 18px;
        }
        .status {
            padding: 12px 14px;
            border-radius: 11px;
            background: var(--signal-dim);
            border: 1px solid #CBE9D7;
            color: var(--signal-deep);
            font-size: 14px;
            margin-bottom: 16px;
        }
        .status-error {
            background: var(--danger-dim);
            border-color: #F3C4C0;
            color: var(--danger);
        }

        @media (max-width: 767px) {
            .auth-shell { grid-template-columns: 1fr; }
            .auth-brand { display: none; }
            .auth-mobile-brand { display: flex; }
            .auth-form-col { padding: 28px 16px; }
            .auth-card { box-shadow: none; border: 0; background: transparent; padding: 8px 0; }
        }
    </style>
</head>
<body>
<div class="auth-shell">
    <main class="auth-form-col">
        <a class="auth-mobile-brand" href="{{ url('/') }}">
            <svg class="mark" viewBox="0 0 32 32" fill="none" aria-hidden="true">
                <rect x="1" y="1" width="30" height="30" rx="9" fill="#0F221A" stroke="#2FA66B" stroke-width="1.4"/>
                <circle cx="11" cy="16" r="3.2" fill="#2FA66B"/>
                <circle cx="21" cy="9.5" r="2.4" fill="#E2A63D"/>
                <circle cx="21" cy="22.5" r="2.4" fill="#E9EEE9"/>
                <path d="M13.6 14.6L18.6 10.6M13.6 17.4L18.6 21.4" stroke="#4C6459" stroke-width="1.3"/>
            </svg>
            WaGateway
        </a>
        <div class="auth-card">
            {{ $slot }}
        </div>
    </main>
    <aside class="auth-brand">
        <a class="brand" href="{{ url('/') }}">
            <svg class="mark" viewBox="0 0 32 32" fill="none" aria-hidden="true">
                <rect x="1" y="1" width="30" height="30" rx="9" fill="#0F221A" stroke="#2FA66B" stroke-width="1.4"/>
                <circle cx="11" cy="16" r="3.2" fill="#2FA66B"/>
                <circle cx="21" cy="9.5" r="2.4" fill="#E2A63D"/>
                <circle cx="21" cy="22.5" r="2.4" fill="#E9EEE9"/>
                <path d="M13.6 14.6L18.6 10.6M13.6 17.4L18.6 21.4" stroke="#4C6459" stroke-width="1.3"/>
            </svg>
            WaGateway
        </a>
        <div class="brand-copy">
            <span class="eyebrow"><span class="dot"></span>{{ $isAr ? 'بدون Meta · بدون انتظار الموافقة' : 'No Meta · no approval wait' }}</span>
            <h2>
                @if ($isAr)
                    رقم واتساب واحد، <span class="accent">بوابة API</span> كاملة.
                @else
                    One WhatsApp number. A <span class="accent">full API</span> gateway.
                @endif
            </h2>
            <ul class="benefits">
                <li>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M13 2 3 14h7l-1 8 11-14h-7l1-6Z"/></svg>
                    {{ $isAr ? 'ربط فوري بمسح QR' : 'Instant QR link' }}
                </li>
                <li>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
                    {{ $isAr ? 'بدون انتظار موافقة' : 'No approval wait' }}
                </li>
                <li>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                    {{ $isAr ? 'بدون رسوم لكل رسالة' : 'No per-message fee' }}
                </li>
            </ul>
        </div>
        <div></div>
    </aside>
</div>
<script>
    document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.getAttribute('data-toggle-password'));
            if (!input) return;
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.setAttribute('aria-pressed', show ? 'true' : 'false');
            btn.setAttribute('aria-label', show
                ? @json($isAr ? 'إخفاء كلمة المرور' : 'Hide password')
                : @json($isAr ? 'إظهار كلمة المرور' : 'Show password'));
        });
    });
    document.querySelectorAll('form[data-loading]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            var btn = form.querySelector('.btn-submit');
            if (!btn) return;
            if (btn.dataset.submitted === '1') {
                e.preventDefault();
                return;
            }
            btn.dataset.submitted = '1';
            btn.classList.add('is-loading');
            btn.setAttribute('aria-busy', 'true');
        });
    });
</script>
</body>
</html>
