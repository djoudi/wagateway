<x-layouts.app title="Templates">

<div class="flex items-center justify-between mb-5">
    <div>
        <h2 class="text-sm font-semibold text-gray-900">Message templates</h2>
        <p class="text-xs text-gray-400 mt-0.5">
            {{ $templates->count() }} / {{ auth()->user()->plan?->max_templates ?? 10 }} used
        </p>
    </div>
    <button wire:click="openCreate"
            class="flex items-center gap-2 px-4 py-2 bg-[#25D366] text-white text-sm font-semibold rounded-xl hover:bg-green-600 transition-colors shadow-sm">
        <i class="ti ti-plus"></i> New template
    </button>
</div>

{{-- Grid --}}
@if ($templates->isEmpty())
    <div class="bg-white rounded-xl border border-dashed border-gray-200 p-14 text-center">
        <i class="ti ti-template block text-3xl text-gray-300 mb-2"></i>
        <p class="text-sm font-medium text-gray-600 mb-1">No templates yet</p>
        <p class="text-xs text-gray-400 mb-4">Create reusable message templates with dynamic variables</p>
        <button wire:click="openCreate"
                class="px-4 py-2 bg-[#25D366] text-white text-sm font-medium rounded-lg hover:bg-green-600 transition-colors">
            Create first template
        </button>
    </div>
@else
    <div class="grid grid-cols-3 gap-3">
        @foreach ($templates as $tpl)
            <div class="bg-white rounded-xl border border-gray-100 p-4 flex flex-col">

                {{-- Header --}}
                <div class="flex items-start gap-2 mb-3">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0
                        {{ $tpl->type === 'text' ? 'bg-blue-100' : ($tpl->type === 'image' ? 'bg-purple-100' : 'bg-orange-100') }}">
                        <i class="ti text-sm
                            {{ $tpl->type === 'text' ? 'ti-message-2 text-blue-700' :
                               ($tpl->type === 'image' ? 'ti-photo text-purple-700' : 'ti-file text-orange-700') }}"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-semibold text-gray-900 truncate">{{ $tpl->name }}</div>
                        <span class="text-[10px] text-gray-400 capitalize">{{ $tpl->type }}</span>
                    </div>
                </div>

                {{-- Body preview --}}
                <div class="flex-1 bg-gray-50 rounded-lg p-3 mb-3 text-xs text-gray-600 leading-relaxed overflow-hidden"
                     style="max-height: 80px; display: -webkit-box; -webkit-line-clamp: 4; -webkit-box-orient: vertical; overflow: hidden;">
                    {{ $tpl->body }}
                </div>

                {{-- Variables --}}
                @if (!empty($tpl->variables))
                    <div class="flex flex-wrap gap-1 mb-3">
                        @foreach ($tpl->variables as $var)
                            <span class="text-[10px] px-2 py-0.5 bg-blue-50 text-blue-600 rounded-full border border-blue-100 font-mono">
                                {{ '{{' . $var . '}}' }}
                            </span>
                        @endforeach
                    </div>
                @endif

                {{-- Actions --}}
                <div class="flex gap-1.5 mt-auto">
                    <button wire:click="openPreview({{ $tpl->id }})"
                            class="flex-1 flex items-center justify-center gap-1 py-1.5 border border-gray-200 rounded-lg text-xs font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                        <i class="ti ti-eye text-sm"></i> Preview
                    </button>
                    <button wire:click="openEdit({{ $tpl->id }})"
                            class="w-8 h-7 flex items-center justify-center border border-gray-200 rounded-lg text-gray-500 hover:bg-gray-50 transition-colors">
                        <i class="ti ti-pencil text-sm"></i>
                    </button>
                    <button wire:click="duplicate({{ $tpl->id }})"
                            class="w-8 h-7 flex items-center justify-center border border-gray-200 rounded-lg text-gray-500 hover:bg-gray-50 transition-colors">
                        <i class="ti ti-copy text-sm"></i>
                    </button>
                    <button wire:click="delete({{ $tpl->id }})"
                            wire:confirm="Delete this template?"
                            class="w-8 h-7 flex items-center justify-center border border-red-100 rounded-lg text-red-400 hover:bg-red-50 transition-colors">
                        <i class="ti ti-trash text-sm"></i>
                    </button>
                </div>
            </div>
        @endforeach
    </div>
@endif

{{-- ── Create / Edit Modal ────────────────────────────────────────────────── --}}
@if ($showForm)
    <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50" wire:click.self="$set('showForm',false)">
        <div class="bg-white rounded-2xl w-[520px] p-6 shadow-xl max-h-[90vh] overflow-y-auto">
            <h3 class="text-sm font-semibold text-gray-900 mb-5">
                {{ $editingId ? 'Edit template' : 'New template' }}
            </h3>

            {{-- Name --}}
            <div class="mb-4">
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Template name <span class="text-red-400">*</span></label>
                <input wire:model="name" type="text" placeholder="e.g. Order confirmation"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#25D366] focus:ring-1 focus:ring-[#25D366]/30 transition-all" />
                @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Type --}}
            <div class="mb-4">
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Type</label>
                <div class="flex gap-2">
                    @foreach (['text' => 'Text', 'image' => 'Image', 'document' => 'Document'] as $val => $label)
                        <button wire:click="$set('type','{{ $val }}')"
                                class="flex-1 py-1.5 rounded-lg text-xs font-medium border transition-all
                                    {{ $type === $val ? 'bg-[#25D366] text-white border-[#25D366]' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            @if ($type !== 'text')
                <div class="mb-4">
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Media URL</label>
                    <input wire:model="mediaUrl" type="url" placeholder="https://example.com/file"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#25D366]" />
                </div>
            @endif

            {{-- Body --}}
            <div class="mb-4">
                <div class="flex items-center justify-between mb-1.5">
                    <label class="text-xs font-medium text-gray-600">
                        {{ $type === 'text' ? 'Message body' : 'Caption' }} <span class="text-red-400">*</span>
                    </label>
                    <span class="text-[10px] text-gray-400">Use {{ '{{variable}}' }} for dynamic values</span>
                </div>
                <textarea wire:model.live="body" rows="5"
                          placeholder="مرحبا {{name}}، طلبك رقم #{{order_id}} تم تأكيده."
                          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#25D366] focus:ring-1 focus:ring-[#25D366]/30 resize-y transition-all"></textarea>
                @error('body') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Variables --}}
            <div class="mb-5">
                <label class="block text-xs font-medium text-gray-600 mb-2">Variables</label>
                <div class="flex gap-2 mb-2">
                    <input wire:model="newVar" type="text" placeholder="variable_name"
                           wire:keydown.enter.prevent="addVariable"
                           class="flex-1 border border-gray-200 rounded-lg px-3 py-1.5 text-xs font-mono outline-none focus:border-[#25D366]" />
                    <button wire:click="addVariable"
                            class="px-3 py-1.5 bg-gray-100 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-200 transition-colors">
                        Add
                    </button>
                </div>
                @if (!empty($variables))
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($variables as $i => $var)
                            <span class="flex items-center gap-1.5 px-2 py-1 bg-blue-50 text-blue-700 text-xs rounded-lg border border-blue-100 font-mono">
                                {{ '{{' . $var . '}}' }}
                                <button wire:click="removeVariable({{ $i }})" class="text-blue-400 hover:text-red-500 transition-colors">
                                    <i class="ti ti-x text-[10px]"></i>
                                </button>
                            </span>
                        @endforeach
                    </div>
                @else
                    <p class="text-[10px] text-gray-400">Variables are auto-detected from {{ '{{' }}body{{ '}}' }} — or add manually above.</p>
                @endif
            </div>

            <div class="flex gap-3">
                <button wire:click="$set('showForm',false)"
                        class="flex-1 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button wire:click="save"
                        class="flex-1 py-2.5 bg-[#25D366] text-white text-sm font-semibold rounded-xl hover:bg-green-600 transition-colors">
                    {{ $editingId ? 'Save changes' : 'Create template' }}
                </button>
            </div>
        </div>
    </div>
@endif

{{-- ── Preview Modal ──────────────────────────────────────────────────────── --}}
@if ($previewId && $previewTemplate)
    <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50" wire:click.self="closePreview">
        <div class="bg-white rounded-2xl w-[420px] p-6 shadow-xl">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-900">Preview: {{ $previewTemplate->name }}</h3>
                <button wire:click="closePreview" class="text-gray-400 hover:text-gray-600">
                    <i class="ti ti-x text-sm"></i>
                </button>
            </div>

            {{-- Fill variables --}}
            @if (!empty($previewTemplate->variables))
                <div class="mb-4 space-y-2">
                    <p class="text-xs font-medium text-gray-600 mb-2">Fill sample values:</p>
                    @foreach ($previewTemplate->variables as $var)
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-mono text-blue-600 w-28 flex-shrink-0">{{ '{{' . $var . '}}' }}</span>
                            <input wire:model.live="previewData.{{ $var }}"
                                   type="text" placeholder="Sample value…"
                                   class="flex-1 border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs outline-none focus:border-[#25D366]" />
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Rendered preview (WhatsApp bubble style) --}}
            <div class="bg-[#E8FDD8] rounded-2xl rounded-tl-sm p-4 text-sm text-gray-800 leading-relaxed">
                @php
                    $rendered = $previewTemplate->body;
                    foreach ($previewData as $k => $v) {
                        $rendered = str_replace('{{' . $k . '}}', '<span class="font-semibold text-green-800">' . htmlspecialchars($v) . '</span>', $rendered);
                    }
                    // Highlight unfilled variables
                    $rendered = preg_replace('/\{\{(\w+)\}\}/', '<span class="text-amber-600 font-mono text-xs">{{$1}}</span>', $rendered);
                @endphp
                {!! $rendered !!}
                <div class="text-right text-[10px] text-gray-400 mt-1">{{ now()->format('H:i') }} ✓✓</div>
            </div>

            <button wire:click="closePreview"
                    class="w-full mt-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 transition-colors">
                Close
            </button>
        </div>
    </div>
@endif

</x-layouts.app>
