<?php

namespace App\Livewire\Webhooks;

use App\Enums\WebhookEvent;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Livewire\Component;

class WebhookManager extends Component
{
    // Form
    public string $name      = '';
    public string $url       = '';
    public array  $events    = [];
    public bool   $showForm  = false;
    public ?int   $editingId = null;

    // Delivery log
    public ?int   $inspectingWebhookId = null;

    public array $availableEvents;

    public function mount(): void
    {
        $this->availableEvents = WebhookEvent::values();
    }

    public function openCreate(): void
    {
        $this->reset(['name', 'url', 'events', 'editingId']);
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $webhook       = Webhook::where('id', $id)->where('user_id', auth()->id())->firstOrFail();
        $this->name    = $webhook->name;
        $this->url     = $webhook->url;
        $this->events  = $webhook->events;
        $this->editingId = $id;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'name'     => 'required|string|max:60',
            'url'      => 'required|url|max:500',
            'events'   => 'required|array|min:1',
            'events.*' => 'in:' . implode(',', WebhookEvent::values()),
        ]);

        $user  = auth()->user();
        $limit = $user->plan?->max_webhooks ?? 3;

        if (! $this->editingId && $user->webhooks()->count() >= $limit) {
            $this->addError('url', "Plan limit: {$limit} webhooks. Upgrade to add more.");
            return;
        }

        $data = ['name' => $this->name, 'url' => $this->url, 'events' => $this->events];

        if ($this->editingId) {
            Webhook::where('id', $this->editingId)->where('user_id', $user->id)->update($data);
        } else {
            $user->webhooks()->create($data);
        }

        $this->showForm  = false;
        $this->editingId = null;
        $this->reset(['name', 'url', 'events']);
        $this->dispatch('notify', type: 'success', message: 'Webhook saved successfully.');
    }

    public function toggleActive(int $id): void
    {
        $webhook = Webhook::where('id', $id)->where('user_id', auth()->id())->firstOrFail();
        $webhook->update(['is_active' => ! $webhook->is_active]);
    }

    public function delete(int $id): void
    {
        Webhook::where('id', $id)->where('user_id', auth()->id())->delete();
    }

    public function inspectDeliveries(int $webhookId): void
    {
        $this->inspectingWebhookId = $webhookId;
    }

    public function closeInspect(): void
    {
        $this->inspectingWebhookId = null;
    }

    public function render()
    {
        $webhooks = Webhook::where('user_id', auth()->id())->latest()->get();

        $deliveries = $this->inspectingWebhookId
            ? WebhookDelivery::where('webhook_id', $this->inspectingWebhookId)
                ->latest()->limit(20)->get()
            : collect();

        return view('livewire.webhooks.webhook-manager', compact('webhooks', 'deliveries'));
    }
}
