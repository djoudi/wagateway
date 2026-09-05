@php $isAr = app()->getLocale() === 'ar'; @endphp
<x-layouts.app :title="$isAr ? 'الرسائل' : 'Messages'">
    <livewire:messages.message-log />
</x-layouts.app>
