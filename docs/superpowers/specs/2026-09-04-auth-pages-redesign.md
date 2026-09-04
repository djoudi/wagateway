# Auth pages redesign (register + login)

Date: 2026-09-04
Status: draft for review

## Problem

`/register` and `/login` are a small English-only card on gray (`Inter`, WhatsApp green `#25D366`). The landing page (`welcome.blade.php`) is Arabic RTL with Cairo / IBM Plex Sans Arabic and the WaGateway palette (`#2FA66B`, `#F1F3EE`, `#0B1512`). The mismatch looks outdated and breaks the product story after “ابدأ مجاناً”.

Registration logic itself is in scope only as-is: no backend change.

## Goals

- Split-screen auth pages that match the landing visual identity.
- Locale and direction from the browser `Accept-Language` header (`ar` → Arabic RTL, otherwise English LTR).
- Same layout for register and login.
- Mobile: brand panel hidden; form is full width.
- Keep current fields, validation, CSRF, plan hint, and redirects.

## Non-goals

- Full Laravel `lang/` files or a language switcher UI.
- Social login, extra fields, captcha, or auth-flow changes.
- Dashboard / Filament restyle.
- Dark mode.

## Layout

Two columns, full viewport height (`min-h-dvh`).

| Locale | Form column | Brand column |
|--------|-------------|--------------|
| `ar` (RTL) | end / right | start / left |
| `en` (LTR) | start / left | end / right |

- Desktop (`>=768px`): 50/50 (form max-width ~420px centered in its column).
- Mobile (`<768px`): brand column `display: none`; form column full width with comfortable padding.
- Brand column: dark ink (`#0B1512`) with landing mark, headline, three benefits.
- Form column: paper (`#F1F3EE`) with a white card.

## Visual system (from landing)

Reuse landing tokens, not Tailwind gray + `#25D366`:

- Ink `#0B1512`, paper `#F1F3EE`, card `#FFFFFF`, line `#DDE3DC`
- Signal `#2FA66B`, signal-deep `#1B7A4D`, signal-dim `#E7F3EC`
- Text `#16211C`, muted `#5B6660`, amber `#E2A63D`
- Display: Cairo; body: IBM Plex Sans Arabic; mono: IBM Plex Mono
- Radius 14px / 11px buttons; focus ring 2.5px signal
- Respect `prefers-reduced-motion`

Logo: same SVG mark as the landing header (not the old WhatsApp bubble).

## Brand panel copy

Arabic:

- Eyebrow: بدون Meta · بدون انتظار الموافقة
- Headline: رقم واتساب واحد، بوابة API كاملة.
- Benefits: ربط فوري بمسح QR · بدون انتظار موافقة · بدون رسوم لكل رسالة

English:

- Eyebrow: No Meta · no approval wait
- Headline: One WhatsApp number. A full API gateway.
- Benefits: Instant QR link · No approval wait · No per-message fee

Link back to `/` on the brand mark.

## Form UX

Register fields (unchanged): name, email, password, password confirmation, optional hidden `plan`.

Login fields (unchanged): email, password, remember, forgot-password if the route exists.

Rules:

- Visible labels (not placeholder-only).
- Password show/hide toggle; inputs `type="email"` / `autocomplete` attributes.
- Errors under the field; first invalid field focused after failed submit (browser default is enough).
- Submit: full-width primary button; disabled + spinner while submitting (`onsubmit`).
- Register plan banner (`pro` / `business`) stays, localized.
- Footer link: register ↔ login.
- Touch targets ≥44px; body text ≥16px on mobile.

## Locale

Middleware (web stack) sets locale from `Accept-Language`:

- If the header contains `ar` (including `ar-DZ`, `ar-SA`, …) → `ar`
- Else → `en`

No cookie, no `?lang=` in this iteration. Strings live in the Blade views as `@if(app()->getLocale() === 'ar')` (or a tiny helper array in the auth layout). No `lang/` JSON files.

`<html lang>` and `dir` come from that locale (`ar`/`rtl` vs `en`/`ltr`).

## Architecture

```
resources/views/components/layouts/auth.blade.php   # split shell, fonts, tokens, brand panel
resources/views/auth/register.blade.php             # form only
resources/views/auth/login.blade.php                # form only
app/Http/Middleware/SetLocaleFromAcceptLanguage.php
bootstrap/app.php                                   # append middleware to web
```

No changes to:

- `RegisterController` / `LoginController`
- routes
- validation rules
- dashboard

CSS: prefer a scoped `<style>` in the auth layout using the same CSS variables as the landing page, so auth does not depend on Vite/Tailwind for the new look (landing already does this). Keep `@vite` only if existing `app.css` is still needed; otherwise drop it on these pages to avoid mixing two design systems.

## Error handling

- Validation: existing Laravel `@error` under each field.
- Session `status` on login: keep, styled with signal-dim.
- Submit loading state is client-side only; server errors re-render the form.

## Testing

Manual:

- `Accept-Language: ar` → RTL split, Arabic copy.
- `Accept-Language: en` → LTR split, English copy.
- Width 375px: brand hidden, form usable, no horizontal scroll.
- Width 1024px: two columns.
- Register with invalid password → errors under fields, layout intact.
- Login + register links swap correctly.
- Plan query `?plan=pro` still shows the banner and hidden input.

No new automated tests required unless the repo already has HTTP tests for auth; if present, add a locale assertion.

## Success criteria

- Auth pages no longer look like a generic Inter/gray card.
- Visual continuity with `welcome.blade.php`.
- Arabic browsers see Arabic RTL; others see English LTR.
- Existing registration and login still work with the same fields and redirects.
