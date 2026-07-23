<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\MessageResource;
use App\Models\Device;
use App\Models\Message;
use App\Services\MessageDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MessageController extends Controller
{
    public function __construct(private readonly MessageDispatcher $dispatcher) {}

    // ─── POST /api/v1/messages/send/text ──────────────────────────────────────
    public function sendText(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id' => ['required', 'string'],
            'to'        => ['required', 'string', 'min:7', 'max:20'],
            'body'      => ['required', 'string', 'max:4096'],
        ]);

        $device = $this->resolveDevice($data['device_id']);
        $data['type'] = 'text';
        $data['_key'] = $request->input('_key');

        try {
            $message = $this->dispatcher->send(auth()->user(), $device, $data);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'error' => $e->errors()], 422);
        }

        return response()->json([
            'success' => true,
            'data'    => new MessageResource($message),
        ], 201);
    }

    // ─── POST /api/v1/messages/send/image ─────────────────────────────────────
    public function sendImage(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id' => ['required', 'string'],
            'to'        => ['required', 'string'],
            'url'       => ['required', 'url'],
            'caption'   => ['nullable', 'string', 'max:1024'],
        ]);

        $data['type'] = 'image';
        return $this->dispatchMedia($request, $data);
    }

    // ─── POST /api/v1/messages/send/document ──────────────────────────────────
    public function sendDocument(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id' => ['required', 'string'],
            'to'        => ['required', 'string'],
            'url'       => ['required', 'url'],
            'filename'  => ['required', 'string', 'max:255'],
            'caption'   => ['nullable', 'string', 'max:1024'],
        ]);

        $data['type'] = 'document';
        return $this->dispatchMedia($request, $data);
    }

    // ─── POST /api/v1/messages/send/location ──────────────────────────────────
    public function sendLocation(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id' => ['required', 'string'],
            'to'        => ['required', 'string'],
            'latitude'  => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'name'      => ['nullable', 'string', 'max:255'],
        ]);

        $device = $this->resolveDevice($data['device_id']);
        $data['type'] = 'location';

        try {
            $message = $this->dispatcher->send(auth()->user(), $device, $data);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'error' => $e->errors()], 422);
        }

        return response()->json(['success' => true, 'data' => new MessageResource($message)], 201);
    }

    // ─── GET /api/v1/messages ─────────────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $messages = Message::query()
            ->where('user_id', auth()->id())
            ->when($request->status,    fn ($q, $s) => $q->where('status', $s))
            ->when($request->device_id, fn ($q, $d) => $q->whereHas('device', fn ($dq) => $dq->where('uuid', $d)))
            ->when($request->type,      fn ($q, $t) => $q->where('type', $t))
            ->latest()
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data'    => MessageResource::collection($messages->items()),
            'meta'    => [
                'total'        => $messages->total(),
                'per_page'     => $messages->perPage(),
                'current_page' => $messages->currentPage(),
                'last_page'    => $messages->lastPage(),
            ],
        ]);
    }

    // ─── GET /api/v1/messages/{uuid} ──────────────────────────────────────────
    public function show(Message $message): JsonResponse
    {
        $this->authorize('view', $message);

        return response()->json(['success' => true, 'data' => new MessageResource($message)]);
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function resolveDevice(string $uuid): Device
    {
        $device = Device::where('uuid', $uuid)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        return $device;
    }

    private function dispatchMedia(Request $request, array $data): JsonResponse
    {
        $device = $this->resolveDevice($data['device_id']);
        $data['_key'] = $request->input('_key');

        try {
            $message = $this->dispatcher->send(auth()->user(), $device, $data);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'error' => $e->errors()], 422);
        }

        return response()->json(['success' => true, 'data' => new MessageResource($message)], 201);
    }
}
