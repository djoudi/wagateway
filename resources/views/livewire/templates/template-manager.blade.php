<div>
@php $isAr = app()->getLocale() === 'ar'; @endphp

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
    <div>
        <h2 class="text-sm font-semibold text-text">{{ $isAr ? 'قوالب الرسائل' : 'Message templates' }}</h2>
        <p class="text-xs text-muted mt-0.5">
            {{ $templates->count() }} / {{ auth()->user()->plan?->max_templates ?? 10 }} {{ $isAr ? 'مستخدم' : 'used' }}
        </p>
    </div>
    <button wire:click="openCreate"
            class="flex items-center gap-2 px-4 py-2 bg-signal text-[#06170F] text-sm font-semibold rounded-xl hover:bg-[#37B879] transition-colors shadow-sm min-h-11">
        <i class="ti ti-plus"></i> {{ $isAr ? 'قالب جديد' : 'New template' }}
    </button>
</div>

@if ($templates->isEmpty())
    <div class="bg-card rounded-[14px] border border-dashed border-line p-14 text-center">
        <i class="ti ti-template block text-3xl text-muted mb-2"></i>
        <p class="text-sm font-medium text-text mb-1">{{ $isAr ? 'لا توجد قوالب بعد' : 'No templates yet' }}</p>
        <p class="text-xs text-muted mb-4">{{ $isAr ? 'أنشئ قوالب رسائل قابلة لإعادة الاستخدام مع متغيرات ديناميكية' : 'Create reusable message templates with dynamic variables' }}</p>
        <button wire:click="openCreate"
                class="px-4 py-2 bg-signal text-[#06170F] text-sm font-medium rounded-lg hover:bg-[#37B879] transition-colors min-h-11">
            {{ $isAr ? 'إنشاء أول قالب' : 'Create first template' }}
        </button>
    </div>
@else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        @foreach ($templates as $tpl)
            <div class="bg-card rounded-[14px] border border-line p-4 flex flex-col">

                <div class="flex items-start gap-2 mb-3">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 bg-signal-dim">
                        <i class="ti text-sm text-signal-deep
                            {{ $tpl->type === 'text' ? 'ti-message-2' :
                               ($tpl->type === 'image' ? 'ti-photo' : 'ti-file') }}"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-semibold text-text truncate">{{ $tpl->name }}</div>
                        <span class="text-[10px] text-muted capitalize">{{ $tpl->type }}</span>
                    </div>
                </div>

                <div class="flex-1 bg-paper rounded-lg p-3 mb-3 text-xs text-muted leading-relaxed overflow-hidden"
                     style="max-height: 80px; display: -webkit-box; -webkit-line-clamp: 4; -webkit-box-orient: vertical; overflow: hidden;">
                    {{ $tpl->body }}
                </div>

                @if (!empty($tpl->variables))
                    <div class="flex flex-wrap gap-1 mb-3">
                        @foreach ($tpl->variables as $var)
                            <span class="text-[10px] px-2 py-0.5 bg-signal-dim text-signal-deep rounded-full border border-signal/20 font-mono">
                                {{ '{' . '{' . $var . '}' . '}' }}
                            </span>
                        @endforeach
                    </div>
                @endif

                <div class="flex gap-1.5 mt-auto">
                    <button wire:click="openPreview({{ $tpl->id }})"
                            class="flex-1 flex items-center justify-center gap-1 py-1.5 min-h-11 border border-line rounded-lg text-xs font-medium text-muted hover:bg-paper transition-colors">
                        <i class="ti ti-eye text-sm"></i> {{ $isAr ? 'معاينة' : 'Preview' }}
                    </button>
                    <button wire:click="openEdit({{ $tpl->id }})"
                            class="min-w-11 min-h-11 flex items-center justify-center border border-line rounded-lg text-muted hover:bg-paper transition-colors">
                        <i class="ti ti-pencil text-sm"></i>
                    </button>
                    <button wire:click="duplicate({{ $tpl->id }})"
                            class="min-w-11 min-h-11 flex items-center justify-center border border-line rounded-lg text-muted hover:bg-paper transition-colors">
                        <i class="ti ti-copy text-sm"></i>
                    </button>
                    <button wire:click="delete({{ $tpl->id }})"
                            wire:confirm="{{ $isAr ? 'حذف هذا القالب؟' : 'Delete this template?' }}"
                            class="min-w-11 min-h-11 flex items-center justify-center border border-danger/20 rounded-lg text-danger hover:bg-danger-dim transition-colors">
                        <i class="ti ti-trash text-sm"></i>
                    </button>
                </div>
            </div>
        @endforeach
    </div>
@endif

@if ($showForm)
    <div class="fixed inset-0 bg-ink/50 flex items-center justify-center z-50 p-4" wire:click.self="$set('showForm',false)">
        <div class="bg-card rounded-[14px] w-[520px] max-w-full p-6 shadow-xl max-h-[90vh] overflow-y-auto border border-line">
            <h3 class="text-sm font-semibold text-text mb-5">
                {{ $editingId ? ($isAr ? 'تعديل القالب' : 'Edit template') : ($isAr ? 'قالب جديد' : 'New template') }}
            </h3>

            <div class="mb-4">
                <label class="block text-xs font-medium text-muted mb-1.5">{{ $isAr ? 'اسم القالب' : 'Template name' }} <span class="text-danger">*</span></label>
                <input wire:model="name" type="text" placeholder="{{ $isAr ? 'مثال: تأكيد الطلب' : 'e.g. Order confirmation' }}"
                       class="w-full border border-line rounded-lg px-3 py-2 text-base outline-none min-h-11 focus:border-signal focus:ring-2 focus:ring-signal/20 transition-all" />
                @error('name') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-xs font-medium text-muted mb-1.5">{{ $isAr ? 'النوع' : 'Type' }}</label>
                <div class="flex gap-2">
                    @foreach (['text' => ($isAr ? 'نص' : 'Text'), 'image' => ($isAr ? 'صورة' : 'Image'), 'document' => ($isAr ? 'مستند' : 'Document')] as $val => $label)
                        <button wire:click="$set('type','{{ $val }}')"
                                class="flex-1 py-1.5 rounded-lg text-xs font-medium border transition-all min-h-11
                                    {{ $type === $val ? 'bg-signal text-[#06170F] border-signal' : 'bg-card text-muted border-line hover:border-muted' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            @if ($type !== 'text')
                <div class="mb-4">
                    <label class="block text-xs font-medium text-muted mb-1.5">{{ $isAr ? 'رابط الوسائط' : 'Media URL' }}</label>
                    <input wire:model="mediaUrl" type="url" placeholder="https://example.com/file"
                           class="w-full border border-line rounded-lg px-3 py-2 text-base outline-none focus:border-signal min-h-11" />
                </div>
            @endif

            <div class="mb-4">
                <div class="flex items-center justify-between mb-1.5">
                    <label class="text-xs font-medium text-muted">
                        {{ $type === 'text' ? ($isAr ? 'نص الرسالة' : 'Message body') : ($isAr ? 'التعليق' : 'Caption') }} <span class="text-danger">*</span>
                    </label>
                    <span class="text-[10px] text-muted">{{ $isAr ? 'استخدم' : 'Use' }} <code class="font-mono">{{ '{' . '{variable}' . '}' }}</code></span>
                </div>
                <textarea wire:model.live="body" rows="5"
                          placeholder="مرحبا @{{name}}، طلبك رقم #@{{order_id}} تم تأكيده."
                          class="w-full border border-line rounded-lg px-3 py-2 text-base outline-none focus:border-signal focus:ring-2 focus:ring-signal/20 resize-y transition-all"></textarea>
                @error('body') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-5">
                <label class="block text-xs font-medium text-muted mb-2">{{ $isAr ? 'المتغيرات' : 'Variables' }}</label>
                <div class="flex gap-2 mb-2">
                    <input wire:model="newVar" type="text" placeholder="variable_name"
                           wire:keydown.enter.prevent="addVariable"
                           class="flex-1 border border-line rounded-lg px-3 py-1.5 text-xs font-mono outline-none focus:border-signal min-h-11" />
                    <button wire:click="addVariable"
                            class="px-3 py-1.5 bg-paper text-text text-xs font-medium rounded-lg hover:bg-line transition-colors min-h-11">
                        {{ $isAr ? 'إضافة' : 'Add' }}
                    </button>
                </div>
                @if (!empty($variables))
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($variables as $i => $var)
                            <span class="flex items-center gap-1.5 px-2 py-1 bg-signal-dim text-signal-deep text-xs rounded-lg border border-signal/20 font-mono">
                                {{ '{' . '{' . $var . '}' . '}' }}
                                <button wire:click="removeVariable({{ $i }})" class="text-signal hover:text-danger transition-colors">
                                    <i class="ti ti-x text-[10px]"></i>
                                </button>
                            </span>
                        @endforeach
                    </div>
                @else
                    <p class="text-[10px] text-muted">{{ $isAr ? 'تُكتشف المتغيرات تلقائياً من النص — أو أضفها يدوياً أعلاه.' : 'Variables are auto-detected from the body — or add manually above.' }}</p>
                @endif
            </div>

            <div class="flex gap-3">
                <button wire:click="$set('showForm',false)"
                        class="flex-1 py-2.5 border border-line rounded-xl text-sm text-muted hover:bg-paper transition-colors min-h-11">
                    {{ $isAr ? 'إلغاء' : 'Cancel' }}
                </button>
                <button wire:click="save"
                        class="flex-1 py-2.5 bg-signal text-[#06170F] text-sm font-semibold rounded-xl hover:bg-[#37B879] transition-colors min-h-11">
                    {{ $editingId ? ($isAr ? 'حفظ التغييرات' : 'Save changes') : ($isAr ? 'إنشاء القالب' : 'Create template') }}
                </button>
            </div>
        </div>
    </div>
@endif

@if ($previewId && $previewTemplate)
    <div class="fixed inset-0 bg-ink/50 flex items-center justify-center z-50 p-4" wire:click.self="closePreview">
        <div class="bg-card rounded-[14px] w-[420px] max-w-full p-6 shadow-xl border border-line">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-text">{{ $isAr ? 'معاينة:' : 'Preview:' }} {{ $previewTemplate->name }}</h3>
                <button wire:click="closePreview" class="text-muted hover:text-text">
                    <i class="ti ti-x text-sm"></i>
                </button>
            </div>

            @if (!empty($previewTemplate->variables))
                <div class="mb-4 space-y-2">
                    <p class="text-xs font-medium text-muted mb-2">{{ $isAr ? 'أدخل قيماً تجريبية:' : 'Fill sample values:' }}</p>
                    @foreach ($previewTemplate->variables as $var)
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-mono text-signal-deep w-28 flex-shrink-0">{{ '{' . '{' . $var . '}' . '}' }}</span>
                            <input wire:model.live="previewData.{{ $var }}"
                                   type="text" placeholder="{{ $isAr ? 'قيمة تجريبية…' : 'Sample value…' }}"
                                   class="flex-1 border border-line rounded-lg px-2.5 py-1.5 text-xs outline-none focus:border-signal min-h-11" />
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="wa-bubble">
                @php
                    $rendered = $previewTemplate->body;
                    foreach ($previewData as $k => $v) {
                        $rendered = str_replace('{{' . $k . '}}', '<span class="font-semibold text-signal-deep">' . htmlspecialchars($v) . '</span>', $rendered);
                    }
                    $rendered = preg_replace('/\{\{(\w+)\}\}/', '<span class="text-amber font-mono text-xs">{{$1}}</span>', $rendered);
                @endphp
                {!! $rendered !!}
                <div class="text-end text-[10px] text-muted mt-1">{{ now()->format('H:i') }} ✓✓</div>
            </div>

            <button wire:click="closePreview"
                    class="w-full mt-4 py-2 border border-line rounded-xl text-sm text-muted hover:bg-paper transition-colors min-h-11">
                {{ $isAr ? 'إغلاق' : 'Close' }}
            </button>
        </div>
    </div>
@endif
</div>
