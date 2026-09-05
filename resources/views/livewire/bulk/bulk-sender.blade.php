<div>
@php $isAr = app()->getLocale() === 'ar'; @endphp

@if ($activeJobUuid && in_array($activeJobStatus, ['pending','running']))
<div class="mb-5 bg-card border-2 border-signal/50 rounded-[14px] p-4"
     wire:poll.3s="pollProgress">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-signal-dim flex items-center justify-center">
                <div class="w-4 h-4 border-2 border-signal border-t-transparent rounded-full animate-spin"></div>
            </div>
            <div>
                <div class="text-sm font-bold text-text">
                    {{ $activeJobStats['name'] ?? ($isAr ? 'البث قيد التنفيذ…' : 'Broadcast in progress…') }}
                </div>
                <div class="text-xs text-muted mt-0.5">
                    {{ number_format($activeJobStats['sent'] ?? 0) }}
                    / {{ number_format($activeJobStats['total'] ?? 0) }} {{ $isAr ? 'رسائل مُرسلة' : 'messages sent' }}
                    @if (($activeJobStats['failed'] ?? 0) > 0)
                        · <span class="text-danger">{{ $activeJobStats['failed'] }} {{ $isAr ? 'فاشلة' : 'failed' }}</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-lg font-bold text-signal">
                {{ number_format($activeJobStats['percent'] ?? 0, 1) }}%
            </span>
            <button wire:click="cancelJob('{{ $activeJobUuid }}')"
                    wire:confirm="{{ $isAr ? 'إلغاء هذا البث؟ لا يمكن استرجاع الرسائل المُرسلة.' : 'Cancel this broadcast? Messages already sent cannot be recalled.' }}"
                    class="px-3 py-1.5 border border-danger/30 text-danger text-xs font-semibold
                           rounded-lg hover:bg-danger-dim transition-colors min-h-11">
                {{ $isAr ? 'إلغاء' : 'Cancel' }}
            </button>
        </div>
    </div>
    <div class="w-full h-2 bg-paper rounded-full overflow-hidden">
        <div class="h-full bg-signal rounded-full transition-all duration-500"
             style="width: {{ $activeJobStats['percent'] ?? 0 }}%"></div>
    </div>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

    <div class="bg-card rounded-[14px] border border-line p-5">
        <h2 class="text-sm font-bold text-text mb-5">{{ $isAr ? 'إنشاء بث' : 'Compose broadcast' }}</h2>

        <div class="mb-4">
            <label class="block text-xs font-semibold text-muted mb-1.5">{{ $isAr ? 'اسم الحملة (اختياري)' : 'Campaign name (optional)' }}</label>
            <input wire:model="jobName" type="text" placeholder="{{ $isAr ? 'مثال: عرض رمضان 2025' : 'e.g. Ramadan Promo 2025' }}"
                   class="w-full border border-line rounded-xl px-3.5 py-2.5 text-base outline-none min-h-11
                          focus:border-signal focus:ring-2 focus:ring-signal/20 transition-all" />
        </div>

        <div class="mb-4">
            <label class="block text-xs font-semibold text-muted mb-1.5">
                {{ $isAr ? 'جهاز الإرسال' : 'Sending device' }} <span class="text-danger">*</span>
            </label>
            <select wire:model="selectedDevice"
                    class="w-full border border-line rounded-xl px-3.5 py-2.5 text-base min-h-11
                           outline-none focus:border-signal bg-card">
                <option value="">{{ $isAr ? 'اختر جهازاً متصلاً…' : 'Select a connected device…' }}</option>
                @foreach ($devices as $device)
                    <option value="{{ $device->uuid }}">
                        {{ $device->name }} · {{ $device->phone_number ?? ($isAr ? 'جارٍ الربط…' : 'linking…') }}
                    </option>
                @endforeach
            </select>
            @error('selectedDevice')
                <p class="text-xs text-danger mt-1.5 flex items-center gap-1">
                    <i class="ti ti-circle-x text-sm"></i> {{ $message }}
                </p>
            @enderror
            @if ($devices->isEmpty())
                <div class="mt-2 flex items-center gap-2 p-2.5 bg-amber/10 border border-amber/30 rounded-lg text-xs text-amber">
                    <i class="ti ti-alert-circle flex-shrink-0"></i>
                    {{ $isAr ? 'لا توجد أجهزة متصلة.' : 'No connected devices.' }}
                    <a href="{{ route('devices') }}" class="underline font-semibold ms-auto">{{ $isAr ? 'أضف جهازاً ←' : 'Add one →' }}</a>
                </div>
            @endif
        </div>

        <div class="mb-4">
            <label class="block text-xs font-semibold text-muted mb-1.5">{{ $isAr ? 'نوع الرسالة' : 'Message type' }}</label>
            <div class="flex gap-2">
                @foreach (['text' => ($isAr ? 'نص' : 'Text'), 'image' => ($isAr ? 'صورة' : 'Image'), 'document' => ($isAr ? 'مستند' : 'Document')] as $val => $label)
                    <button wire:click="$set('messageType','{{ $val }}')"
                            class="flex-1 py-2 rounded-xl text-xs font-semibold border transition-all min-h-11
                                {{ $messageType === $val
                                    ? 'bg-signal text-[#06170F] border-signal shadow-sm'
                                    : 'bg-card text-muted border-line hover:border-muted' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        @if ($messageType === 'text')
            <div class="mb-4">
                <div class="flex items-center justify-between mb-1.5">
                    <label class="text-xs font-semibold text-muted">{{ $isAr ? 'الرسالة' : 'Message' }} <span class="text-danger">*</span></label>
                    <span class="text-[10px] text-muted">{{ mb_strlen($messageBody) }} / 4096</span>
                </div>
                <textarea wire:model="messageBody" rows="4"
                          placeholder="{{ $isAr ? "اكتب رسالتك…\nاستخدم @{{name}} أو @{{company}} للتخصيص." : "Type your message…\nUse @{{name}} or @{{company}} for personalisation." }}"
                          class="w-full border border-line rounded-xl px-3.5 py-2.5 text-base outline-none
                                 focus:border-signal focus:ring-2 focus:ring-signal/20 resize-y transition-all"></textarea>
                @error('messageBody')
                    <p class="text-xs text-danger mt-1.5"><i class="ti ti-circle-x me-1"></i>{{ $message }}</p>
                @enderror
            </div>
        @else
            <div class="mb-4">
                <label class="block text-xs font-semibold text-muted mb-1.5">{{ $isAr ? 'رابط الوسائط' : 'Media URL' }} <span class="text-danger">*</span></label>
                <input wire:model="mediaUrl" type="url" placeholder="https://example.com/file.jpg"
                       class="w-full border border-line rounded-xl px-3.5 py-2.5 text-base outline-none min-h-11
                              focus:border-signal transition-all font-mono" />
                @error('mediaUrl')
                    <p class="text-xs text-danger mt-1.5"><i class="ti ti-circle-x me-1"></i>{{ $message }}</p>
                @enderror
            </div>
            <div class="mb-4">
                <label class="block text-xs font-semibold text-muted mb-1.5">{{ $isAr ? 'التعليق (اختياري)' : 'Caption (optional)' }}</label>
                <input wire:model="mediaCaption" type="text"
                       class="w-full border border-line rounded-xl px-3.5 py-2.5 text-base outline-none min-h-11
                              focus:border-signal transition-all" />
            </div>
        @endif

        <div class="mb-4">
            <div class="flex items-center justify-between mb-1.5">
                <label class="text-xs font-semibold text-muted">{{ $isAr ? 'المستلمون' : 'Recipients' }} <span class="text-danger">*</span></label>
                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full
                    {{ $recipientCount > 0 ? 'bg-signal-dim text-signal-deep' : 'bg-paper text-muted' }}">
                    {{ number_format($recipientCount) }} {{ $isAr ? 'أرقام' : 'numbers' }}
                </span>
            </div>
            <textarea wire:model.live.debounce.500ms="recipients" rows="5"
                      placeholder="{{ $isAr ? "رقم واحد في كل سطر:\n213770123456\n213550987654" : "One number per line:\n213770123456\n213550987654" }}"
                      class="w-full border border-line rounded-xl px-3.5 py-2.5 text-xs font-mono
                             outline-none focus:border-signal resize-y transition-all"></textarea>
            <p class="text-[10px] text-muted mt-1">
                {{ $isAr ? 'الصيغة الدولية بدون +. تُتخطى الأرقام غير الصالحة تلقائياً.' : 'International format without +. Invalid numbers are skipped automatically.' }}
            </p>
        </div>

        <div class="mb-5 p-3.5 bg-amber/10 border border-amber/30 rounded-xl">
            <label class="flex items-center gap-2.5 cursor-pointer">
                <input type="checkbox" wire:model.live="randomDelay" class="w-4 h-4 accent-[#2FA66B] rounded" />
                <div>
                    <span class="text-xs font-semibold text-text">{{ $isAr ? 'تأخير عشوائي ضد الحظر' : 'Anti-ban random delay' }}</span>
                    <p class="text-[10px] text-muted mt-0.5">
                        {{ $isAr ? 'يحاكي سرعة الكتابة البشرية. يُنصح به بشدة لتجنب تعليق الرقم.' : 'Mimics human typing speed. Strongly recommended to avoid number suspension.' }}
                    </p>
                </div>
            </label>
            @if ($randomDelay)
                <div class="flex items-center gap-2 mt-3 ps-6 text-xs text-text">
                    <span>{{ $isAr ? 'بين' : 'Between' }}</span>
                    <input wire:model="delayMin" type="number" min="1" max="30"
                           class="w-14 border border-amber/40 bg-card rounded-lg px-2 py-1 text-center
                                  outline-none focus:border-amber text-xs min-h-11" />
                    <span>{{ $isAr ? 'و' : 'and' }}</span>
                    <input wire:model="delayMax" type="number" min="1" max="60"
                           class="w-14 border border-amber/40 bg-card rounded-lg px-2 py-1 text-center
                                  outline-none focus:border-amber text-xs min-h-11" />
                    <span>{{ $isAr ? 'ثوانٍ' : 'seconds' }}</span>
                </div>
            @endif
        </div>

        <button wire:click="preview"
                @if($devices->isEmpty()) disabled @endif
                class="w-full py-3 bg-signal text-[#06170F] text-sm font-bold rounded-xl
                       hover:bg-[#37B879] active:scale-95 transition-all shadow-sm min-h-11
                       disabled:opacity-50 disabled:cursor-not-allowed
                       flex items-center justify-center gap-2">
            <i class="ti ti-eye text-base"></i> {{ $isAr ? 'معاينة وتأكيد' : 'Preview & confirm' }}
        </button>
    </div>

    <div class="bg-card rounded-[14px] border border-line p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-bold text-text">{{ $isAr ? 'البثوث الأخيرة' : 'Recent broadcasts' }}</h2>
            <span class="text-[10px] text-muted">{{ $recentJobs->total() }} {{ $isAr ? 'إجمالي' : 'total' }}</span>
        </div>

        <div class="space-y-3">
            @forelse ($recentJobs as $job)
                <div class="border border-line rounded-xl p-3.5 hover:border-muted transition-colors">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-text truncate flex-1 me-2">
                            {{ $job->name }}
                        </span>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full flex-shrink-0
                            {{ match($job->status) {
                                'completed' => 'bg-signal-dim text-signal-deep',
                                'running'   => 'bg-signal-dim text-signal-deep',
                                'pending'   => 'bg-amber/20 text-amber',
                                'cancelled' => 'bg-paper text-muted',
                                'failed'    => 'bg-danger-dim text-danger',
                                default     => 'bg-paper text-muted',
                            } }}">
                            {{ $job->status }}
                        </span>
                    </div>

                    @if (in_array($job->status, ['running','completed']))
                        <div class="w-full h-1 bg-paper rounded-full mb-2 overflow-hidden">
                            <div class="h-full bg-signal rounded-full"
                                 style="width: {{ $job->progressPercent() }}%"></div>
                        </div>
                    @endif

                    <div class="flex items-center gap-3 text-[10px] text-muted">
                        <span><i class="ti ti-users me-1"></i>{{ number_format($job->total_recipients) }}</span>
                        <span class="text-signal-deep font-medium">
                            <i class="ti ti-check me-1"></i>{{ number_format($job->sent_count) }} {{ $isAr ? 'مُرسلة' : 'sent' }}
                        </span>
                        @if ($job->failed_count > 0)
                            <span class="text-danger">
                                <i class="ti ti-x me-1"></i>{{ $job->failed_count }} {{ $isAr ? 'فاشلة' : 'failed' }}
                            </span>
                        @endif
                        <span class="ms-auto text-muted">{{ $job->created_at->diffForHumans() }}</span>
                    </div>
                </div>
            @empty
                <div class="text-center py-10 text-muted">
                    <i class="ti ti-send block text-3xl mb-2"></i>
                    <p class="text-xs">{{ $isAr ? 'لا توجد بثوث بعد' : 'No broadcasts yet' }}</p>
                </div>
            @endforelse
        </div>

        @if ($recentJobs->hasPages())
            <div class="mt-3 pt-3 border-t border-line">{{ $recentJobs->links() }}</div>
        @endif
    </div>
</div>

@if ($showConfirm && $previewStats)
    <div class="fixed inset-0 bg-ink/50 flex items-center justify-center z-50 p-4">
        <div class="bg-card rounded-[14px] w-[420px] max-w-full p-6 shadow-2xl border border-line">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-11 h-11 rounded-xl bg-signal-dim flex items-center justify-center">
                    <i class="ti ti-send text-signal-deep text-xl"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-text">{{ $isAr ? 'تأكيد البث' : 'Confirm broadcast' }}</h3>
                    <p class="text-xs text-muted mt-0.5">{{ $isAr ? 'راجع قبل الإرسال. لا يمكن التراجع.' : 'Review before sending. This cannot be undone.' }}</p>
                </div>
            </div>

            <div class="bg-paper rounded-xl p-4 mb-4 space-y-2.5">
                @foreach ([
                    ($isAr ? 'إجمالي المستلمين' : 'Total recipients')     => number_format($previewStats['recipients']),
                    ($isAr ? 'سيتم إرسالها' : 'Will be sent')         => number_format($previewStats['can_send']),
                    ($isAr ? 'محجوبة (الحد اليومي)' : 'Blocked (daily limit)')=> $previewStats['blocked'] > 0 ? number_format($previewStats['blocked']) : '—',
                    ($isAr ? 'المدة التقديرية' : 'Estimated duration')   => $previewStats['est_minutes'] > 0 ? $previewStats['est_minutes'] . ($isAr ? ' د' : ' min') : ($isAr ? 'فوري' : 'Immediate'),
                ] as $label => $val)
                    <div class="flex justify-between text-xs">
                        <span class="text-muted">{{ $label }}</span>
                        <span class="font-bold {{ (str_contains((string) $label, 'Blocked') || str_contains((string) $label, 'محجوبة')) && $previewStats['blocked'] > 0 ? 'text-danger' : 'text-text' }}">
                            {{ $val }}
                        </span>
                    </div>
                @endforeach
            </div>

            @if (!$randomDelay)
                <div class="mb-4 p-3 bg-danger-dim border border-danger/20 rounded-xl text-xs text-danger flex items-start gap-2">
                    <i class="ti ti-alert-triangle flex-shrink-0 mt-0.5"></i>
                    {{ $isAr ? 'الإرسال بدون تأخير يزيد بشكل كبير من خطر حظر رقمك.' : 'Sending without delay significantly increases the risk of your number being banned.' }}
                </div>
            @endif

            <div class="flex gap-3">
                <button wire:click="$set('showConfirm',false)"
                        class="flex-1 py-2.5 border border-line rounded-xl text-sm text-muted
                               hover:bg-paper transition-colors font-medium min-h-11">
                    {{ $isAr ? 'رجوع' : 'Back' }}
                </button>
                <button wire:click="send"
                        class="flex-1 py-2.5 bg-signal text-[#06170F] text-sm font-bold rounded-xl
                               hover:bg-[#37B879] active:scale-95 transition-all min-h-11">
                    <span wire:loading.remove wire:target="send">
                        {{ $isAr ? 'إرسال' : 'Send' }} {{ number_format($previewStats['can_send']) }} {{ $isAr ? 'رسالة' : 'messages' }}
                    </span>
                    <span wire:loading wire:target="send">{{ $isAr ? 'جارٍ الإضافة للطابور…' : 'Queuing…' }}</span>
                </button>
            </div>
        </div>
    </div>
@endif
</div>
