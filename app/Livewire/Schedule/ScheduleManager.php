<?php

namespace App\Livewire\Schedule;

use App\Models\Device;
use App\Models\ScheduledMessage;
use Livewire\Component;
use Livewire\WithPagination;

class ScheduleManager extends Component
{
    use WithPagination;

    // Form
    public string  $selectedDevice = '';
    public string  $messageType    = 'text';
    public string  $messageBody    = '';
    public string  $mediaUrl       = '';
    public string  $mediaCaption   = '';
    public string  $toNumber       = '';
    public string  $scheduledAt    = '';
    public bool    $showForm       = false;

    // Filters
    public string  $statusFilter   = 'pending';

    public function mount(): void
    {
        // Default scheduled time: tomorrow 09:00
        $this->scheduledAt = now()->addDay()->setTime(9, 0)->format('Y-m-d\TH:i');
    }

    public function openForm(): void
    {
        $this->reset(['messageBody', 'mediaUrl', 'mediaCaption', 'toNumber']);
        $this->scheduledAt = now()->addDay()->setTime(9, 0)->format('Y-m-d\TH:i');
        $this->showForm    = true;
    }

    public function save(): void
    {
        $this->validate([
            'selectedDevice' => 'required|string',
            'toNumber'       => 'required|string|min:7|max:20',
            'messageType'    => 'required|in:text,image,document,audio,video',
            'messageBody'    => 'required_if:messageType,text|string|max:4096',
            'mediaUrl'       => 'required_unless:messageType,text|url',
            'scheduledAt'    => 'required|date|after:now',
        ]);

        $user   = auth()->user();
        $device = Device::where('uuid', $this->selectedDevice)
            ->where('user_id', $user->id)
            ->firstOrFail();

        ScheduledMessage::create([
            'user_id'      => $user->id,
            'device_id'    => $device->id,
            'to_number'    => preg_replace('/[^0-9]/', '', $this->toNumber),
            'message_data' => array_filter([
                'type'    => $this->messageType,
                'body'    => $this->messageType === 'text' ? $this->messageBody : null,
                'url'     => $this->messageType !== 'text' ? $this->mediaUrl : null,
                'caption' => $this->mediaCaption ?: null,
            ]),
            'scheduled_at' => $this->scheduledAt,
            'status'       => 'pending',
        ]);

        $this->showForm = false;
        $this->dispatch('notify', type: 'success', message: 'Message scheduled successfully.');
        $this->resetPage();
    }

    public function cancel(int $id): void
    {
        ScheduledMessage::where('id', $id)
            ->where('user_id', auth()->id())
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);
    }

    public function render()
    {
        $devices = Device::where('user_id', auth()->id())->get();

        $scheduled = ScheduledMessage::where('user_id', auth()->id())
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->with('device:id,uuid,name')
            ->orderBy('scheduled_at')
            ->paginate(20);

        $counts = [
            'pending'   => ScheduledMessage::where('user_id', auth()->id())->where('status', 'pending')->count(),
            'sent'      => ScheduledMessage::where('user_id', auth()->id())->where('status', 'sent')->count(),
            'failed'    => ScheduledMessage::where('user_id', auth()->id())->where('status', 'failed')->count(),
            'cancelled' => ScheduledMessage::where('user_id', auth()->id())->where('status', 'cancelled')->count(),
        ];

        return view('livewire.schedule.schedule-manager', compact('devices', 'scheduled', 'counts'));
    }
}
