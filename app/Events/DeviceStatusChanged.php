<?php

namespace App\Events;

use App\Models\Device;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeviceStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Device $device) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->device->user_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'device.status';
    }

    public function broadcastWith(): array
    {
        return [
            'device_id'    => $this->device->uuid,
            'device_name'  => $this->device->name,
            'status'       => $this->device->status->value,
            'phone_number' => $this->device->phone_number,
        ];
    }
}
