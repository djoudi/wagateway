<div>
{{-- ── One-time key flash ────────────────────────────────────────────────── --}}
@if ($newKeyFlash)
<div class="mb-5 bg-amber-50 border-2 border-amber-300 rounded-2xl p-5">
    <div class="flex items-start gap-3">
        <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center flex-shrink-0">
            <i class="ti ti-alert-triangle text-amber-600 text-xl"></i>
        </div>
        <div class="flex-1">
            <p class="text-sm font-bold text-amber-900 mb-1">Copy your new API key immediately</p>
            <p class="text-xs text-amber-700 mb-3">
                This key is shown <strong>only once</strong>. We do not store raw keys — you cannot retrieve it later.
                Store it securely in a password manager or environment variable.
            </p>
            <div class="flex items-center gap-2 bg-white border border-amber-200 rounded-xl px-4 py-3">
                <code class="flex-1 text-xs font-mono text-gray-900 break-all select-all">
                    {{ $newKeyFlash['key'] }}
                </code>
                <button onclick="
                    navigator.clipboard.writeText('{{ $newKeyFlash['key'] }}').then(() => {
                        this.innerHTML = '<i class=\'ti ti-check text-green-600\'></i>';
                        setTimeout(() => this.innerHTML = '<i class=\'ti ti-copy text-amber-600\'></i>', 2000);
                    });"
                    class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-lg
                           bg-amber-50 hover:bg-amber-100 border border-amber-200 transition-colors">
                    <i class="ti ti-copy text-amber-600 text-sm"></i>
                </button>
            </div>
        </div>
        <button wire:click="dismissFlash"
                class="flex-shrink-0 text-amber-400 hover:text-amber-600 transition-colors">
            <i class="ti ti-x text-sm"></i>
        </button>
    </div>
</div>
@endif

{{-- ── Key Cards ─────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 gap-4 mb-4">

    {{-- Live Key --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-green-100 flex items-center justify-center">
                    <i class="ti ti-key text-green-700 text-base"></i>
                </div>
                <div>
                    <div class="text-sm font-bold text-gray-900">Production key</div>
                    <div class="text-[10px] text-green-600 font-mono mt-0.5">wg_live_...</div>
                </div>
            </div>
            <span class="text-[10px] font-semibold px-2.5 py-1 rounded-full
                {{ $liveKeyExists ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                {{ $liveKeyExists ? 'active' : 'not generated' }}
            </span>
        </div>

        {{-- Masked display only — no raw key ever in DOM --}}
        <div class="bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 mb-4">
            <code class="text-xs font-mono text-gray-600 select-all">{{ $liveKeyDisplay }}</code>
        </div>

        @if ($lastUsedAt)
            <div class="flex items-center gap-2 text-[10px] text-gray-400 mb-4">
                <i class="ti ti-clock text-sm"></i>
                Last used: {{ $lastUsedAt->diffForHumans() }}
                @if ($lastUsedIp)
                    from <span class="font-mono">{{ $lastUsedIp }}</span>
                @endif
            </div>
        @endif

        <button wire:click="requestRegenerate('live')"
                class="w-full py-2.5 border border-red-100 text-red-500 text-sm font-semibold
                       rounded-xl hover:bg-red-50 transition-colors flex items-center justify-center gap-2">
            <i class="ti ti-refresh text-base"></i> Regenerate live key
        </button>
    </div>

    {{-- Test Key --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-blue-100 flex items-center justify-center">
                    <i class="ti ti-flask text-blue-700 text-base"></i>
                </div>
                <div>
                    <div class="text-sm font-bold text-gray-900">Test / staging key</div>
                    <div class="text-[10px] text-blue-600 font-mono mt-0.5">wg_test_...</div>
                </div>
            </div>
            <span class="text-[10px] font-semibold px-2.5 py-1 rounded-full bg-blue-100 text-blue-700">
                test mode
            </span>
        </div>

        <div class="bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 mb-4">
            <code class="text-xs font-mono text-gray-600 select-all">{{ $testKeyDisplay }}</code>
        </div>

        <div class="flex items-start gap-2 p-3 bg-blue-50 border border-blue-100 rounded-xl text-xs text-blue-700 mb-4">
            <i class="ti ti-info-circle flex-shrink-0 text-sm mt-0.5"></i>
            <span>Test key requests are logged separately and do not affect your daily message limit.</span>
        </div>

        <button wire:click="requestRegenerate('test')"
                class="w-full py-2.5 border border-red-100 text-red-500 text-sm font-semibold
                       rounded-xl hover:bg-red-50 transition-colors flex items-center justify-center gap-2">
            <i class="ti ti-refresh text-base"></i> Regenerate test key
        </button>
    </div>
</div>

{{-- ── Security notice --}}
<div class="bg-gray-50 border border-gray-200 rounded-2xl p-4 mb-4 flex items-start gap-3">
    <i class="ti ti-shield-lock text-gray-500 text-lg flex-shrink-0 mt-0.5"></i>
    <div class="text-xs text-gray-600 leading-relaxed">
        <strong class="text-gray-800">Security model:</strong>
        API keys are stored as SHA-256 hashes — we never store the raw key.
        If you lose a key, regenerate it. All existing requests using the old key will fail immediately after regeneration.
        Store keys in environment variables, never in source code.
    </div>
</div>

{{-- ── Quick-start code ────────────────────────────────────────────────── --}}
<div class="bg-white rounded-2xl border border-gray-100 p-5">
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-semibold text-gray-900">Quick start</h3>
        <a href="{{ route('docs') }}" class="text-xs text-[#25D366] hover:underline font-medium">
            Full API docs →
        </a>
    </div>
    <div class="bg-gray-900 rounded-xl p-4 text-xs font-mono text-gray-300 leading-relaxed overflow-x-auto">
        <span class="text-gray-500"># Send a text message</span><br>
        curl -X POST {{ url('/api/v1/messages/send/text') }} \<br>
        &nbsp;&nbsp;-H <span class="text-green-400">"Authorization: Bearer wg_live_..."</span> \<br>
        &nbsp;&nbsp;-H <span class="text-green-400">"Content-Type: application/json"</span> \<br>
        &nbsp;&nbsp;-d <span class="text-blue-400">'{"device_id":"dev_xxx","to":"213700000001","body":"Hello!"}'</span>
    </div>
</div>

{{-- ── Regenerate confirm modal ─────────────────────────────────────────── --}}
@if ($showRegenConfirm)
<div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl w-96 p-6 shadow-2xl">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-11 h-11 rounded-xl bg-red-100 flex items-center justify-center flex-shrink-0">
                <i class="ti ti-alert-triangle text-red-500 text-xl"></i>
            </div>
            <div>
                <h3 class="text-sm font-bold text-gray-900">Confirm key regeneration</h3>
                <p class="text-xs text-gray-500 mt-0.5">
                    {{ $regenType === 'live' ? 'Production' : 'Test' }} key will be invalidated immediately.
                </p>
            </div>
        </div>

        <div class="bg-red-50 border border-red-100 rounded-xl p-3 mb-4 text-xs text-red-700">
            <i class="ti ti-circle-x mr-1"></i>
            All applications using the current key will stop working instantly.
            Make sure you update your .env files after regenerating.
        </div>

        <div class="mb-5">
            <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                Confirm your password to proceed
            </label>
            <input wire:model="confirmPassword"
                   type="password"
                   placeholder="Your account password"
                   wire:keydown.enter="confirmRegenerate"
                   class="w-full border {{ $passwordError ? 'border-red-400 bg-red-50' : 'border-gray-200' }}
                          rounded-xl px-3.5 py-2.5 text-sm outline-none
                          focus:border-[#25D366] focus:ring-2 focus:ring-[#25D366]/20 transition-all" />
            @if ($passwordError)
                <p class="text-xs text-red-500 mt-1.5 flex items-center gap-1">
                    <i class="ti ti-circle-x text-sm"></i> Incorrect password. Try again.
                </p>
            @endif
        </div>

        <div class="flex gap-3">
            <button wire:click="cancelRegen"
                    class="flex-1 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-600
                           hover:bg-gray-50 transition-colors font-medium">
                Cancel
            </button>
            <button wire:click="confirmRegenerate"
                    class="flex-1 py-2.5 bg-red-500 text-white text-sm font-bold rounded-xl
                           hover:bg-red-600 transition-colors">
                <span wire:loading.remove wire:target="confirmRegenerate">Regenerate</span>
                <span wire:loading wire:target="confirmRegenerate">Processing…</span>
            </button>
        </div>
    </div>
</div>
@endif
</div>
