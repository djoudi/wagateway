<div class="mb-4">
    <div class="flex items-center gap-2 mb-2">
        <span class="text-[10px] font-bold bg-green-100 text-green-700 px-2 py-0.5 rounded">{{ $method }}</span>
        <code class="text-sm font-mono text-gray-800">{{ $path }}</code>
        @if ($description)
            <span class="text-xs text-gray-400">{{ $description }}</span>
        @endif
    </div>
    {{ $slot }}
</div>
