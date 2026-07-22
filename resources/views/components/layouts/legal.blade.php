<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>{{ $title }} — {{ config('app.name', 'WaGateway') }}</title>
<meta name="robots" content="index, follow" />

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@700;800;900&family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">

<style>
  :root{
    --ink:#0B1512; --ink-soft:#132019; --ink-line:#223028;
    --paper:#F1F3EE; --card:#FFFFFF; --line:#DDE3DC;
    --signal:#2FA66B; --signal-deep:#1B7A4D; --signal-dim:#E7F3EC;
    --amber:#E2A63D; --amber-dim:#FBF1DD;
    --text:#16211C; --muted:#5B6660; --muted-dark:#9AACA3; --paper-on-dark:#E9EEE9;
    --f-display:'Cairo','IBM Plex Sans Arabic',sans-serif;
    --f-body:'IBM Plex Sans Arabic','Cairo',sans-serif;
    --f-mono:'IBM Plex Mono',ui-monospace,monospace;
    --container:1180px;
  }
  *,*::before,*::after{ box-sizing:border-box; }
  body{ margin:0; font-family:var(--f-body); background:var(--paper); color:var(--text); line-height:1.75; -webkit-font-smoothing:antialiased; }
  a{ color:inherit; text-decoration:none; }
  a:focus-visible, summary:focus-visible{ outline:2.5px solid var(--signal); outline-offset:3px; border-radius:4px; }
  .wrap{ max-width:var(--container); margin-inline:auto; padding-inline:28px; }
  h1,h2,h3,h4{ font-family:var(--f-display); font-weight:800; margin:0; color:var(--text); letter-spacing:-0.01em; }

  .eyebrow{ display:inline-flex; align-items:center; gap:8px; font-family:var(--f-mono); font-size:12.5px; letter-spacing:.04em; color:var(--signal-deep); background:var(--signal-dim); border:1px solid #CBE9D7; padding:6px 14px; border-radius:100px; font-weight:500; }
  .eyebrow .dot{ width:6px; height:6px; border-radius:50%; background:var(--signal); flex:none; }

  .btn{ display:inline-flex; align-items:center; justify-content:center; gap:8px; font-weight:700; font-size:14.5px; padding:11px 22px; border-radius:10px; border:1.5px solid transparent; cursor:pointer; transition:all .15s; }
  .btn-primary{ background:var(--signal); color:#06170F; }
  .btn-primary:hover{ background:#37B879; }
  .btn-ghost{ background:transparent; border-color:var(--line); color:var(--text); }
  .btn-ghost:hover{ border-color:var(--signal-deep); color:var(--signal-deep); }

  header.site{ position:sticky; top:0; z-index:50; background:rgba(241,243,238,0.9); backdrop-filter:blur(10px); border-bottom:1px solid var(--line); }
  .nav{ display:flex; align-items:center; justify-content:space-between; padding-block:14px; }
  .brand{ display:flex; align-items:center; gap:9px; font-family:var(--f-display); font-weight:800; font-size:19px; }
  .brand .mark{ width:30px; height:30px; flex:none; }
  .navcta{ display:flex; align-items:center; gap:10px; }

  /* ── Legal document shell ─────────────────────────────────────────── */
  .legal-hero{ background:var(--ink); color:var(--paper-on-dark); padding-block:56px 44px; }
  .legal-hero .eyebrow{ color:#9FE8C1; background:rgba(47,166,107,0.12); border-color:rgba(47,166,107,0.35); margin-bottom:16px; }
  .legal-hero h1{ color:#fff; font-size:clamp(26px,3.4vw,38px); margin-bottom:10px; }
  .legal-hero .meta{ display:flex; gap:18px; flex-wrap:wrap; font-family:var(--f-mono); font-size:12.5px; color:var(--muted-dark); margin-top:16px; }

  .legal-body{ padding-block:56px 90px; }
  .legal-grid{ display:grid; grid-template-columns:270px 1fr; gap:48px; align-items:start; }
  @media (max-width:900px){ .legal-grid{ grid-template-columns:1fr; } }

  .legal-toc{ position:sticky; top:90px; background:var(--card); border:1px solid var(--line); border-radius:14px; padding:20px; }
  .legal-toc .toc-label{ font-family:var(--f-mono); font-size:11px; color:var(--muted); text-transform:uppercase; letter-spacing:.05em; margin-bottom:12px; display:block; }
  .legal-toc ol{ list-style:none; margin:0; padding:0; counter-reset:toc; display:flex; flex-direction:column; gap:2px; }
  .legal-toc li{ counter-increment:toc; }
  .legal-toc a{ display:flex; gap:9px; font-size:13px; color:var(--muted); padding:7px 8px; border-radius:8px; transition:all .15s; line-height:1.4; }
  .legal-toc a::before{ content:counter(toc); font-family:var(--f-mono); font-size:11px; color:var(--signal-deep); flex:none; width:16px; }
  .legal-toc a:hover{ background:var(--signal-dim); color:var(--signal-deep); }

  .legal-article{ background:var(--card); border:1px solid var(--line); border-radius:16px; padding:44px 42px; max-width:78ch; }
  @media (max-width:640px){ .legal-article{ padding:28px 22px; } }
  .legal-article section{ margin-bottom:38px; scroll-margin-top:90px; }
  .legal-article section:last-child{ margin-bottom:0; }
  .legal-article h2{ font-size:19px; display:flex; align-items:center; gap:10px; margin-bottom:14px; padding-bottom:12px; border-bottom:1px solid var(--line); }
  .legal-article h2 .n{ font-family:var(--f-mono); font-size:13px; color:var(--signal-deep); background:var(--signal-dim); width:28px; height:28px; border-radius:8px; display:flex; align-items:center; justify-content:center; flex:none; }
  .legal-article h3{ font-size:15.5px; margin-block:18px 8px; color:var(--text); }
  .legal-article p{ font-size:14.5px; color:var(--text); margin-block:0 12px; }
  .legal-article ul, .legal-article ol.plain{ margin:0 0 12px; padding-inline-start:22px; font-size:14.5px; color:var(--text); display:flex; flex-direction:column; gap:7px; }
  .legal-article strong{ color:var(--text); }
  .legal-article a.inline-link{ color:var(--signal-deep); font-weight:600; border-bottom:1px solid #CBE9D7; }
  .legal-article table{ width:100%; border-collapse:collapse; margin-block:14px; font-size:13.5px; }
  .legal-article th{ background:var(--signal-dim); color:var(--signal-deep); text-align:right; padding:9px 12px; font-weight:700; border:1px solid #CBE9D7; }
  .legal-article td{ padding:9px 12px; border:1px solid var(--line); vertical-align:top; }

  .callout{ border-radius:12px; padding:16px 18px; margin-block:16px; font-size:13.5px; display:flex; gap:10px; align-items:flex-start; }
  .callout svg{ width:18px; height:18px; flex:none; margin-top:2px; }
  .callout.info{ background:#EAF2FB; border:1px solid #BBD6F0; color:#1E3A56; }
  .callout.info svg{ color:#3E7CB8; }
  .callout.warn{ background:var(--amber-dim); border:1px solid #EFD9A6; color:#5C4413; }
  .callout.warn svg{ color:var(--amber); }
  .callout.tip{ background:var(--signal-dim); border:1px solid #CBE9D7; color:#123D28; }
  .callout.tip svg{ color:var(--signal-deep); }

  .lp{ direction:ltr; unicode-bidi:isolate; font-family:var(--f-mono); }

  footer.legal-foot{ background:var(--ink); color:var(--muted-dark); padding-block:32px; margin-top:0; }
  .legal-foot-inner{ display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px; font-size:12.5px; }
  .legal-foot-inner a{ color:var(--muted-dark); }
  .legal-foot-inner a:hover{ color:var(--signal); }
  .foot-links{ display:flex; gap:18px; flex-wrap:wrap; }
</style>
</head>
<body>

<header class="site">
  <div class="wrap nav">
    <a href="{{ route('home') }}" class="brand">
      <svg class="mark" viewBox="0 0 32 32" fill="none" aria-hidden="true">
        <rect x="1" y="1" width="30" height="30" rx="9" fill="#0F221A" stroke="#2FA66B" stroke-width="1.4"/>
        <circle cx="11" cy="16" r="3.2" fill="#2FA66B"/>
        <circle cx="21" cy="9.5" r="2.4" fill="#E2A63D"/>
        <circle cx="21" cy="22.5" r="2.4" fill="#E9EEE9"/>
        <path d="M13.6 14.6L18.6 10.6M13.6 17.4L18.6 21.4" stroke="#4C6459" stroke-width="1.3"/>
      </svg>
      {{ config('app.name', 'WaGateway') }}
    </a>
    <div class="navcta">
      <a href="{{ route('login') }}" class="btn btn-ghost">تسجيل الدخول</a>
      <a href="{{ route('register') }}" class="btn btn-primary">ابدأ مجاناً</a>
    </div>
  </div>
</header>

{{ $slot }}

<footer class="legal-foot">
  <div class="wrap legal-foot-inner">
    <span>© {{ date('Y') }} {{ config('app.name', 'WaGateway') }}. جميع الحقوق محفوظة.</span>
    <div class="foot-links">
      <a href="{{ route('legal.terms') }}">شروط الاستخدام</a>
      <a href="{{ route('legal.privacy') }}">سياسة الخصوصية</a>
      <a href="{{ route('legal.aup') }}">سياسة الاستخدام المقبول</a>
      <a href="{{ route('home') }}">الصفحة الرئيسية</a>
    </div>
  </div>
</footer>

</body>
</html>
