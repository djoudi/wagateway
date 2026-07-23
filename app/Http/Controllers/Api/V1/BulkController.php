<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessBulkJob;
use App\Models\BulkJob;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BulkController extends Controller
{
    // ─── POST /api/v1/messages/bulk ───────────────────────────────────────────
    public function send(Request $request): JsonResponse
    {
        $user = auth()->user();
        $plan = $user->plan;

        $data = $request->validate([
            'device_id'    => ['required', 'string'],
            'recipients'   => ['required', 'array', 'min:1', "max:{$plan->bulk_batch_limit}"],
            'recipients.*' => ['required', 'string'],
            'message'      => ['required', 'array'],
            'message.type' => ['required', 'in:text,image,document'],
            'message.body' => ['required_if:message.type,text', 'string', 'max:4096'],
            'message.url'  => ['required_if:message.type,image,document', 'url'],
            'message.caption'  => ['nullable', 'string'],
            'message.filename' => ['nullable', 'string'],
            'delay_min'    => ['nullable', 'integer', 'min:0', 'max:30'],
            'delay_max'    => ['nullable', 'integer', 'min:0', 'max:60'],
            'name'         => ['nullable', 'string', 'max:80'],
        ]);

        $device = Device::where('uuid', $data['device_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        if (! $device->isConnected()) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'DEVICE_NOT_CONNECTED', 'message' => 'Selected device is not connected.'],
            ], 422);
        }

        // Daily limit pre-check
        $remaining = $user->plan->daily_message_limit - app(\App\Services\RateLimitService::class)->usage($user);
        if (count($data['recipients']) > $remaining) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'RATE_LIMIT', 'message' => "Only {$remaining} messages remaining today."],
            ], 429);
        }

        $job = BulkJob::create([
            'user_id'            => $user->id,
            'device_id'          => $device->id,
            'name'               => $data['name'] ?? 'Bulk — ' . now()->format('d/m H:i'),
            'status'             => 'pending',
            'message_template'   => $data['message'],
            'total_recipients'   => count($data['recipients']),
            'delay_min_seconds'  => $data['delay_min'] ?? 1,
            'delay_max_seconds'  => $data['delay_max'] ?? 3,
        ]);

        ProcessBulkJob::dispatch($job, $data['recipients'])->onQueue('bulk');

        return response()->json([
            'success' => true,
            'data'    => [
                'job_id'      => $job->uuid,
                'status'      => 'pending',
                'total'       => $job->total_recipients,
                'message'     => 'Bulk job queued. Track progress via /api/v1/messages/bulk/{id}',
            ],
        ], 202);
    }

    // ─── GET /api/v1/messages/bulk/{uuid} ─────────────────────────────────────
    public function status(BulkJob $bulkJob): JsonResponse
    {
        $this->authorize('view', $bulkJob);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'             => $bulkJob->uuid,
                'name'           => $bulkJob->name,
                'status'         => $bulkJob->status,
                'total'          => $bulkJob->total_recipients,
                'sent'           => $bulkJob->sent_count,
                'delivered'      => $bulkJob->delivered_count,
                'failed'         => $bulkJob->failed_count,
                'progress_pct'   => $bulkJob->total_recipients > 0
                    ? round(($bulkJob->sent_count / $bulkJob->total_recipients) * 100, 1)
                    : 0,
                'started_at'     => $bulkJob->started_at?->toISOString(),
                'completed_at'   => $bulkJob->completed_at?->toISOString(),
            ],
        ]);
    }

    // ─── DELETE /api/v1/messages/bulk/{uuid} (cancel) ─────────────────────────
    public function cancel(BulkJob $bulkJob): JsonResponse
    {
        $this->authorize('delete', $bulkJob);

        if (! in_array($bulkJob->status, ['pending', 'running'])) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'CANNOT_CANCEL', 'message' => "Job is already {$bulkJob->status}."],
            ], 422);
        }

        $bulkJob->update(['status' => 'cancelled']);

        return response()->json(['success' => true, 'message' => 'Bulk job cancelled.']);
    }
}
