<div>
@php $isAr = app()->getLocale() === 'ar'; @endphp

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

    <div class="lg:col-span-2 space-y-3">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold text-text">{{ $isAr ? 'Webhooks النشطة' : 'Active webhooks' }}</h2>
                <p class="text-xs text-muted mt-0.5">
                    {{ $webhooks->count() }} / {{ auth()->user()->plan?->max_webhooks ?? 3 }} {{ $isAr ? 'مستخدم' : 'used' }}
                </p>
            </div>
            <button wire:click="openCreate"
                    class="flex items-center gap-1.5 px-3 py-2 bg-signal text-[#06170F] text-sm font-medium rounded-lg hover:bg-[#37B879] transition-colors min-h-11">
                <i class="ti ti-plus text-base"></i> {{ $isAr ? 'إضافة Webhook' : 'Add webhook' }}
            </button>
        </div>

        @forelse ($webhooks as $webhook)
            <div class="bg-card rounded-[14px] border border-line p-4">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0
                        {{ $webhook->is_active ? 'bg-signal-dim' : 'bg-paper' }}">
                        <i class="ti ti-webhook text-sm {{ $webhook->is_active ? 'text-signal-deep' : 'text-muted' }}"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                            <span class="text-sm font-semibold text-text">{{ $webhook->name }}</span>
                            <span class="text-[10px] font-medium px-2 py-0.5 rounded-full
                                {{ $webhook->is_active ? 'bg-signal-dim text-signal-deep' : 'bg-paper text-muted' }}">
                                {{ $webhook->is_active ? ($isAr ? 'نشط' : 'active') : ($isAr ? 'متوقف' : 'paused') }}
                            </span>
                        </div>
                        <div class="text-xs font-mono text-muted truncate mb-2">{{ $webhook->url }}</div>
                        <div class="flex flex-wrap gap-1">
                            @foreach ($webhook->events as $event)
                                <span class="text-[10px] px-2 py-0.5 bg-signal-dim text-signal-deep rounded-full border border-signal/20">
                                    {{ $event }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 flex-shrink-0">
                        <div class="text-end text-[10px] text-muted me-2">
                            <div class="text-signal-deep font-medium">{{ number_format($webhook->success_count) }} OK</div>
                            <div class="text-danger">{{ number_format($webhook->failure_count) }} {{ $isAr ? 'فاشل' : 'failed' }}</div>
                        </div>
                        <button wire:click="inspectDeliveries({{ $webhook->id }})"
                                class="w-7 h-7 flex items-center justify-center border border-line rounded-lg hover:bg-paper transition-colors text-muted">
                            <i class="ti ti-list text-sm"></i>
                        </button>
                        <button wire:click="openEdit({{ $webhook->id }})"
                                class="w-7 h-7 flex items-center justify-center border border-line rounded-lg hover:bg-paper transition-colors text-muted">
                            <i class="ti ti-pencil text-sm"></i>
                        </button>
                        <button wire:click="toggleActive({{ $webhook->id }})"
                                class="w-7 h-7 flex items-center justify-center border border-line rounded-lg hover:bg-paper transition-colors
                                    {{ $webhook->is_active ? 'text-amber' : 'text-signal-deep' }}">
                            <i class="ti {{ $webhook->is_active ? 'ti-player-pause' : 'ti-player-play' }} text-sm"></i>
                        </button>
                        <button wire:click="delete({{ $webhook->id }})"
                                wire:confirm="{{ $isAr ? 'حذف هذا الـ webhook؟' : 'Delete this webhook?' }}"
                                class="w-7 h-7 flex items-center justify-center border border-danger/20 rounded-lg hover:bg-danger-dim transition-colors text-danger">
                            <i class="ti ti-trash text-sm"></i>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-card rounded-[14px] border border-dashed border-line p-10 text-center">
                <i class="ti ti-webhook block text-3xl text-muted mb-2"></i>
                <p class="text-sm font-medium text-text">{{ $isAr ? 'لا توجد webhooks' : 'No webhooks configured' }}</p>
                <p class="text-xs text-muted mt-1 mb-4">{{ $isAr ? 'اضبط webhooks لاستقبال الأحداث فورياً في تطبيقاتك' : 'Set up webhooks to receive real-time events in your apps' }}</p>
                <button wire:click="openCreate"
                        class="px-4 py-2 bg-signal text-[#06170F] text-sm font-medium rounded-lg hover:bg-[#37B879] transition-colors min-h-11">
                    {{ $isAr ? 'أضف أول webhook' : 'Add first webhook' }}
                </button>
            </div>
        @endforelse
    </div>

    <div class="bg-card rounded-[14px] border border-line p-4">
        @if ($inspectingWebhookId && $deliveries->count())
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-xs font-semibold text-text">{{ $isAr ? 'التسليمات الأخيرة' : 'Recent deliveries' }}</h3>
                <button wire:click="closeInspect" class="text-muted hover:text-text">
                    <i class="ti ti-x text-sm"></i>
                </button>
            </div>
            <div class="space-y-2">
                @foreach ($deliveries as $d)
                    <div class="p-2 rounded-lg border border-line text-[10px]">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-signal-deep font-medium">{{ $d->event }}</span>
                            <span class="font-medium {{ $d->success ? 'text-signal-deep' : 'text-danger' }}">
                                {{ $d->http_status ?? '—' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-muted">
                            <span>{{ $d->duration_ms ? $d->duration_ms.'ms' : '—' }}</span>
                            <span>{{ $d->created_at->format('H:i:s') }}</span>
                        </div>
                        @if (!$d->success && $d->response_body)
                            <div class="mt-1 text-danger truncate">{{ Str::limit($d->response_body, 60) }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8 text-muted">
                <i class="ti ti-list-check block text-2xl mb-2"></i>
                <p class="text-xs">{!! $isAr ? 'اختر webhook<br>لعرض التسليمات' : 'Select a webhook<br>to view deliveries' !!}</p>
            </div>
        @endif
    </div>
</div>

@if ($showForm)
    <div class="fixed inset-0 bg-ink/50 flex items-center justify-center z-50 p-4" wire:click.self="$set('showForm',false)">
        <div class="bg-card rounded-[14px] w-[480px] max-w-full p-6 shadow-xl border border-line">
            <h3 class="text-sm font-semibold text-text mb-4">
                {{ $editingId ? ($isAr ? 'تعديل webhook' : 'Edit webhook') : ($isAr ? 'إضافة webhook' : 'Add webhook') }}
            </h3>

            <div class="mb-4">
                <label class="block text-xs font-medium text-muted mb-1.5">{{ $isAr ? 'الاسم' : 'Name' }}</label>
                <input wire:model="name" type="text" placeholder="{{ $isAr ? 'مثال: تكامل نظام CRM' : 'e.g. My CRM integration' }}"
                       class="w-full border border-line rounded-lg px-3 py-2 text-base outline-none min-h-11 focus:border-signal focus:ring-2 focus:ring-signal/20" />
                @error('name') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-xs font-medium text-muted mb-1.5">{{ $isAr ? 'رابط نقطة النهاية' : 'Endpoint URL' }}</label>
                <input wire:model="url" type="url" placeholder="https://yourapp.com/webhook/wa"
                       class="w-full border border-line rounded-lg px-3 py-2 text-base outline-none min-h-11 focus:border-signal focus:ring-2 focus:ring-signal/20 font-mono" />
                @error('url') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-5">
                <label class="block text-xs font-medium text-muted mb-2">{{ $isAr ? 'الأحداث للاشتراك' : 'Events to subscribe' }}</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    @foreach ($availableEvents as $event)
                        <label class="flex items-center gap-2 p-2 rounded-lg border border-line hover:border-signal cursor-pointer transition-colors
                            {{ in_array($event, $events) ? 'border-signal bg-signal-dim' : '' }}">
                            <input type="checkbox" wire:model="events" value="{{ $event }}"
                                   class="w-3.5 h-3.5 accent-[#2FA66B]" />
                            <span class="text-[11px] text-text">{{ $event }}</span>
                        </label>
                    @endforeach
                </div>
                @error('events') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex gap-3">
                <button wire:click="$set('showForm',false)"
                        class="flex-1 py-2.5 border border-line rounded-xl text-sm text-muted hover:bg-paper transition-colors min-h-11">
                    {{ $isAr ? 'إلغاء' : 'Cancel' }}
                </button>
                <button wire:click="save"
                        class="flex-1 py-2.5 bg-signal text-[#06170F] text-sm font-semibold rounded-xl hover:bg-[#37B879] transition-colors min-h-11">
                    {{ $editingId ? ($isAr ? 'حفظ التغييرات' : 'Save changes') : ($isAr ? 'إنشاء webhook' : 'Create webhook') }}
                </button>
            </div>
        </div>
    </div>
@endif
</div>
