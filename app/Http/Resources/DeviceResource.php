<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->uuid,
            'name'                 => $this->name,
            'phone_number'         => $this->phone_number,
            'status'               => $this->status->value,
            'status_label'         => $this->status->label(),
            'is_active'            => $this->is_active,
            'messages_sent_today'  => $this->messages_sent_today,
            'messages_sent_total'  => $this->messages_sent_total,
            'connected_at'         => $this->connected_at?->toISOString(),
            'last_seen_at'         => $this->last_seen_at?->toISOString(),
            'created_at'           => $this->created_at->toISOString(),
        ];
    }
}
