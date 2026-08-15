<div>
<div class="grid grid-cols-3 gap-4">

    {{-- ── Webhooks list ───────────────────────────────────────────────────── --}}
    <div class="col-span-2 space-y-3">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Active webhooks</h2>
                <p class="text-xs text-gray-400 mt-0.5">
                    {{ $webhooks->count() }} / {{ auth()->user()->plan?->max_webhooks ?? 3 }} used
                </p>
            </div>
            <button wire:click="openCreate"
                    class="flex items-center gap-1.5 px-3 py-2 bg-[#25D366] text-white text-sm font-medium rounded-lg hover:bg-green-600 transition-colors">
                <i class="ti ti-plus text-base"></i> Add webhook
            </button>
        </div>

        @forelse ($webhooks as $webhook)
            <div class="bg-white rounded-xl border border-gray-100 p-4">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0
                        {{ $webhook->is_active ? 'bg-green-100' : 'bg-gray-100' }}">
                        <i class="ti ti-webhook text-sm {{ $webhook->is_active ? 'text-green-700' : 'text-gray-400' }}"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                            <span class="text-sm font-semibold text-gray-900">{{ $webhook->name }}</span>
                            <span class="text-[10px] font-medium px-2 py-0.5 rounded-full
                                {{ $webhook->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $webhook->is_active ? 'active' : 'paused' }}
                            </span>
                        </div>
                        <div class="text-xs font-mono text-gray-400 truncate mb-2">{{ $webhook->url }}</div>
                        <div class="flex flex-wrap gap-1">
                            @foreach ($webhook->events as $event)
                                <span class="text-[10px] px-2 py-0.5 bg-blue-50 text-blue-700 rounded-full border border-blue-100">
                                    {{ $event }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 flex-shrink-0">
                        <div class="text-right text-[10px] text-gray-400 mr-2">
                            <div class="text-green-600 font-medium">{{ number_format($webhook->success_count) }} OK</div>
                            <div class="text-red-500">{{ number_format($webhook->failure_count) }} failed</div>
                        </div>
                        <button wire:click="inspectDeliveries({{ $webhook->id }})"
                                class="w-7 h-7 flex items-center justify-center border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors text-gray-400">
                            <i class="ti ti-list text-sm"></i>
                        </button>
                        <button wire:click="openEdit({{ $webhook->id }})"
                                class="w-7 h-7 flex items-center justify-center border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors text-gray-400">
                            <i class="ti ti-pencil text-sm"></i>
                        </button>
                        <button wire:click="toggleActive({{ $webhook->id }})"
                                class="w-7 h-7 flex items-center justify-center border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors
                                    {{ $webhook->is_active ? 'text-amber-500' : 'text-green-600' }}">
                            <i class="ti {{ $webhook->is_active ? 'ti-player-pause' : 'ti-player-play' }} text-sm"></i>
                        </button>
                        <button wire:click="delete({{ $webhook->id }})"
                                wire:confirm="Delete this webhook?"
                                class="w-7 h-7 flex items-center justify-center border border-red-100 rounded-lg hover:bg-red-50 transition-colors text-red-400">
                            <i class="ti ti-trash text-sm"></i>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl border border-dashed border-gray-200 p-10 text-center">
                <i class="ti ti-webhook block text-3xl text-gray-300 mb-2"></i>
                <p class="text-sm font-medium text-gray-600">No webhooks configured</p>
                <p class="text-xs text-gray-400 mt-1 mb-4">Set up webhooks to receive real-time events in your apps</p>
                <button wire:click="openCreate"
                        class="px-4 py-2 bg-[#25D366] text-white text-sm font-medium rounded-lg hover:bg-green-600 transition-colors">
                    Add first webhook
                </button>
            </div>
        @endforelse
    </div>

    {{-- ── Delivery log sidebar ────────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl border border-gray-100 p-4">
        @if ($inspectingWebhookId && $deliveries->count())
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-xs font-semibold text-gray-900">Recent deliveries</h3>
                <button wire:click="closeInspect" class="text-gray-400 hover:text-gray-600">
                    <i class="ti ti-x text-sm"></i>
                </button>
            </div>
            <div class="space-y-2">
                @foreach ($deliveries as $d)
                    <div class="p-2 rounded-lg border border-gray-100 text-[10px]">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-blue-600 font-medium">{{ $d->event }}</span>
                            <span class="font-medium {{ $d->success ? 'text-green-600' : 'text-red-500' }}">
                                {{ $d->http_status ?? '—' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-gray-400">
                            <span>{{ $d->duration_ms ? $d->duration_ms.'ms' : '—' }}</span>
                            <span>{{ $d->created_at->format('H:i:s') }}</span>
                        </div>
                        @if (!$d->success && $d->response_body)
                            <div class="mt-1 text-red-400 truncate">{{ Str::limit($d->response_body, 60) }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8 text-gray-400">
                <i class="ti ti-list-check block text-2xl mb-2"></i>
                <p class="text-xs">Select a webhook<br>to view deliveries</p>
            </div>
        @endif
    </div>
</div>

{{-- ── Add / Edit Modal ────────────────────────────────────────────────── --}}
@if ($showForm)
    <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50" wire:click.self="$set('showForm',false)">
        <div class="bg-white rounded-2xl w-[480px] p-6 shadow-xl">
            <h3 class="text-sm font-semibold text-gray-900 mb-4">
                {{ $editingId ? 'Edit webhook' : 'Add webhook' }}
            </h3>

            <div class="mb-4">
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Name</label>
                <input wire:model="name" type="text" placeholder="e.g. My CRM integration"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#25D366] focus:ring-1 focus:ring-[#25D366]/30" />
                @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Endpoint URL</label>
                <input wire:model="url" type="url" placeholder="https://yourapp.com/webhook/wa"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#25D366] focus:ring-1 focus:ring-[#25D366]/30 font-mono" />
                @error('url') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-5">
                <label class="block text-xs font-medium text-gray-600 mb-2">Events to subscribe</label>
                <div class="grid grid-cols-2 gap-2">
                    @foreach ($availableEvents as $event)
                        <label class="flex items-center gap-2 p-2 rounded-lg border border-gray-100 hover:border-green-200 cursor-pointer transition-colors
                            {{ in_array($event, $events) ? 'border-green-200 bg-green-50' : '' }}">
                            <input type="checkbox" wire:model="events" value="{{ $event }}"
                                   class="w-3.5 h-3.5 accent-[#25D366]" />
                            <span class="text-[11px] text-gray-700">{{ $event }}</span>
                        </label>
                    @endforeach
                </div>
                @error('events') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex gap-3">
                <button wire:click="$set('showForm',false)"
                        class="flex-1 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button wire:click="save"
                        class="flex-1 py-2.5 bg-[#25D366] text-white text-sm font-semibold rounded-xl hover:bg-green-600 transition-colors">
                    {{ $editingId ? 'Save changes' : 'Create webhook' }}
                </button>
            </div>
        </div>
    </div>
@endif
</div>
