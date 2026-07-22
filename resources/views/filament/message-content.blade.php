<div class="space-y-3">
    <div class="flex justify-between text-sm">
        <span class="text-gray-500">To</span>
        <span class="font-mono font-medium">{{ $message->to_number }}</span>
    </div>
    <div class="flex justify-between text-sm">
        <span class="text-gray-500">Type</span>
        <span class="font-medium capitalize">{{ $message->type->value }}</span>
    </div>
    <div class="flex justify-between text-sm">
        <span class="text-gray-500">Status</span>
        <span class="font-medium capitalize">{{ $message->status->value }}</span>
    </div>
    <div class="border-t pt-3">
        <p class="text-xs text-gray-500 mb-1">Content</p>
        <pre class="text-sm bg-gray-50 rounded p-3 overflow-auto max-h-40">{{ json_encode($message->content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    </div>
    @if($message->error_message)
    <div class="border-t pt-3">
        <p class="text-xs text-red-500 mb-1">Error</p>
        <p class="text-sm text-red-700 bg-red-50 rounded p-2">{{ $message->error_message }}</p>
    </div>
    @endif
</div>
