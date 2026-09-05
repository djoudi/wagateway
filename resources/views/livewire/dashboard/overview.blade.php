<div>
@php $isAr = app()->getLocale() === 'ar'; @endphp

{{-- Onboarding Checklist (shown only to new users) --}}
@if (!empty($onboarding) && !($onboarding['completed'] ?? true))
<div class="mb-5 bg-signal-dim border border-signal/30 rounded-[14px] p-5">
    <div class="flex items-center gap-3 mb-4">
        <div class="w-9 h-9 rounded-xl bg-signal flex items-center justify-center flex-shrink-0">
            <i class="ti ti-rocket text-[#06170F] text-base"></i>
        </div>
        <div class="flex-1">
            <h3 class="text-sm font-semibold text-text">{{ $isAr ? 'ابدأ مع WaGateway' : 'Get started with WaGateway' }}</h3>
            <p class="text-xs text-muted mt-0.5">{{ $isAr ? 'أكمل هذه الخطوات لإرسال أول رسالة' : 'Complete these steps to send your first message' }}</p>
        </div>
        <div class="text-end">
            <div class="text-lg font-bold text-signal">
                {{ collect($onboarding)->filter()->count() - 1 }} / {{ count($onboarding) - 1 }}
            </div>
            <div class="text-[10px] text-muted">{{ $isAr ? 'خطوات مكتملة' : 'steps done' }}</div>
        </div>
    </div>

    @php
        $done  = collect($onboarding)->only(['account_created','device_connected','first_message_sent','webhook_configured'])->filter()->count();
        $total = 4;
        $pct   = round(($done / $total) * 100);
    @endphp
    <div class="w-full h-1.5 bg-line rounded-full mb-4 overflow-hidden">
        <div class="h-full bg-signal rounded-full transition-all duration-500" style="width: {{ $pct }}%"></div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        @foreach ([
            'account_created'    => ['label' => $isAr ? 'إنشاء الحساب' : 'Create account',       'icon' => 'ti-user-check',   'href' => null],
            'device_connected'   => ['label' => $isAr ? 'ربط جهاز' : 'Connect a device',     'icon' => 'ti-device-mobile','href' => route('devices')],
            'first_message_sent' => ['label' => $isAr ? 'إرسال أول رسالة' : 'Send first message',   'icon' => 'ti-send',         'href' => route('api-keys')],
            'webhook_configured' => ['label' => $isAr ? 'إعداد Webhook' : 'Set up a webhook',     'icon' => 'ti-webhook',      'href' => route('webhooks')],
        ] as $key => $step)
            @php $done = $onboarding[$key] ?? false; @endphp
            <div class="flex items-center gap-2.5 p-3 rounded-xl border
                {{ $done ? 'bg-signal-dim border-signal/30' : 'bg-card border-line' }}">
                <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0
                    {{ $done ? 'bg-signal' : 'bg-paper' }}">
                    <i class="ti {{ $done ? 'ti-check' : $step['icon'] }} text-sm
                        {{ $done ? 'text-[#06170F]' : 'text-muted' }}"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-xs font-medium {{ $done ? 'text-signal-deep line-through' : 'text-text' }} truncate">
                        {{ $step['label'] }}
                    </div>
                </div>
                @if (!$done && $step['href'])
                    <a href="{{ $step['href'] }}"
                       class="flex-shrink-0 text-[10px] font-semibold text-signal-deep hover:underline">
                        {{ $isAr ? 'ابدأ ←' : 'Go →' }}
                    </a>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endif

{{-- Stats Grid --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-5" wire:poll.30s="refresh">

    <div class="bg-card rounded-[14px] border border-line p-4">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-medium text-muted">{{ $isAr ? 'رسائل اليوم' : 'Messages today' }}</span>
            <div class="w-7 h-7 rounded-lg bg-signal-dim flex items-center justify-center">
                <i class="ti ti-send text-signal-deep text-sm"></i>
            </div>
        </div>
        <div class="text-2xl font-bold text-text">{{ number_format($stats['messages_today'] ?? 0) }}</div>
        <div class="flex items-center gap-1 mt-1.5 text-xs
            {{ ($stats['delta_positive'] ?? true) ? 'text-signal-deep' : 'text-danger' }}">
            <i class="ti {{ ($stats['delta_positive'] ?? true) ? 'ti-trending-up' : 'ti-trending-down' }} text-sm"></i>
            {{ ($stats['delta_positive'] ?? true) ? '+' : '' }}{{ $stats['delta_percent'] ?? 0 }}% {{ $isAr ? 'مقابل أمس' : 'vs yesterday' }}
        </div>
    </div>

    <div class="bg-card rounded-[14px] border border-line p-4">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-medium text-muted">{{ $isAr ? 'أجهزة نشطة' : 'Active devices' }}</span>
            <div class="w-7 h-7 rounded-lg bg-signal-dim flex items-center justify-center">
                <i class="ti ti-device-mobile text-signal-deep text-sm"></i>
            </div>
        </div>
        <div class="text-2xl font-bold text-text">{{ $stats['connected_devices'] ?? 0 }}</div>
        <div class="text-xs text-muted mt-1.5">{{ $isAr ? 'من' : 'of' }} {{ $stats['total_devices'] ?? 0 }} {{ $isAr ? 'مسجّل' : 'registered' }}</div>
    </div>

    <div class="bg-card rounded-[14px] border border-line p-4">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-medium text-muted">{{ $isAr ? 'معدل التسليم' : 'Delivery rate' }}</span>
            <div class="w-7 h-7 rounded-lg bg-signal-dim flex items-center justify-center">
                <i class="ti ti-circle-check text-signal-deep text-sm"></i>
            </div>
        </div>
        <div class="text-2xl font-bold text-text">{{ $stats['delivery_rate'] ?? 0 }}%</div>
        <div class="text-xs {{ ($stats['failed_today'] ?? 0) > 0 ? 'text-danger' : 'text-muted' }} mt-1.5">
            {{ $stats['failed_today'] ?? 0 }} {{ $isAr ? 'فشلت اليوم' : 'failed today' }}
        </div>
    </div>

    <div class="bg-card rounded-[14px] border border-line p-4">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-medium text-muted">{{ $isAr ? 'الاستخدام اليومي' : 'Daily usage' }}</span>
            <div class="w-7 h-7 rounded-lg bg-signal-dim flex items-center justify-center">
                <i class="ti ti-chart-bar text-amber text-sm"></i>
            </div>
        </div>
        <div class="text-2xl font-bold text-text">{{ $stats['usage_percent'] ?? 0 }}%</div>
        <div class="w-full h-1.5 bg-paper rounded-full mt-2 overflow-hidden">
            <div class="h-full rounded-full transition-all duration-300
                {{ ($stats['usage_percent'] ?? 0) > 85 ? 'bg-danger' : (($stats['usage_percent'] ?? 0) > 60 ? 'bg-amber' : 'bg-signal') }}"
                 style="width: {{ min($stats['usage_percent'] ?? 0, 100) }}%"></div>
        </div>
        <div class="text-[10px] text-muted mt-1.5">
            {{ number_format($stats['usage'] ?? 0) }} / {{ number_format($stats['limit'] ?? 0) }}
        </div>
    </div>
</div>

{{-- Chart + Device Status --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-3 mb-5">

    <div class="lg:col-span-2 bg-card rounded-[14px] border border-line p-5">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h3 class="text-sm font-semibold text-text">{{ $isAr ? 'حجم الرسائل — 7 أيام' : 'Message volume — 7 days' }}</h3>
                <p class="text-xs text-muted mt-0.5">{{ $isAr ? 'مُرسلة + مُسلَّمة + مقروءة' : 'Sent + delivered + read' }}</p>
            </div>
            <span class="text-xs font-medium text-muted">
                {{ $isAr ? 'المجموع:' : 'Total:' }} <strong class="text-text">{{ number_format(collect($chartData)->sum('value')) }}</strong>
            </span>
        </div>
        @php $maxVal = collect($chartData)->max('value') ?: 1; @endphp
        <div class="flex items-end gap-2" style="height:88px;">
            @foreach ($chartData as $bar)
                <div class="flex-1 flex flex-col items-center gap-1.5 group">
                    <div class="relative w-full">
                        <div class="w-full rounded-t-md transition-all duration-300 cursor-pointer
                            {{ $bar['today'] ? 'bg-signal' : 'bg-signal-dim group-hover:bg-signal/50' }}"
                             style="height: {{ max(round(($bar['value'] / $maxVal) * 72), 3) }}px"
                             title="{{ number_format($bar['value']) }} messages"></div>
                        <div class="absolute bottom-full mb-1 start-1/2 -translate-x-1/2 hidden group-hover:block
                                    bg-ink text-paper-on-dark text-[10px] rounded px-1.5 py-0.5 whitespace-nowrap z-10">
                            {{ number_format($bar['value']) }}
                        </div>
                    </div>
                    <span class="text-[10px] font-medium {{ $bar['today'] ? 'text-signal' : 'text-muted' }}">
                        {{ $bar['label'] }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="bg-card rounded-[14px] border border-line p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-text">{{ $isAr ? 'حالة الأجهزة' : 'Device status' }}</h3>
            <a href="{{ route('devices') }}" class="text-[10px] text-signal-deep hover:underline font-medium">
                {{ $isAr ? 'إدارة ←' : 'Manage →' }}
            </a>
        </div>

        @forelse ($deviceStatus as $device)
            @php
                $statusLabel = $device['status'] === 'connected'
                    ? ($isAr ? 'متصل' : 'connected')
                    : ($device['status'] === 'connecting'
                        ? ($isAr ? 'جارٍ الاتصال' : 'connecting')
                        : ($isAr ? 'غير متصل' : 'offline'));
            @endphp
            <div class="flex items-center gap-2.5 py-2.5 border-b border-line last:border-0">
                <div class="relative flex-shrink-0">
                    <div class="w-7 h-7 rounded-lg bg-paper flex items-center justify-center">
                        <i class="ti ti-device-mobile text-muted text-sm"></i>
                    </div>
                    <span class="absolute -bottom-0.5 -end-0.5 w-2.5 h-2.5 rounded-full border-2 border-card
                        {{ $device['status'] === 'connected' ? 'bg-signal' :
                           ($device['status'] === 'connecting' ? 'bg-amber' : 'bg-muted') }}">
                    </span>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-xs font-semibold text-text truncate">{{ $device['name'] }}</div>
                    <div class="text-[10px] text-muted">
                        {{ number_format($device['messages_sent_today'] ?? 0) }} {{ $isAr ? 'رسائل اليوم' : 'msgs today' }}
                    </div>
                </div>
                <span class="text-[10px] font-medium
                    {{ $device['status'] === 'connected' ? 'text-signal-deep' :
                       ($device['status'] === 'connecting' ? 'text-amber' : 'text-muted') }}">
                    {{ $statusLabel }}
                </span>
            </div>
        @empty
            <div class="text-center py-6">
                <i class="ti ti-device-mobile-off block text-2xl text-muted mb-2"></i>
                <p class="text-xs text-muted mb-3">{{ $isAr ? 'لا توجد أجهزة' : 'No devices connected' }}</p>
                <a href="{{ route('devices') }}"
                   class="text-xs font-semibold text-signal-deep hover:underline">
                    {{ $isAr ? '+ أضف جهازك الأول' : '+ Add your first device' }}
                </a>
            </div>
        @endforelse
    </div>
</div>

{{-- Quick Actions --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
    <a href="{{ route('bulk') }}"
       class="flex items-center gap-3 p-4 bg-card rounded-[14px] border border-line
              hover:border-signal hover:bg-signal-dim
              transition-all duration-150 group">
        <div class="w-9 h-9 rounded-xl bg-signal-dim flex items-center justify-center
                    group-hover:bg-signal transition-colors flex-shrink-0">
            <i class="ti ti-send text-signal-deep text-lg group-hover:text-[#06170F]"></i>
        </div>
        <div class="flex-1">
            <div class="text-sm font-semibold text-text">{{ $isAr ? 'إرسال جماعي' : 'Bulk send' }}</div>
            <div class="text-xs text-muted mt-0.5">{{ $isAr ? 'أرسل لعدة جهات' : 'Broadcast to multiple contacts' }}</div>
        </div>
        <i class="ti ti-chevron-right text-muted group-hover:text-signal rtl:rotate-180"></i>
    </a>
    <a href="{{ route('webhooks') }}"
       class="flex items-center gap-3 p-4 bg-card rounded-[14px] border border-line
              hover:border-signal hover:bg-signal-dim
              transition-all duration-150 group">
        <div class="w-9 h-9 rounded-xl bg-signal-dim flex items-center justify-center
                    group-hover:bg-signal transition-colors flex-shrink-0">
            <i class="ti ti-webhook text-signal-deep text-lg group-hover:text-[#06170F]"></i>
        </div>
        <div class="flex-1">
            <div class="text-sm font-semibold text-text">Webhooks</div>
            <div class="text-xs text-muted mt-0.5">{{ $isAr ? 'اضبط الاستدعاءات الفورية' : 'Configure real-time callbacks' }}</div>
        </div>
        <i class="ti ti-chevron-right text-muted group-hover:text-signal rtl:rotate-180"></i>
    </a>
    <a href="{{ route('api-keys') }}"
       class="flex items-center gap-3 p-4 bg-card rounded-[14px] border border-line
              hover:border-signal hover:bg-signal-dim
              transition-all duration-150 group">
        <div class="w-9 h-9 rounded-xl bg-signal-dim flex items-center justify-center
                    group-hover:bg-signal transition-colors flex-shrink-0">
            <i class="ti ti-key text-signal-deep text-lg group-hover:text-[#06170F]"></i>
        </div>
        <div class="flex-1">
            <div class="text-sm font-semibold text-text">{{ $isAr ? 'مفاتيح API' : 'API keys' }}</div>
            <div class="text-xs text-muted mt-0.5">{{ $isAr ? 'إدارة بيانات الاعتماد' : 'Manage your credentials' }}</div>
        </div>
        <i class="ti ti-chevron-right text-muted group-hover:text-signal rtl:rotate-180"></i>
    </a>
</div>
</div>
