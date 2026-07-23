<?php

namespace App\Services;

use App\Enums\MessageStatus;
use App\Enums\MessageType;
use App\Models\Device;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MessageDispatcher
{
    public function __construct(
        private readonly WhatsAppService $wa,
        private readonly RateLimitService $rateLimit,
        private readonly WebhookService   $webhooks,
    ) {}

    /**
     * Validate, persist and dispatch a single message.
     * Returns the created Message model.
     *
     * @throws ValidationException|\\RuntimeException
     */
    public function send(User $user, Device $device, array $data): Message
    {
        // 1. Rate-limit check
        $limit = $this->rateLimit->check($user);
        if (! $limit['allowed']) {
            throw ValidationException::withMessages([
                'rate_limit' => "Daily message limit of {$limit['limit']} reached.",
            ]);
        }

        // 2. Device availability check
        if (! $device->isConnected()) {
            throw ValidationException::withMessages([
                'device' => "Device [{$device->name}] is not connected.",
            ]);
        }

        // 3. Persist + send atomically (DB transaction protects the record)
        return DB::transaction(function () use ($user, $device, $data, $limit) {

            $message = Message::create([
                'user_id'   => $user->id,
                'device_id' => $device->id,
                'to_number' => $data['to'],
                'type'      => $data['type'] ?? MessageType::Text->value,
                'content'   => $this->buildContent($data),
                'status'    => MessageStatus::Sending,
                'is_test'   => $user->api_key_test && str_starts_with($data['_key'] ?? '', 'wg_test_'),
            ]);

            // 4. Call WA node service
            $result = $this->dispatchToNode($device, $message, $data);

            if ($result['success'] ?? false) {
                $message->markSent($result['id'] ?? '');
                $this->rateLimit->increment($user);
                $device->increment('messages_sent_today');
                $device->increment('messages_sent_total');
                // Fire webhook async
                $this->webhooks->dispatch($user, 'message.sent', [
                    'message_id' => $message->uuid,
                    'to'         => $message->to_number,
                    'type'       => $message->type->value,
                    'status'     => 'sent',
                ]);
            } else {
                $message->markFailed($result['error'] ?? 'Unknown WA node error');
            }

            return $message->refresh();
        });
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function dispatchToNode(Device $device, Message $message, array $data): array
    {
        return match ($message->type) {
            MessageType::Text     => $this->wa->sendText($device, $message->to_number, $data['body']),
            MessageType::Image    => $this->wa->sendMedia($device, $message->to_number, 'image', $data['url'], $data['caption'] ?? null),
            MessageType::Document => $this->wa->sendMedia($device, $message->to_number, 'document', $data['url'], $data['caption'] ?? null, $data['filename'] ?? null),
            MessageType::Audio    => $this->wa->sendMedia($device, $message->to_number, 'audio', $data['url']),
            MessageType::Video    => $this->wa->sendMedia($device, $message->to_number, 'video', $data['url'], $data['caption'] ?? null),
            MessageType::Location => $this->wa->sendLocation($device, $message->to_number, $data['latitude'], $data['longitude'], $data['name'] ?? null),
            MessageType::Contact  => $this->wa->sendContact($device, $message->to_number, $data['vcard']),
            default               => ['success' => false, 'error' => 'Unsupported message type'],
        };
    }

    private function buildContent(array $data): array
    {
        return array_filter([
            'body'     => $data['body']     ?? null,
            'url'      => $data['url']      ?? null,
            'caption'  => $data['caption']  ?? null,
            'filename' => $data['filename'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude'=> $data['longitude']?? null,
            'name'     => $data['name']     ?? null,
            'vcard'    => $data['vcard']    ?? null,
        ]);
    }
}
