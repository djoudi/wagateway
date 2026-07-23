<?php

namespace App\Livewire\Templates;

use App\Models\Template;
use Livewire\Component;

class TemplateManager extends Component
{
    // Form fields
    public string  $name      = '';
    public string  $type      = 'text';
    public string  $body      = '';
    public string  $mediaUrl  = '';
    public array   $variables = [];
    public string  $newVar    = '';

    // UI state
    public bool    $showForm    = false;
    public ?int    $editingId   = null;
    public ?int    $previewId   = null;
    public array   $previewData = [];

    public function openCreate(): void
    {
        $this->reset(['name', 'type', 'body', 'mediaUrl', 'variables', 'newVar', 'editingId']);
        $this->type     = 'text';
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $tpl = Template::where('id', $id)->where('user_id', auth()->id())->firstOrFail();

        $this->editingId = $id;
        $this->name      = $tpl->name;
        $this->type      = $tpl->type;
        $this->body      = $tpl->body;
        $this->mediaUrl  = $tpl->media_url ?? '';
        $this->variables = $tpl->variables ?? [];
        $this->showForm  = true;
    }

    public function addVariable(): void
    {
        $var = trim($this->newVar);
        if ($var && ! in_array($var, $this->variables)) {
            $this->variables[] = $var;
        }
        $this->newVar = '';
    }

    public function removeVariable(int $index): void
    {
        array_splice($this->variables, $index, 1);
        $this->variables = array_values($this->variables);
    }

    // Auto-detect variables from body text {{variable}}
    public function updatedBody(): void
    {
        preg_match_all('/\{\{(\w+)\}\}/', $this->body, $matches);
        $detected = array_unique($matches[1] ?? []);
        foreach ($detected as $v) {
            if (! in_array($v, $this->variables)) {
                $this->variables[] = $v;
            }
        }
    }

    public function save(): void
    {
        $rules = [
            'name'       => 'required|string|max:80',
            'type'       => 'required|in:text,image,document',
            'body'       => 'required|string|max:4096',
            'mediaUrl'   => 'nullable|url',
            'variables'  => 'array',
            'variables.*'=> 'string|max:50',
        ];

        $this->validate($rules);

        $user  = auth()->user();
        $limit = $user->plan?->max_templates ?? 10;

        if (! $this->editingId && Template::where('user_id', $user->id)->count() >= $limit) {
            $this->addError('name', "Plan limit: {$limit} templates. Upgrade to add more.");
            return;
        }

        $data = [
            'name'      => $this->name,
            'type'      => $this->type,
            'body'      => $this->body,
            'media_url' => $this->mediaUrl ?: null,
            'variables' => $this->variables,
        ];

        if ($this->editingId) {
            Template::where('id', $this->editingId)->where('user_id', $user->id)->update($data);
        } else {
            $user->templates()->create($data);
        }

        $this->showForm  = false;
        $this->editingId = null;
        $this->dispatch('notify', type: 'success', message: 'Template saved.');
    }

    public function duplicate(int $id): void
    {
        $tpl  = Template::where('id', $id)->where('user_id', auth()->id())->firstOrFail();
        $copy = $tpl->replicate();
        $copy->name = $tpl->name . ' (copy)';
        $copy->save();
        $this->dispatch('notify', type: 'success', message: 'Template duplicated.');
    }

    public function delete(int $id): void
    {
        Template::where('id', $id)->where('user_id', auth()->id())->delete();
    }

    public function openPreview(int $id): void
    {
        $this->previewId   = $id;
        $this->previewData = [];
    }

    public function closePreview(): void
    {
        $this->previewId = null;
    }

    public function render()
    {
        $templates = Template::where('user_id', auth()->id())
            ->latest()
            ->get();

        $previewTemplate = $this->previewId
            ? Template::where('id', $this->previewId)->where('user_id', auth()->id())->first()
            : null;

        return view('livewire.templates.template-manager', compact('templates', 'previewTemplate'));
    }
}
