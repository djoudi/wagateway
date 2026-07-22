<x-layouts.app title="Dashboard">

{{-- ── Onboarding Checklist (shown only to new users) ─────────────────────── --}}
@if (!empty($onboarding) && !($onboarding['completed'] ?? true))
<div class="mb-5 bg-gradient-to-r from-[#25D366]/10 to-white border border-[#25D366]/30 rounded-2xl p-5">
    <div class="flex items-center gap-3 mb-4">
        <div class="w-9 h-9 rounded-xl bg-[#25D366] flex items-center justify-center flex-shrink-0">
            <i class="ti ti-rocket text-white text-base"></i>
        </div>
        <div class="flex-1">
            <h3 class="text-sm font-semibold text-gray-900">Get started with WaGateway</h3>
            <p class="text-xs text-gray-500 mt-0.5">Complete these steps to send your first message</p>
        </div>
        <div class="text-right">
            <div class="text-lg font-bold text-[#25D366]">
                {{ collect($onboarding)->filter()->count() - 1 }} / {{ count($onboarding) - 1 }}
            </div>
            <div class="text-[10px] text-gray-400">steps done</div>
        </div>
    </div>

    {{-- Progress bar --}}
    @php
        $done  = collect($onboarding)->only(['account_created','device_connected','first_message_sent','webhook_configured'])->filter()->count();
        $total = 4;
        $pct   = round(($done / $total) * 100);
    @endphp
    <div class="w-full h-1.5 bg-gray-200 rounded-full mb-4 overflow-hidden">
        <div class="h-full bg-[#25D366] rounded-full transition-all duration-500" style="width: {{ $pct }}%"></div>
    </div>

    <div class="grid grid-cols-4 gap-3">
        @foreach ([
            'account_created'    => ['label' => 'Create account',       'icon' => 'ti-user-check',   'href' => null],
            'device_connected'   => ['label' => 'Connect a device',     'icon' => 'ti-device-mobile','href' => route('devices')],
            'first_message_sent' => ['label' => 'Send first message',   'icon' => 'ti-send',         'href' => route('api-keys')],
            'webhook_configured' => ['label' => 'Set up a webhook',     'icon' => 'ti-webhook',      'href' => route('webhooks')],
        ] as $key => $step)
            @php $done = $onboarding[$key] ?? false; @endphp
            <div class="flex items-center gap-2.5 p-3 rounded-xl border
                {{ $done ? 'bg-green-50 border-green-200' : 'bg-white border-gray-100' }}">
                <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0
                    {{ $done ? 'bg-green-500' : 'bg-gray-100' }}">
                    <i class="ti {{ $done ? 'ti-check' : $step['icon'] }} text-sm
                        {{ $done ? 'text-white' : 'text-gray-400' }}"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-xs font-medium {{ $done ? 'text-green-700 line-through' : 'text-gray-700' }} truncate">
                        {{ $step['label'] }}
                    </div>
                </div>
                @if (!$done && $step['href'])
                    <a href="{{ $step['href'] }}"
                       class="flex-shrink-0 text-[10px] font-semibold text-[#25D366] hover:underline">
                        Go →
                    </a>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endif

{{-- ── Stats Grid ───────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-4 gap-3 mb-5" wire:poll.30s="refresh">

    <div class="bg-white rounded-2xl border border-gray-100 p-4">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-medium text-gray-500">Messages today</span>
            <div class="w-7 h-7 rounded-lg bg-green-50 flex items-center justify-center">
                <i class="ti ti-send text-green-600 text-sm"></i>
            </div>
        </div>
        <div class="text-2xl font-bold text-gray-900">{{ number_format($stats['messages_today'] ?? 0) }}</div>
        <div class="flex items-center gap-1 mt-1.5 text-xs
            {{ ($stats['delta_positive'] ?? true) ? 'text-green-600' : 'text-red-500' }}">
            <i class="ti {{ ($stats['delta_positive'] ?? true) ? 'ti-trending-up' : 'ti-trending-down' }} text-sm"></i>
            {{ ($stats['delta_positive'] ?? true) ? '+' : '' }}{{ $stats['delta_percent'] ?? 0 }}% vs yesterday
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 p-4">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-medium text-gray-500">Active devices</span>
            <div class="w-7 h-7 rounded-lg bg-blue-50 flex items-center justify-center">
                <i class="ti ti-device-mobile text-blue-600 text-sm"></i>
            </div>
        </div>
        <div class="text-2xl font-bold text-gray-900">{{ $stats['connected_devices'] ?? 0 }}</div>
        <div class="text-xs text-gray-400 mt-1.5">of {{ $stats['total_devices'] ?? 0 }} registered</div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 p-4">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-medium text-gray-500">Delivery rate</span>
            <div class="w-7 h-7 rounded-lg bg-purple-50 flex items-center justify-center">
                <i class="ti ti-circle-check text-purple-600 text-sm"></i>
            </div>
        </div>
        <div class="text-2xl font-bold text-gray-900">{{ $stats['delivery_rate'] ?? 0 }}%</div>
        <div class="text-xs {{ ($stats['failed_today'] ?? 0) > 0 ? 'text-red-500' : 'text-gray-400' }} mt-1.5">
            {{ $stats['failed_today'] ?? 0 }} failed today
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 p-4">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-medium text-gray-500">Daily usage</span>
            <div class="w-7 h-7 rounded-lg bg-amber-50 flex items-center justify-center">
                <i class="ti ti-chart-bar text-amber-600 text-sm"></i>
            </div>
        </div>
        <div class="text-2xl font-bold text-gray-900">{{ $stats['usage_percent'] ?? 0 }}%</div>
        <div class="w-full h-1.5 bg-gray-100 rounded-full mt-2 overflow-hidden">
            <div class="h-full rounded-full transition-all duration-300
                {{ ($stats['usage_percent'] ?? 0) > 85 ? 'bg-red-500' : (($stats['usage_percent'] ?? 0) > 60 ? 'bg-amber-400' : 'bg-[#25D366]') }}"
                 style="width: {{ min($stats['usage_percent'] ?? 0, 100) }}%"></div>
        </div>
        <div class="text-[10px] text-gray-400 mt-1.5">
            {{ number_format($stats['usage'] ?? 0) }} / {{ number_format($stats['limit'] ?? 0) }}
        </div>
    </div>
</div>

{{-- ── Chart + Device Status ─────────────────────────────────────────────── --}}
<div class="grid grid-cols-3 gap-3 mb-5">

    <div class="col-span-2 bg-white rounded-2xl border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h3 class="text-sm font-semibold text-gray-900">Message volume — 7 days</h3>
                <p class="text-xs text-gray-400 mt-0.5">Sent + delivered + read</p>
            </div>
            <span class="text-xs font-medium text-gray-500">
                Total: <strong class="text-gray-900">{{ number_format(collect($chartData)->sum('value')) }}</strong>
            </span>
        </div>
        @php $maxVal = collect($chartData)->max('value') ?: 1; @endphp
        <div class="flex items-end gap-2" style="height:88px;">
            @foreach ($chartData as $bar)
                <div class="flex-1 flex flex-col items-center gap-1.5 group">
                    <div class="relative w-full">
                        <div class="w-full rounded-t-md transition-all duration-300 cursor-pointer
                            {{ $bar['today'] ? 'bg-[#25D366]' : 'bg-green-100 group-hover:bg-green-300' }}"
                             style="height: {{ max(round(($bar['value'] / $maxVal) * 72), 3) }}px"
                             title="{{ number_format($bar['value']) }} messages"></div>
                        {{-- Tooltip --}}
                        <div class="absolute bottom-full mb-1 left-1/2 -translate-x-1/2 hidden group-hover:block
                                    bg-gray-900 text-white text-[10px] rounded px-1.5 py-0.5 whitespace-nowrap z-10">
                            {{ number_format($bar['value']) }}
                        </div>
                    </div>
                    <span class="text-[10px] font-medium {{ $bar['today'] ? 'text-[#25D366]' : 'text-gray-400' }}">
                        {{ $bar['label'] }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-900">Device status</h3>
            <a href="{{ route('devices') }}" class="text-[10px] text-[#25D366] hover:underline font-medium">
                Manage →
            </a>
        </div>

        @forelse ($deviceStatus as $device)
            <div class="flex items-center gap-2.5 py-2.5 border-b border-gray-50 last:border-0">
                <div class="relative flex-shrink-0">
                    <div class="w-7 h-7 rounded-lg bg-gray-100 flex items-center justify-center">
                        <i class="ti ti-device-mobile text-gray-500 text-sm"></i>
                    </div>
                    <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full border-2 border-white
                        {{ $device['status'] === 'connected' ? 'bg-green-500' :
                           ($device['status'] === 'connecting' ? 'bg-amber-400' : 'bg-gray-300') }}">
                    </span>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-xs font-semibold text-gray-900 truncate">{{ $device['name'] }}</div>
                    <div class="text-[10px] text-gray-400">
                        {{ number_format($device['messages_sent_today'] ?? 0) }} msgs today
                    </div>
                </div>
                <span class="text-[10px] font-medium
                    {{ $device['status'] === 'connected' ? 'text-green-600' :
                       ($device['status'] === 'connecting' ? 'text-amber-600' : 'text-gray-400') }}">
                    {{ $device['status'] }}
                </span>
            </div>
        @empty
            <div class="text-center py-6">
                <i class="ti ti-device-mobile-off block text-2xl text-gray-300 mb-2"></i>
                <p class="text-xs text-gray-500 mb-3">No devices connected</p>
                <a href="{{ route('devices') }}"
                   class="text-xs font-semibold text-[#25D366] hover:underline">
                    + Add your first device
                </a>
            </div>
        @endforelse
    </div>
</div>

{{-- ── Quick Actions ─────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-3 gap-3">
    @foreach ([
        ['href' => route('bulk'),     'icon' => 'ti-send',       'color' => 'green',  'title' => 'Bulk send',   'desc' => 'Broadcast to multiple contacts'],
        ['href' => route('webhooks'), 'icon' => 'ti-webhook',    'color' => 'blue',   'title' => 'Webhooks',    'desc' => 'Configure real-time callbacks'],
        ['href' => route('api-keys'), 'icon' => 'ti-key',        'color' => 'purple', 'title' => 'API keys',    'desc' => 'Manage your credentials'],
    ] as $action)
        <a href="{{ $action['href'] }}"
           class="flex items-center gap-3 p-4 bg-white rounded-2xl border border-gray-100
                  hover:border-{{ $action['color'] }}-200 hover:bg-{{ $action['color'] }}-50
                  transition-all duration-150 group">
            <div class="w-9 h-9 rounded-xl bg-{{ $action['color'] }}-100 flex items-center justify-center
                        group-hover:bg-{{ $action['color'] }}-200 transition-colors flex-shrink-0">
                <i class="ti {{ $action['icon'] }} text-{{ $action['color'] }}-700 text-lg"></i>
            </div>
            <div class="flex-1">
                <div class="text-sm font-semibold text-gray-900">{{ $action['title'] }}</div>
                <div class="text-xs text-gray-400 mt-0.5">{{ $action['desc'] }}</div>
            </div>
            <i class="ti ti-chevron-right text-gray-300 group-hover:text-{{ $action['color'] }}-400 transition-colors"></i>
        </a>
    @endforeach
</div>

</x-layouts.app>
