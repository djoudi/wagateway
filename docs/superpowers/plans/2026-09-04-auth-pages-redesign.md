# Auth Pages Redesign Implementation Plan

> **For agentic workers:** Implement task-by-task. Steps use checkbox syntax.

**Goal:** Replace the outdated English Inter card on `/register` and `/login` with a split-screen layout matching the landing page, locale from Accept-Language.

**Architecture:** Shared Blade auth layout (brand panel + form slot). Web middleware sets `ar`/`en` from `Accept-Language`. Register/login views only contain their forms. No controller changes.

**Tech Stack:** Laravel Blade, CSS variables from landing, Pest feature tests.

## Global Constraints

- Visual tokens from landing: `#0B1512`, `#F1F3EE`, `#2FA66B`, Cairo / IBM Plex Sans Arabic.
- Locale: header contains `ar` → `ar` + RTL; else `en` + LTR. No cookie, no lang files.
- Same fields, CSRF, plan hint, redirects as today.
- Mobile `<768px`: hide brand panel.
- No emojis as icons.

### Task 1: Locale middleware + test

**Files:**
- Create: `app/Http/Middleware/SetLocaleFromAcceptLanguage.php`
- Modify: `bootstrap/app.php`
- Test: `tests/Feature/AuthPagesTest.php`

- [ ] Test GET `/register` with `Accept-Language: ar` asserts `lang="ar"` and Arabic copy.
- [ ] Test GET `/register` with `Accept-Language: en` asserts `lang="en"` and English copy.
- [ ] Middleware: if Accept-Language contains `ar` (case-insensitive) set locale `ar`, else `en`.
- [ ] Append to web middleware in `bootstrap/app.php`.

### Task 2: Auth layout

**Files:**
- Create: `resources/views/components/layouts/auth.blade.php`

- [ ] Split shell, fonts, CSS tokens, brand panel, `$slot` for form.
- [ ] `lang`/`dir` from `app()->getLocale()`.
- [ ] Brand hidden below 768px.

### Task 3: Register + login forms

**Files:**
- Modify: `resources/views/auth/register.blade.php`
- Modify: `resources/views/auth/login.blade.php`

- [ ] Wrap with `<x-layouts.auth>`.
- [ ] Keep fields, `@csrf`, plan hidden input, validation errors.
- [ ] Password show/hide, 16px inputs, submit loading state.
- [ ] Bilingual labels via locale ifs.
