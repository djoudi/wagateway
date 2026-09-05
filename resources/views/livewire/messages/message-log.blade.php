<div>
@php $isAr = app()->getLocale() === 'ar'; @endphp

    <div class="flex flex-wrap items-center gap-4 mb-4 p-3 bg-card rounded-[14px] border border-line">
        <div class="text-xs text-muted">{{ $isAr ? 'اليوم:' : 'Today:' }} <span class="font-semibold text-text">{{ number_format($summary['total']) }}</span></div>
        <div class="text-xs text-muted">{{ $isAr ? 'مُرسلة:' : 'Sent:' }} <span class="font-semibold text-signal-deep">{{ number_format($summary['sent']) }}</span></div>
        <div class="text-xs text-muted">{{ $isAr ? 'فاشلة:' : 'Failed:' }} <span class="font-semibold text-danger">{{ number_format($summary['failed']) }}</span></div>
    </div>

    <div class="bg-card rounded-[14px] border border-line overflow-hidden mb-0">

        <div class="flex items-center gap-2 p-3 border-b border-line flex-wrap">

            <div class="flex items-center gap-2 bg-paper border border-line rounded-lg px-3 py-1.5 w-full sm:w-52 min-h-11">
                <i class="ti ti-search text-muted text-sm"></i>
                <input wire:model.live.debounce.400ms="search" type="text"
                       placeholder="{{ $isAr ? 'ابحث بالرقم أو المحتوى…' : 'Search number or content…' }}"
                       class="bg-transparent text-base sm:text-xs text-text outline-none w-full placeholder:text-muted" />
            </div>

            <select wire:model.live="statusFilter"
                    class="border border-line rounded-lg px-3 py-1.5 text-xs text-text outline-none bg-card min-h-11">
                <option value="">{{ $isAr ? 'كل الحالات' : 'All statuses' }}</option>
                <option value="queued">{{ $isAr ? 'في الانتظار' : 'Queued' }}</option>
                <option value="sent">{{ $isAr ? 'مُرسلة' : 'Sent' }}</option>
                <option value="delivered">{{ $isAr ? 'مُسلَّمة' : 'Delivered' }}</option>
                <option value="read">{{ $isAr ? 'مقروءة' : 'Read' }}</option>
                <option value="failed">{{ $isAr ? 'فاشلة' : 'Failed' }}</option>
            </select>

            <select wire:model.live="typeFilter"
                    class="border border-line rounded-lg px-3 py-1.5 text-xs text-text outline-none bg-card min-h-11">
                <option value="">{{ $isAr ? 'كل الأنواع' : 'All types' }}</option>
                <option value="text">{{ $isAr ? 'نص' : 'Text' }}</option>
                <option value="image">{{ $isAr ? 'صورة' : 'Image' }}</option>
                <option value="document">{{ $isAr ? 'مستند' : 'Document' }}</option>
                <option value="audio">{{ $isAr ? 'صوت' : 'Audio' }}</option>
                <option value="video">{{ $isAr ? 'فيديو' : 'Video' }}</option>
                <option value="location">{{ $isAr ? 'موقع' : 'Location' }}</option>
            </select>

            @if ($search || $statusFilter || $typeFilter)
                <button wire:click="resetFilters"
                        class="text-xs text-muted hover:text-danger transition-colors flex items-center gap-1 min-h-11">
                    <i class="ti ti-x text-sm"></i> {{ $isAr ? 'مسح' : 'Clear' }}
                </button>
            @endif

            <div class="ms-auto text-xs text-muted">
                {{ $messages->total() }} {{ $isAr ? 'نتيجة' : 'result(s)' }}
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="bg-paper border-b border-line">
                        <th class="text-start px-4 py-2.5 text-muted font-medium">{{ $isAr ? 'إلى' : 'To' }}</th>
                        <th class="text-start px-4 py-2.5 text-muted font-medium">{{ $isAr ? 'الرسالة' : 'Message' }}</th>
                        <th class="text-start px-4 py-2.5 text-muted font-medium">{{ $isAr ? 'الجهاز' : 'Device' }}</th>
                        <th class="text-start px-4 py-2.5 text-muted font-medium">{{ $isAr ? 'النوع' : 'Type' }}</th>
                        <th class="text-start px-4 py-2.5 text-muted font-medium">{{ $isAr ? 'الحالة' : 'Status' }}</th>
                        <th class="text-start px-4 py-2.5 text-muted font-medium">{{ $isAr ? 'الوقت' : 'Time' }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($messages as $msg)
                        <tr class="hover:bg-paper transition-colors">
                            <td class="px-4 py-2.5 font-medium text-text">{{ $msg->to_number }}</td>
                            <td class="px-4 py-2.5 text-muted max-w-[200px]">
                                <span class="block truncate">
                                    {{ $msg->content['body'] ?? ($msg->content['caption'] ?? '[' . $msg->type->value . ']') }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-muted">{{ $msg->device?->name ?? '—' }}</td>
                            <td class="px-4 py-2.5">
                                <span class="px-2 py-0.5 rounded-full text-[10px] bg-paper text-muted font-medium">
                                    {{ $msg->type->value }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5">
                                @php
                                    $badgeClass = match($msg->status->value) {
                                        'read'      => 'bg-signal-dim text-signal-deep',
                                        'delivered' => 'bg-signal-dim text-signal-deep',
                                        'sent'      => 'bg-signal-dim text-signal-deep',
                                        'failed'    => 'bg-danger-dim text-danger',
                                        'queued'    => 'bg-amber/20 text-amber',
                                        default     => 'bg-paper text-muted',
                                    };
                                @endphp
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-medium {{ $badgeClass }}">
                                    {{ $msg->status->value }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-muted">
                                {{ $msg->created_at->format('H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-muted">
                                <i class="ti ti-message-off block text-2xl mb-2"></i>
                                {{ $isAr ? 'لا توجد رسائل' : 'No messages found' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($messages->hasPages())
            <div class="px-4 py-3 border-t border-line">
                {{ $messages->links() }}
            </div>
        @endif
    </div>
</div>
