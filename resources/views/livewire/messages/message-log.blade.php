<x-layouts.app title="Messages">

    {{-- Summary bar --}}
    <div class="flex items-center gap-4 mb-4 p-3 bg-white rounded-xl border border-gray-100">
        <div class="text-xs text-gray-500">Today: <span class="font-semibold text-gray-900">{{ number_format($summary['total']) }}</span></div>
        <div class="text-xs text-gray-500">Sent: <span class="font-semibold text-green-700">{{ number_format($summary['sent']) }}</span></div>
        <div class="text-xs text-gray-500">Failed: <span class="font-semibold text-red-600">{{ number_format($summary['failed']) }}</span></div>
        <div class="ml-auto flex items-center gap-2">
            <button class="flex items-center gap-1.5 px-3 py-1.5 border border-gray-200 rounded-lg text-xs text-gray-600 hover:bg-gray-50 transition-colors">
                <i class="ti ti-download text-sm"></i> Export CSV
            </button>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl border border-gray-100 overflow-hidden mb-0">

        <div class="flex items-center gap-2 p-3 border-b border-gray-100 flex-wrap">

            {{-- Search --}}
            <div class="flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-lg px-3 py-1.5 w-52">
                <i class="ti ti-search text-gray-400 text-sm"></i>
                <input wire:model.live.debounce.400ms="search" type="text"
                       placeholder="Search number or content…"
                       class="bg-transparent text-xs text-gray-700 outline-none w-full placeholder-gray-400" />
            </div>

            {{-- Status filter --}}
            <select wire:model.live="statusFilter"
                    class="border border-gray-200 rounded-lg px-3 py-1.5 text-xs text-gray-700 outline-none bg-white">
                <option value="">All statuses</option>
                <option value="queued">Queued</option>
                <option value="sent">Sent</option>
                <option value="delivered">Delivered</option>
                <option value="read">Read</option>
                <option value="failed">Failed</option>
            </select>

            {{-- Type filter --}}
            <select wire:model.live="typeFilter"
                    class="border border-gray-200 rounded-lg px-3 py-1.5 text-xs text-gray-700 outline-none bg-white">
                <option value="">All types</option>
                <option value="text">Text</option>
                <option value="image">Image</option>
                <option value="document">Document</option>
                <option value="audio">Audio</option>
                <option value="video">Video</option>
                <option value="location">Location</option>
            </select>

            @if ($search || $statusFilter || $typeFilter)
                <button wire:click="resetFilters"
                        class="text-xs text-gray-400 hover:text-red-500 transition-colors flex items-center gap-1">
                    <i class="ti ti-x text-sm"></i> Clear
                </button>
            @endif

            <div class="ml-auto text-xs text-gray-400">
                {{ $messages->total() }} result(s)
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="text-left px-4 py-2.5 text-gray-500 font-medium">To</th>
                        <th class="text-left px-4 py-2.5 text-gray-500 font-medium">Message</th>
                        <th class="text-left px-4 py-2.5 text-gray-500 font-medium">Device</th>
                        <th class="text-left px-4 py-2.5 text-gray-500 font-medium">Type</th>
                        <th class="text-left px-4 py-2.5 text-gray-500 font-medium">Status</th>
                        <th class="text-left px-4 py-2.5 text-gray-500 font-medium">Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($messages as $msg)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-2.5 font-medium text-gray-900">{{ $msg->to_number }}</td>
                            <td class="px-4 py-2.5 text-gray-500 max-w-[200px]">
                                <span class="block truncate">
                                    {{ $msg->content['body'] ?? ($msg->content['caption'] ?? '[' . $msg->type->value . ']') }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-gray-500">{{ $msg->device?->name ?? '—' }}</td>
                            <td class="px-4 py-2.5">
                                <span class="px-2 py-0.5 rounded-full text-[10px] bg-gray-100 text-gray-600 font-medium">
                                    {{ $msg->type->value }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5">
                                @php
                                    $badgeClass = match($msg->status->value) {
                                        'read'      => 'bg-purple-100 text-purple-700',
                                        'delivered' => 'bg-blue-100 text-blue-700',
                                        'sent'      => 'bg-green-100 text-green-700',
                                        'failed'    => 'bg-red-100 text-red-600',
                                        'queued'    => 'bg-yellow-100 text-yellow-700',
                                        default     => 'bg-gray-100 text-gray-600',
                                    };
                                @endphp
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-medium {{ $badgeClass }}">
                                    {{ $msg->status->value }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-gray-400">
                                {{ $msg->created_at->format('H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-gray-400">
                                <i class="ti ti-message-off block text-2xl mb-2"></i>
                                No messages found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($messages->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">
                {{ $messages->links() }}
            </div>
        @endif
    </div>

</x-layouts.app>
