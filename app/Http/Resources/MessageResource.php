<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->uuid,
            'to'           => $this->to_number,
            'type'         => $this->type->value,
            'content'      => $this->content,
            'status'       => $this->status->value,
            'device_id'    => $this->device?->uuid,
            'wa_id'        => $this->wa_message_id,
            'sent_at'      => $this->sent_at?->toISOString(),
            'delivered_at' => $this->delivered_at?->toISOString(),
            'read_at'      => $this->read_at?->toISOString(),
            'failed_at'    => $this->failed_at?->toISOString(),
            'error'        => $this->error_message,
            'created_at'   => $this->created_at->toISOString(),
        ];
    }
}
