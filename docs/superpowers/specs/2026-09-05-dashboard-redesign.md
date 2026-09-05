# Dashboard product restyle (app shell + all customer pages)

Date: 2026-09-05
Status: draft for review

## Problem

Customer dashboard at `/dashboard` (and every page using `x-layouts.app`) still uses a 2024-era Inter / Tailwind gray shell with WhatsApp green `#25D366` and a WhatsApp chat-bubble logo. Landing (`welcome.blade.php`) and auth (`layouts/auth`) already use Cairo / IBM Plex Sans Arabic and the WaGateway palette (`#2FA66B`, `#F1F3EE`, `#0B1512`). After register/login the product feels like a different app.

The shell is also English-only (`<html lang="en">`), has a fixed 220px sidebar with no mobile drawer, hard-coded `grid-cols-3` / `grid-cols-4` that overflow on phones, and dead topbar controls (Search, Bell, Help) that do nothing.

## Goals

- Restyle the app shell and every customer dashboard page to match landing + auth visual identity.
- Hybrid chrome: ink sidebar, paper content.
- Locale and direction from existing `SetLocaleFromAcceptLanguage` (`ar*` → Arabic RTL, else English LTR). No language switcher.
- Mobile: hamburger + overlay drawer under 768px.
- Keep Livewire/PHP behavior, routes, validation, CSRF, billing, and Reverb config injection unchanged.

## Non-goals

- Filament `/admin` restyle.
- Laravel `lang/` files or a language switcher UI.
- Dark-mode toggle or a full dark content surface.
- New features (working global search, notifications, help center, CSV export) unless a handler already exists.
- Auth pages (already redesigned).
- Backend/query/Livewire class logic changes.

## Decisions (locked)

- Scope: whole customer product (shell + all pages listed below).
- Locale: same as auth (`Accept-Language`).
- Theme: hybrid (ink sidebar + paper main).
- Mobile nav: drawer + hamburger.
- Implementation: one layout + Tailwind token update; restyle existing Livewire blades in place. No new component library.

## Visual system

Reuse landing/auth tokens. Put them in `resources/css/app.css` `@theme` so Tailwind utilities work, and as CSS variables on `:root`.

| Token | Value | Use |
|-------|-------|-----|
| `--ink` | `#0B1512` | Sidebar background |
| `--ink-soft` | `#132019` | Sidebar hover / active wash |
| `--ink-line` | `#223028` | Sidebar borders |
| `--paper` | `#F1F3EE` | Main background |
| `--card` | `#FFFFFF` | Cards, tables, modals |
| `--line` | `#DDE3DC` | Card/table borders |
| `--signal` | `#2FA66B` | Primary CTA, active nav, success |
| `--signal-deep` | `#1B7A4D` | Links, hover on signal |
| `--signal-dim` | `#E7F3EC` | Success banners, active nav fill on paper |
| `--amber` | `#E2A63D` | Connecting / warning |
| `--text` | `#16211C` | Body on paper |
| `--muted` | `#5B6660` | Secondary text on paper |
| `--paper-on-dark` | `#E9EEE9` | Primary text on ink sidebar |
| `--muted-dark` | `#9AACA3` | Secondary text on ink sidebar |
| `--danger` | `#B42318` | Errors / destructive |
| `--danger-dim` | `#FDECEC` | Error banners |
| `--f-display` | Cairo | Headings |
| `--f-body` | IBM Plex Sans Arabic | UI text |
| `--f-mono` | IBM Plex Mono | Keys, numbers, code |
| `--radius` | `14px` | Cards |

Rules:

- Drop Inter and `#25D366` / `#128C7E` from app shell, Tailwind `@theme`, and all in-scope blades.
- Logo: same SVG mark as landing/auth header (not the WhatsApp bubble path currently in the sidebar).
- Primary button: background `--signal`, text `#06170F` (not white on `#25D366`).
- Focus ring: 2.5px `--signal`, offset 3px. Respect `prefers-reduced-motion`.
- Touch targets ≥44px. Body text ≥16px on mobile for form inputs; UI chrome may use 13–14px.
- Status is never color-only: connected / connecting / offline include a text label plus a dot.

## Architecture

```
resources/css/app.css                                 # tokens, font-sans, utilities
resources/views/components/layouts/app.blade.php      # hybrid shell, locale, drawer
resources/views/components/nav-item.blade.php         # RTL-safe active state
resources/views/livewire/dashboard/overview.blade.php
resources/views/livewire/devices/device-manager.blade.php
resources/views/livewire/messages/message-log.blade.php
resources/views/livewire/bulk/bulk-sender.blade.php
resources/views/livewire/schedule/schedule-manager.blade.php
resources/views/livewire/webhooks/webhook-manager.blade.php
resources/views/livewire/templates/template-manager.blade.php
resources/views/livewire/api-keys/api-key-manager.blade.php
resources/views/livewire/billing/billing-page.blade.php
resources/views/livewire/docs/api-docs.blade.php
resources/views/billing/checkout-success.blade.php
resources/views/billing/checkout-failure.blade.php
resources/views/{dashboard,devices,messages,bulk,schedule,webhooks,templates,api-keys,billing,docs}.blade.php  # titles only
```

`resources/views/layouts/app.blade.php` is unused (`x-layouts.app` is the only shell). Do not update it as a second design. Leave it unused so it cannot drift into the customer UI.

Keep in the layout (unchanged behavior):

- `window.WaGatewayConfig` Reverb injection
- `@vite(['resources/css/app.css', 'resources/js/app.js'])`
- `@livewireStyles` / `@livewireScripts`
- Alpine notify toast
- Logout POST form + CSRF

## Shell

`<html lang>` and `dir` from `app()->getLocale()` (`ar`/`rtl` vs `en`/`ltr`).

Desktop (`>=768px`):

- Sticky ink sidebar, width 240px, full viewport height.
- Nav groups stay: Core / Automation / Account (localized labels).
- Active item: `--ink-soft` background, `--signal` bar on the **start** edge (`border-inline-start`, never `border-r` only).
- Footer: avatar initials, truncated name, plan chip, logout control with accessible name. Logout is visually quieter than nav items.

Mobile (`<768px`):

- Sidebar `transform` off-canvas (end/start follows `dir`). Closed by default.
- Topbar hamburger (`aria-expanded`, `aria-controls="app-sidebar"`), min 44×44.
- Open: overlay scrim 50% ink, sidebar slides in, body scroll locked.
- Close: hamburger, backdrop click, Escape.
- Main content is full width; no persistent 220px column.

Topbar:

- Page title (from layout `$title`, localized at the page wrapper).
- Hamburger on mobile only.
- Remove Search input, Bell button, and Help button (they have no handlers).

## Pages in scope

Every customer page that uses `x-layouts.app`:

| Route name | Page |
|------------|------|
| `dashboard` | Overview: onboarding, 4 stats, 7-day bars, device list, 3 quick actions |
| `devices` | Device cards, add/QR modals |
| `messages` | Filters + log. Remove the Export CSV button (no Livewire/controller handler) |
| `bulk` | Composer + progress |
| `schedule` | List + create modal |
| `webhooks` | List + create/edit modal |
| `templates` | Cards + editor modal |
| `api-keys` | Key display + regen |
| `billing` | Plan cards + checkout modal |
| `docs` | API docs |
| `billing.checkout.success` / `.failure` | Result cards |

Page wrappers pass a localized `title` into the layout.

Nav and title copy (exact):

| Key | English | Arabic |
|-----|---------|--------|
| Group Core | Core | أساسي |
| Dashboard | Dashboard | لوحة التحكم |
| Devices | Devices | الأجهزة |
| Messages | Messages | الرسائل |
| Bulk send | Bulk send | إرسال جماعي |
| Group Automation | Automation | أتمتة |
| Scheduler | Scheduler | الجدولة |
| Webhooks | Webhooks | Webhooks |
| Templates | Templates | القوالب |
| Group Account | Account | الحساب |
| API keys | API keys | مفاتيح API |
| Billing | Billing | الفوترة |
| API docs | API docs | توثيق API |
| Logout (aria) | Log out | تسجيل الخروج |

Onboarding, empty states, buttons, and filter labels follow the same bilingual `@if ($isAr)` pattern. Do not invent extra nav items.

## Content restyle rules

- Cards: white, `1px` `--line`, radius 14px, no gray-100 / gray-50.
- Grids: `grid-cols-1` default; `sm:grid-cols-2`; `lg:grid-cols-3` or `lg:grid-cols-4` as today. Never `grid-cols-3`/`grid-cols-4` without a breakpoint.
- Chart + device row: stack on small screens (`grid-cols-1 lg:grid-cols-3`, chart `lg:col-span-2`).
- Primary / secondary / destructive buttons as in the design section.
- Modals: card surface, 50% ink scrim, visible close control, inputs min-height 44px, focus ring signal.
- Empty states: short localized sentence + one primary CTA.
- Alpine `hover:bg-{{ $color }}-50` style classes on overview quick actions do not emit CSS in Tailwind 4. Replace with explicit token classes.

## Locale / copy

Reuse the auth pattern: `$isAr = app()->getLocale() === 'ar'` then `@if ($isAr)` in blades. No `lang/` files.

Must be bilingual (nav, titles, onboarding steps, empty states, buttons, filter labels, banners, toast strings that live in blades). Dynamic data (device names, emails, API keys, message bodies) stays as stored.

No cookie and no `?lang=` in this iteration.

## Error handling

- Unchanged Laravel / Livewire validation; `@error` under the field.
- Plan-limit and action banners: `--danger-dim` / `--danger`, localized, with the existing upgrade/retry link.
- Toasts: success uses signal-dim; error uses danger-dim. Auto-dismiss 4s stays. Position `inset-inline-end` so RTL is correct.

## Testing

Pest (`tests/Feature/AuthPagesTest.php` stays; add `tests/Feature/DashboardPagesTest.php`):

- `GET /dashboard` with `Accept-Language: ar` as an authenticated user asserts `lang="ar"`, `dir="rtl"`, Arabic nav (e.g. لوحة التحكم or equivalent chosen copy), and no Inter / `#25D366` in the layout HTML.
- Same with `en` asserts `lang="en"`, `dir="ltr"`, English "Dashboard".
- Layout HTML includes hamburger `aria-controls` / `aria-expanded` for the drawer.
- Unverified user can still open `/dashboard` (existing auth fix; keep that test).

Manual:

- 375px: drawer, no horizontal scroll, stats stack.
- 1024px: ink sidebar visible, paper content.
- Add-device / billing modals usable in both locales.
- Logout still POSTs.

PHP is often missing in this workspace; write the tests even if they cannot be run here.

## Success criteria

- After login, dashboard uses the same brand as landing/auth (fonts, signal green, logo mark).
- Arabic browsers get Arabic RTL chrome and page copy; others get English LTR.
- Phone users can open every nav item via the drawer.
- No dead Search/Bell/Help in the topbar.
- Livewire features still work: poll stats, QR connect, bulk send, billing checkout.

## Out of scope reminders

Do not change `RegisterController`, `LoginController`, session cookie path, Reverb config, or Filament admin in this work.
