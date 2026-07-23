<?php

namespace App\Livewire\Messages;

use App\Models\Message;
use Livewire\Component;
use Livewire\WithPagination;

class MessageLog extends Component
{
    use WithPagination;

    public string  $search     = '';
    public string  $statusFilter = '';
    public string  $typeFilter   = '';
    public string  $deviceFilter = '';
    public string  $dateFrom     = '';
    public string  $dateTo       = '';
    public int     $perPage      = 50;

    protected $queryString = [
        'search'       => ['except' => ''],
        'statusFilter' => ['except' => '', 'as' => 'status'],
        'typeFilter'   => ['except' => '', 'as' => 'type'],
    ];

    public function updatingSearch(): void    { $this->resetPage(); }
    public function updatingStatusFilter(): void { $this->resetPage(); }
    public function updatingTypeFilter(): void   { $this->resetPage(); }

    public function resetFilters(): void
    {
        $this->search = $this->statusFilter = $this->typeFilter = '';
        $this->dateFrom = $this->dateTo = '';
        $this->resetPage();
    }

    public function render()
    {
        $messages = Message::query()
            ->where('user_id', auth()->id())
            ->when($this->search, fn ($q) => $q
                ->where('to_number', 'like', "%{$this->search}%")
                ->orWhereJsonContains('content->body', $this->search)
            )
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->typeFilter,   fn ($q) => $q->where('type',   $this->typeFilter))
            ->when($this->deviceFilter, fn ($q) => $q->whereHas('device', fn ($dq) =>
                $dq->where('uuid', $this->deviceFilter)
            ))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo,   fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->with('device:id,uuid,name')
            ->latest()
            ->paginate($this->perPage);

        $summary = [
            'total'     => Message::where('user_id', auth()->id())->whereDate('created_at', today())->count(),
            'sent'      => Message::where('user_id', auth()->id())->whereDate('created_at', today())->whereIn('status', ['sent','delivered','read'])->count(),
            'failed'    => Message::where('user_id', auth()->id())->whereDate('created_at', today())->where('status', 'failed')->count(),
        ];

        return view('livewire.messages.message-log', compact('messages', 'summary'));
    }
}
