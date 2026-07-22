<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>{{ config('app.name', 'WaGateway') }} — بوابة WhatsApp API بدون Meta</title>
<meta name="description" content="اربط رقم واتساب خلال دقيقة عبر QR، وابدأ الإرسال عبر API فوري — بدون اشتراك Meta، بدون رسوم لكل رسالة." />

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@700;800;900&family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">

<style>
  :root{
    --ink:        #0B1512;
    --ink-soft:   #132019;
    --ink-line:   #223028;
    --paper:      #F1F3EE;
    --card:       #FFFFFF;
    --line:       #DDE3DC;
    --signal:     #2FA66B;
    --signal-deep:#1B7A4D;
    --signal-dim: #E7F3EC;
    --amber:      #E2A63D;
    --amber-dim:  #FBF1DD;
    --text:       #16211C;
    --muted:      #5B6660;
    --muted-dark: #9AACA3;
    --paper-on-dark: #E9EEE9;

    --f-display: 'Cairo', 'IBM Plex Sans Arabic', sans-serif;
    --f-body:    'IBM Plex Sans Arabic', 'Cairo', sans-serif;
    --f-mono:    'IBM Plex Mono', ui-monospace, monospace;

    --container: 1180px;
    --radius: 14px;
  }

  *,*::before,*::after{ box-sizing:border-box; }
  html{ scroll-behavior:smooth; }
  @media (prefers-reduced-motion: reduce){
    html{ scroll-behavior:auto; }
    *,*::before,*::after{ animation-duration:0.001ms !important; animation-iteration-count:1 !important; transition-duration:0.001ms !important; scroll-behavior:auto !important; }
  }

  body{
    margin:0;
    font-family:var(--f-body);
    background:var(--paper);
    color:var(--text);
    line-height:1.7;
    -webkit-font-smoothing:antialiased;
  }

  img,svg{ display:block; max-width:100%; }
  a{ color:inherit; text-decoration:none; }

  a:focus-visible, button:focus-visible, summary:focus-visible, input:focus-visible{
    outline: 2.5px solid var(--signal);
    outline-offset: 3px;
    border-radius: 4px;
  }

  .wrap{
    max-width:var(--container);
    margin-inline:auto;
    padding-inline:28px;
  }

  .eyebrow{
    display:inline-flex;
    align-items:center;
    gap:8px;
    font-family:var(--f-mono);
    font-size:12.5px;
    letter-spacing:.04em;
    color:var(--signal-deep);
    background:var(--signal-dim);
    border:1px solid #CBE9D7;
    padding:6px 14px;
    border-radius:100px;
    font-weight:500;
  }
  .eyebrow.on-dark{
    color:#9FE8C1;
    background:rgba(47,166,107,0.12);
    border-color:rgba(47,166,107,0.35);
  }
  .eyebrow .dot{ width:6px; height:6px; border-radius:50%; background:var(--signal); flex:none; }

  h1,h2,h3,h4{
    font-family:var(--f-display);
    font-weight:800;
    margin:0;
    color:var(--text);
    letter-spacing:-0.01em;
  }

  .btn{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    font-family:var(--f-body);
    font-weight:700;
    font-size:15px;
    padding:13px 26px;
    border-radius:11px;
    border:1.5px solid transparent;
    cursor:pointer;
    transition:transform .15s ease, box-shadow .15s ease, background .15s ease, border-color .15s ease;
    white-space:nowrap;
  }
  .btn:active{ transform:translateY(1px); }
  .btn-primary{
    background:var(--signal);
    color:#06170F;
    box-shadow:0 1px 0 rgba(0,0,0,0.05), 0 8px 20px -8px rgba(31,122,78,0.55);
  }
  .btn-primary:hover{ background:#37B879; box-shadow:0 10px 26px -8px rgba(31,122,78,0.65); }
  .btn-ghost{
    background:transparent;
    border-color:var(--line);
    color:var(--text);
  }
  .btn-ghost:hover{ border-color:var(--signal-deep); color:var(--signal-deep); }
  .btn-on-dark{
    border-color:var(--ink-line);
    color:var(--paper-on-dark);
  }
  .btn-on-dark:hover{ border-color:var(--signal); color:#9FE8C1; }
  .btn-lg{ padding:15px 32px; font-size:16px; border-radius:12px; }

  /* ── NAV ─────────────────────────────────────────────────────────────── */
  header.site{
    position:sticky; top:0; z-index:50;
    background:rgba(241,243,238,0.86);
    backdrop-filter:blur(10px);
    border-bottom:1px solid var(--line);
  }
  .nav{
    display:flex; align-items:center; justify-content:space-between;
    padding-block:14px;
  }
  .brand{ display:flex; align-items:center; gap:9px; font-family:var(--f-display); font-weight:800; font-size:19px; color:var(--text); }
  .brand .mark{ width:30px; height:30px; flex:none; }
  .navlinks{ display:flex; align-items:center; gap:30px; }
  .navlinks a{ font-size:14.5px; font-weight:600; color:var(--muted); transition:color .15s; }
  .navlinks a:hover{ color:var(--signal-deep); }
  .navcta{ display:flex; align-items:center; gap:10px; }
  .navtoggle{ display:none; }

  @media (max-width:860px){
    .navlinks{ display:none; }
    .navcta .btn-ghost{ display:none; }
  }

  /* ── HERO ────────────────────────────────────────────────────────────── */
  .hero{
    background:var(--ink);
    color:var(--paper-on-dark);
    position:relative;
    overflow:hidden;
    padding-block:88px 40px;
  }
  .hero::before{
    content:"";
    position:absolute; inset:0;
    background:
      radial-gradient(620px 420px at 82% -10%, rgba(47,166,107,0.20), transparent 60%),
      radial-gradient(500px 380px at 8% 110%, rgba(226,166,61,0.10), transparent 60%);
    pointer-events:none;
  }
  .hero-grid{
    position:relative;
    display:grid;
    grid-template-columns: 1.05fr 0.95fr;
    gap:56px;
    align-items:center;
  }
  @media (max-width:980px){
    .hero-grid{ grid-template-columns:1fr; gap:52px; }
  }

  .hero h1{
    font-size:clamp(34px, 4.6vw, 54px);
    line-height:1.16;
    color:#fff;
    margin-block:20px 18px;
  }
  .hero h1 .accent{ color:var(--signal); }
  .hero p.lead{
    font-size:17.5px;
    color:var(--muted-dark);
    max-width:52ch;
    margin-bottom:30px;
  }
  .hero-ctas{ display:flex; gap:14px; flex-wrap:wrap; margin-bottom:34px; }

  .hero-trust{
    display:flex; flex-wrap:wrap; gap:22px;
    padding-top:22px;
    border-top:1px solid var(--ink-line);
  }
  .trust-item{ display:flex; align-items:center; gap:9px; font-size:13.5px; color:var(--muted-dark); font-weight:500; }
  .trust-item svg{ width:17px; height:17px; color:var(--signal); flex:none; }

  /* Signature diagram */
  .diagram{
    position:relative;
    background:var(--ink-soft);
    border:1px solid var(--ink-line);
    border-radius:20px;
    padding:30px 22px;
    min-height:420px;
  }
  .diagram-label{
    font-family:var(--f-mono); font-size:11.5px; color:var(--muted-dark);
    letter-spacing:.06em; text-transform:uppercase; margin-bottom:18px;
  }
  .diagram svg{ width:100%; height:auto; overflow:visible; }
  .node{
    fill:var(--ink); stroke:var(--ink-line); stroke-width:1.4;
  }
  .node-main{ fill:#0F221A; stroke:var(--signal); }
  .node-label{
    font-family:var(--f-mono); font-size:12px; fill:#DCEAE2; font-weight:500;
  }
  .node-sub{
    font-family:var(--f-mono); font-size:9.5px; fill:var(--muted-dark);
  }
  .flow-line{ fill:none; stroke:var(--ink-line); stroke-width:1.6; }
  .pulse{ fill:var(--signal); filter:drop-shadow(0 0 5px rgba(47,166,107,0.9)); }
  @keyframes travel1{ 0%{ offset-distance:0%; opacity:0;} 8%{opacity:1;} 92%{opacity:1;} 100%{ offset-distance:100%; opacity:0;} }
  .p1{ offset-path:path("M40,120 C120,120 130,60 230,60"); animation:travel1 3.2s ease-in-out infinite; }
  .p2{ offset-path:path("M40,120 C120,120 140,120 230,120"); animation:travel1 3.2s ease-in-out infinite; animation-delay:.5s; }
  .p3{ offset-path:path("M40,120 C120,120 140,180 230,180"); animation:travel1 3.2s ease-in-out infinite; animation-delay:1s; }
  .p4{ offset-path:path("M40,120 C120,120 130,240 230,240"); animation:travel1 3.2s ease-in-out infinite; animation-delay:1.5s; }

  /* ── SECTION shells ─────────────────────────────────────────────────── */
  section{ padding-block:84px; }
  .section-head{ max-width:640px; margin-bottom:46px; }
  .section-head .eyebrow{ margin-bottom:16px; }
  .section-head h2{ font-size:clamp(26px,3.2vw,36px); line-height:1.28; margin-bottom:14px; }
  .section-head p{ color:var(--muted); font-size:16px; max-width:56ch; }
  .center{ text-align:center; margin-inline:auto; }

  /* ── COMPARISON ─────────────────────────────────────────────────────── */
  .compare{ display:grid; grid-template-columns:1fr 1fr; gap:20px; }
  @media (max-width:760px){ .compare{ grid-template-columns:1fr; } }
  .compare-card{
    background:var(--card); border:1.5px solid var(--line); border-radius:var(--radius);
    padding:28px 26px;
  }
  .compare-card.win{ border-color:var(--signal); box-shadow:0 14px 34px -18px rgba(27,122,77,0.35); }
  .compare-card h3{ font-size:18px; margin-bottom:4px; }
  .compare-card .tag{ font-family:var(--f-mono); font-size:11.5px; color:var(--muted); margin-bottom:18px; display:block; }
  .compare-card.win .tag{ color:var(--signal-deep); }
  .compare-list{ list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:12px; }
  .compare-list li{ display:flex; align-items:flex-start; gap:10px; font-size:14.5px; color:var(--text); }
  .compare-list svg{ width:17px; height:17px; flex:none; margin-top:2px; }
  .ico-x{ color:#C4695B; }
  .ico-check{ color:var(--signal-deep); }

  /* ── STEPS ───────────────────────────────────────────────────────────── */
  .steps{ display:grid; grid-template-columns:repeat(3,1fr); gap:24px; }
  @media (max-width:860px){ .steps{ grid-template-columns:1fr; } }
  .step{ position:relative; padding-inline-start:0; }
  .step .num{
    font-family:var(--f-mono); font-size:13px; color:var(--signal-deep);
    background:var(--signal-dim); border:1px solid #CBE9D7;
    width:36px; height:36px; border-radius:10px;
    display:flex; align-items:center; justify-content:center; font-weight:600;
    margin-bottom:18px;
  }
  .step h3{ font-size:18.5px; margin-bottom:10px; }
  .step p{ color:var(--muted); font-size:14.5px; }

  /* ── FEATURES ────────────────────────────────────────────────────────── */
  .features{ display:grid; grid-template-columns:repeat(3,1fr); gap:20px; }
  @media (max-width:860px){ .features{ grid-template-columns:1fr 1fr; } }
  @media (max-width:600px){ .features{ grid-template-columns:1fr; } }
  .feature-card{
    background:var(--card); border:1px solid var(--line); border-radius:var(--radius);
    padding:26px 24px; transition:border-color .15s, transform .15s;
  }
  .feature-card:hover{ border-color:var(--signal); transform:translateY(-2px); }
  .feature-card .ico{
    width:42px; height:42px; border-radius:11px;
    background:var(--signal-dim); color:var(--signal-deep);
    display:flex; align-items:center; justify-content:center; margin-bottom:16px;
  }
  .feature-card .ico svg{ width:21px; height:21px; }
  .feature-card h3{ font-size:16.5px; margin-bottom:8px; }
  .feature-card p{ color:var(--muted); font-size:14px; }

  /* ── CODE SECTION ────────────────────────────────────────────────────── */
  .code-section{ background:var(--ink); color:var(--paper-on-dark); }
  .code-section .section-head p{ color:var(--muted-dark); }
  .code-grid{ display:grid; grid-template-columns:1fr 1.1fr; gap:44px; align-items:center; }
  @media (max-width:900px){ .code-grid{ grid-template-columns:1fr; } }
  .code-points{ display:flex; flex-direction:column; gap:18px; margin-top:26px; }
  .code-point{ display:flex; gap:12px; align-items:flex-start; }
  .code-point svg{ width:19px; height:19px; color:var(--signal); flex:none; margin-top:2px; }
  .code-point div b{ display:block; font-size:14.5px; margin-bottom:2px; color:#fff; }
  .code-point div span{ font-size:13.5px; color:var(--muted-dark); }

  .terminal{
    background:#0D1713; border:1px solid var(--ink-line); border-radius:14px;
    overflow:hidden; box-shadow:0 30px 60px -30px rgba(0,0,0,0.6);
  }
  .terminal-bar{
    display:flex; align-items:center; gap:7px;
    padding:12px 16px; border-bottom:1px solid var(--ink-line);
  }
  .terminal-bar span{ width:10px; height:10px; border-radius:50%; background:#2A3B33; }
  .terminal-bar .fname{ margin-inline-start:8px; font-family:var(--f-mono); font-size:11.5px; color:var(--muted-dark); }
  .terminal pre{
    margin:0; padding:22px 20px; direction:ltr; text-align:left;
    font-family:var(--f-mono); font-size:13px; line-height:1.85; overflow-x:auto;
    color:#CFE3D8;
  }
  .terminal .c-key{ color:#7FD8A5; }
  .terminal .c-str{ color:#E2C08D; }
  .terminal .c-mut{ color:#6C8079; }
  .terminal .c-fn{ color:#87B7E8; }

  /* ── PRICING ─────────────────────────────────────────────────────────── */
  .pricing{ display:grid; grid-template-columns:repeat(3,1fr); gap:22px; align-items:stretch; }
  @media (max-width:900px){ .pricing{ grid-template-columns:1fr; max-width:420px; margin-inline:auto; } }
  .price-card{
    background:var(--card); border:1.5px solid var(--line); border-radius:16px;
    padding:30px 26px; display:flex; flex-direction:column;
  }
  .price-card.feat{
    border-color:var(--signal); position:relative;
    box-shadow:0 20px 46px -22px rgba(27,122,77,0.4);
  }
  .price-card .badge{
    position:absolute; top:-13px; right:26px;
    background:var(--signal); color:#06170F; font-size:12px; font-weight:700;
    padding:5px 13px; border-radius:100px; font-family:var(--f-body);
  }
  .price-card h3{ font-size:16.5px; color:var(--muted); font-weight:700; margin-bottom:14px; }
  .price-card .amount{ font-family:var(--f-display); font-size:38px; font-weight:800; color:var(--text); }
  .price-card .amount small{ font-family:var(--f-body); font-size:14px; color:var(--muted); font-weight:500; }
  .price-card .plist{ list-style:none; margin:26px 0 30px; padding:0; display:flex; flex-direction:column; gap:11px; flex:1; }
  .price-card .plist li{ display:flex; gap:9px; align-items:flex-start; font-size:14px; color:var(--text); }
  .price-card .plist svg{ width:16px; height:16px; color:var(--signal-deep); flex:none; margin-top:3px; }
  .price-card .plist li.off{ color:var(--muted); }
  .price-card .plist li.off svg{ color:#C7CCC8; }

  /* ── FAQ ─────────────────────────────────────────────────────────────── */
  .faq{ max-width:760px; margin-inline:auto; display:flex; flex-direction:column; gap:10px; }
  details{
    background:var(--card); border:1px solid var(--line); border-radius:12px;
    padding:4px 22px;
  }
  details[open]{ border-color:var(--signal); }
  summary{
    cursor:pointer; list-style:none; padding-block:18px;
    display:flex; align-items:center; justify-content:space-between; gap:16px;
    font-weight:700; font-size:15.5px;
  }
  summary::-webkit-details-marker{ display:none; }
  summary .chev{ width:19px; height:19px; color:var(--muted); flex:none; transition:transform .2s; }
  details[open] summary .chev{ transform:rotate(180deg); color:var(--signal-deep); }
  .faq-a{ padding-bottom:20px; color:var(--muted); font-size:14.5px; max-width:64ch; }

  /* ── FINAL CTA ───────────────────────────────────────────────────────── */
  .final-cta{
    background:linear-gradient(155deg, var(--signal-deep), #14513A 70%);
    color:#fff; border-radius:24px; padding:64px 40px; text-align:center;
    position:relative; overflow:hidden;
  }
  .final-cta::before{
    content:""; position:absolute; inset:0;
    background:radial-gradient(500px 300px at 50% 0%, rgba(255,255,255,0.12), transparent 60%);
  }
  .final-cta h2{ position:relative; color:#fff; font-size:clamp(24px,3vw,32px); margin-bottom:14px; }
  .final-cta p{ position:relative; color:#D7ECDF; margin-bottom:30px; font-size:15.5px; }
  .final-cta .btn-primary{ background:#fff; color:var(--signal-deep); box-shadow:none; }
  .final-cta .btn-primary:hover{ background:#EFFBF3; }

  /* ── FOOTER ──────────────────────────────────────────────────────────── */
  footer{ background:var(--ink); color:var(--muted-dark); padding-block:52px 30px; }
  .foot-grid{ display:grid; grid-template-columns:1.4fr repeat(3,1fr); gap:36px; margin-bottom:44px; }
  @media (max-width:760px){ .foot-grid{ grid-template-columns:1fr 1fr; } }
  .foot-brand .brand{ color:#fff; margin-bottom:12px; }
  .foot-brand p{ font-size:13.5px; max-width:32ch; }
  .foot-col h4{ font-family:var(--f-body); font-size:13px; color:#fff; font-weight:700; margin-bottom:14px; }
  .foot-col ul{ list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:10px; }
  .foot-col a{ font-size:13.5px; color:var(--muted-dark); transition:color .15s; }
  .foot-col a:hover{ color:var(--signal); }
  .foot-bottom{
    display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;
    border-top:1px solid var(--ink-line); padding-top:22px; font-size:12.5px;
  }

  .lp{ direction:ltr; unicode-bidi:isolate; font-family:var(--f-mono); }
</style>
</head>
<body>

<!-- ══════════════════════════ NAV ══════════════════════════ -->
<header class="site">
  <div class="wrap nav">
    <a href="#top" class="brand">
      <svg class="mark" viewBox="0 0 32 32" fill="none" aria-hidden="true">
        <rect x="1" y="1" width="30" height="30" rx="9" fill="#0F221A" stroke="#2FA66B" stroke-width="1.4"/>
        <circle cx="11" cy="16" r="3.2" fill="#2FA66B"/>
        <circle cx="21" cy="9.5" r="2.4" fill="#E2A63D"/>
        <circle cx="21" cy="22.5" r="2.4" fill="#E9EEE9"/>
        <path d="M13.6 14.6L18.6 10.6M13.6 17.4L18.6 21.4" stroke="#4C6459" stroke-width="1.3"/>
      </svg>
      WaGateway
    </a>
    <nav class="navlinks">
      <a href="#features">المزايا</a>
      <a href="#how">كيف تعمل</a>
      <a href="#pricing">التسعير</a>
      <a href="#faq">الأسئلة الشائعة</a>
    </nav>
    <div class="navcta">
      <a href="{{ route('login') }}" class="btn btn-ghost">تسجيل الدخول</a>
      <a href="{{ route('register') }}" class="btn btn-primary">ابدأ مجاناً</a>
    </div>
  </div>
</header>

<!-- ══════════════════════════ HERO ══════════════════════════ -->
<section class="hero" id="top">
  <div class="wrap hero-grid">
    <div>
      <span class="eyebrow on-dark"><span class="dot"></span> بدون Meta · بدون انتظار الموافقة</span>
      <h1>رقم واتساب واحد،<br><span class="accent">بوابة API</span> كاملة.</h1>
      <p class="lead">
        اربط رقم واتساب — شخصي أو مخصص للعمل — خلال دقيقة بمسح رمز QR،
        وابدأ إرسال آلاف الرسائل عبر واجهة برمجية واحدة. بدون اشتراك Meta،
        بدون رسوم لكل رسالة، بدون أسابيع من مراجعة الحساب التجاري.
      </p>
      <div class="hero-ctas">
        <a href="{{ route('register') }}" class="btn btn-primary btn-lg">أنشئ حسابك مجاناً</a>
        <a href="#code" class="btn btn-on-dark btn-lg">شاهد الـ API</a>
      </div>
      <div class="hero-trust">
        <span class="trust-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M13 2 3 14h7l-1 8 11-14h-7l1-6Z"/></svg>
          ربط فوري بمسح QR
        </span>
        <span class="trust-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
          بدون انتظار موافقة
        </span>
        <span class="trust-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
          دفع محلي بالدينار
        </span>
      </div>
    </div>

    <div class="diagram" role="img" aria-label="رسم توضيحي: رقم واتساب يتحول إلى بوابة API تتفرع إلى الإرسال الجماعي، القوالب، لوحة التحكم، وأحداث Webhook">
      <div class="diagram-label">/ من رقم إلى بنية تحتية</div>
      <svg viewBox="0 0 280 260" preserveAspectRatio="xMidYMid meet">
        <path class="flow-line" d="M40,120 C120,120 130,60 230,60"/>
        <path class="flow-line" d="M40,120 C120,120 140,120 230,120"/>
        <path class="flow-line" d="M40,120 C120,120 140,180 230,180"/>
        <path class="flow-line" d="M40,120 C120,120 130,240 230,240"/>

        <circle class="pulse p1" r="3.4"/>
        <circle class="pulse p2" r="3.4"/>
        <circle class="pulse p3" r="3.4"/>
        <circle class="pulse p4" r="3.4"/>

        <g>
          <rect class="node node-main" x="0" y="98" width="80" height="44" rx="10"/>
          <text class="node-label" x="40" y="116" text-anchor="middle">213•••••001</text>
          <text class="node-sub" x="40" y="130" text-anchor="middle">WhatsApp</text>
        </g>

        <g>
          <rect class="node" x="230" y="42" width="50" height="36" rx="9"/>
          <text class="node-label" x="255" y="64" text-anchor="middle" font-size="10">API</text>
        </g>
        <g>
          <rect class="node" x="230" y="102" width="50" height="36" rx="9"/>
          <text class="node-label" x="255" y="124" text-anchor="middle" font-size="10">Bulk</text>
        </g>
        <g>
          <rect class="node" x="230" y="162" width="50" height="36" rx="9"/>
          <text class="node-label" x="255" y="184" text-anchor="middle" font-size="10">Hook</text>
        </g>
        <g>
          <rect class="node" x="230" y="222" width="50" height="36" rx="9"/>
          <text class="node-label" x="255" y="244" text-anchor="middle" font-size="9">Panel</text>
        </g>
      </svg>
    </div>
  </div>
</section>

<!-- ══════════════════════════ COMPARISON ══════════════════════════ -->
<section id="compare">
  <div class="wrap">
    <div class="section-head center">
      <span class="eyebrow"><span class="dot"></span> المقارنة</span>
      <h2>الطريق الرسمي، مقابل طريق WaGateway</h2>
      <p class="center">نفس الوجهة — إرسال رسائل واتساب برمجياً — بمسارين مختلفين تماماً في السرعة والتكلفة.</p>
    </div>

    <div class="compare">
      <div class="compare-card">
        <span class="tag">// الطريقة الرسمية</span>
        <h3>WhatsApp Business API عبر Meta</h3>
        <ul class="compare-list">
          <li><svg class="ico-x" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg> مراجعة حساب تجاري قد تستغرق أسابيع</li>
          <li><svg class="ico-x" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg> يتطلب رقماً تجارياً معتمداً فقط</li>
          <li><svg class="ico-x" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg> رسوم لكل محادثة بعد الحد المجاني</li>
          <li><svg class="ico-x" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg> تعقيد تقني وبنية تحتية إضافية للبدء</li>
        </ul>
      </div>
      <div class="compare-card win">
        <span class="tag">// WaGateway</span>
        <h3>ربط مباشر عبر QR</h3>
        <ul class="compare-list">
          <li><svg class="ico-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg> ربط فوري خلال دقيقة — بدون مراجعة</li>
          <li><svg class="ico-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg> يعمل مع أي رقم واتساب عادي</li>
          <li><svg class="ico-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg> صفر رسوم لكل رسالة — اشتراك شهري ثابت</li>
          <li><svg class="ico-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg> نقطة API واحدة، وثائق جاهزة بأمثلة كود</li>
        </ul>
      </div>
    </div>
  </div>
</section>

<!-- ══════════════════════════ HOW IT WORKS ══════════════════════════ -->
<section id="how" style="background:var(--card); border-top:1px solid var(--line); border-bottom:1px solid var(--line);">
  <div class="wrap">
    <div class="section-head">
      <span class="eyebrow"><span class="dot"></span> ثلاث خطوات</span>
      <h2>من التسجيل إلى أول رسالة، خلال دقائق</h2>
    </div>
    <div class="steps">
      <div class="step">
        <span class="num">01</span>
        <h3>اربط رقمك</h3>
        <p>امسح رمز QR من لوحة التحكم بكاميرا هاتفك، تماماً كما تربط واتساب ويب. لا حاجة لأي إعداد إضافي.</p>
      </div>
      <div class="step">
        <span class="num">02</span>
        <h3>وصّل الـ API</h3>
        <p>مفتاحا API — للإنتاج وللاختبار — جاهزان فور التسجيل. أرسل أول طلب خلال دقيقتين بأي لغة برمجة.</p>
      </div>
      <div class="step">
        <span class="num">03</span>
        <h3>أرسل وتابع</h3>
        <p>رسائل فردية، حملات جماعية، أو ردود تلقائية عبر Webhooks — كل شيء من لوحة تحكم واحدة.</p>
      </div>
    </div>
  </div>
</section>

<!-- ══════════════════════════ FEATURES ══════════════════════════ -->
<section id="features">
  <div class="wrap">
    <div class="section-head">
      <span class="eyebrow"><span class="dot"></span> المزايا</span>
      <h2>كل ما تحتاجه لإدارة تواصلك عبر واتساب</h2>
      <p>من الإرسال الفردي إلى الحملات الجماعية، مبني للفرق التي تحتاج تحكماً كاملاً وموثوقية حقيقية.</p>
    </div>

    <div class="features">
      <div class="feature-card">
        <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="6" y="2" width="12" height="20" rx="2.5"/><path d="M11 18h2"/></svg></div>
        <h3>ربط أجهزة متعددة</h3>
        <p>اربط عدة أرقام واتساب وأدرها من مكان واحد — رقم مستقل لكل قسم في مؤسستك.</p>
      </div>
      <div class="feature-card">
        <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4 20-7Z"/></svg></div>
        <h3>إرسال جماعي آمن</h3>
        <p>ابثّ رسائلك لمئات الأرقام مع تأخير عشوائي ذكي يحميك من حظر واتساب.</p>
      </div>
      <div class="feature-card">
        <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2v4M12 18v4M4.9 4.9l2.8 2.8M16.3 16.3l2.8 2.8M2 12h4M18 12h4M4.9 19.1l2.8-2.8M16.3 7.7l2.8-2.8"/></svg></div>
        <h3>أحداث Webhook فورية</h3>
        <p>استقبل كل رسالة واردة وكل تحديث حالة مباشرة داخل نظامك، موقّعة ومؤمّنة.</p>
      </div>
      <div class="feature-card">
        <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16v4H4zM4 12h10v8H4zM17 12h3v8h-3z"/></svg></div>
        <h3>قوالب ديناميكية</h3>
        <p>احفظ رسائلك المتكررة بمتغيرات جاهزة — الاسم، رقم الطلب، التاريخ — وأعد استخدامها فوراً.</p>
      </div>
      <div class="feature-card">
        <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/></svg></div>
        <h3>جدولة ذكية</h3>
        <p>جهّز رسائلك اليوم لتُرسل تلقائياً في الوقت والتاريخ الذي تحدده — بدون تدخل يدوي.</p>
      </div>
      <div class="feature-card">
        <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m7 8-4 4 4 4M17 8l4 4-4 4M14 4l-4 16"/></svg></div>
        <h3>API موثّق بالكامل</h3>
        <p>مرجع تقني كامل بأمثلة PHP وPython وJavaScript وcURL، جاهز للدمج مباشرة.</p>
      </div>
    </div>
  </div>
</section>

<!-- ══════════════════════════ CODE / DEVS ══════════════════════════ -->
<section class="code-section" id="code">
  <div class="wrap">
    <div class="code-grid">
      <div>
        <span class="eyebrow on-dark"><span class="dot"></span> للمطورين</span>
        <h2 style="color:#fff; font-size:clamp(24px,3vw,32px); margin-block:16px 8px;">أرسل أول رسالة خلال دقيقتين</h2>
        <p style="color:var(--muted-dark); font-size:15.5px; max-width:48ch;">
          واجهة REST واحدة، توثيق واضح، واستجابات JSON متوقّعة. لا حاجة لـ SDK معقّد.
        </p>
        <div class="code-points">
          <div class="code-point">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
            <div><b>مفتاحان جاهزان فوراً</b><span>مفتاح إنتاج ومفتاح اختبار منفصلان عند أول تسجيل دخول.</span></div>
          </div>
          <div class="code-point">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
            <div><b>توقيع HMAC على كل Webhook</b><span>تحقّق من صحة كل حدث وارد بثقة كاملة.</span></div>
          </div>
          <div class="code-point">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
            <div><b>حدود واضحة حسب الخطة</b><span>تعرف بالضبط كم رسالة يمكنك إرسالها يومياً.</span></div>
          </div>
        </div>
      </div>

      <div class="terminal">
        <div class="terminal-bar">
          <span></span><span></span><span></span>
          <span class="fname">send-message.sh</span>
        </div>
        <pre><span class="c-mut"># إرسال رسالة نصية</span>
<span class="c-fn">curl</span> -X POST https://app.wagateway.dz/api/v1/messages/send/text \
  -H <span class="c-str">"Authorization: Bearer wg_live_xxxxxxxxxxxx"</span> \
  -H <span class="c-str">"Content-Type: application/json"</span> \
  -d <span class="c-str">'{
    "device_id": "uuid-device",
    "to": "213700000001",
    "body": "مرحباً! تم تأكيد طلبك."
  }'</span>

<span class="c-mut"># الاستجابة</span>
{
  <span class="c-key">"success"</span>: <span class="c-fn">true</span>,
  <span class="c-key">"data"</span>: {
    <span class="c-key">"id"</span>: <span class="c-str">"msg-uuid-xxxx"</span>,
    <span class="c-key">"status"</span>: <span class="c-str">"sent"</span>,
    <span class="c-key">"sent_at"</span>: <span class="c-str">"2026-07-20T10:42:00Z"</span>
  }
}</pre>
      </div>
    </div>
  </div>
</section>

<!-- ══════════════════════════ PRICING ══════════════════════════ -->
<section id="pricing">
  <div class="wrap">
    <div class="section-head center">
      <span class="eyebrow"><span class="dot"></span> التسعير</span>
      <h2>خطط تناسب حجم استخدامك</h2>
      <p class="center">بدون رسوم مخفية، وبدون تكلفة إضافية لكل رسالة — سعر شهري ثابت مهما ارتفع حجم إرسالك ضمن حدود خطتك.</p>
    </div>

    <div class="pricing">
      <div class="price-card">
        <h3>Starter</h3>
        <div class="amount">500 <small>د.ج / شهر</small></div>
        <ul class="plist">
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 6 9 17l-5-5"/></svg> 1,000 رسالة/يوم</li>
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 6 9 17l-5-5"/></svg> جهازان (رقمان)</li>
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 6 9 17l-5-5"/></svg> 3 webhooks</li>
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 6 9 17l-5-5"/></svg> قوالب رسائل</li>
          <li class="off"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M18 6 6 18M6 6l12 12"/></svg> إرسال جماعي</li>
          <li class="off"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M18 6 6 18M6 6l12 12"/></svg> جدولة الرسائل</li>
        </ul>
        <a href="{{ route('register', ['plan' => 'starter']) }}" class="btn btn-ghost">ابدأ بـ Starter</a>
      </div>

      <div class="price-card feat">
        <span class="badge">الأكثر اختياراً</span>
        <h3>Pro</h3>
        <div class="amount">1,500 <small>د.ج / شهر</small></div>
        <ul class="plist">
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 6 9 17l-5-5"/></svg> 10,000 رسالة/يوم</li>
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 6 9 17l-5-5"/></svg> 10 أجهزة</li>
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 6 9 17l-5-5"/></svg> 10 webhooks</li>
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 6 9 17l-5-5"/></svg> إرسال جماعي</li>
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 6 9 17l-5-5"/></svg> جدولة الرسائل</li>
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 6 9 17l-5-5"/></svg> قوالب رسائل</li>
        </ul>
        <a href="{{ route('register', ['plan' => 'pro']) }}" class="btn btn-primary">ابدأ بـ Pro</a>
      </div>

      <div class="price-card">
        <h3>Business</h3>
        <div class="amount">2,500 <small>د.ج / شهر</small></div>
        <ul class="plist">
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 6 9 17l-5-5"/></svg> 100,000 رسالة/يوم</li>
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 6 9 17l-5-5"/></svg> 30 جهاز</li>
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 6 9 17l-5-5"/></svg> 30 webhook</li>
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 6 9 17l-5-5"/></svg> إرسال جماعي وجدولة</li>
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 6 9 17l-5-5"/></svg> دعم أولوية عبر واتساب</li>
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 6 9 17l-5-5"/></svg> قوالب غير محدودة تقريباً</li>
        </ul>
        <a href="{{ route('register', ['plan' => 'business']) }}" class="btn btn-ghost">ابدأ بـ Business</a>
      </div>
    </div>
  </div>
</section>

<!-- ══════════════════════════ FAQ ══════════════════════════ -->
<section id="faq" style="background:var(--card); border-top:1px solid var(--line); border-bottom:1px solid var(--line);">
  <div class="wrap">
    <div class="section-head center">
      <span class="eyebrow"><span class="dot"></span> الأسئلة الشائعة</span>
      <h2>كل ما تحتاج معرفته قبل البدء</h2>
    </div>

    <div class="faq">
      <details open>
        <summary>هل أحتاج حساب WhatsApp Business API الرسمي من Meta؟
          <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
        </summary>
        <p class="faq-a">لا. تعمل المنصة عبر تقنية مختلفة تحاكي WhatsApp Web، ولا تتطلب أي اشتراك أو موافقة من Meta. يكفي رقم يحمل تطبيق واتساب نشطاً.</p>
      </details>
      <details>
        <summary>هل يمكنني استخدام نفس الرقم على هاتفي والمنصة معاً؟
          <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
        </summary>
        <p class="faq-a">نعم — يبقى رقمك نشطاً وقابلاً للاستخدام العادي على هاتفك، بينما تعمل المنصة كجهاز إضافي مرتبط، تماماً مثل WhatsApp Web.</p>
      </details>
      <details>
        <summary>هل يوجد خطر لحظر رقمي؟
          <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
        </summary>
        <p class="faq-a">عند اتباع حدود الإرسال الآمنة — وهو ما تفرضه المنصة تلقائياً عبر التأخير العشوائي بين رسائل الحملات الجماعية — يكون الخطر منخفضاً جداً. نوصي دائماً باستخدام رقم مخصص للعمل عند الإرسال بكميات كبيرة.</p>
      </details>
      <details>
        <summary>كيف تُحمى بياناتي ومفاتيح API الخاصة بي؟
          <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
        </summary>
        <p class="faq-a">لا نخزّن مفاتيح API كنص صريح — فقط بصمة مشفّرة منها. جلسات الأجهزة مشفّرة بالكامل، وكل اتصال بين المتصفح والمنصة يمر عبر HTTPS.</p>
      </details>
      <details>
        <summary>ماذا يحدث إذا نفد رصيدي اليومي من الرسائل؟
          <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
        </summary>
        <p class="faq-a">تتوقف الرسائل الجديدة حتى إعادة التعيين التلقائية في منتصف الليل، أو يمكنك ترقية خطتك فوراً من لوحة التحكم دون انتظار.</p>
      </details>
    </div>
  </div>
</section>

<!-- ══════════════════════════ FINAL CTA ══════════════════════════ -->
<section>
  <div class="wrap">
    <div class="final-cta">
      <h2>جاهز تربط رقمك؟</h2>
      <p>أنشئ حسابك الآن وابدأ إرسال رسائلك الأولى خلال دقائق — بدون بطاقة ائتمان، بدون التزام.</p>
      <a href="{{ route('register') }}" class="btn btn-primary btn-lg">أنشئ حسابك مجاناً</a>
    </div>
  </div>
</section>

<!-- ══════════════════════════ FOOTER ══════════════════════════ -->
<footer>
  <div class="wrap">
    <div class="foot-grid">
      <div class="foot-brand">
        <a href="#top" class="brand">
          <svg class="mark" viewBox="0 0 32 32" fill="none" aria-hidden="true">
            <rect x="1" y="1" width="30" height="30" rx="9" fill="#132019" stroke="#2FA66B" stroke-width="1.4"/>
            <circle cx="11" cy="16" r="3.2" fill="#2FA66B"/>
            <circle cx="21" cy="9.5" r="2.4" fill="#E2A63D"/>
            <circle cx="21" cy="22.5" r="2.4" fill="#E9EEE9"/>
            <path d="M13.6 14.6L18.6 10.6M13.6 17.4L18.6 21.4" stroke="#4C6459" stroke-width="1.3"/>
          </svg>
          WaGateway
        </a>
        <p>بوابة WhatsApp API تربط رقمك ببنيتك التحتية خلال دقائق — بدون Meta.</p>
      </div>
      <div class="foot-col">
        <h4>المنتج</h4>
        <ul>
          <li><a href="#features">المزايا</a></li>
          <li><a href="#pricing">التسعير</a></li>
          <li><a href="#code">توثيق API</a></li>
        </ul>
      </div>
      <!-- TODO: wire to real routes once about/contact/support pages exist -->
      <div class="foot-col">
        <h4>الشركة</h4>
        <ul>
          <li><a href="#">من نحن</a></li>
          <li><a href="#">تواصل معنا</a></li>
          <li><a href="#">الدعم الفني</a></li>
        </ul>
      </div>
      <div class="foot-col">
        <h4>قانوني</h4>
        <ul>
          <li><a href="{{ route('legal.terms') }}">شروط الاستخدام</a></li>
          <li><a href="{{ route('legal.privacy') }}">سياسة الخصوصية</a></li>
          <li><a href="{{ route('legal.aup') }}">سياسة الاستخدام المقبول</a></li>
        </ul>
      </div>
    </div>
    <div class="foot-bottom">
      <span>© 2026 WaGateway. جميع الحقوق محفوظة.</span>
      <span class="lp">status.wagateway.dz</span>
    </div>
  </div>
</footer>

</body>
</html>
