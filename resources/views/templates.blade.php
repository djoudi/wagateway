@php $isAr = app()->getLocale() === 'ar'; @endphp
<x-layouts.app :title="$isAr ? 'القوالب' : 'Templates'">
    <livewire:templates.template-manager />
</x-layouts.app>
