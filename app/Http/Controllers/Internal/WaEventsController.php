<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Enums\DeviceStatus;
use App\Enums\MessageStatus;
use App\Events\DeviceStatusChanged;
use App\Events\QrCodeGenerated;
use App\Models\Device;
use App\Models\Message;
use App\Services\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Internal endpoint — called exclusively by the Node.js wa-service.
 * Hardened: timing-safe secret comparison, input sanitization, idempotency.
 */
class WaEventsController extends Controller
{
    public function __construct(private readonly WebhookService $webhooks) {}

    public function handle(Request $request): JsonResponse
    {
        // ── 1. Timing-safe secret verification ───────────────────────────────
        $provided = $request->header('X-WG-Secret', '');
        $expected = config('services.wa_node.secret', '');

        if (! hash_equals(
            hash('sha256', $expected),
            hash('sha256', $provided)
        )) {
            Log::warning('[WA Events] Unauthorized access attempt', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // ── 2. Validate event structure ───────────────────────────────────────
        $event = $request->input('event');
        $data  = $request->input('data', []);

        if (! is_string($event) || empty($event)) {
            return response()->json(['error' => 'Invalid event'], 422);
        }

        // ── 3. Sanitize string inputs ─────────────────────────────────────────
        if (isset($data['body'])) {
            $data['body'] = strip_tags((string) $data['body']);
        }

        Log::info("[WA] {$event}", ['session' => $data['session_id'] ?? null]);

        match ($event) {
            'qr'               => $this->handleQr($data),
            'ready'            => $this->handleReady($data),
            'disconnected'     => $this->handleDisconnected($data),
            'message_ack'      => $this->handleMessageAck($data),
            'message_received' => $this->handleMessageReceived($data),
            'auth_failure'     => $this->handleAuthFailure($data),
            'banned'           => $this->handleBanned($data),
            default            => Log::notice("[WA] Unknown event: {$event}"),
        };

        return response()->json(['received' => true]);
    }

    // ─── Handlers ─────────────────────────────────────────────────────────────

    private function handleQr(array $data): void
    {
        $device = $this->findDevice($data['session_id'] ?? '');
        if (! $device) return;

        $device->update([
            'qr_code'       => $data['qr'] ?? null,
            'qr_expires_at' => now()->addSeconds(60),
            'status'        => DeviceStatus::Connecting,
        ]);

        $this->webhooks->dispatch($device->user, 'qr.generated', [
            'device_id' => $device->uuid,
        ]);

        // Real-time push to dashboard
        broadcast(new QrCodeGenerated($device, $data['qr'] ?? ''))->toOthers();
    }

    private function handleReady(array $data): void
    {
        $device = $this->findDevice($data['session_id'] ?? '');
        if (! $device) return;

        $device->update([
            'status'       => DeviceStatus::Connected,
            'phone_number' => $this->sanitizePhone($data['phone'] ?? ''),
            'connected_at' => now(),
            'last_seen_at' => now(),
            'qr_code'      => null,
            'qr_expires_at'=> null,
        ]);

        $this->webhooks->dispatch($device->user, 'device.connected', [
            'device_id'    => $device->uuid,
            'phone_number' => $device->phone_number,
        ]);

        broadcast(new DeviceStatusChanged($device))->toOthers();
    }

    private function handleDisconnected(array $data): void
    {
        $device = $this->findDevice($data['session_id'] ?? '');
        if (! $device) return;

        $device->update(['status' => DeviceStatus::Disconnected]);

        $this->webhooks->dispatch($device->user, 'device.disconnected', [
            'device_id' => $device->uuid,
            'reason'    => $data['reason'] ?? 'unknown',
        ]);

        broadcast(new DeviceStatusChanged($device))->toOthers();
    }

    private function handleMessageAck(array $data): void
    {
        $waId = $data['id'] ?? null;
        if (! $waId) return;

        // Idempotency: find by WA message ID
        $message = Message::where('wa_message_id', $waId)->first();
        if (! $message) return;

        $ackMap = [
            1  => MessageStatus::Sent,
            2  => MessageStatus::Delivered,
            3  => MessageStatus::Read,
            -1 => MessageStatus::Failed,
        ];

        $newStatus = $ackMap[(int)($data['ack'] ?? 0)] ?? null;
        if (! $newStatus) return;

        // Prevent backwards status update (e.g. read → delivered)
        $order = [
            MessageStatus::Queued->value    => 0,
            MessageStatus::Sending->value   => 1,
            MessageStatus::Sent->value      => 2,
            MessageStatus::Delivered->value => 3,
            MessageStatus::Read->value      => 4,
            MessageStatus::Failed->value    => 5,
        ];

        $currentOrder = $order[$message->status->value] ?? 0;
        $newOrder     = $order[$newStatus->value] ?? 0;
        if ($newOrder < $currentOrder) return; // never go backwards

        $tsField = match($newStatus) {
            MessageStatus::Delivered => 'delivered_at',
            MessageStatus::Read      => 'read_at',
            MessageStatus::Failed    => 'failed_at',
            default                  => null,
        };

        $message->update(array_filter([
            'status'   => $newStatus,
            $tsField   => $tsField ? now() : null,
        ]));

        $evtMap = [
            MessageStatus::Delivered->value => 'message.delivered',
            MessageStatus::Read->value      => 'message.read',
            MessageStatus::Failed->value    => 'message.failed',
        ];

        if (isset($evtMap[$newStatus->value])) {
            $this->webhooks->dispatch($message->user, $evtMap[$newStatus->value], [
                'message_id' => $message->uuid,
                'to'         => $message->to_number,
                'status'     => $newStatus->value,
                'timestamp'  => now()->toISOString(),
            ]);
        }
    }

    private function handleMessageReceived(array $data): void
    {
        $device = $this->findDevice($data['session_id'] ?? '');
        if (! $device) return;

        // Idempotency: skip duplicate inbound messages
        $waId = $data['id'] ?? null;
        if ($waId && Message::where('wa_message_id', $waId)->exists()) {
            return;
        }

        $message = Message::create([
            'user_id'       => $device->user_id,
            'device_id'     => $device->id,
            'to_number'     => $this->sanitizePhone($data['from'] ?? ''),
            'type'          => in_array($data['type'] ?? '', ['text','image','document','audio','video','location','contact'])
                                ? $data['type'] : 'text',
            'content'       => ['body' => strip_tags($data['body'] ?? '')],
            'status'        => MessageStatus::Delivered,
            'wa_message_id' => $waId,
            'sent_at'       => now(),
            'delivered_at'  => now(),
        ]);

        $this->webhooks->dispatch($device->user, 'message.received', [
            'from'       => $message->to_number,
            'body'       => $message->content['body'],
            'type'       => $message->type->value,
            'device_id'  => $device->uuid,
            'message_id' => $message->uuid,
            'timestamp'  => now()->toISOString(),
        ]);
    }

    private function handleAuthFailure(array $data): void
    {
        $device = $this->findDevice($data['session_id'] ?? '');
        if (! $device) return;

        $device->update(['status' => DeviceStatus::Disconnected]);
        Log::warning("[WA] Auth failure for device {$device->uuid}");
        broadcast(new DeviceStatusChanged($device))->toOthers();
    }

    private function handleBanned(array $data): void
    {
        $device = $this->findDevice($data['session_id'] ?? '');
        if (! $device) return;

        $device->update(['status' => DeviceStatus::Banned]);

        $this->webhooks->dispatch($device->user, 'device.banned', [
            'device_id' => $device->uuid,
        ]);

        broadcast(new DeviceStatusChanged($device))->toOthers();
    }

    // ─── Utilities ────────────────────────────────────────────────────────────

    private function findDevice(string $uuid): ?Device
    {
        if (empty($uuid)) return null;
        return Device::where('uuid', $uuid)->with('user')->first();
    }

    private function sanitizePhone(string $phone): string
    {
        // Strip @c.us, spaces, dashes — keep digits only
        return preg_replace('/[^0-9]/', '', str_replace('@c.us', '', $phone));
    }
}
