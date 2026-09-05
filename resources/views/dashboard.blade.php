@php $isAr = app()->getLocale() === 'ar'; @endphp
<x-layouts.app :title="$isAr ? 'لوحة التحكم' : 'Dashboard'">
    <livewire:dashboard.overview />
</x-layouts.app>
