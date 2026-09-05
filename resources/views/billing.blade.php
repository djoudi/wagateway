@php $isAr = app()->getLocale() === 'ar'; @endphp
<x-layouts.app :title="$isAr ? 'الفوترة' : 'Billing'">
    <livewire:billing.billing-page />
</x-layouts.app>
