@php $isAr = app()->getLocale() === 'ar'; @endphp
<x-layouts.app :title="$isAr ? 'فشلت عملية الدفع' : 'Payment failed'">

<div class="max-w-md mx-auto mt-10 bg-card rounded-[14px] border border-line p-8 text-center">

    <div class="w-14 h-14 rounded-full bg-danger-dim flex items-center justify-center mx-auto mb-4">
        <i class="ti ti-x text-3xl text-danger"></i>
    </div>
    <h1 class="text-lg font-bold text-text mb-2">{{ $isAr ? 'لم تكتمل عملية الدفع' : 'Payment did not complete' }}</h1>
    <p class="text-sm text-muted mb-6">
        @if ($isAr)
            لم يتم تحصيل أي مبلغ من حسابك. يمكنك المحاولة مرة أخرى أو اختيار طريقة دفع بديلة
            (تحويل CCP أو بنكي) من صفحة الفوترة.
        @else
            No amount was charged. You can try again or choose an alternative payment method
            (CCP or bank transfer) from the billing page.
        @endif
    </p>

    <div class="bg-paper rounded-xl p-4 mb-6 text-start text-xs space-y-1.5">
        <div class="flex justify-between"><span class="text-muted">{{ $isAr ? 'رقم الفاتورة' : 'Invoice number' }}</span><span class="font-mono font-semibold">{{ $invoice->invoice_number }}</span></div>
        <div class="flex justify-between"><span class="text-muted">{{ $isAr ? 'الخطة' : 'Plan' }}</span><span class="font-semibold">{{ $invoice->plan->name }}</span></div>
        <div class="flex justify-between"><span class="text-muted">{{ $isAr ? 'الحالة' : 'Status' }}</span><span class="font-semibold text-danger">{{ $isAr ? 'لم تكتمل' : 'Incomplete' }}</span></div>
    </div>

    <a href="{{ route('billing') }}" class="inline-block w-full py-2.5 bg-signal text-[#06170F] text-sm font-semibold rounded-xl hover:bg-[#37B879] transition-colors min-h-11">
        {{ $isAr ? 'المحاولة مرة أخرى' : 'Try again' }}
    </a>
</div>

</x-layouts.app>
