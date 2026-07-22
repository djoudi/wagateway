<x-layouts.app title="فشلت عملية الدفع">

<div class="max-w-md mx-auto mt-10 bg-white rounded-2xl border border-gray-100 p-8 text-center">

    <div class="w-14 h-14 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-4">
        <i class="ti ti-x text-3xl text-red-500"></i>
    </div>
    <h1 class="text-lg font-bold text-gray-900 mb-2">لم تكتمل عملية الدفع</h1>
    <p class="text-sm text-gray-500 mb-6">
        لم يتم تحصيل أي مبلغ من حسابك. يمكنك المحاولة مرة أخرى أو اختيار طريقة دفع بديلة
        (تحويل CCP أو بنكي) من صفحة الفوترة.
    </p>

    <div class="bg-gray-50 rounded-xl p-4 mb-6 text-right text-xs space-y-1.5">
        <div class="flex justify-between"><span class="text-gray-400">رقم الفاتورة</span><span class="font-mono font-semibold">{{ $invoice->invoice_number }}</span></div>
        <div class="flex justify-between"><span class="text-gray-400">الخطة</span><span class="font-semibold">{{ $invoice->plan->name }}</span></div>
        <div class="flex justify-between"><span class="text-gray-400">الحالة</span><span class="font-semibold text-red-500">لم تكتمل</span></div>
    </div>

    <a href="{{ route('billing') }}" class="inline-block w-full py-2.5 bg-[#25D366] text-white text-sm font-semibold rounded-xl hover:bg-green-600 transition-colors">
        المحاولة مرة أخرى
    </a>
</div>

</x-layouts.app>
