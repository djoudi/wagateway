@php $isAr = app()->getLocale() === 'ar'; @endphp
<x-layouts.app :title="$isAr ? 'مفاتيح API' : 'API keys'">
    <livewire:api-keys.api-key-manager />
</x-layouts.app>
