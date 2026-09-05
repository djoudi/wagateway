<!DOCTYPE html>
<html lang="{{ app()->getLocale() === 'ar' ? 'ar' : 'en' }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" class="h-full">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ $title ?? 'WaGateway' }}</title>

    <script>
        // Runtime Reverb/Echo config injected by Blade — env is only
        // available at runtime in containerised deploys, not at Vite build.
        @php
            $reverbApps = config('reverb.apps.apps.0', []);
            $reverbOpts = $reverbApps['options'] ?? [];
        @endphp
        window.WaGatewayConfig = {
            reverb_app_key: @json($reverbApps['key'] ?? $reverbApps['app_key'] ?? null),
            reverb_host:    @json($reverbOpts['host'] ?? null),
            reverb_port:    @json((int) ($reverbOpts['port'] ?? 80)),
            reverb_scheme:  @json($reverbOpts['scheme'] ?? 'http'),
        };
    </script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@700;800;900&family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        [x-cloak] { display: none !important; }
        a:focus-visible, button:focus-visible, input:focus-visible, select:focus-visible, textarea:focus-visible {
            outline: 2.5px solid #2FA66B;
            outline-offset: 3px;
        }
    </style>
</head>
@php $isAr = app()->getLocale() === 'ar'; @endphp
<body
    class="h-full bg-paper text-text antialiased font-sans"
    x-data="{ sidebarOpen: false }"
    @keydown.escape.window="sidebarOpen = false"
    :class="sidebarOpen ? 'overflow-hidden md:overflow-auto' : ''"
>

<div
    x-data="{
        show: false,
        type: 'success',
        message: '',
        init() {
            window.addEventListener('notify', e => {
                this.type    = e.detail[0]?.type ?? 'success';
                this.message = e.detail[0]?.message ?? '';
                this.show    = true;
                setTimeout(() => this.show = false, 4000);
            });
        }
    }"
    x-show="show"
    x-cloak
    x-transition
    class="fixed top-4 inset-inline-end-4 z-50 flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg text-sm font-medium border"
    :class="type === 'success' ? 'bg-signal-dim text-signal-deep border-signal' : 'bg-danger-dim text-danger border-danger'"
>
    <i class="ti" :class="type === 'success' ? 'ti-circle-check' : 'ti-alert-circle'"></i>
    <span x-text="message"></span>
    <button type="button" @click="show=false" class="ms-2 opacity-60 hover:opacity-100">
        <i class="ti ti-x text-xs"></i>
    </button>
</div>

<div class="flex h-full">

    <div
        x-show="sidebarOpen"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-30 bg-ink/50 md:hidden"
        @click="sidebarOpen = false"
    ></div>

    <aside
        id="app-sidebar"
        class="fixed inset-y-0 start-0 z-40 flex h-screen w-[240px] flex-col bg-ink text-paper-on-dark transition-transform duration-200 md:static md:z-auto md:flex-shrink-0 md:!translate-x-0"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full rtl:translate-x-full'"
    >
        <div class="px-4 py-4 border-b border-ink-line">
            <div class="flex items-center gap-2.5">
                <svg class="w-8 h-8 flex-shrink-0" viewBox="0 0 32 32" fill="none" aria-hidden="true">
                    <rect x="1" y="1" width="30" height="30" rx="9" fill="#0F221A" stroke="#2FA66B" stroke-width="1.4"/>
                    <circle cx="11" cy="16" r="3.2" fill="#2FA66B"/>
                    <circle cx="21" cy="9.5" r="2.4" fill="#E2A63D"/>
                    <circle cx="21" cy="22.5" r="2.4" fill="#E9EEE9"/>
                    <path d="M13.6 14.6L18.6 10.6M13.6 17.4L18.6 21.4" stroke="#4C6459" stroke-width="1.3"/>
                </svg>
                <div>
                    <div class="text-sm font-semibold text-paper-on-dark leading-none font-display">WaGateway</div>
                    <div class="text-[10px] text-signal font-medium mt-0.5">v1.0</div>
                </div>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto py-2">
            <div class="px-3 pt-3 pb-1 text-[10px] font-semibold text-muted-dark uppercase tracking-wider">{{ $isAr ? 'أساسي' : 'Core' }}</div>
            <x-nav-item href="{{ route('dashboard') }}" icon="ti-layout-dashboard">{{ $isAr ? 'لوحة التحكم' : 'Dashboard' }}</x-nav-item>
            <x-nav-item href="{{ route('devices') }}"   icon="ti-device-mobile">{{ $isAr ? 'الأجهزة' : 'Devices' }}</x-nav-item>
            <x-nav-item href="{{ route('messages') }}"  icon="ti-message-2">{{ $isAr ? 'الرسائل' : 'Messages' }}</x-nav-item>
            <x-nav-item href="{{ route('bulk') }}"      icon="ti-send">{{ $isAr ? 'إرسال جماعي' : 'Bulk send' }}</x-nav-item>

            <div class="px-3 pt-4 pb-1 text-[10px] font-semibold text-muted-dark uppercase tracking-wider">{{ $isAr ? 'أتمتة' : 'Automation' }}</div>
            <x-nav-item href="{{ route('schedule') }}"  icon="ti-calendar">{{ $isAr ? 'الجدولة' : 'Scheduler' }}</x-nav-item>
            <x-nav-item href="{{ route('webhooks') }}"  icon="ti-webhook">Webhooks</x-nav-item>
            <x-nav-item href="{{ route('templates') }}" icon="ti-template">{{ $isAr ? 'القوالب' : 'Templates' }}</x-nav-item>

            <div class="px-3 pt-4 pb-1 text-[10px] font-semibold text-muted-dark uppercase tracking-wider">{{ $isAr ? 'الحساب' : 'Account' }}</div>
            <x-nav-item href="{{ route('api-keys') }}"  icon="ti-key">{{ $isAr ? 'مفاتيح API' : 'API keys' }}</x-nav-item>
            <x-nav-item href="{{ route('billing') }}"   icon="ti-credit-card">{{ $isAr ? 'الفوترة' : 'Billing' }}</x-nav-item>
            <x-nav-item href="{{ route('docs') }}"      icon="ti-book-2">{{ $isAr ? 'توثيق API' : 'API docs' }}</x-nav-item>
        </nav>

        <div class="px-3 py-3 border-t border-ink-line">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-full bg-signal flex items-center justify-center text-[#06170F] text-[11px] font-semibold flex-shrink-0">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-xs font-medium text-paper-on-dark truncate">{{ auth()->user()->name }}</div>
                    <div class="text-[10px] bg-ink-soft text-signal rounded px-1.5 py-0.5 inline-block mt-0.5 font-medium">
                        {{ auth()->user()->plan?->name ?? 'Free' }}
                    </div>
                </div>
                <a href="{{ route('logout') }}"
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                   aria-label="{{ $isAr ? 'تسجيل الخروج' : 'Log out' }}"
                   class="text-muted-dark hover:text-paper-on-dark transition-colors min-h-11 min-w-11 inline-flex items-center justify-center">
                    <i class="ti ti-logout text-sm"></i>
                </a>
            </div>
            <form id="logout-form" method="POST" action="{{ route('logout') }}" class="hidden">@csrf</form>
        </div>
    </aside>

    <div class="flex-1 flex flex-col overflow-hidden min-w-0">
        <header class="h-13 bg-card border-b border-line flex items-center gap-3 px-5 flex-shrink-0">
            <button
                type="button"
                class="md:hidden min-h-11 min-w-11 inline-flex items-center justify-center rounded-lg border border-line text-text hover:bg-paper transition-colors"
                aria-controls="app-sidebar"
                :aria-expanded="sidebarOpen.toString()"
                @click="sidebarOpen = !sidebarOpen"
            >
                <i class="ti ti-menu-2 text-lg" aria-hidden="true"></i>
                <span class="sr-only">{{ $isAr ? 'القائمة' : 'Menu' }}</span>
            </button>
            <h1 class="text-[15px] font-semibold text-text flex-1 font-display">{{ $title ?? ($isAr ? 'لوحة التحكم' : 'Dashboard') }}</h1>
        </header>

        <main class="flex-1 overflow-y-auto p-5">
            {{ $slot }}
        </main>
    </div>
</div>

@livewireScripts
<script>
window.addEventListener('copy-to-clipboard', e => {
    navigator.clipboard.writeText(e.detail.text).then(() => {
        window.dispatchEvent(new CustomEvent('notify', { detail: [{ type: 'success', message: @json($isAr ? 'تم النسخ' : 'Copied to clipboard!') }] }));
    });
});
</script>
</body>
</html>
