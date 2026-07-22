@props(['href', 'icon'])

@php
    $active = request()->is(ltrim(parse_url($href, PHP_URL_PATH), '/') . '*') ||
              request()->url() === $href;
@endphp

<a href="{{ $href }}"
   class="flex items-center gap-2.5 px-3 py-2 mx-1 rounded-lg text-[13px] font-medium transition-colors
          {{ $active
             ? 'bg-green-50 text-green-700 border-r-2 border-[#25D366]'
             : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
>
    <i class="ti {{ $icon }} text-base w-4 text-center {{ $active ? 'text-[#25D366]' : '' }}"></i>
    {{ $slot }}
</a>
