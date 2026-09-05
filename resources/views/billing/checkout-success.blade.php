@php $isAr = app()->getLocale() === 'ar'; @endphp
<x-layouts.app :title="$isAr ? 'جاري تأكيد الدفع' : 'Payment confirmation'">

<div class="max-w-md mx-auto mt-10 bg-card rounded-[14px] border border-line p-8 text-center">

    @if ($invoice->status === 'paid')
        <div class="w-14 h-14 rounded-full bg-signal-dim flex items-center justify-center mx-auto mb-4">
            <i class="ti ti-circle-check text-3xl text-signal-deep"></i>
        </div>
        <h1 class="text-lg font-bold text-text mb-2">{{ $isAr ? 'تم الدفع بنجاح' : 'Payment successful' }}</h1>
        <p class="text-sm text-muted mb-6">
            @if ($isAr)
                تم تفعيل خطة {{ $invoice->plan->name }} على حسابك.
            @else
                The {{ $invoice->plan->name }} plan is now active on your account.
            @endif
        </p>
    @else
        <div class="w-14 h-14 rounded-full bg-amber/20 flex items-center justify-center mx-auto mb-4">
            <div class="w-6 h-6 border-2 border-amber border-t-transparent rounded-full animate-spin"></div>
        </div>
        <h1 class="text-lg font-bold text-text mb-2">{{ $isAr ? 'جاري تأكيد الدفع…' : 'Confirming payment…' }}</h1>
        <p class="text-sm text-muted mb-6">
            @if ($isAr)
                تم استلام دفعتك بنجاح من بوابة الدفع، وننتظر تأكيداً نهائياً لتفعيل خطتك تلقائياً.
                هذا يستغرق عادة أقل من دقيقة. هذه الصفحة تُحدَّث تلقائياً.
            @else
                Your payment was received from the gateway. We are waiting for final confirmation to activate your plan automatically.
                This usually takes less than a minute. This page refreshes automatically.
            @endif
        </p>
        <script>
            setTimeout(() => window.location.reload(), 4000);
        </script>
    @endif

    <div class="bg-paper rounded-xl p-4 mb-6 text-start text-xs space-y-1.5">
        <div class="flex justify-between"><span class="text-muted">{{ $isAr ? 'رقم الفاتورة' : 'Invoice number' }}</span><span class="font-mono font-semibold">{{ $invoice->invoice_number }}</span></div>
        <div class="flex justify-between"><span class="text-muted">{{ $isAr ? 'الخطة' : 'Plan' }}</span><span class="font-semibold">{{ $invoice->plan->name }}</span></div>
        <div class="flex justify-between"><span class="text-muted">{{ $isAr ? 'المبلغ' : 'Amount' }}</span><span class="font-semibold">{{ number_format($invoice->amount) }} د.ج</span></div>
    </div>

    <a href="{{ route('billing') }}" class="inline-block w-full py-2.5 bg-signal text-[#06170F] text-sm font-semibold rounded-xl hover:bg-[#37B879] transition-colors min-h-11">
        {{ $isAr ? 'الذهاب إلى الفوترة' : 'Go to billing' }}
    </a>
</div>

</x-layouts.app>
