@props(['href', 'icon'])

@php
    $active = request()->is(ltrim(parse_url($href, PHP_URL_PATH), '/') . '*') ||
              request()->url() === $href;
@endphp

<a href="{{ $href }}"
   class="flex items-center gap-2.5 px-3 py-2.5 mx-1 rounded-lg text-[13px] font-medium transition-colors min-h-11
          {{ $active
             ? 'bg-ink-soft text-paper-on-dark border-inline-start-2 border-signal'
             : 'text-muted-dark hover:bg-ink-soft hover:text-paper-on-dark' }}"
>
    <i class="ti {{ $icon }} text-base w-4 text-center {{ $active ? 'text-signal' : '' }}"></i>
    {{ $slot }}
</a>
