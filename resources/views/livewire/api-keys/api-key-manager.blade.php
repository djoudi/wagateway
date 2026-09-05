<div>
@php $isAr = app()->getLocale() === 'ar'; @endphp

@if ($newKeyFlash)
<div class="mb-5 bg-amber/10 border-2 border-amber rounded-[14px] p-5">
    <div class="flex items-start gap-3">
        <div class="w-10 h-10 rounded-xl bg-amber/20 flex items-center justify-center flex-shrink-0">
            <i class="ti ti-alert-triangle text-amber text-xl"></i>
        </div>
        <div class="flex-1">
            <p class="text-sm font-bold text-text mb-1">{{ $isAr ? 'انسخ مفتاح API الجديد فوراً' : 'Copy your new API key immediately' }}</p>
            <p class="text-xs text-muted mb-3">
                @if ($isAr)
                    يُعرض هذا المفتاح <strong>مرة واحدة فقط</strong>. لا نخزّن المفاتيح الخام — لا يمكنك استرجاعه لاحقاً.
                    احفظه بأمان في مدير كلمات المرور أو متغير بيئة.
                @else
                    This key is shown <strong>only once</strong>. We do not store raw keys — you cannot retrieve it later.
                    Store it securely in a password manager or environment variable.
                @endif
            </p>
            <div class="flex items-center gap-2 bg-card border border-amber/40 rounded-xl px-4 py-3">
                <code class="flex-1 text-xs font-mono text-text break-all select-all">
                    {{ $newKeyFlash['key'] }}
                </code>
                <button onclick="
                    navigator.clipboard.writeText('{{ $newKeyFlash['key'] }}').then(() => {
                        this.innerHTML = '<i class=\'ti ti-check text-signal-deep\'></i>';
                        setTimeout(() => this.innerHTML = '<i class=\'ti ti-copy text-amber\'></i>', 2000);
                    });"
                    class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-lg
                           bg-amber/10 hover:bg-amber/20 border border-amber/40 transition-colors">
                    <i class="ti ti-copy text-amber text-sm"></i>
                </button>
            </div>
        </div>
        <button wire:click="dismissFlash"
                class="flex-shrink-0 text-amber hover:text-text transition-colors">
            <i class="ti ti-x text-sm"></i>
        </button>
    </div>
</div>
@endif

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">

    <div class="bg-card rounded-[14px] border border-line p-5">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-signal-dim flex items-center justify-center">
                    <i class="ti ti-key text-signal-deep text-base"></i>
                </div>
                <div>
                    <div class="text-sm font-bold text-text">{{ $isAr ? 'مفتاح الإنتاج' : 'Production key' }}</div>
                    <div class="text-[10px] text-signal-deep font-mono mt-0.5">wg_live_...</div>
                </div>
            </div>
            <span class="text-[10px] font-semibold px-2.5 py-1 rounded-full
                {{ $liveKeyExists ? 'bg-signal-dim text-signal-deep' : 'bg-paper text-muted' }}">
                {{ $liveKeyExists ? ($isAr ? 'نشط' : 'active') : ($isAr ? 'غير مُنشأ' : 'not generated') }}
            </span>
        </div>

        <div class="bg-paper border border-line rounded-xl px-4 py-3 mb-4">
            <code class="text-xs font-mono text-muted select-all">{{ $liveKeyDisplay }}</code>
        </div>

        @if ($lastUsedAt)
            <div class="flex items-center gap-2 text-[10px] text-muted mb-4">
                <i class="ti ti-clock text-sm"></i>
                {{ $isAr ? 'آخر استخدام:' : 'Last used:' }} {{ $lastUsedAt->diffForHumans() }}
                @if ($lastUsedIp)
                    {{ $isAr ? 'من' : 'from' }} <span class="font-mono">{{ $lastUsedIp }}</span>
                @endif
            </div>
        @endif

        <button wire:click="requestRegenerate('live')"
                class="w-full py-2.5 border border-danger/20 text-danger text-sm font-semibold
                       rounded-xl hover:bg-danger-dim transition-colors flex items-center justify-center gap-2 min-h-11">
            <i class="ti ti-refresh text-base"></i> {{ $isAr ? 'إعادة توليد مفتاح الإنتاج' : 'Regenerate live key' }}
        </button>
    </div>

    <div class="bg-card rounded-[14px] border border-line p-5">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-signal-dim flex items-center justify-center">
                    <i class="ti ti-flask text-signal-deep text-base"></i>
                </div>
                <div>
                    <div class="text-sm font-bold text-text">{{ $isAr ? 'مفتاح الاختبار' : 'Test / staging key' }}</div>
                    <div class="text-[10px] text-signal-deep font-mono mt-0.5">wg_test_...</div>
                </div>
            </div>
            <span class="text-[10px] font-semibold px-2.5 py-1 rounded-full bg-signal-dim text-signal-deep">
                {{ $isAr ? 'وضع الاختبار' : 'test mode' }}
            </span>
        </div>

        <div class="bg-paper border border-line rounded-xl px-4 py-3 mb-4">
            <code class="text-xs font-mono text-muted select-all">{{ $testKeyDisplay }}</code>
        </div>

        <div class="flex items-start gap-2 p-3 bg-signal-dim border border-signal/20 rounded-xl text-xs text-signal-deep mb-4">
            <i class="ti ti-info-circle flex-shrink-0 text-sm mt-0.5"></i>
            <span>{{ $isAr ? 'طلبات مفتاح الاختبار تُسجَّل بشكل منفصل ولا تؤثر على حد الرسائل اليومي.' : 'Test key requests are logged separately and do not affect your daily message limit.' }}</span>
        </div>

        <button wire:click="requestRegenerate('test')"
                class="w-full py-2.5 border border-danger/20 text-danger text-sm font-semibold
                       rounded-xl hover:bg-danger-dim transition-colors flex items-center justify-center gap-2 min-h-11">
            <i class="ti ti-refresh text-base"></i> {{ $isAr ? 'إعادة توليد مفتاح الاختبار' : 'Regenerate test key' }}
        </button>
    </div>
</div>

<div class="bg-paper border border-line rounded-[14px] p-4 mb-4 flex items-start gap-3">
    <i class="ti ti-shield-lock text-muted text-lg flex-shrink-0 mt-0.5"></i>
    <div class="text-xs text-muted leading-relaxed">
        <strong class="text-text">{{ $isAr ? 'نموذج الأمان:' : 'Security model:' }}</strong>
        @if ($isAr)
            تُخزَّن مفاتيح API كتجزئة SHA-256 — لا نخزّن المفتاح الخام أبداً.
            إذا فقدت مفتاحاً، أعد توليده. تتوقف جميع الطلبات بالمفتاح القديم فوراً بعد إعادة التوليد.
            احفظ المفاتيح في متغيرات البيئة، وليس في الشيفرة المصدرية.
        @else
            API keys are stored as SHA-256 hashes — we never store the raw key.
            If you lose a key, regenerate it. All existing requests using the old key will fail immediately after regeneration.
            Store keys in environment variables, never in source code.
        @endif
    </div>
</div>

<div class="bg-card rounded-[14px] border border-line p-5">
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-semibold text-text">{{ $isAr ? 'البدء السريع' : 'Quick start' }}</h3>
        <a href="{{ route('docs') }}" class="text-xs text-signal-deep hover:underline font-medium">
            {{ $isAr ? 'توثيق API الكامل ←' : 'Full API docs →' }}
        </a>
    </div>
    <div class="bg-ink rounded-xl p-4 text-xs font-mono text-paper-on-dark leading-relaxed overflow-x-auto">
        <span class="text-muted-dark"># Send a text message</span><br>
        curl -X POST {{ url('/api/v1/messages/send/text') }} \<br>
        &nbsp;&nbsp;-H <span class="text-signal">"Authorization: Bearer wg_live_..."</span> \<br>
        &nbsp;&nbsp;-H <span class="text-signal">"Content-Type: application/json"</span> \<br>
        &nbsp;&nbsp;-d <span class="text-paper-on-dark">'{"device_id":"dev_xxx","to":"213700000001","body":"Hello!"}'</span>
    </div>
</div>

@if ($showRegenConfirm)
<div class="fixed inset-0 bg-ink/50 flex items-center justify-center z-50 p-4">
    <div class="bg-card rounded-[14px] w-96 max-w-full p-6 shadow-2xl border border-line">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-11 h-11 rounded-xl bg-danger-dim flex items-center justify-center flex-shrink-0">
                <i class="ti ti-alert-triangle text-danger text-xl"></i>
            </div>
            <div>
                <h3 class="text-sm font-bold text-text">{{ $isAr ? 'تأكيد إعادة التوليد' : 'Confirm key regeneration' }}</h3>
                <p class="text-xs text-muted mt-0.5">
                    {{ $regenType === 'live' ? ($isAr ? 'مفتاح الإنتاج' : 'Production') : ($isAr ? 'مفتاح الاختبار' : 'Test') }} {{ $isAr ? 'سيُلغى فوراً.' : 'key will be invalidated immediately.' }}
                </p>
            </div>
        </div>

        <div class="bg-danger-dim border border-danger/20 rounded-xl p-3 mb-4 text-xs text-danger">
            <i class="ti ti-circle-x me-1"></i>
            {{ $isAr ? 'ستتوقف جميع التطبيقات التي تستخدم المفتاح الحالي فوراً. حدّث ملفات .env بعد إعادة التوليد.' : 'All applications using the current key will stop working instantly. Make sure you update your .env files after regenerating.' }}
        </div>

        <div class="mb-5">
            <label class="block text-xs font-semibold text-text mb-1.5">
                {{ $isAr ? 'أكد كلمة المرور للمتابعة' : 'Confirm your password to proceed' }}
            </label>
            <input wire:model="confirmPassword"
                   type="password"
                   placeholder="{{ $isAr ? 'كلمة مرور الحساب' : 'Your account password' }}"
                   wire:keydown.enter="confirmRegenerate"
                   class="w-full border {{ $passwordError ? 'border-danger bg-danger-dim' : 'border-line' }}
                          rounded-xl px-3.5 py-2.5 text-base outline-none min-h-11
                          focus:border-signal focus:ring-2 focus:ring-signal/20 transition-all" />
            @if ($passwordError)
                <p class="text-xs text-danger mt-1.5 flex items-center gap-1">
                    <i class="ti ti-circle-x text-sm"></i> {{ $isAr ? 'كلمة المرور غير صحيحة. حاول مرة أخرى.' : 'Incorrect password. Try again.' }}
                </p>
            @endif
        </div>

        <div class="flex gap-3">
            <button wire:click="cancelRegen"
                    class="flex-1 py-2.5 border border-line rounded-xl text-sm text-muted
                           hover:bg-paper transition-colors font-medium min-h-11">
                {{ $isAr ? 'إلغاء' : 'Cancel' }}
            </button>
            <button wire:click="confirmRegenerate"
                    class="flex-1 py-2.5 bg-danger text-white text-sm font-bold rounded-xl
                           hover:opacity-90 transition-colors min-h-11">
                <span wire:loading.remove wire:target="confirmRegenerate">{{ $isAr ? 'إعادة التوليد' : 'Regenerate' }}</span>
                <span wire:loading wire:target="confirmRegenerate">{{ $isAr ? 'جارٍ المعالجة…' : 'Processing…' }}</span>
            </button>
        </div>
    </div>
</div>
@endif
</div>
