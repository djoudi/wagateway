<div>
@php $user = auth()->user(); @endphp

{{-- Current plan banner --}}
<div class="bg-white rounded-xl border border-green-200 p-4 mb-5 flex items-center gap-4">
    <div class="w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center flex-shrink-0">
        <i class="ti ti-crown text-green-700 text-lg"></i>
    </div>
    <div class="flex-1">
        <div class="text-sm font-semibold text-gray-900">
            Current plan: <span class="text-[#25D366]">{{ $user->plan?->name ?? 'Free' }}</span>
        </div>
        <div class="text-xs text-gray-500 mt-0.5">
            @if ($user->plan_expires_at)
                Renews {{ $user->plan_expires_at->format('d M Y') }}
            @else
                No active subscription
            @endif
        </div>
    </div>
    <div class="grid grid-cols-3 gap-4 text-center">
        <div>
            <div class="text-xs text-gray-400">Messages/day</div>
            <div class="text-sm font-bold text-gray-900">{{ number_format($usageStats['messages_today']) }}<span class="text-gray-400 font-normal">/{{ number_format($usageStats['daily_limit']) }}</span></div>
        </div>
        <div>
            <div class="text-xs text-gray-400">Devices</div>
            <div class="text-sm font-bold text-gray-900">{{ $usageStats['devices_used'] }}<span class="text-gray-400 font-normal">/{{ $usageStats['devices_max'] }}</span></div>
        </div>
        <div>
            <div class="text-xs text-gray-400">Webhooks</div>
            <div class="text-sm font-bold text-gray-900">{{ $usageStats['webhooks_used'] }}<span class="text-gray-400 font-normal">/{{ $usageStats['webhooks_max'] }}</span></div>
        </div>
    </div>
</div>

{{-- Billing cycle toggle --}}
<div class="flex items-center justify-center gap-2 mb-5">
    <span class="text-sm text-gray-600">Monthly</span>
    <button wire:click="$set('billingCycle', billingCycle === 'monthly' ? 'yearly' : 'monthly')"
            class="relative w-10 h-5 rounded-full transition-colors {{ $billingCycle === 'yearly' ? 'bg-[#25D366]' : 'bg-gray-200' }}">
        <span class="absolute top-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform
            {{ $billingCycle === 'yearly' ? 'translate-x-5' : 'translate-x-0.5' }}"></span>
    </button>
    <span class="text-sm text-gray-600">Yearly <span class="text-[10px] font-semibold text-green-600 bg-green-100 px-1.5 py-0.5 rounded-full ml-1">Save 20%</span></span>
</div>

{{-- Plans grid --}}
<div class="grid grid-cols-3 gap-4 mb-6">
    @foreach ($plans as $plan)
        @php $isCurrent = $user->plan_id === $plan->id; @endphp
        <div class="bg-white rounded-xl border p-5 flex flex-col relative
            {{ $plan->slug === 'pro' ? 'border-[#25D366] shadow-sm shadow-green-100' : 'border-gray-100' }}">

            @if ($plan->slug === 'pro')
                <div class="absolute -top-2.5 left-1/2 -translate-x-1/2">
                    <span class="px-3 py-0.5 bg-[#25D366] text-white text-[10px] font-semibold rounded-full">
                        Most popular
                    </span>
                </div>
            @endif

            <div class="mb-4">
                <div class="text-xs font-medium text-gray-500 mb-1">{{ $plan->name }}</div>
                <div class="flex items-end gap-1">
                    <span class="text-2xl font-bold text-gray-900">
                        {{ number_format($billingCycle === 'yearly' ? $plan->price_yearly / 12 : $plan->price_monthly) }}
                    </span>
                    <span class="text-sm text-gray-400 mb-1">DZD/mo</span>
                </div>
                @if ($billingCycle === 'yearly')
                    <div class="text-[10px] text-green-600 font-medium">
                        Billed yearly: {{ number_format($plan->price_yearly) }} DZD
                    </div>
                @endif
            </div>

            <div class="space-y-2 mb-5 flex-1">
                <div class="flex items-center gap-2 text-xs text-gray-600">
                    <i class="ti ti-check text-green-500 flex-shrink-0"></i>
                    {{ number_format($plan->daily_message_limit) }} messages/day
                </div>
                <div class="flex items-center gap-2 text-xs text-gray-600">
                    <i class="ti ti-check text-green-500 flex-shrink-0"></i>
                    {{ $plan->max_devices }} connected devices
                </div>
                <div class="flex items-center gap-2 text-xs text-gray-600">
                    <i class="ti ti-check text-green-500 flex-shrink-0"></i>
                    {{ $plan->max_webhooks }} webhooks
                </div>
                @foreach ($plan->features as $feature)
                    <div class="flex items-center gap-2 text-xs text-gray-600">
                        <i class="ti ti-check text-green-500 flex-shrink-0"></i>
                        {{ str_replace('_', ' ', ucfirst($feature)) }}
                    </div>
                @endforeach
            </div>

            @if ($isCurrent)
                <div class="w-full py-2 text-center text-sm font-medium text-green-700 bg-green-50 rounded-lg border border-green-200">
                    <i class="ti ti-circle-check mr-1"></i> Current plan
                </div>
            @else
                <button wire:click="selectPlan({{ $plan->id }})"
                        class="w-full py-2 text-sm font-semibold rounded-lg transition-colors
                            {{ $plan->slug === 'pro'
                                ? 'bg-[#25D366] text-white hover:bg-green-600'
                                : 'border border-gray-200 text-gray-700 hover:bg-gray-50' }}">
                    {{ $plan->price_monthly > ($user->plan?->price_monthly ?? 0) ? 'Upgrade' : 'Downgrade' }}
                </button>
            @endif
        </div>
    @endforeach
</div>

{{-- Upgrade Modal --}}
@if ($showUpgradeModal && $selectedPlanId)
    @php $selectedPlan = $plans->firstWhere('id', $selectedPlanId); @endphp
    <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl w-[440px] p-6 shadow-xl">
            <div class="text-sm font-semibold text-gray-900 mb-1">
                Upgrade to {{ $selectedPlan?->name }}
            </div>
            <p class="text-xs text-gray-400 mb-5">
                Choose your payment method. Your plan will be activated after payment confirmation.
            </p>

            {{-- Price summary --}}
            <div class="bg-gray-50 rounded-xl p-4 mb-4">
                <div class="flex justify-between text-xs mb-1">
                    <span class="text-gray-500">{{ $selectedPlan?->name }} ({{ $billingCycle }})</span>
                    <span class="font-semibold text-gray-900">
                        {{ number_format($billingCycle === 'yearly' ? $selectedPlan?->price_yearly : $selectedPlan?->price_monthly) }} DZD
                    </span>
                </div>
                <div class="flex justify-between text-xs">
                    <span class="text-gray-400">Billing cycle</span>
                    <span class="text-gray-700 capitalize">{{ $billingCycle }}</span>
                </div>
            </div>

            {{-- Payment method --}}
            <div class="mb-4">
                <label class="block text-xs font-medium text-gray-600 mb-2">Payment method</label>
                <div class="space-y-2">
                    @foreach ([
                        'card'          => ['label' => 'بطاقة Edahabia / CIB', 'sub' => 'تفعيل فوري عبر Chargily'],
                        'ccp'           => ['label' => 'CCP (Algérie Poste)',  'sub' => 'تفعيل خلال 24 ساعة من التأكيد'],
                        'bank_transfer' => ['label' => 'تحويل بنكي',           'sub' => 'تفعيل خلال 24 ساعة من التأكيد'],
                    ] as $val => $opt)
                        <label class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-colors
                            {{ $paymentMethod === $val ? 'border-[#25D366] bg-green-50' : 'border-gray-100 hover:border-gray-200' }}">
                            <input type="radio" wire:model="paymentMethod" value="{{ $val }}"
                                   class="accent-[#25D366]" />
                            <span class="flex-1">
                                <span class="block text-sm text-gray-700">{{ $opt['label'] }}</span>
                                <span class="block text-[11px] text-gray-400">{{ $opt['sub'] }}</span>
                            </span>
                            @if ($val === 'card')
                                <span class="text-[10px] font-semibold px-2 py-0.5 bg-green-100 text-green-700 rounded-full">موصى به</span>
                            @endif
                        </label>
                    @endforeach
                </div>
            </div>

            @if ($paymentMethod === 'card')
                <div class="mb-4 p-3 bg-green-50 border border-green-100 rounded-lg text-xs text-green-700">
                    <i class="ti ti-shield-check mr-1"></i>
                    سنُحوّلك لبوابة دفع آمنة (Chargily) لإتمام العملية. تُفعَّل خطتك تلقائياً فور تأكيد الدفع.
                </div>
            @else
                <div class="mb-4 p-3 bg-blue-50 border border-blue-100 rounded-lg text-xs text-blue-700">
                    <i class="ti ti-info-circle mr-1"></i>
                    بعد التأكيد، سنُرسل لك تفاصيل الدفع بالبريد الإلكتروني. تُفعَّل خطتك خلال 24 ساعة من تأكيد استلام الدفع.
                </div>
            @endif

            <div class="flex gap-3">
                <button wire:click="closeModal" wire:loading.attr="disabled" wire:target="requestUpgrade"
                        class="flex-1 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 transition-colors disabled:opacity-50">
                    Cancel
                </button>
                <button wire:click="requestUpgrade" wire:loading.attr="disabled" wire:target="requestUpgrade"
                        class="flex-1 py-2.5 bg-[#25D366] text-white text-sm font-semibold rounded-xl hover:bg-green-600 transition-colors disabled:opacity-70">
                    <span wire:loading.remove wire:target="requestUpgrade">
                        {{ $paymentMethod === 'card' ? 'المتابعة إلى الدفع' : 'تأكيد الطلب' }}
                    </span>
                    <span wire:loading wire:target="requestUpgrade">جارٍ التحويل…</span>
                </button>
            </div>
        </div>
    </div>
@endif
</div>
