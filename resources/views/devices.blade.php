@php $isAr = app()->getLocale() === 'ar'; @endphp
<x-layouts.app :title="$isAr ? 'الأجهزة' : 'Devices'">
    <livewire:devices.device-manager />
</x-layouts.app>
