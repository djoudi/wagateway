@php $isAr = app()->getLocale() === 'ar'; @endphp
<x-layouts.app :title="$isAr ? 'توثيق API' : 'API docs'">
    <livewire:docs.api-docs />
</x-layouts.app>
