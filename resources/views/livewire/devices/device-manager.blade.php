<div>
@php $isAr = app()->getLocale() === 'ar'; @endphp

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
        <div>
            <h2 class="text-sm font-semibold text-text">{{ $isAr ? 'الأجهزة المتصلة' : 'Connected devices' }}</h2>
            <p class="text-xs text-muted mt-0.5">
                @if ($isAr)
                    {{ $devices->where('status','connected')->count() }} من {{ $devices->count() }} متصل
                    · الخطة تسمح بـ {{ auth()->user()->plan?->max_devices ?? 2 }}
                @else
                    {{ $devices->where('status','connected')->count() }} of {{ $devices->count() }} connected
                    · Plan allows {{ auth()->user()->plan?->max_devices ?? 2 }}
                @endif
            </p>
        </div>
        <button wire:click="openAddModal"
                class="flex items-center justify-center gap-2 px-3 py-2 bg-signal text-[#06170F] text-sm font-medium rounded-lg hover:bg-[#37B879] transition-colors min-h-11">
            <i class="ti ti-plus text-base"></i> {{ $isAr ? 'إضافة جهاز' : 'Add device' }}
        </button>
    </div>

    @error('plan')
        <div class="mb-4 flex items-center gap-2 p-3 bg-danger-dim border border-danger/30 rounded-lg text-sm text-danger">
            <i class="ti ti-alert-circle"></i> {{ $message }}
            <a href="{{ route('billing') }}" class="ms-auto underline font-medium">{{ $isAr ? 'ترقية الخطة' : 'Upgrade plan' }}</a>
        </div>
    @enderror

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        @forelse ($devices as $device)
            <div class="bg-card rounded-[14px] border
                {{ $device->status->value === 'connecting' ? 'border-amber' : 'border-line' }}
                p-4">

                <div class="flex items-start gap-3 mb-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0
                        {{ $device->status->value === 'connected' ? 'bg-signal-dim' :
                           ($device->status->value === 'connecting' ? 'bg-amber/20' : 'bg-paper') }}">
                        <i class="ti ti-device-mobile text-lg
                            {{ $device->status->value === 'connected' ? 'text-signal-deep' :
                               ($device->status->value === 'connecting' ? 'text-amber' : 'text-muted') }}"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-semibold text-text truncate">{{ $device->name }}</div>
                        <div class="text-xs text-muted mt-0.5">
                            {{ $device->phone_number ?? ($isAr ? 'غير متصل' : 'Not connected') }}
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full flex-shrink-0
                            {{ $device->status->value === 'connected' ? 'bg-signal' :
                               ($device->status->value === 'connecting' ? 'bg-amber' : 'bg-muted') }}">
                        </span>
                        <span class="text-[11px] font-medium
                            {{ $device->status->value === 'connected' ? 'text-signal-deep' :
                               ($device->status->value === 'connecting' ? 'text-amber' : 'text-muted') }}">
                            {{ $device->status->label() }}
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 mb-3">
                    <div class="bg-paper rounded-lg p-2">
                        <div class="text-[10px] text-muted">{{ $isAr ? 'اليوم' : 'Today' }}</div>
                        <div class="text-sm font-semibold text-text">{{ number_format($device->messages_sent_today) }}</div>
                    </div>
                    <div class="bg-paper rounded-lg p-2">
                        <div class="text-[10px] text-muted">{{ $isAr ? 'الإجمالي' : 'Total' }}</div>
                        <div class="text-sm font-semibold text-text">{{ number_format($device->messages_sent_total) }}</div>
                    </div>
                </div>

                <div class="flex gap-2">
                    @if ($device->status->value === 'connected')
                        <button wire:click="disconnectDevice('{{ $device->uuid }}')"
                                wire:confirm="{{ $isAr ? 'قطع اتصال هذا الجهاز؟' : 'Disconnect this device?' }}"
                                class="flex-1 flex items-center justify-center gap-1.5 py-1.5 min-h-11 border border-line rounded-lg text-xs font-medium text-muted hover:bg-paper transition-colors">
                            <i class="ti ti-unlink text-sm"></i> {{ $isAr ? 'قطع الاتصال' : 'Disconnect' }}
                        </button>
                    @else
                        <button wire:click="reconnectDevice('{{ $device->uuid }}')"
                                class="flex-1 flex items-center justify-center gap-1.5 py-1.5 min-h-11 bg-signal rounded-lg text-xs font-medium text-[#06170F] hover:bg-[#37B879] transition-colors">
                            <i class="ti ti-qrcode text-sm"></i> {{ $isAr ? 'إعادة الربط' : 'Reconnect' }}
                        </button>
                    @endif
                    <button wire:click="removeDevice('{{ $device->uuid }}')"
                            wire:confirm="{{ $isAr ? 'حذف هذا الجهاز نهائياً؟' : 'Permanently remove this device?' }}"
                            class="flex items-center justify-center min-w-11 min-h-11 border border-danger/20 text-danger hover:bg-danger-dim rounded-lg transition-colors">
                        <i class="ti ti-trash text-sm"></i>
                    </button>
                </div>
            </div>
        @empty
            <div class="sm:col-span-2 lg:col-span-3 bg-card rounded-[14px] border border-dashed border-line p-10 text-center">
                <i class="ti ti-device-mobile-off text-3xl text-muted block mb-2"></i>
                <p class="text-sm font-medium text-text">{{ $isAr ? 'لا توجد أجهزة بعد' : 'No devices yet' }}</p>
                <p class="text-xs text-muted mt-1 mb-4">{{ $isAr ? 'أضف جهاز واتساب الأول لبدء إرسال الرسائل' : 'Add your first WhatsApp device to start sending messages' }}</p>
                <button wire:click="openAddModal"
                        class="px-4 py-2 bg-signal text-[#06170F] text-sm font-medium rounded-lg hover:bg-[#37B879] transition-colors min-h-11">
                    <i class="ti ti-plus me-1"></i> {{ $isAr ? 'أضف الجهاز الأول' : 'Add first device' }}
                </button>
            </div>
        @endforelse
    </div>

    @if ($showAddModal)
    <div class="fixed inset-0 bg-ink/50 flex items-center justify-center z-50 p-4" wire:click.self="$set('showAddModal',false)">
        <div class="bg-card rounded-[14px] w-80 max-w-full p-6 shadow-xl border border-line">
            <h3 class="text-sm font-semibold text-text mb-4">{{ $isAr ? 'إضافة جهاز جديد' : 'Add new device' }}</h3>
            <div class="mb-4">
                <label class="block text-xs text-muted mb-1.5">{{ $isAr ? 'اسم الجهاز' : 'Device name' }}</label>
                <input wire:model="newDeviceName" type="text" placeholder="{{ $isAr ? 'مثال: التسويق، الدعم…' : 'e.g. Marketing, Support…' }}"
                       class="w-full border border-line rounded-lg px-3 py-2 text-base outline-none min-h-11 focus:border-signal focus:ring-2 focus:ring-signal/20" />
                @error('newDeviceName')
                    <p class="text-xs text-danger mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex gap-2">
                <button wire:click="$set('showAddModal',false)"
                        class="flex-1 py-2 border border-line rounded-lg text-sm text-muted hover:bg-paper transition-colors min-h-11">
                    {{ $isAr ? 'إلغاء' : 'Cancel' }}
                </button>
                <button wire:click="createDevice"
                        class="flex-1 py-2 bg-signal text-[#06170F] text-sm font-medium rounded-lg hover:bg-[#37B879] transition-colors min-h-11">
                    <span wire:loading.remove wire:target="createDevice">{{ $isAr ? 'متابعة' : 'Continue' }}</span>
                    <span wire:loading wire:target="createDevice">{{ $isAr ? 'جارٍ البدء…' : 'Starting…' }}</span>
                </button>
            </div>
        </div>
    </div>
    @endif

    @if ($showQrModal)
    <div class="fixed inset-0 bg-ink/50 flex items-center justify-center z-50 p-4">
        <div class="bg-card rounded-[14px] w-72 max-w-full p-6 shadow-xl text-center border border-line">
            <div class="w-10 h-10 rounded-xl bg-signal-dim flex items-center justify-center mx-auto mb-3">
                <i class="ti ti-qrcode text-signal-deep text-xl"></i>
            </div>
            <h3 class="text-sm font-semibold text-text">{{ $isAr ? 'ربط' : 'Connect' }} {{ $qrDeviceName }}</h3>
            <p class="text-xs text-muted mt-1 mb-4">
                {{ $isAr ? 'افتح واتساب ← الإعدادات ← الأجهزة المرتبطة ← ربط جهاز' : 'Open WhatsApp → Settings → Linked Devices → Link a Device' }}
            </p>

            @if ($qrStatus === 'connected')
                <div class="w-36 h-36 mx-auto mb-4 bg-signal-dim rounded-xl flex flex-col items-center justify-center">
                    <i class="ti ti-circle-check text-4xl text-signal"></i>
                    <p class="text-xs text-signal-deep mt-2 font-medium">{{ $isAr ? 'تم الربط!' : 'Connected!' }}</p>
                </div>
                <button wire:click="closeQrModal"
                        class="w-full py-2 bg-signal text-[#06170F] text-sm font-medium rounded-lg min-h-11">
                    {{ $isAr ? 'تم' : 'Done' }}
                </button>

            @elseif ($qrCode)
                <div class="w-40 h-40 mx-auto mb-3 bg-card border border-line rounded-xl flex items-center justify-center overflow-hidden">
                    <img src="{{ $qrCode }}" alt="QR Code" class="w-full h-full object-contain p-1" />
                </div>
                <p class="text-[10px] text-muted mb-4">{{ $isAr ? 'يُحدَّث رمز QR كل 60 ثانية. أبقِ هذه النافذة مفتوحة.' : 'QR refreshes every 60 seconds. Keep this window open.' }}</p>
                <button wire:click="closeQrModal"
                        class="w-full py-2 border border-line text-muted text-sm rounded-lg hover:bg-paper transition-colors min-h-11">
                    {{ $isAr ? 'إلغاء' : 'Cancel' }}
                </button>

            @else
                <div class="w-40 h-40 mx-auto mb-3 bg-paper rounded-xl flex flex-col items-center justify-center">
                    <div class="w-8 h-8 border-2 border-signal border-t-transparent rounded-full animate-spin mb-2"></div>
                    <p class="text-xs text-muted">{{ $isAr ? 'جارٍ إنشاء رمز QR…' : 'Generating QR…' }}</p>
                </div>
                <button wire:click="closeQrModal"
                        class="w-full py-2 border border-line text-muted text-sm rounded-lg hover:bg-paper transition-colors min-h-11">
                    {{ $isAr ? 'إلغاء' : 'Cancel' }}
                </button>
            @endif
        </div>
    </div>
    @endif
</div>
