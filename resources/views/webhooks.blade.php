@php $isAr = app()->getLocale() === 'ar'; @endphp
<x-layouts.app :title="$isAr ? 'Webhooks' : 'Webhooks'">
    <livewire:webhooks.webhook-manager />
</x-layouts.app>
