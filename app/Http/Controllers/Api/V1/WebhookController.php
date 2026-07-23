<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\WebhookEvent;
use App\Http\Controllers\Controller;
use App\Http\Resources\WebhookResource;
use App\Models\Webhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WebhookController extends Controller
{
    // ─── GET /api/v1/webhooks ─────────────────────────────────────────────────
    public function index(): JsonResponse
    {
        $webhooks = auth()->user()->webhooks()->latest()->get();

        return response()->json(['success' => true, 'data' => WebhookResource::collection($webhooks)]);
    }

    // ─── POST /api/v1/webhooks ────────────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $user  = auth()->user();
        $limit = $user->plan?->max_webhooks ?? 3;

        if ($user->webhooks()->count() >= $limit) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'WEBHOOK_LIMIT_REACHED', 'message' => "Plan limit: {$limit} webhooks."],
            ], 403);
        }

        $data = $request->validate([
            'name'   => ['required', 'string', 'max:60'],
            'url'    => ['required', 'url', 'max:500'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => [Rule::in(WebhookEvent::values())],
        ]);

        $webhook = $user->webhooks()->create($data);

        return response()->json(['success' => true, 'data' => new WebhookResource($webhook)], 201);
    }

    // ─── PATCH /api/v1/webhooks/{uuid} ────────────────────────────────────────
    public function update(Request $request, Webhook $webhook): JsonResponse
    {
        $this->authorize('update', $webhook);

        $data = $request->validate([
            'name'      => ['sometimes', 'string', 'max:60'],
            'url'       => ['sometimes', 'url', 'max:500'],
            'events'    => ['sometimes', 'array', 'min:1'],
            'events.*'  => [Rule::in(WebhookEvent::values())],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $webhook->update($data);

        return response()->json(['success' => true, 'data' => new WebhookResource($webhook)]);
    }

    // ─── DELETE /api/v1/webhooks/{uuid} ───────────────────────────────────────
    public function destroy(Webhook $webhook): JsonResponse
    {
        $this->authorize('delete', $webhook);
        $webhook->delete();

        return response()->json(['success' => true, 'message' => 'Webhook removed.']);
    }
}
