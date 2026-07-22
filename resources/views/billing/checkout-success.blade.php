<x-layouts.app title="جاري تأكيد الدفع">

<div class="max-w-md mx-auto mt-10 bg-white rounded-2xl border border-gray-100 p-8 text-center">

    @if ($invoice->status === 'paid')
        <div class="w-14 h-14 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-4">
            <i class="ti ti-circle-check text-3xl text-green-600"></i>
        </div>
        <h1 class="text-lg font-bold text-gray-900 mb-2">تم الدفع بنجاح</h1>
        <p class="text-sm text-gray-500 mb-6">تم تفعيل خطة {{ $invoice->plan->name }} على حسابك.</p>
    @else
        <div class="w-14 h-14 rounded-full bg-amber-100 flex items-center justify-center mx-auto mb-4">
            <div class="w-6 h-6 border-2 border-amber-500 border-t-transparent rounded-full animate-spin"></div>
        </div>
        <h1 class="text-lg font-bold text-gray-900 mb-2">جاري تأكيد الدفع…</h1>
        <p class="text-sm text-gray-500 mb-6">
            تم استلام دفعتك بنجاح من بوابة الدفع، وننتظر تأكيداً نهائياً لتفعيل خطتك تلقائياً.
            هذا يستغرق عادة أقل من دقيقة. هذه الصفحة تُحدَّث تلقائياً.
        </p>
        {{-- Plain server-side reload — re-renders this same view with the
             invoice's real current status from the DB. No client-side
             polling endpoint needed; the webhook is the only writer. --}}
        <script>
            setTimeout(() => window.location.reload(), 4000);
        </script>
    @endif

    <div class="bg-gray-50 rounded-xl p-4 mb-6 text-right text-xs space-y-1.5">
        <div class="flex justify-between"><span class="text-gray-400">رقم الفاتورة</span><span class="font-mono font-semibold">{{ $invoice->invoice_number }}</span></div>
        <div class="flex justify-between"><span class="text-gray-400">الخطة</span><span class="font-semibold">{{ $invoice->plan->name }}</span></div>
        <div class="flex justify-between"><span class="text-gray-400">المبلغ</span><span class="font-semibold">{{ number_format($invoice->amount) }} د.ج</span></div>
    </div>

    <a href="{{ route('billing') }}" class="inline-block w-full py-2.5 bg-[#25D366] text-white text-sm font-semibold rounded-xl hover:bg-green-600 transition-colors">
        الذهاب إلى لوحة التحكم
    </a>
</div>

</x-layouts.app>
