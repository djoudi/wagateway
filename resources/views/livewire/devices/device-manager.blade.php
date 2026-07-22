<x-layouts.app title="Devices">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-5">
        <div>
            <h2 class="text-sm font-semibold text-gray-900">Connected devices</h2>
            <p class="text-xs text-gray-400 mt-0.5">
                {{ $devices->where('status','connected')->count() }} of {{ $devices->count() }} connected
                · Plan allows {{ auth()->user()->plan?->max_devices ?? 2 }}
            </p>
        </div>
        <button wire:click="openAddModal"
                class="flex items-center gap-2 px-3 py-2 bg-[#25D366] text-white text-sm font-medium rounded-lg hover:bg-green-600 transition-colors">
            <i class="ti ti-plus text-base"></i> Add device
        </button>
    </div>

    @error('plan')
        <div class="mb-4 flex items-center gap-2 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
            <i class="ti ti-alert-circle"></i> {{ $message }}
            <a href="{{ route('billing') }}" class="ml-auto underline font-medium">Upgrade plan</a>
        </div>
    @enderror

    {{-- Device Grid --}}
    <div class="grid grid-cols-3 gap-3">
        @forelse ($devices as $device)
            <div class="bg-white rounded-xl border
                {{ $device->status->value === 'connected' ? 'border-gray-100' :
                   ($device->status->value === 'connecting' ? 'border-amber-200' : 'border-gray-100') }}
                p-4">

                <div class="flex items-start gap-3 mb-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0
                        {{ $device->status->value === 'connected' ? 'bg-green-100' :
                           ($device->status->value === 'connecting' ? 'bg-amber-100' : 'bg-gray-100') }}">
                        <i class="ti ti-device-mobile text-lg
                            {{ $device->status->value === 'connected' ? 'text-green-700' :
                               ($device->status->value === 'connecting' ? 'text-amber-700' : 'text-gray-400') }}"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-semibold text-gray-900 truncate">{{ $device->name }}</div>
                        <div class="text-xs text-gray-400 mt-0.5">
                            {{ $device->phone_number ?? 'Not connected' }}
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full flex-shrink-0
                            {{ $device->status->value === 'connected' ? 'bg-green-500' :
                               ($device->status->value === 'connecting' ? 'bg-amber-400' : 'bg-gray-300') }}">
                        </span>
                        <span class="text-[11px] font-medium
                            {{ $device->status->value === 'connected' ? 'text-green-600' :
                               ($device->status->value === 'connecting' ? 'text-amber-600' : 'text-gray-400') }}">
                            {{ $device->status->label() }}
                        </span>
                    </div>
                </div>

                {{-- Stats --}}
                <div class="grid grid-cols-2 gap-2 mb-3">
                    <div class="bg-gray-50 rounded-lg p-2">
                        <div class="text-[10px] text-gray-400">Today</div>
                        <div class="text-sm font-semibold text-gray-900">{{ number_format($device->messages_sent_today) }}</div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-2">
                        <div class="text-[10px] text-gray-400">Total</div>
                        <div class="text-sm font-semibold text-gray-900">{{ number_format($device->messages_sent_total) }}</div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex gap-2">
                    @if ($device->status->value === 'connected')
                        <button wire:click="disconnectDevice('{{ $device->uuid }}')"
                                wire:confirm="Disconnect this device?"
                                class="flex-1 flex items-center justify-center gap-1.5 py-1.5 border border-gray-200 rounded-lg text-xs font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                            <i class="ti ti-unlink text-sm"></i> Disconnect
                        </button>
                    @else
                        <button wire:click="reconnectDevice('{{ $device->uuid }}')"
                                class="flex-1 flex items-center justify-center gap-1.5 py-1.5 bg-[#25D366] rounded-lg text-xs font-medium text-white hover:bg-green-600 transition-colors">
                            <i class="ti ti-qrcode text-sm"></i> Reconnect
                        </button>
                    @endif
                    <button wire:click="removeDevice('{{ $device->uuid }}')"
                            wire:confirm="Permanently remove this device?"
                            class="flex items-center justify-center w-8 h-7 border border-red-100 text-red-400 hover:bg-red-50 rounded-lg transition-colors">
                        <i class="ti ti-trash text-sm"></i>
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-3 bg-white rounded-xl border border-dashed border-gray-200 p-10 text-center">
                <i class="ti ti-device-mobile-off text-3xl text-gray-300 block mb-2"></i>
                <p class="text-sm font-medium text-gray-600">No devices yet</p>
                <p class="text-xs text-gray-400 mt-1 mb-4">Add your first WhatsApp device to start sending messages</p>
                <button wire:click="openAddModal"
                        class="px-4 py-2 bg-[#25D366] text-white text-sm font-medium rounded-lg hover:bg-green-600 transition-colors">
                    <i class="ti ti-plus mr-1"></i> Add first device
                </button>
            </div>
        @endforelse
    </div>

    {{-- ── Add Device Modal ───────────────────────────────────────────────── --}}
    @if ($showAddModal)
    <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50" wire:click.self="$set('showAddModal',false)">
        <div class="bg-white rounded-2xl w-80 p-6 shadow-xl">
            <h3 class="text-sm font-semibold text-gray-900 mb-4">Add new device</h3>
            <div class="mb-4">
                <label class="block text-xs text-gray-500 mb-1.5">Device name</label>
                <input wire:model="newDeviceName" type="text" placeholder="e.g. Marketing, Support…"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#25D366] focus:ring-1 focus:ring-[#25D366]" />
                @error('newDeviceName')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex gap-2">
                <button wire:click="$set('showAddModal',false)"
                        class="flex-1 py-2 border border-gray-200 rounded-lg text-sm text-gray-600 hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button wire:click="createDevice"
                        class="flex-1 py-2 bg-[#25D366] text-white text-sm font-medium rounded-lg hover:bg-green-600 transition-colors">
                    <span wire:loading.remove wire:target="createDevice">Continue</span>
                    <span wire:loading wire:target="createDevice">Starting…</span>
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ── QR Code Modal ──────────────────────────────────────────────────── --}}
    @if ($showQrModal)
    <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl w-72 p-6 shadow-xl text-center">
            <div class="w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center mx-auto mb-3">
                <i class="ti ti-qrcode text-green-700 text-xl"></i>
            </div>
            <h3 class="text-sm font-semibold text-gray-900">Connect {{ $qrDeviceName }}</h3>
            <p class="text-xs text-gray-400 mt-1 mb-4">
                Open WhatsApp → Settings → Linked Devices → Link a Device
            </p>

            @if ($qrStatus === 'connected')
                <div class="w-36 h-36 mx-auto mb-4 bg-green-50 rounded-xl flex flex-col items-center justify-center">
                    <i class="ti ti-circle-check text-4xl text-green-500"></i>
                    <p class="text-xs text-green-700 mt-2 font-medium">Connected!</p>
                </div>
                <button wire:click="closeQrModal"
                        class="w-full py-2 bg-[#25D366] text-white text-sm font-medium rounded-lg">
                    Done
                </button>

            @elseif ($qrCode)
                <div class="w-40 h-40 mx-auto mb-3 bg-white border border-gray-200 rounded-xl flex items-center justify-center overflow-hidden">
                    <img src="{{ $qrCode }}" alt="QR Code" class="w-full h-full object-contain p-1" />
                </div>
                <p class="text-[10px] text-gray-400 mb-4">QR refreshes every 60 seconds. Keep this window open.</p>
                <button wire:click="closeQrModal"
                        class="w-full py-2 border border-gray-200 text-gray-600 text-sm rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </button>

            @else
                <div class="w-40 h-40 mx-auto mb-3 bg-gray-50 rounded-xl flex flex-col items-center justify-center">
                    <div class="w-8 h-8 border-2 border-[#25D366] border-t-transparent rounded-full animate-spin mb-2"></div>
                    <p class="text-xs text-gray-400">Generating QR…</p>
                </div>
                <button wire:click="closeQrModal"
                        class="w-full py-2 border border-gray-200 text-gray-600 text-sm rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
            @endif
        </div>
    </div>
    @endif

</x-layouts.app>
