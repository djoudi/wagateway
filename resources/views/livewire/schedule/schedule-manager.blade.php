<div>
{{-- Stats bar --}}
<div class="flex items-center gap-3 mb-5">
    @foreach ([
        'pending'   => ['label' => 'Pending',   'color' => 'yellow'],
        'sent'      => ['label' => 'Sent',       'color' => 'green'],
        'failed'    => ['label' => 'Failed',     'color' => 'red'],
        'cancelled' => ['label' => 'Cancelled',  'color' => 'gray'],
    ] as $status => $meta)
        <button wire:click="$set('statusFilter','{{ $status }}')"
                class="flex items-center gap-2 px-4 py-2 rounded-xl border text-sm font-medium transition-colors
                    {{ $statusFilter === $status
                        ? 'border-' . $meta['color'] . '-300 bg-' . $meta['color'] . '-50 text-' . $meta['color'] . '-700'
                        : 'border-gray-100 bg-white text-gray-600 hover:border-gray-200' }}">
            <span class="text-base font-bold">{{ $counts[$status] }}</span>
            {{ $meta['label'] }}
        </button>
    @endforeach

    <div class="ml-auto">
        <button wire:click="openForm"
                class="flex items-center gap-2 px-4 py-2 bg-[#25D366] text-white text-sm font-semibold rounded-xl hover:bg-green-600 transition-colors shadow-sm">
            <i class="ti ti-calendar-plus"></i> Schedule message
        </button>
    </div>
</div>

{{-- Table --}}
<div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
    @if ($scheduled->isEmpty())
        <div class="text-center py-16 text-gray-400">
            <i class="ti ti-calendar-off block text-3xl mb-2"></i>
            <p class="text-sm font-medium text-gray-600">No {{ $statusFilter }} messages</p>
            @if ($statusFilter === 'pending')
                <p class="text-xs mt-1 mb-4">Schedule your first message to send it automatically</p>
                <button wire:click="openForm"
                        class="px-4 py-2 bg-[#25D366] text-white text-sm font-medium rounded-lg hover:bg-green-600 transition-colors">
                    Schedule a message
                </button>
            @endif
        </div>
    @else
        <table class="w-full text-xs">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-100">
                    <th class="text-left px-4 py-3 font-medium text-gray-500">To</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500">Message</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500">Device</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500">Type</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500">Scheduled for</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach ($scheduled as $item)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $item->to_number }}</td>
                        <td class="px-4 py-3 text-gray-500 max-w-[200px]">
                            <span class="block truncate">
                                {{ $item->message_data['body'] ?? ($item->message_data['caption'] ?? '[' . ($item->message_data['type'] ?? 'media') . ']') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $item->device?->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 bg-gray-100 text-gray-600 rounded-full font-medium">
                                {{ $item->message_data['type'] ?? 'text' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-700">
                            <div class="font-medium">{{ $item->scheduled_at->format('d M Y') }}</div>
                            <div class="text-gray-400">{{ $item->scheduled_at->format('H:i') }}</div>
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $badge = match($item->status) {
                                    'pending'   => 'bg-yellow-100 text-yellow-700',
                                    'sent'      => 'bg-green-100 text-green-700',
                                    'failed'    => 'bg-red-100 text-red-600',
                                    'cancelled' => 'bg-gray-100 text-gray-500',
                                    default     => 'bg-gray-100 text-gray-500',
                                };
                            @endphp
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $badge }}">
                                {{ $item->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @if ($item->status === 'pending')
                                <button wire:click="cancel({{ $item->id }})"
                                        wire:confirm="Cancel this scheduled message?"
                                        class="w-7 h-7 flex items-center justify-center border border-red-100 text-red-400 hover:bg-red-50 rounded-lg transition-colors">
                                    <i class="ti ti-x text-sm"></i>
                                </button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if ($scheduled->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">
                {{ $scheduled->links() }}
            </div>
        @endif
    @endif
</div>

{{-- Schedule form modal --}}
@if ($showForm)
    <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50" wire:click.self="$set('showForm',false)">
        <div class="bg-white rounded-2xl w-[440px] p-6 shadow-xl max-h-[90vh] overflow-y-auto">
            <h3 class="text-sm font-semibold text-gray-900 mb-5">Schedule a message</h3>

            {{-- Device --}}
            <div class="mb-4">
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Device <span class="text-red-400">*</span></label>
                <select wire:model="selectedDevice"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#25D366] bg-white">
                    <option value="">Select device…</option>
                    @foreach ($devices as $d)
                        <option value="{{ $d->uuid }}">{{ $d->name }} · {{ $d->phone_number ?? 'not connected' }}</option>
                    @endforeach
                </select>
                @error('selectedDevice') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- To --}}
            <div class="mb-4">
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Recipient number <span class="text-red-400">*</span></label>
                <input wire:model="toNumber" type="text" placeholder="213700000001"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono outline-none focus:border-[#25D366]" />
                @error('toNumber') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Type --}}
            <div class="mb-4">
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Message type</label>
                <div class="flex gap-2 flex-wrap">
                    @foreach (['text','image','document','audio','video'] as $t)
                        <button wire:click="$set('messageType','{{ $t }}')"
                                class="px-3 py-1 rounded-lg text-xs font-medium border transition-all
                                    {{ $messageType === $t ? 'bg-[#25D366] text-white border-[#25D366]' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300' }}">
                            {{ ucfirst($t) }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Body or URL --}}
            @if ($messageType === 'text')
                <div class="mb-4">
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Message <span class="text-red-400">*</span></label>
                    <textarea wire:model="messageBody" rows="3"
                              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#25D366] resize-y"></textarea>
                    @error('messageBody') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            @else
                <div class="mb-4">
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Media URL <span class="text-red-400">*</span></label>
                    <input wire:model="mediaUrl" type="url" placeholder="https://example.com/file"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#25D366]" />
                    @error('mediaUrl') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="mb-4">
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Caption (optional)</label>
                    <input wire:model="mediaCaption" type="text"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#25D366]" />
                </div>
            @endif

            {{-- Scheduled at --}}
            <div class="mb-5">
                <label class="block text-xs font-medium text-gray-600 mb-1.5">
                    Send at <span class="text-red-400">*</span>
                </label>
                <input wire:model="scheduledAt" type="datetime-local"
                       min="{{ now()->addMinutes(5)->format('Y-m-d\TH:i') }}"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#25D366]" />
                @error('scheduledAt') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex gap-3">
                <button wire:click="$set('showForm',false)"
                        class="flex-1 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button wire:click="save"
                        class="flex-1 py-2.5 bg-[#25D366] text-white text-sm font-semibold rounded-xl hover:bg-green-600 transition-colors">
                    <span wire:loading.remove wire:target="save">Schedule</span>
                    <span wire:loading wire:target="save">Saving…</span>
                </button>
            </div>
        </div>
    </div>
@endif
</div>
