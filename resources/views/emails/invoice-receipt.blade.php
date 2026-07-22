<x-mail::message>

# تم تأكيد دفعتك ✓

مرحباً {{ $invoice->user->name }}،

تم تفعيل خطة **{{ $invoice->plan->name }}** على حسابك بنجاح. نسخة من الفاتورة الرسمية مرفقة بهذه الرسالة.

<x-mail::table>
| | |
|:---|---:|
| رقم الفاتورة | {{ $invoice->invoice_number }} |
| الخطة | {{ $invoice->plan->name }} |
| دورة الفوترة | {{ $invoice->billing_cycle === 'yearly' ? 'سنوي' : 'شهري' }} |
| المبلغ | {{ number_format($invoice->amount) }} {{ $invoice->currency }} |
| تاريخ الدفع | {{ $invoice->paid_at?->format('Y-m-d H:i') }} |
</x-mail::table>

<x-mail::button :url="route('billing')">
الذهاب إلى صفحة الفوترة
</x-mail::button>

شكراً لاستخدامك {{ config('app.name', 'WaGateway') }}.

</x-mail::message>
