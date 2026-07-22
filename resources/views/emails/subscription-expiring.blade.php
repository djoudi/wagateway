<x-mail::message>

# {{ $daysRemaining <= 1 ? 'اشتراكك ينتهي غداً' : "اشتراكك ينتهي خلال {$daysRemaining} أيام" }}

مرحباً {{ $user->name }}،

اشتراكك في خطة **{{ $user->plan?->name }}** سينتهي بتاريخ
**{{ $user->plan_expires_at?->format('Y-m-d') }}**.

@if ($daysRemaining <= 1)
لتجنّب انقطاع الخدمة، يُرجى تجديد اشتراكك اليوم. بعد انتهاء الاشتراك، تستمر خدمتك لفترة سماح قصيرة قبل التعليق الكامل.
@else
جدّد اشتراكك الآن لضمان استمرارية إرسال رسائلك دون انقطاع.
@endif

<x-mail::button :url="route('billing')">
تجديد الاشتراك الآن
</x-mail::button>

فريق {{ config('app.name', 'WaGateway') }}

</x-mail::message>
