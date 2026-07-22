<x-layouts.app title="Bulk Send">

{{-- ── Live Progress Banner ────────────────────────────────────────────────── --}}
@if ($activeJobUuid && in_array($activeJobStatus, ['pending','running']))
<div class="mb-5 bg-white border-2 border-[#25D366]/50 rounded-2xl p-4"
     wire:poll.3s="pollProgress">
    <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center">
                <div class="w-4 h-4 border-2 border-[#25D366] border-t-transparent rounded-full animate-spin"></div>
            </div>
            <div>
                <div class="text-sm font-bold text-gray-900">
                    {{ $activeJobStats['name'] ?? 'Broadcast in progress…' }}
                </div>
                <div class="text-xs text-gray-500 mt-0.5">
                    {{ number_format($activeJobStats['sent'] ?? 0) }}
                    / {{ number_format($activeJobStats['total'] ?? 0) }} messages sent
                    @if (($activeJobStats['failed'] ?? 0) > 0)
                        · <span class="text-red-500">{{ $activeJobStats['failed'] }} failed</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-lg font-bold text-[#25D366]">
                {{ number_format($activeJobStats['percent'] ?? 0, 1) }}%
            </span>
            <button wire:click="cancelJob('{{ $activeJobUuid }}')"
                    wire:confirm="Cancel this broadcast? Messages already sent cannot be recalled."
                    class="px-3 py-1.5 border border-red-200 text-red-500 text-xs font-semibold
                           rounded-lg hover:bg-red-50 transition-colors">
                Cancel
            </button>
        </div>
    </div>
    <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
        <div class="h-full bg-[#25D366] rounded-full transition-all duration-500"
             style="width: {{ $activeJobStats['percent'] ?? 0 }}%"></div>
    </div>
</div>
@endif

<div class="grid grid-cols-2 gap-4">

    {{-- ── Compose Panel ──────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-5">
        <h2 class="text-sm font-bold text-gray-900 mb-5">Compose broadcast</h2>

        {{-- Job Name --}}
        <div class="mb-4">
            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Campaign name (optional)</label>
            <input wire:model="jobName" type="text" placeholder="e.g. Ramadan Promo 2025"
                   class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm outline-none
                          focus:border-[#25D366] focus:ring-2 focus:ring-[#25D366]/20 transition-all" />
        </div>

        {{-- Device --}}
        <div class="mb-4">
            <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                Sending device <span class="text-red-400">*</span>
            </label>
            <select wire:model="selectedDevice"
                    class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm
                           outline-none focus:border-[#25D366] bg-white">
                <option value="">Select a connected device…</option>
                @foreach ($devices as $device)
                    <option value="{{ $device->uuid }}">
                        {{ $device->name }} · {{ $device->phone_number ?? 'linking…' }}
                    </option>
                @endforeach
            </select>
            @error('selectedDevice')
                <p class="text-xs text-red-500 mt-1.5 flex items-center gap-1">
                    <i class="ti ti-circle-x text-sm"></i> {{ $message }}
                </p>
            @enderror
            @if ($devices->isEmpty())
                <div class="mt-2 flex items-center gap-2 p-2.5 bg-amber-50 border border-amber-100 rounded-lg text-xs text-amber-700">
                    <i class="ti ti-alert-circle flex-shrink-0"></i>
                    No connected devices.
                    <a href="{{ route('devices') }}" class="underline font-semibold ml-auto">Add one →</a>
                </div>
            @endif
        </div>

        {{-- Message Type --}}
        <div class="mb-4">
            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Message type</label>
            <div class="flex gap-2">
                @foreach (['text' => 'Text', 'image' => 'Image', 'document' => 'Document'] as $val => $label)
                    <button wire:click="$set('messageType','{{ $val }}')"
                            class="flex-1 py-2 rounded-xl text-xs font-semibold border transition-all
                                {{ $messageType === $val
                                    ? 'bg-[#25D366] text-white border-[#25D366] shadow-sm'
                                    : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Body or Media --}}
        @if ($messageType === 'text')
            <div class="mb-4">
                <div class="flex items-center justify-between mb-1.5">
                    <label class="text-xs font-semibold text-gray-600">Message <span class="text-red-400">*</span></label>
                    <span class="text-[10px] text-gray-400">{{ mb_strlen($messageBody) }} / 4096</span>
                </div>
                <textarea wire:model="messageBody" rows="4"
                          placeholder="Type your message…&#10;Use {{name}} or {{company}} for personalisation."
                          class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm outline-none
                                 focus:border-[#25D366] focus:ring-2 focus:ring-[#25D366]/20 resize-y transition-all"></textarea>
                @error('messageBody')
                    <p class="text-xs text-red-500 mt-1.5"><i class="ti ti-circle-x mr-1"></i>{{ $message }}</p>
                @enderror
            </div>
        @else
            <div class="mb-4">
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Media URL <span class="text-red-400">*</span></label>
                <input wire:model="mediaUrl" type="url" placeholder="https://example.com/file.jpg"
                       class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm outline-none
                              focus:border-[#25D366] transition-all font-mono" />
                @error('mediaUrl')
                    <p class="text-xs text-red-500 mt-1.5"><i class="ti ti-circle-x mr-1"></i>{{ $message }}</p>
                @enderror
            </div>
            <div class="mb-4">
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Caption (optional)</label>
                <input wire:model="mediaCaption" type="text"
                       class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm outline-none
                              focus:border-[#25D366] transition-all" />
            </div>
        @endif

        {{-- Recipients --}}
        <div class="mb-4">
            <div class="flex items-center justify-between mb-1.5">
                <label class="text-xs font-semibold text-gray-600">Recipients <span class="text-red-400">*</span></label>
                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full
                    {{ $recipientCount > 0 ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                    {{ number_format($recipientCount) }} numbers
                </span>
            </div>
            <textarea wire:model.live.debounce.500ms="recipients" rows="5"
                      placeholder="One number per line:&#10;213770123456&#10;213550987654"
                      class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs font-mono
                             outline-none focus:border-[#25D366] resize-y transition-all"></textarea>
            <p class="text-[10px] text-gray-400 mt-1">
                International format without +. Invalid numbers are skipped automatically.
            </p>
        </div>

        {{-- Anti-ban delay --}}
        <div class="mb-5 p-3.5 bg-amber-50 border border-amber-100 rounded-xl">
            <label class="flex items-center gap-2.5 cursor-pointer">
                <input type="checkbox" wire:model.live="randomDelay" class="w-4 h-4 accent-[#25D366] rounded" />
                <div>
                    <span class="text-xs font-semibold text-amber-900">Anti-ban random delay</span>
                    <p class="text-[10px] text-amber-700 mt-0.5">
                        Mimics human typing speed. Strongly recommended to avoid number suspension.
                    </p>
                </div>
            </label>
            @if ($randomDelay)
                <div class="flex items-center gap-2 mt-3 pl-6 text-xs text-amber-800">
                    <span>Between</span>
                    <input wire:model="delayMin" type="number" min="1" max="30"
                           class="w-14 border border-amber-200 bg-white rounded-lg px-2 py-1 text-center
                                  outline-none focus:border-amber-400 text-xs" />
                    <span>and</span>
                    <input wire:model="delayMax" type="number" min="1" max="60"
                           class="w-14 border border-amber-200 bg-white rounded-lg px-2 py-1 text-center
                                  outline-none focus:border-amber-400 text-xs" />
                    <span>seconds</span>
                </div>
            @endif
        </div>

        <button wire:click="preview"
                @if($devices->isEmpty()) disabled @endif
                class="w-full py-3 bg-[#25D366] text-white text-sm font-bold rounded-xl
                       hover:bg-green-600 active:scale-95 transition-all shadow-sm
                       disabled:opacity-50 disabled:cursor-not-allowed
                       flex items-center justify-center gap-2">
            <i class="ti ti-eye text-base"></i> Preview & confirm
        </button>
    </div>

    {{-- ── Recent Broadcasts ───────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-bold text-gray-900">Recent broadcasts</h2>
            <span class="text-[10px] text-gray-400">{{ $recentJobs->total() }} total</span>
        </div>

        <div class="space-y-3">
            @forelse ($recentJobs as $job)
                <div class="border border-gray-100 rounded-xl p-3.5 hover:border-gray-200 transition-colors">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-gray-900 truncate flex-1 mr-2">
                            {{ $job->name }}
                        </span>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full flex-shrink-0
                            {{ match($job->status) {
                                'completed' => 'bg-green-100 text-green-700',
                                'running'   => 'bg-blue-100 text-blue-700',
                                'pending'   => 'bg-yellow-100 text-yellow-700',
                                'cancelled' => 'bg-gray-100 text-gray-500',
                                'failed'    => 'bg-red-100 text-red-600',
                                default     => 'bg-gray-100 text-gray-500',
                            } }}">
                            {{ $job->status }}
                        </span>
                    </div>

                    @if (in_array($job->status, ['running','completed']))
                        <div class="w-full h-1 bg-gray-100 rounded-full mb-2 overflow-hidden">
                            <div class="h-full bg-[#25D366] rounded-full"
                                 style="width: {{ $job->progressPercent() }}%"></div>
                        </div>
                    @endif

                    <div class="flex items-center gap-3 text-[10px] text-gray-500">
                        <span><i class="ti ti-users mr-1"></i>{{ number_format($job->total_recipients) }}</span>
                        <span class="text-green-600 font-medium">
                            <i class="ti ti-check mr-1"></i>{{ number_format($job->sent_count) }} sent
                        </span>
                        @if ($job->failed_count > 0)
                            <span class="text-red-500">
                                <i class="ti ti-x mr-1"></i>{{ $job->failed_count }} failed
                            </span>
                        @endif
                        <span class="ml-auto text-gray-400">{{ $job->created_at->diffForHumans() }}</span>
                    </div>
                </div>
            @empty
                <div class="text-center py-10 text-gray-400">
                    <i class="ti ti-send block text-3xl mb-2"></i>
                    <p class="text-xs">No broadcasts yet</p>
                </div>
            @endforelse
        </div>

        @if ($recentJobs->hasPages())
            <div class="mt-3 pt-3 border-t border-gray-100">{{ $recentJobs->links() }}</div>
        @endif
    </div>
</div>

{{-- ── Preview / Confirm Modal ──────────────────────────────────────────── --}}
@if ($showConfirm && $previewStats)
    <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl w-[420px] p-6 shadow-2xl">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-11 h-11 rounded-xl bg-green-100 flex items-center justify-center">
                    <i class="ti ti-send text-green-700 text-xl"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-gray-900">Confirm broadcast</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Review before sending. This cannot be undone.</p>
                </div>
            </div>

            <div class="bg-gray-50 rounded-xl p-4 mb-4 space-y-2.5">
                @foreach ([
                    'Total recipients'     => number_format($previewStats['recipients']),
                    'Will be sent'         => number_format($previewStats['can_send']),
                    'Blocked (daily limit)'=> $previewStats['blocked'] > 0 ? number_format($previewStats['blocked']) : '—',
                    'Estimated duration'   => $previewStats['est_minutes'] > 0 ? $previewStats['est_minutes'] . ' min' : 'Immediate',
                ] as $label => $val)
                    <div class="flex justify-between text-xs">
                        <span class="text-gray-500">{{ $label }}</span>
                        <span class="font-bold {{ $label === 'Blocked (daily limit)' && $previewStats['blocked'] > 0 ? 'text-red-500' : 'text-gray-900' }}">
                            {{ $val }}
                        </span>
                    </div>
                @endforeach
            </div>

            @if (!$randomDelay)
                <div class="mb-4 p-3 bg-red-50 border border-red-100 rounded-xl text-xs text-red-700 flex items-start gap-2">
                    <i class="ti ti-alert-triangle flex-shrink-0 mt-0.5"></i>
                    Sending without delay significantly increases the risk of your number being banned by WhatsApp.
                </div>
            @endif

            <div class="flex gap-3">
                <button wire:click="$set('showConfirm',false)"
                        class="flex-1 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-600
                               hover:bg-gray-50 transition-colors font-medium">
                    Back
                </button>
                <button wire:click="send"
                        class="flex-1 py-2.5 bg-[#25D366] text-white text-sm font-bold rounded-xl
                               hover:bg-green-600 active:scale-95 transition-all">
                    <span wire:loading.remove wire:target="send">
                        Send {{ number_format($previewStats['can_send']) }} messages
                    </span>
                    <span wire:loading wire:target="send">Queuing…</span>
                </button>
            </div>
        </div>
    </div>
@endif

</x-layouts.app>
