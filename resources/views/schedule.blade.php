@php $isAr = app()->getLocale() === 'ar'; @endphp
<x-layouts.app :title="$isAr ? 'الجدولة' : 'Scheduler'">
    <livewire:schedule.schedule-manager />
</x-layouts.app>
