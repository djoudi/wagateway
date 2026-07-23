<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WebhookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->uuid,
            'name'             => $this->name,
            'url'              => $this->url,
            'events'           => $this->events,
            'is_active'        => $this->is_active,
            'success_count'    => $this->success_count,
            'failure_count'    => $this->failure_count,
            'last_triggered_at'=> $this->last_triggered_at?->toISOString(),
            'created_at'       => $this->created_at->toISOString(),
        ];
    }
}
