<?php

namespace App\Events;

use App\Models\Device;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QrCodeGenerated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Device $device,
        public readonly string $qr,
    ) {}

    /**
     * Broadcast on the user's private channel so only the
     * authenticated owner receives their own QR updates.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->device->user_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'qr.generated';
    }

    public function broadcastWith(): array
    {
        return [
            'device_id'   => $this->device->uuid,
            'device_name' => $this->device->name,
            'qr'          => $this->qr,
            'expires_at'  => now()->addSeconds(60)->toISOString(),
        ];
    }
}
