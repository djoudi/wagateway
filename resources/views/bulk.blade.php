@php $isAr = app()->getLocale() === 'ar'; @endphp
<x-layouts.app :title="$isAr ? 'إرسال جماعي' : 'Bulk send'">
    <livewire:bulk.bulk-sender />
</x-layouts.app>
