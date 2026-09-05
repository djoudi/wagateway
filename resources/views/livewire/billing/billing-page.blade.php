<div>
@php
    $user = auth()->user();
    $isAr = app()->getLocale() === 'ar';
@endphp

<div class="bg-card rounded-[14px] border border-signal/40 p-4 mb-5 flex flex-col lg:flex-row lg:items-center gap-4">
    <div class="w-10 h-10 rounded-xl bg-signal-dim flex items-center justify-center flex-shrink-0">
        <i class="ti ti-crown text-signal-deep text-lg"></i>
    </div>
    <div class="flex-1">
        <div class="text-sm font-semibold text-text">
            {{ $isAr ? 'الخطة الحالية:' : 'Current plan:' }} <span class="text-signal">{{ $user->plan?->name ?? 'Free' }}</span>
        </div>
        <div class="text-xs text-muted mt-0.5">
            @if ($user->plan_expires_at)
                {{ $isAr ? 'تُجدَّد' : 'Renews' }} {{ $user->plan_expires_at->format('d M Y') }}
            @else
                {{ $isAr ? 'لا يوجد اشتراك نشط' : 'No active subscription' }}
            @endif
        </div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-center">
        <div>
            <div class="text-xs text-muted">{{ $isAr ? 'رسائل/يوم' : 'Messages/day' }}</div>
            <div class="text-sm font-bold text-text">{{ number_format($usageStats['messages_today']) }}<span class="text-muted font-normal">/{{ number_format($usageStats['daily_limit']) }}</span></div>
        </div>
        <div>
            <div class="text-xs text-muted">{{ $isAr ? 'الأجهزة' : 'Devices' }}</div>
            <div class="text-sm font-bold text-text">{{ $usageStats['devices_used'] }}<span class="text-muted font-normal">/{{ $usageStats['devices_max'] }}</span></div>
        </div>
        <div>
            <div class="text-xs text-muted">Webhooks</div>
            <div class="text-sm font-bold text-text">{{ $usageStats['webhooks_used'] }}<span class="text-muted font-normal">/{{ $usageStats['webhooks_max'] }}</span></div>
        </div>
    </div>
</div>

<div class="flex items-center justify-center gap-2 mb-5">
    <span class="text-sm text-muted">{{ $isAr ? 'شهري' : 'Monthly' }}</span>
    <button wire:click="$set('billingCycle', billingCycle === 'monthly' ? 'yearly' : 'monthly')"
            class="relative w-10 h-5 rounded-full transition-colors {{ $billingCycle === 'yearly' ? 'bg-signal' : 'bg-line' }}">
        <span class="absolute top-0.5 w-4 h-4 bg-card rounded-full shadow transition-transform
            {{ $billingCycle === 'yearly' ? 'translate-x-5' : 'translate-x-0.5' }}"></span>
    </button>
    <span class="text-sm text-muted">{{ $isAr ? 'سنوي' : 'Yearly' }} <span class="text-[10px] font-semibold text-signal-deep bg-signal-dim px-1.5 py-0.5 rounded-full ms-1">{{ $isAr ? 'وفّر 20%' : 'Save 20%' }}</span></span>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    @foreach ($plans as $plan)
        @php $isCurrent = $user->plan_id === $plan->id; @endphp
        <div class="bg-card rounded-[14px] border p-5 flex flex-col relative
            {{ $plan->slug === 'pro' ? 'border-signal shadow-sm' : 'border-line' }}">

            @if ($plan->slug === 'pro')
                <div class="absolute -top-2.5 start-1/2 -translate-x-1/2">
                    <span class="px-3 py-0.5 bg-signal text-[#06170F] text-[10px] font-semibold rounded-full">
                        {{ $isAr ? 'الأكثر طلباً' : 'Most popular' }}
                    </span>
                </div>
            @endif

            <div class="mb-4">
                <div class="text-xs font-medium text-muted mb-1">{{ $plan->name }}</div>
                <div class="flex items-end gap-1">
                    <span class="text-2xl font-bold text-text">
                        {{ number_format($billingCycle === 'yearly' ? $plan->price_yearly / 12 : $plan->price_monthly) }}
                    </span>
                    <span class="text-sm text-muted mb-1">DZD/mo</span>
                </div>
                @if ($billingCycle === 'yearly')
                    <div class="text-[10px] text-signal-deep font-medium">
                        {{ $isAr ? 'يُفوتر سنوياً:' : 'Billed yearly:' }} {{ number_format($plan->price_yearly) }} DZD
                    </div>
                @endif
            </div>

            <div class="space-y-2 mb-5 flex-1">
                <div class="flex items-center gap-2 text-xs text-muted">
                    <i class="ti ti-check text-signal flex-shrink-0"></i>
                    {{ number_format($plan->daily_message_limit) }} {{ $isAr ? 'رسالة/يوم' : 'messages/day' }}
                </div>
                <div class="flex items-center gap-2 text-xs text-muted">
                    <i class="ti ti-check text-signal flex-shrink-0"></i>
                    {{ $plan->max_devices }} {{ $isAr ? 'أجهزة متصلة' : 'connected devices' }}
                </div>
                <div class="flex items-center gap-2 text-xs text-muted">
                    <i class="ti ti-check text-signal flex-shrink-0"></i>
                    {{ $plan->max_webhooks }} webhooks
                </div>
                @foreach ($plan->features as $feature)
                    <div class="flex items-center gap-2 text-xs text-muted">
                        <i class="ti ti-check text-signal flex-shrink-0"></i>
                        {{ str_replace('_', ' ', ucfirst($feature)) }}
                    </div>
                @endforeach
            </div>

            @if ($isCurrent)
                <div class="w-full py-2 text-center text-sm font-medium text-signal-deep bg-signal-dim rounded-lg border border-signal/30 min-h-11 flex items-center justify-center">
                    <i class="ti ti-circle-check me-1"></i> {{ $isAr ? 'الخطة الحالية' : 'Current plan' }}
                </div>
            @else
                <button wire:click="selectPlan({{ $plan->id }})"
                        class="w-full py-2 text-sm font-semibold rounded-lg transition-colors min-h-11
                            {{ $plan->slug === 'pro'
                                ? 'bg-signal text-[#06170F] hover:bg-[#37B879]'
                                : 'border border-line text-text hover:bg-paper' }}">
                    {{ $plan->price_monthly > ($user->plan?->price_monthly ?? 0) ? ($isAr ? 'ترقية' : 'Upgrade') : ($isAr ? 'تخفيض' : 'Downgrade') }}
                </button>
            @endif
        </div>
    @endforeach
</div>

@if ($showUpgradeModal && $selectedPlanId)
    @php $selectedPlan = $plans->firstWhere('id', $selectedPlanId); @endphp
    <div class="fixed inset-0 bg-ink/50 flex items-center justify-center z-50 p-4">
        <div class="bg-card rounded-[14px] w-[440px] max-w-full p-6 shadow-xl border border-line">
            <div class="text-sm font-semibold text-text mb-1">
                {{ $isAr ? 'الترقية إلى' : 'Upgrade to' }} {{ $selectedPlan?->name }}
            </div>
            <p class="text-xs text-muted mb-5">
                {{ $isAr ? 'اختر طريقة الدفع. تُفعَّل خطتك بعد تأكيد الدفع.' : 'Choose your payment method. Your plan will be activated after payment confirmation.' }}
            </p>

            <div class="bg-paper rounded-xl p-4 mb-4">
                <div class="flex justify-between text-xs mb-1">
                    <span class="text-muted">{{ $selectedPlan?->name }} ({{ $billingCycle }})</span>
                    <span class="font-semibold text-text">
                        {{ number_format($billingCycle === 'yearly' ? $selectedPlan?->price_yearly : $selectedPlan?->price_monthly) }} DZD
                    </span>
                </div>
                <div class="flex justify-between text-xs">
                    <span class="text-muted">{{ $isAr ? 'دورة الفوترة' : 'Billing cycle' }}</span>
                    <span class="text-text capitalize">{{ $billingCycle }}</span>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-xs font-medium text-muted mb-2">{{ $isAr ? 'طريقة الدفع' : 'Payment method' }}</label>
                <div class="space-y-2">
                    @foreach ([
                        'card'          => ['label' => $isAr ? 'بطاقة Edahabia / CIB' : 'Edahabia / CIB card', 'sub' => $isAr ? 'تفعيل فوري عبر Chargily' : 'Instant activation via Chargily'],
                        'ccp'           => ['label' => $isAr ? 'CCP (Algérie Poste)' : 'CCP (Algérie Poste)',  'sub' => $isAr ? 'تفعيل خلال 24 ساعة من التأكيد' : 'Activated within 24h of confirmation'],
                        'bank_transfer' => ['label' => $isAr ? 'تحويل بنكي' : 'Bank transfer',           'sub' => $isAr ? 'تفعيل خلال 24 ساعة من التأكيد' : 'Activated within 24h of confirmation'],
                    ] as $val => $opt)
                        <label class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-colors
                            {{ $paymentMethod === $val ? 'border-signal bg-signal-dim' : 'border-line hover:border-muted' }}">
                            <input type="radio" wire:model="paymentMethod" value="{{ $val }}"
                                   class="accent-[#2FA66B]" />
                            <span class="flex-1">
                                <span class="block text-sm text-text">{{ $opt['label'] }}</span>
                                <span class="block text-[11px] text-muted">{{ $opt['sub'] }}</span>
                            </span>
                            @if ($val === 'card')
                                <span class="text-[10px] font-semibold px-2 py-0.5 bg-signal-dim text-signal-deep rounded-full">{{ $isAr ? 'موصى به' : 'Recommended' }}</span>
                            @endif
                        </label>
                    @endforeach
                </div>
            </div>

            @if ($paymentMethod === 'card')
                <div class="mb-4 p-3 bg-signal-dim border border-signal/20 rounded-lg text-xs text-signal-deep">
                    <i class="ti ti-shield-check me-1"></i>
                    {{ $isAr ? 'سنُحوّلك لبوابة دفع آمنة (Chargily) لإتمام العملية. تُفعَّل خطتك تلقائياً فور تأكيد الدفع.' : 'We will redirect you to a secure payment gateway (Chargily). Your plan activates automatically after payment confirmation.' }}
                </div>
            @else
                <div class="mb-4 p-3 bg-signal-dim border border-signal/20 rounded-lg text-xs text-signal-deep">
                    <i class="ti ti-info-circle me-1"></i>
                    {{ $isAr ? 'بعد التأكيد، سنُرسل لك تفاصيل الدفع بالبريد الإلكتروني. تُفعَّل خطتك خلال 24 ساعة من تأكيد استلام الدفع.' : 'After confirming, we will email you payment details. Your plan activates within 24 hours of payment receipt.' }}
                </div>
            @endif

            <div class="flex gap-3">
                <button wire:click="closeModal" wire:loading.attr="disabled" wire:target="requestUpgrade"
                        class="flex-1 py-2.5 border border-line rounded-xl text-sm text-muted hover:bg-paper transition-colors disabled:opacity-50 min-h-11">
                    {{ $isAr ? 'إلغاء' : 'Cancel' }}
                </button>
                <button wire:click="requestUpgrade" wire:loading.attr="disabled" wire:target="requestUpgrade"
                        class="flex-1 py-2.5 bg-signal text-[#06170F] text-sm font-semibold rounded-xl hover:bg-[#37B879] transition-colors disabled:opacity-70 min-h-11">
                    <span wire:loading.remove wire:target="requestUpgrade">
                        @if ($paymentMethod === 'card')
                            {{ $isAr ? 'المتابعة إلى الدفع' : 'Continue to payment' }}
                        @else
                            {{ $isAr ? 'تأكيد الطلب' : 'Confirm order' }}
                        @endif
                    </span>
                    <span wire:loading wire:target="requestUpgrade">{{ $isAr ? 'جارٍ التحويل…' : 'Redirecting…' }}</span>
                </button>
            </div>
        </div>
    </div>
@endif
</div>
