<div>
@php $isAr = app()->getLocale() === 'ar'; @endphp

<div class="flex flex-wrap items-center gap-3 mb-5">
    @foreach ([
        'pending'   => ['label' => $isAr ? 'قيد الانتظار' : 'Pending',   'active' => 'border-amber bg-amber/10 text-amber'],
        'sent'      => ['label' => $isAr ? 'مُرسلة' : 'Sent',       'active' => 'border-signal bg-signal-dim text-signal-deep'],
        'failed'    => ['label' => $isAr ? 'فاشلة' : 'Failed',     'active' => 'border-danger bg-danger-dim text-danger'],
        'cancelled' => ['label' => $isAr ? 'ملغاة' : 'Cancelled',  'active' => 'border-line bg-paper text-muted'],
    ] as $status => $meta)
        <button wire:click="$set('statusFilter','{{ $status }}')"
                class="flex items-center gap-2 px-4 py-2 rounded-xl border text-sm font-medium transition-colors min-h-11
                    {{ $statusFilter === $status
                        ? $meta['active']
                        : 'border-line bg-card text-muted hover:border-muted' }}">
            <span class="text-base font-bold">{{ $counts[$status] }}</span>
            {{ $meta['label'] }}
        </button>
    @endforeach

    <div class="ms-auto">
        <button wire:click="openForm"
                class="flex items-center gap-2 px-4 py-2 bg-signal text-[#06170F] text-sm font-semibold rounded-xl hover:bg-[#37B879] transition-colors shadow-sm min-h-11">
            <i class="ti ti-calendar-plus"></i> {{ $isAr ? 'جدولة رسالة' : 'Schedule message' }}
        </button>
    </div>
</div>

<div class="bg-card rounded-[14px] border border-line overflow-hidden">
    @if ($scheduled->isEmpty())
        <div class="text-center py-16 text-muted">
            <i class="ti ti-calendar-off block text-3xl mb-2"></i>
            <p class="text-sm font-medium text-text">{{ $isAr ? 'لا توجد رسائل' : 'No' }} {{ $statusFilter }} {{ $isAr ? '' : 'messages' }}</p>
            @if ($statusFilter === 'pending')
                <p class="text-xs mt-1 mb-4">{{ $isAr ? 'جدول رسالتك الأولى ليتم إرسالها تلقائياً' : 'Schedule your first message to send it automatically' }}</p>
                <button wire:click="openForm"
                        class="px-4 py-2 bg-signal text-[#06170F] text-sm font-medium rounded-lg hover:bg-[#37B879] transition-colors min-h-11">
                    {{ $isAr ? 'جدولة رسالة' : 'Schedule a message' }}
                </button>
            @endif
        </div>
    @else
        <div class="overflow-x-auto">
        <table class="w-full text-xs">
            <thead>
                <tr class="bg-paper border-b border-line">
                    <th class="text-start px-4 py-3 font-medium text-muted">{{ $isAr ? 'إلى' : 'To' }}</th>
                    <th class="text-start px-4 py-3 font-medium text-muted">{{ $isAr ? 'الرسالة' : 'Message' }}</th>
                    <th class="text-start px-4 py-3 font-medium text-muted">{{ $isAr ? 'الجهاز' : 'Device' }}</th>
                    <th class="text-start px-4 py-3 font-medium text-muted">{{ $isAr ? 'النوع' : 'Type' }}</th>
                    <th class="text-start px-4 py-3 font-medium text-muted">{{ $isAr ? 'مجدولة لـ' : 'Scheduled for' }}</th>
                    <th class="text-start px-4 py-3 font-medium text-muted">{{ $isAr ? 'الحالة' : 'Status' }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @foreach ($scheduled as $item)
                    <tr class="hover:bg-paper transition-colors">
                        <td class="px-4 py-3 font-medium text-text">{{ $item->to_number }}</td>
                        <td class="px-4 py-3 text-muted max-w-[200px]">
                            <span class="block truncate">
                                {{ $item->message_data['body'] ?? ($item->message_data['caption'] ?? '[' . ($item->message_data['type'] ?? 'media') . ']') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-muted">{{ $item->device?->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 bg-paper text-muted rounded-full font-medium">
                                {{ $item->message_data['type'] ?? 'text' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-text">
                            <div class="font-medium">{{ $item->scheduled_at->format('d M Y') }}</div>
                            <div class="text-muted">{{ $item->scheduled_at->format('H:i') }}</div>
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $badge = match($item->status) {
                                    'pending'   => 'bg-amber/20 text-amber',
                                    'sent'      => 'bg-signal-dim text-signal-deep',
                                    'failed'    => 'bg-danger-dim text-danger',
                                    'cancelled' => 'bg-paper text-muted',
                                    default     => 'bg-paper text-muted',
                                };
                            @endphp
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $badge }}">
                                {{ $item->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @if ($item->status === 'pending')
                                <button wire:click="cancel({{ $item->id }})"
                                        wire:confirm="{{ $isAr ? 'إلغاء هذه الرسالة المجدولة؟' : 'Cancel this scheduled message?' }}"
                                        class="w-7 h-7 flex items-center justify-center border border-danger/20 text-danger hover:bg-danger-dim rounded-lg transition-colors">
                                    <i class="ti ti-x text-sm"></i>
                                </button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>

        @if ($scheduled->hasPages())
            <div class="px-4 py-3 border-t border-line">
                {{ $scheduled->links() }}
            </div>
        @endif
    @endif
</div>

@if ($showForm)
    <div class="fixed inset-0 bg-ink/50 flex items-center justify-center z-50 p-4" wire:click.self="$set('showForm',false)">
        <div class="bg-card rounded-[14px] w-[440px] max-w-full p-6 shadow-xl max-h-[90vh] overflow-y-auto border border-line">
            <h3 class="text-sm font-semibold text-text mb-5">{{ $isAr ? 'جدولة رسالة' : 'Schedule a message' }}</h3>

            <div class="mb-4">
                <label class="block text-xs font-medium text-muted mb-1.5">{{ $isAr ? 'الجهاز' : 'Device' }} <span class="text-danger">*</span></label>
                <select wire:model="selectedDevice"
                        class="w-full border border-line rounded-lg px-3 py-2 text-base outline-none focus:border-signal bg-card min-h-11">
                    <option value="">{{ $isAr ? 'اختر جهازاً…' : 'Select device…' }}</option>
                    @foreach ($devices as $d)
                        <option value="{{ $d->uuid }}">{{ $d->name }} · {{ $d->phone_number ?? ($isAr ? 'غير متصل' : 'not connected') }}</option>
                    @endforeach
                </select>
                @error('selectedDevice') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-xs font-medium text-muted mb-1.5">{{ $isAr ? 'رقم المستلم' : 'Recipient number' }} <span class="text-danger">*</span></label>
                <input wire:model="toNumber" type="text" placeholder="213700000001"
                       class="w-full border border-line rounded-lg px-3 py-2 text-base font-mono outline-none focus:border-signal min-h-11" />
                @error('toNumber') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-xs font-medium text-muted mb-1.5">{{ $isAr ? 'نوع الرسالة' : 'Message type' }}</label>
                <div class="flex gap-2 flex-wrap">
                    @foreach (['text','image','document','audio','video'] as $t)
                        <button wire:click="$set('messageType','{{ $t }}')"
                                class="px-3 py-1 rounded-lg text-xs font-medium border transition-all min-h-11
                                    {{ $messageType === $t ? 'bg-signal text-[#06170F] border-signal' : 'bg-card text-muted border-line hover:border-muted' }}">
                            {{ ucfirst($t) }}
                        </button>
                    @endforeach
                </div>
            </div>

            @if ($messageType === 'text')
                <div class="mb-4">
                    <label class="block text-xs font-medium text-muted mb-1.5">{{ $isAr ? 'الرسالة' : 'Message' }} <span class="text-danger">*</span></label>
                    <textarea wire:model="messageBody" rows="3"
                              class="w-full border border-line rounded-lg px-3 py-2 text-base outline-none focus:border-signal resize-y"></textarea>
                    @error('messageBody') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
                </div>
            @else
                <div class="mb-4">
                    <label class="block text-xs font-medium text-muted mb-1.5">{{ $isAr ? 'رابط الوسائط' : 'Media URL' }} <span class="text-danger">*</span></label>
                    <input wire:model="mediaUrl" type="url" placeholder="https://example.com/file"
                           class="w-full border border-line rounded-lg px-3 py-2 text-base outline-none focus:border-signal min-h-11" />
                    @error('mediaUrl') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="mb-4">
                    <label class="block text-xs font-medium text-muted mb-1.5">{{ $isAr ? 'التعليق (اختياري)' : 'Caption (optional)' }}</label>
                    <input wire:model="mediaCaption" type="text"
                           class="w-full border border-line rounded-lg px-3 py-2 text-base outline-none focus:border-signal min-h-11" />
                </div>
            @endif

            <div class="mb-5">
                <label class="block text-xs font-medium text-muted mb-1.5">
                    {{ $isAr ? 'أرسل في' : 'Send at' }} <span class="text-danger">*</span>
                </label>
                <input wire:model="scheduledAt" type="datetime-local"
                       min="{{ now()->addMinutes(5)->format('Y-m-d\TH:i') }}"
                       class="w-full border border-line rounded-lg px-3 py-2 text-base outline-none focus:border-signal min-h-11" />
                @error('scheduledAt') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex gap-3">
                <button wire:click="$set('showForm',false)"
                        class="flex-1 py-2.5 border border-line rounded-xl text-sm text-muted hover:bg-paper transition-colors min-h-11">
                    {{ $isAr ? 'إلغاء' : 'Cancel' }}
                </button>
                <button wire:click="save"
                        class="flex-1 py-2.5 bg-signal text-[#06170F] text-sm font-semibold rounded-xl hover:bg-[#37B879] transition-colors min-h-11">
                    <span wire:loading.remove wire:target="save">{{ $isAr ? 'جدولة' : 'Schedule' }}</span>
                    <span wire:loading wire:target="save">{{ $isAr ? 'جارٍ الحفظ…' : 'Saving…' }}</span>
                </button>
            </div>
        </div>
    </div>
@endif
</div>
