<!DOCTYPE html>
<html lang="en" class="h-full">
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
            reverb_app_key: @json($reverbApps['app_key'] ?? null),
            reverb_host:    @json($reverbOpts['host'] ?? null),
            reverb_port:    @json((int) ($reverbOpts['port'] ?? 80)),
            reverb_scheme:  @json($reverbOpts['scheme'] ?? 'http'),
        };
    </script>

    <!-- Tabler Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css" />
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; }
        :root {
            --wa-green: #25D366;
            --wa-dark:  #128C7E;
            --sidebar-w: 220px;
        }
    </style>
</head>
<body class="h-full bg-gray-50 text-gray-900 antialiased">

{{-- Notification toast --}}
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
    x-transition
    class="fixed top-4 right-4 z-50 flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg text-sm font-medium"
    :class="type === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200'"
    style="display:none;"
>
    <i class="ti" :class="type === 'success' ? 'ti-circle-check' : 'ti-alert-circle'"></i>
    <span x-text="message"></span>
    <button @click="show=false" class="ml-2 opacity-60 hover:opacity-100">
        <i class="ti ti-x text-xs"></i>
    </button>
</div>

<div class="flex h-full">

    {{-- ── SIDEBAR ─────────────────────────────────────────────────────── --}}
    <aside class="w-[220px] bg-white border-r border-gray-100 flex flex-col flex-shrink-0 h-screen sticky top-0">

        {{-- Brand --}}
        <div class="px-4 py-4 border-b border-gray-100">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 bg-[#25D366] rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 fill-white" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/>
                        <path d="M12 0C5.373 0 0 5.373 0 12c0 2.125.558 4.118 1.532 5.845L.057 23.04a.5.5 0 0 0 .611.61l5.275-1.461A11.938 11.938 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0z"/>
                    </svg>
                </div>
                <div>
                    <div class="text-sm font-semibold text-gray-900 leading-none">WaGateway</div>
                    <div class="text-[10px] text-[#25D366] font-medium mt-0.5">v1.0</div>
                </div>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 overflow-y-auto py-2">
            <div class="px-3 pt-3 pb-1 text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Core</div>
            <x-nav-item href="{{ route('dashboard') }}" icon="ti-layout-dashboard">Dashboard</x-nav-item>
            <x-nav-item href="{{ route('devices') }}"   icon="ti-device-mobile">Devices</x-nav-item>
            <x-nav-item href="{{ route('messages') }}"  icon="ti-message-2">Messages</x-nav-item>
            <x-nav-item href="{{ route('bulk') }}"      icon="ti-send">Bulk send</x-nav-item>

            <div class="px-3 pt-4 pb-1 text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Automation</div>
            <x-nav-item href="{{ route('schedule') }}"  icon="ti-calendar">Scheduler</x-nav-item>
            <x-nav-item href="{{ route('webhooks') }}"  icon="ti-webhook">Webhooks</x-nav-item>
            <x-nav-item href="{{ route('templates') }}" icon="ti-template">Templates</x-nav-item>

            <div class="px-3 pt-4 pb-1 text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Account</div>
            <x-nav-item href="{{ route('api-keys') }}"  icon="ti-key">API keys</x-nav-item>
            <x-nav-item href="{{ route('billing') }}"   icon="ti-credit-card">Billing</x-nav-item>
            <x-nav-item href="{{ route('docs') }}"      icon="ti-book-2">API docs</x-nav-item>
        </nav>

        {{-- User footer --}}
        <div class="px-3 py-3 border-t border-gray-100">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-full bg-[#25D366] flex items-center justify-center text-white text-[11px] font-semibold flex-shrink-0">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-xs font-medium text-gray-900 truncate">{{ auth()->user()->name }}</div>
                    <div class="text-[10px] bg-green-50 text-green-700 rounded px-1.5 py-0.5 inline-block mt-0.5 font-medium">
                        {{ auth()->user()->plan?->name ?? 'Free' }}
                    </div>
                </div>
                <a href="{{ route('logout') }}"
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                   class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="ti ti-logout text-sm"></i>
                </a>
            </div>
            <form id="logout-form" method="POST" action="{{ route('logout') }}" class="hidden">@csrf</form>
        </div>
    </aside>

    {{-- ── MAIN ──────────────────────────────────────────────────────────── --}}
    <div class="flex-1 flex flex-col overflow-hidden">

        {{-- Topbar --}}
        <header class="h-13 bg-white border-b border-gray-100 flex items-center gap-3 px-5 flex-shrink-0">
            <h1 class="text-[15px] font-semibold text-gray-900 flex-1">{{ $title ?? 'Dashboard' }}</h1>

            <div class="flex items-center gap-1.5 bg-gray-50 border border-gray-200 rounded-lg px-3 py-1.5 w-44">
                <i class="ti ti-search text-gray-400 text-sm"></i>
                <input type="text" placeholder="Search…"
                    class="bg-transparent text-sm text-gray-700 outline-none w-full placeholder-gray-400" />
            </div>

            <button class="w-8 h-8 flex items-center justify-center border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors text-gray-500 relative">
                <i class="ti ti-bell text-sm"></i>
                <span class="absolute top-1.5 right-1.5 w-1.5 h-1.5 bg-red-500 rounded-full ring-1 ring-white"></span>
            </button>
            <button class="w-8 h-8 flex items-center justify-center border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors text-gray-500">
                <i class="ti ti-help-circle text-sm"></i>
            </button>
        </header>

        {{-- Page content --}}
        <main class="flex-1 overflow-y-auto p-5">
            {{ $slot }}
        </main>
    </div>
</div>

@livewireScripts
<script>
// Copy to clipboard handler
window.addEventListener('copy-to-clipboard', e => {
    navigator.clipboard.writeText(e.detail.text).then(() => {
        window.dispatchEvent(new CustomEvent('notify', { detail: [{ type: 'success', message: 'Copied to clipboard!' }] }));
    });
});
</script>
</body>
</html>
