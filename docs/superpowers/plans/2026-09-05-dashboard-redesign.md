# Dashboard Product Restyle Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Restyle the customer app shell and every `x-layouts.app` page to match landing/auth (hybrid ink sidebar + paper content, Accept-Language locale, mobile drawer).

**Architecture:** Keep Livewire PHP classes and routes unchanged. Put WaGateway tokens in `resources/css/app.css`. Rebuild `components/layouts/app.blade.php` as the only shell (locale, drawer, landing mark). Restyle each Livewire Blade in place: bilingual `@if ($isAr)`, token colors, responsive grids. Do not touch Filament, auth controllers, or `resources/views/layouts/app.blade.php`.

**Tech Stack:** Laravel Blade, Livewire 4, Tailwind 4 via Vite, Alpine (drawer + toasts), Pest, Tabler icons, Cairo / IBM Plex Sans Arabic.

## Global Constraints

- Tokens: ink `#0B1512`, paper `#F1F3EE`, card `#FFFFFF`, line `#DDE3DC`, signal `#2FA66B`, signal-deep `#1B7A4D`, signal-dim `#E7F3EC`, amber `#E2A63D`, text `#16211C`, muted `#5B6660`, paper-on-dark `#E9EEE9`, muted-dark `#9AACA3`, danger `#B42318`, danger-dim `#FDECEC`.
- Fonts: Cairo display, IBM Plex Sans Arabic body, IBM Plex Mono for keys/code. No Inter.
- Locale: existing `SetLocaleFromAcceptLanguage` — header contains `ar` → `ar`+RTL, else `en`+LTR. No cookie, no `lang/` files, no switcher.
- Primary button: background `#2FA66B`, text `#06170F`. Never `#25D366`. Logo: landing SVG mark, not WhatsApp bubble.
- Mobile `<768px`: hamburger + overlay drawer. Grids never `grid-cols-3`/`grid-cols-4` without `sm:`/`lg:`.
- No new features. Remove dead Search/Bell/Help and the Messages Export CSV button (no handler).
- Do not change RegisterController, LoginController, session cookie path, Reverb config, or Filament admin.
- PHP may be missing in this workspace: still write Pest tests; run `./vendor/bin/pest` when PHP exists. After CSS/Blade token changes, run `npm run build` so `public/build` matches.

---

### Task 1: Dashboard shell Pest tests

**Files:**
- Create: `tests/Feature/DashboardPagesTest.php`

**Interfaces:**
- Consumes: `User` factory, web middleware locale, `GET /dashboard` as authenticated user
- Produces: failing tests that lock `lang`/`dir`, Arabic/English nav copy, hamburger a11y, absence of Inter and `#25D366`

- [ ] **Step 1: Write the failing tests**

```php
<?php

use App\Models\User;

function dashboardUser(): User
{
    return User::factory()->create();
}

test('dashboard is arabic rtl when accept-language is ar', function () {
    $this->actingAs(dashboardUser())
        ->withHeaders(['Accept-Language' => 'ar-DZ,ar;q=0.9'])
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('lang="ar"', false)
        ->assertSee('dir="rtl"', false)
        ->assertSee('لوحة التحكم')
        ->assertSee('الأجهزة')
        ->assertDontSee('>Dashboard</', false);
});

test('dashboard is english ltr when accept-language is en', function () {
    $this->actingAs(dashboardUser())
        ->withHeaders(['Accept-Language' => 'en-US,en;q=0.9'])
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('lang="en"', false)
        ->assertSee('dir="ltr"', false)
        ->assertSee('Dashboard')
        ->assertSee('Devices')
        ->assertDontSee('لوحة التحكم');
});

test('dashboard shell has a mobile drawer hamburger', function () {
    $html = $this->actingAs(dashboardUser())
        ->get('/dashboard')
        ->assertOk()
        ->getContent();

    expect($html)
        ->toContain('aria-controls="app-sidebar"')
        ->toContain('aria-expanded');
});

test('dashboard shell does not use Inter or WhatsApp green', function () {
    $html = $this->actingAs(dashboardUser())
        ->get('/dashboard')
        ->assertOk()
        ->getContent();

    expect($html)->not->toContain('Inter')
        ->and($html)->not->toContain('#25D366')
        ->and($html)->not->toContain('ti-bell')
        ->and($html)->not->toContain('placeholder="Search');
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `./vendor/bin/pest tests/Feature/DashboardPagesTest.php`

Expected: FAIL — `lang="en"` always, no `لوحة التحكم`, no `aria-controls="app-sidebar"`, HTML still has Inter / `#25D366`.

If PHP is missing, skip the run and continue; do not skip writing the file.

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/DashboardPagesTest.php
git commit -m "test: lock dashboard locale, drawer, and brand tokens"
```

---

### Task 2: Tailwind tokens in app.css

**Files:**
- Modify: `resources/css/app.css`

**Interfaces:**
- Consumes: spec token table
- Produces: `--color-ink`, `--color-paper`, `--color-signal`, `--font-sans` Cairo/IBM Plex so later blades can use `bg-ink`, `bg-paper`, `bg-signal`, `text-signal-deep`

- [ ] **Step 1: Replace `resources/css/app.css` entirely**

```css
@import 'tailwindcss';

@theme {
    --color-ink: #0B1512;
    --color-ink-soft: #132019;
    --color-ink-line: #223028;
    --color-paper: #F1F3EE;
    --color-card: #FFFFFF;
    --color-line: #DDE3DC;
    --color-signal: #2FA66B;
    --color-signal-deep: #1B7A4D;
    --color-signal-dim: #E7F3EC;
    --color-amber: #E2A63D;
    --color-text: #16211C;
    --color-muted: #5B6660;
    --color-paper-on-dark: #E9EEE9;
    --color-muted-dark: #9AACA3;
    --color-danger: #B42318;
    --color-danger-dim: #FDECEC;
    --font-sans: 'IBM Plex Sans Arabic', 'Cairo', ui-sans-serif, system-ui, sans-serif;
    --font-display: 'Cairo', 'IBM Plex Sans Arabic', sans-serif;
    --font-mono: 'IBM Plex Mono', ui-monospace, monospace;
}

.wa-bubble {
    background: #E7F3EC;
    border-radius: 1rem 1rem 1rem 0;
    padding: 1rem;
    font-size: 0.875rem;
    line-height: 1.625;
    color: #16211C;
}

::-webkit-scrollbar       { width: 4px; height: 4px; }
::-webkit-scrollbar-track  { background: transparent; }
::-webkit-scrollbar-thumb  { background: #DDE3DC; border-radius: 2px; }

@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.001ms !important;
        transition-duration: 0.001ms !important;
    }
}
```

- [ ] **Step 2: Confirm no `#25D366` or `Inter` remain in this file**

Search `resources/css/app.css` for `25D366` and `Inter`. Expected: zero matches.

- [ ] **Step 3: Commit**

```bash
git add resources/css/app.css
git commit -m "feat: replace dashboard Tailwind tokens with landing palette"
```

---

### Task 3: Nav item (RTL-safe active)

**Files:**
- Modify: `resources/views/components/nav-item.blade.php`

**Interfaces:**
- Consumes: `href`, `icon`, slot label (already localized by caller)
- Produces: active state with `border-inline-start` and ink-sidebar colors

- [ ] **Step 1: Replace the component**

```blade
@props(['href', 'icon'])

@php
    $active = request()->is(ltrim(parse_url($href, PHP_URL_PATH), '/') . '*') ||
              request()->url() === $href;
@endphp

<a href="{{ $href }}"
   class="flex items-center gap-2.5 px-3 py-2.5 mx-1 rounded-lg text-[13px] font-medium transition-colors min-h-11
          {{ $active
             ? 'bg-ink-soft text-paper-on-dark border-inline-start-2 border-signal'
             : 'text-muted-dark hover:bg-ink-soft hover:text-paper-on-dark' }}"
>
    <i class="ti {{ $icon }} text-base w-4 text-center {{ $active ? 'text-signal' : '' }}"></i>
    {{ $slot }}
</a>
```

- [ ] **Step 2: Confirm no `border-r-2` or `#25D366`**

Expected: file uses `border-inline-start` and `border-signal` only.

- [ ] **Step 3: Commit**

```bash
git add resources/views/components/nav-item.blade.php
git commit -m "feat: make dashboard nav active state RTL-safe"
```

---

### Task 4: Hybrid app shell + drawer

**Files:**
- Modify: `resources/views/components/layouts/app.blade.php`

**Interfaces:**
- Consumes: `$title`, `$slot`, `auth()->user()`, `app()->getLocale()`, Task 2 tokens, Task 3 nav-item
- Produces: `lang`/`dir`, ink sidebar `id="app-sidebar"`, hamburger `aria-controls="app-sidebar"`, no Search/Bell/Help, landing mark, bilingual nav copy from spec table, logout POST + CSRF, Reverb `window.WaGatewayConfig` unchanged

- [ ] **Step 1: Rewrite `resources/views/components/layouts/app.blade.php`**

Keep the existing Reverb script block and Livewire/Vite includes. Replace fonts, `:root`, sidebar, topbar, toast position.

Key structure (implement the full file; do not leave the old Inter/WhatsApp bubble block):

```blade
<!DOCTYPE html>
<html lang="{{ app()->getLocale() === 'ar' ? 'ar' : 'en' }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" class="h-full">
{{-- head: Cairo + IBM Plex Google Fonts (same href as auth layout), Tabler CDN, @vite, @livewireStyles --}}
{{-- body: x-data="{ sidebarOpen: false }" class="h-full bg-paper text-text antialiased font-sans" --}}
```

Nav copy (exact):

| English | Arabic |
|---------|--------|
| Core | أساسي |
| Dashboard | لوحة التحكم |
| Devices | الأجهزة |
| Messages | الرسائل |
| Bulk send | إرسال جماعي |
| Automation | أتمتة |
| Scheduler | الجدولة |
| Webhooks | Webhooks |
| Templates | القوالب |
| Account | الحساب |
| API keys | مفاتيح API |
| Billing | الفوترة |
| API docs | توثيق API |
| Log out (aria-label) | تسجيل الخروج |

Shell behavior:

- `@php $isAr = app()->getLocale() === 'ar'; @endphp` at top of body.
- Sidebar: `id="app-sidebar"`, `bg-ink text-paper-on-dark`, width 240px, `fixed inset-y-start-0 z-40` on mobile with `translate` when `!sidebarOpen`; `md:static md:translate-x-0`.
- Backdrop: `md:hidden` fixed inset-0 `bg-ink/50 z-30` when `sidebarOpen`, `@click="sidebarOpen=false"`.
- Hamburger in header: `md:hidden`, `min-h-11 min-w-11`, `aria-controls="app-sidebar"`, `:aria-expanded="sidebarOpen.toString()"`, `@click="sidebarOpen=!sidebarOpen"`.
- `@keydown.escape.window="sidebarOpen=false"`.
- Logo SVG (same as auth):

```html
<svg class="w-8 h-8" viewBox="0 0 32 32" fill="none" aria-hidden="true">
    <rect x="1" y="1" width="30" height="30" rx="9" fill="#0F221A" stroke="#2FA66B" stroke-width="1.4"/>
    <circle cx="11" cy="16" r="3.2" fill="#2FA66B"/>
    <circle cx="21" cy="9.5" r="2.4" fill="#E2A63D"/>
    <circle cx="21" cy="22.5" r="2.4" fill="#E9EEE9"/>
    <path d="M13.6 14.6L18.6 10.6M13.6 17.4L18.6 21.4" stroke="#4C6459" stroke-width="1.3"/>
</svg>
```

- Topbar: title only + hamburger. Delete Search input, Bell, Help.
- Toast: `fixed top-4 inset-inline-end-4`; success `bg-signal-dim text-signal-deep border-signal`; error `bg-danger-dim text-danger`.
- Logout: existing POST form; control `aria-label="{{ $isAr ? 'تسجيل الخروج' : 'Log out' }}"`.
- Copy-to-clipboard script: message `{{ $isAr ? 'تم النسخ' : 'Copied to clipboard!' }}`.
- Do not emit the string `Inter` or `#25D366` anywhere in this file (including comments).

- [ ] **Step 2: Run Pest**

Run: `./vendor/bin/pest tests/Feature/DashboardPagesTest.php`

Expected: PASS (shell assertions). Overview page copy may still be English; tests only cover layout chrome.

- [ ] **Step 3: Commit**

```bash
git add resources/views/components/layouts/app.blade.php
git commit -m "feat: hybrid ink sidebar and mobile drawer for dashboard"
```

---

### Task 5: Localized page titles

**Files:**
- Modify: `resources/views/dashboard.blade.php`
- Modify: `resources/views/devices.blade.php`
- Modify: `resources/views/messages.blade.php`
- Modify: `resources/views/bulk.blade.php`
- Modify: `resources/views/schedule.blade.php`
- Modify: `resources/views/webhooks.blade.php`
- Modify: `resources/views/templates.blade.php`
- Modify: `resources/views/api-keys.blade.php`
- Modify: `resources/views/billing.blade.php`
- Modify: `resources/views/docs.blade.php`
- Modify: `resources/views/billing/checkout-success.blade.php`
- Modify: `resources/views/billing/checkout-failure.blade.php`

**Interfaces:**
- Consumes: spec title table; `app()->getLocale()`
- Produces: each wrapper passes Arabic or English `$title`

- [ ] **Step 1: Pattern for each wrapper**

Example `resources/views/dashboard.blade.php`:

```blade
@php $isAr = app()->getLocale() === 'ar'; @endphp
<x-layouts.app :title="$isAr ? 'لوحة التحكم' : 'Dashboard'">
    <livewire:dashboard.overview />
</x-layouts.app>
```

Titles:

| File | English | Arabic |
|------|---------|--------|
| dashboard | Dashboard | لوحة التحكم |
| devices | Devices | الأجهزة |
| messages | Messages | الرسائل |
| bulk | Bulk send | إرسال جماعي |
| schedule | Scheduler | الجدولة |
| webhooks | Webhooks | Webhooks |
| templates | Templates | القوالب |
| api-keys | API keys | مفاتيح API |
| billing | Billing | الفوترة |
| docs | API docs | توثيق API |
| checkout-success | Payment confirmation | جاري تأكيد الدفع |
| checkout-failure | Payment failed | فشلت عملية الدفع |

- [ ] **Step 2: Commit**

```bash
git add resources/views/dashboard.blade.php resources/views/devices.blade.php resources/views/messages.blade.php resources/views/bulk.blade.php resources/views/schedule.blade.php resources/views/webhooks.blade.php resources/views/templates.blade.php resources/views/api-keys.blade.php resources/views/billing.blade.php resources/views/docs.blade.php resources/views/billing/checkout-success.blade.php resources/views/billing/checkout-failure.blade.php
git commit -m "feat: localize dashboard page titles from Accept-Language"
```

---

### Task 6: Overview page restyle

**Files:**
- Modify: `resources/views/livewire/dashboard/overview.blade.php`

**Interfaces:**
- Consumes: existing `$onboarding`, `$stats`, `$chartData`, `$deviceStatus` from `App\Livewire\Dashboard\Overview` (do not change the PHP class)
- Produces: bilingual overview, responsive grids, token colors, explicit quick-action classes (no `hover:bg-{{ $color }}-50`)

- [ ] **Step 1: Restyle the Blade only**

At top of the root `<div>`:

```blade
@php $isAr = app()->getLocale() === 'ar'; @endphp
```

Required replacements:

- All `#25D366` → `signal` / `#2FA66B` via classes `bg-signal`, `text-signal`, `border-signal`.
- `grid-cols-4` → `grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4`
- Chart+devices `grid-cols-3` → `grid grid-cols-1 lg:grid-cols-3`; chart `lg:col-span-2`
- Quick actions `grid-cols-3` → `grid grid-cols-1 sm:grid-cols-3`
- Onboarding steps `grid-cols-4` → `grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4`
- Cards: `bg-card border border-line rounded-[14px]`
- Quick actions: three explicit class strings, e.g. `hover:border-signal hover:bg-signal-dim` (never interpolated Tailwind color names).
- Copy:

| English | Arabic |
|---------|--------|
| Get started with WaGateway | ابدأ مع WaGateway |
| Complete these steps to send your first message | أكمل هذه الخطوات لإرسال أول رسالة |
| steps done | خطوات مكتملة |
| Create account | إنشاء الحساب |
| Connect a device | ربط جهاز |
| Send first message | إرسال أول رسالة |
| Set up a webhook | إعداد Webhook |
| Go → | ابدأ ← |
| Messages today | رسائل اليوم |
| vs yesterday | مقابل أمس |
| Active devices | أجهزة نشطة |
| of {n} registered | من {n} مسجّل |
| Delivery rate | معدل التسليم |
| failed today | فشلت اليوم |
| Daily usage | الاستخدام اليومي |
| Message volume — 7 days | حجم الرسائل — 7 أيام |
| Sent + delivered + read | مُرسلة + مُسلَّمة + مقروءة |
| Total: | المجموع: |
| Device status | حالة الأجهزة |
| Manage → | إدارة ← |
| No devices connected | لا توجد أجهزة |
| + Add your first device | + أضف جهازك الأول |
| Bulk send / Broadcast to multiple contacts | إرسال جماعي / أرسل لعدة جهات |
| Webhooks / Configure real-time callbacks | Webhooks / اضبط الاستدعاءات الفورية |
| API keys / Manage your credentials | مفاتيح API / إدارة بيانات الاعتماد |

Status labels: connected → `متصل` / `connected`; connecting → `جارٍ الاتصال` / `connecting`; else `offline` / `غير متصل`. Keep the text label next to the color dot.

- [ ] **Step 2: Grep the file for `#25D366` and `grid-cols-4` without a breakpoint**

Expected: zero.

- [ ] **Step 3: Commit**

```bash
git add resources/views/livewire/dashboard/overview.blade.php
git commit -m "feat: restyle dashboard overview to landing tokens"
```

---

### Task 7: Devices page

**Files:**
- Modify: `resources/views/livewire/devices/device-manager.blade.php`

**Interfaces:**
- Consumes: existing Livewire device methods (`openAddModal`, etc.) — do not change PHP
- Produces: bilingual UI, signal buttons (`bg-signal text-[#06170F]`), `grid-cols-1 sm:grid-cols-2 lg:grid-cols-3`, danger banners for plan errors

- [ ] **Step 1: Restyle + translate visible strings**

English → Arabic for chrome only:

- Connected devices → الأجهزة المتصلة
- `{n} of {m} connected · Plan allows {p}` → `{n} من {m} متصل · الخطة تسمح بـ {p}`
- Add device → إضافة جهاز
- Upgrade plan → ترقية الخطة
- Empty / modal titles already in the file: translate with `$isAr` the same way. Keep `wire:click` names unchanged.
- Primary buttons: `bg-signal text-[#06170F] hover:bg-[#37B879] min-h-11`
- Inputs: `min-h-11 text-base border-line focus:border-signal focus:ring-2 focus:ring-signal/20`
- Modal scrim: `bg-ink/50`

- [ ] **Step 2: Commit**

```bash
git add resources/views/livewire/devices/device-manager.blade.php
git commit -m "feat: restyle devices page to landing tokens"
```

---

### Task 8: Messages page

**Files:**
- Modify: `resources/views/livewire/messages/message-log.blade.php`

**Interfaces:**
- Consumes: existing `$summary`, `$search`, filters — do not add CSV export
- Produces: bilingual filters, token colors, Export CSV button removed

- [ ] **Step 1: Remove the Export CSV button block** (the `<button>` with `ti-download` and no `wire:` / `href`).

- [ ] **Step 2: Restyle summary/filters/table**

- `$isAr` translations: Today/Sent/Failed, Search placeholder, All statuses, Queued/Sent/Delivered/Read/Failed, type filter labels, empty state.
- `ml-auto` → `ms-auto`. Borders `border-line`. Status colors plus text (already has words).

- [ ] **Step 3: Commit**

```bash
git add resources/views/livewire/messages/message-log.blade.php
git commit -m "feat: restyle messages log and remove dead CSV export"
```

---

### Task 9: Bulk, scheduler, webhooks, templates

**Files:**
- Modify: `resources/views/livewire/bulk/bulk-sender.blade.php`
- Modify: `resources/views/livewire/schedule/schedule-manager.blade.php`
- Modify: `resources/views/livewire/webhooks/webhook-manager.blade.php`
- Modify: `resources/views/livewire/templates/template-manager.blade.php`

**Interfaces:**
- Consumes: existing Livewire properties/actions
- Produces: same behavior, landing tokens, `$isAr` copy, responsive grids, signal primary buttons

- [ ] **Step 1: For each file, replace every `#25D366` with signal classes, `grid-cols-3` with `grid-cols-1 sm:grid-cols-2 lg:grid-cols-3`, gray-100 borders with `border-line`, white cards with `bg-card`.**

- [ ] **Step 2: Add `@php $isAr = app()->getLocale() === 'ar'; @endphp` and bilingual labels for headings, buttons, empty states, modal titles. Do not translate user-entered content or event name enums.**

- [ ] **Step 3: Primary actions `bg-signal text-[#06170F] min-h-11`. Modal overlays `bg-ink/50`.**

- [ ] **Step 4: Grep the four files for `#25D366`**

Expected: zero.

- [ ] **Step 5: Commit**

```bash
git add resources/views/livewire/bulk/bulk-sender.blade.php resources/views/livewire/schedule/schedule-manager.blade.php resources/views/livewire/webhooks/webhook-manager.blade.php resources/views/livewire/templates/template-manager.blade.php
git commit -m "feat: restyle bulk, scheduler, webhooks, and templates"
```

---

### Task 10: API keys, billing, docs, checkout

**Files:**
- Modify: `resources/views/livewire/api-keys/api-key-manager.blade.php`
- Modify: `resources/views/livewire/billing/billing-page.blade.php`
- Modify: `resources/views/livewire/docs/api-docs.blade.php`
- Modify: `resources/views/billing/checkout-success.blade.php`
- Modify: `resources/views/billing/checkout-failure.blade.php`

**Interfaces:**
- Consumes: existing billing/checkout invoice data and docs `$sections` (docs already has a code-language switcher — keep it; that is API sample language, not UI locale)
- Produces: token restyle; checkout pages bilingual (they are Arabic-only today — add English when locale is `en`)

- [ ] **Step 1: API keys** — `$isAr` for flash/copy headings; `grid-cols-2` → `grid-cols-1 md:grid-cols-2`; signal links; mono for keys.

- [ ] **Step 2: Billing** — plan grid `grid-cols-1 md:grid-cols-3`; featured plan `border-signal`; CTA `bg-signal text-[#06170F]`; bilingual plan chrome (plan `name` from DB stays).

- [ ] **Step 3: Docs** — inner sidebar `border-line bg-card`; active section `bg-signal-dim text-signal-deep`; keep `$languages` sample switcher.

- [ ] **Step 4: Checkout success/failure** — wrap copy in `$isAr` (keep current Arabic strings; add English equivalents). Button `bg-signal text-[#06170F]`. Card `border-line`. Success CTA English: "Go to billing"; failure: "Try again".

- [ ] **Step 5: Grep all five files plus earlier Livewire files for `#25D366` and `Inter`**

```bash
rg -n '#25D366|Inter' resources/views/components/layouts/app.blade.php resources/views/livewire resources/views/billing resources/css/app.css
```

Expected: no matches in those paths. (`resources/views/layouts/app.blade.php` may still match — leave it unused.)

- [ ] **Step 6: Commit**

```bash
git add resources/views/livewire/api-keys/api-key-manager.blade.php resources/views/livewire/billing/billing-page.blade.php resources/views/livewire/docs/api-docs.blade.php resources/views/billing/checkout-success.blade.php resources/views/billing/checkout-failure.blade.php
git commit -m "feat: restyle api keys, billing, docs, and checkout pages"
```

---

### Task 11: Rebuild Vite assets and re-run tests

**Files:**
- Modify: `public/build/*` (generated)

**Interfaces:**
- Consumes: Task 2 `app.css`
- Produces: committed build artifacts so production `@vite` serves new tokens without a local `npm run dev`

- [ ] **Step 1: Build**

```bash
npm run build
```

Expected: Vite writes hashed CSS/JS under `public/build`.

- [ ] **Step 2: Run Pest**

```bash
./vendor/bin/pest tests/Feature/DashboardPagesTest.php tests/Feature/AuthPagesTest.php
```

Expected: PASS. If PHP missing, note that in the PR and still commit the build.

- [ ] **Step 3: Commit build output**

```bash
git add public/build
git commit -m "chore: rebuild Vite assets for dashboard tokens"
```

---

## Spec coverage

| Spec requirement | Task |
|------------------|------|
| Hybrid ink/paper shell | 4 |
| Accept-Language lang/dir | 4 (middleware already exists) |
| Nav copy table | 4 |
| Hamburger drawer | 4 |
| Remove Search/Bell/Help | 4 |
| Landing logo mark | 4 |
| Tokens in app.css | 2 |
| RTL-safe nav active | 3 |
| Localized titles | 5 |
| Overview + responsive grids | 6 |
| All Livewire pages | 7–10 |
| Remove Export CSV | 8 |
| Checkout bilingual | 10 |
| Pest tests | 1, 4, 11 |
| Vite rebuild | 11 |
| No Filament / auth controllers | (not in any task) |
| Unused `layouts/app.blade.php` left alone | (not in any task) |
