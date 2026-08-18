<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\ScheduledMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    // ─── POST /api/v1/messages/schedule ───────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id'    => ['required', 'string'],
            'to'           => ['required', 'string', 'min:7', 'max:20'],
            'type'         => ['required', 'in:text,image,document,audio,video'],
            'body'         => ['required_if:type,text', 'string', 'max:4096'],
            'url'          => ['required_unless:type,text', 'url'],
            'caption'      => ['nullable', 'string', 'max:1024'],
            'scheduled_at' => ['required', 'date', 'after:now'],
        ]);

        $device = Device::where('uuid', $data['device_id'])
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $scheduled = ScheduledMessage::create([
            'user_id'      => auth()->id(),
            'device_id'    => $device->id,
            'to_number'    => preg_replace('/[^0-9]/', '', $data['to']),
            'message_data' => [
                'type'    => $data['type'],
                'body'    => $data['body'] ?? null,
                'url'     => $data['url'] ?? null,
                'caption' => $data['caption'] ?? null,
            ],
            'scheduled_at' => $data['scheduled_at'],
            'status'       => 'pending',
        ]);

        return response()->json(['success' => true, 'data' => $scheduled], 201);
    }

    // ─── GET /api/v1/messages/schedule ────────────────────────────────────────
    public function index(): JsonResponse
    {
        $scheduled = auth()->user()->scheduledMessages()
            ->with('device:id,uuid,name')
            ->latest('scheduled_at')
            ->get();

        return response()->json(['success' => true, 'data' => $scheduled]);
    }

    // ─── DELETE /api/v1/messages/schedule/{uuid} ──────────────────────────────
    public function destroy(ScheduledMessage $scheduled): JsonResponse
    {
        if ($scheduled->user_id !== auth()->id() || $scheduled->status !== 'pending') {
            abort(403, 'You can only cancel your own pending scheduled messages.');
        }

        $scheduled->update(['status' => 'cancelled']);

        return response()->json(['success' => true, 'message' => 'Scheduled message cancelled.']);
    }
}
