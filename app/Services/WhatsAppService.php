<?php

namespace App\Services;

use App\Enums\DeviceStatus;
use App\Enums\MessageType;
use App\Models\Device;
use App\Models\Message;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private string $baseUrl;
    private string $secret;
    private int    $timeout = 10;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.wa_node.url'), '/');
        $this->secret  = config('services.wa_node.secret');
    }

    // ─── Session Management ───────────────────────────────────────────────────

    public function startSession(Device $device): array
    {
        return $this->post('/session/start', [
            'session_id'   => $device->uuid,
            'webhook_url'  => route('internal.wa-events'),
            'webhook_secret' => $this->secret,
        ]);
    }

    public function getSessionStatus(Device $device): array
    {
        return $this->get("/session/status/{$device->uuid}");
    }

    public function terminateSession(Device $device): array
    {
        return $this->delete("/session/{$device->uuid}");
    }

    public function getQrCode(Device $device): array
    {
        return $this->get("/session/qr/{$device->uuid}");
    }

    // ─── Messaging ────────────────────────────────────────────────────────────

    public function sendText(Device $device, string $to, string $body): array
    {
        return $this->post('/send/text', [
            'session_id' => $device->uuid,
            'to'         => $this->normalizeNumber($to),
            'body'       => $body,
        ]);
    }

    public function sendMedia(Device $device, string $to, string $type, string $url, ?string $caption = null, ?string $filename = null): array
    {
        return $this->post("/send/{$type}", [
            'session_id' => $device->uuid,
            'to'         => $this->normalizeNumber($to),
            'url'        => $url,
            'caption'    => $caption,
            'filename'   => $filename,
        ]);
    }

    public function sendLocation(Device $device, string $to, float $lat, float $lng, ?string $name = null): array
    {
        return $this->post('/send/location', [
            'session_id' => $device->uuid,
            'to'         => $this->normalizeNumber($to),
            'latitude'   => $lat,
            'longitude'  => $lng,
            'name'       => $name,
        ]);
    }

    public function sendContact(Device $device, string $to, array $vcard): array
    {
        return $this->post('/send/contact', [
            'session_id' => $device->uuid,
            'to'         => $this->normalizeNumber($to),
            'vcard'      => $vcard,
        ]);
    }

    // ─── Health ───────────────────────────────────────────────────────────────

    public function ping(): bool
    {
        try {
            $res = Http::timeout(3)->get("{$this->baseUrl}/health");
            return $res->ok();
        } catch (ConnectionException) {
            return false;
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Normalise phone number to WhatsApp JID format.
     * Strips spaces/dashes, ensures no leading +, appends @c.us
     */
    private function normalizeNumber(string $number): string
    {
        $clean = preg_replace('/[^0-9]/', '', $number);
        return $clean . '@c.us';
    }

    private function post(string $endpoint, array $data): array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->timeout($this->timeout)
                ->post("{$this->baseUrl}{$endpoint}", $data);

            return $this->parseResponse($response);
        } catch (ConnectionException $e) {
            Log::error("[WA Service] Connection failed: {$e->getMessage()}");
            return ['success' => false, 'error' => 'WA service unreachable'];
        }
    }

    private function get(string $endpoint): array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->timeout($this->timeout)
                ->get("{$this->baseUrl}{$endpoint}");

            return $this->parseResponse($response);
        } catch (ConnectionException $e) {
            Log::error("[WA Service] GET failed: {$endpoint} — {$e->getMessage()}");
            return ['success' => false, 'error' => 'WA service unreachable'];
        }
    }

    private function delete(string $endpoint): array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->timeout($this->timeout)
                ->delete("{$this->baseUrl}{$endpoint}");

            return $this->parseResponse($response);
        } catch (ConnectionException $e) {
            return ['success' => false, 'error' => 'WA service unreachable'];
        }
    }

    private function headers(): array
    {
        return [
            'X-WG-Secret'  => $this->secret,
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ];
    }

    private function parseResponse(\Illuminate\Http\Client\Response $response): array
    {
        if ($response->failed()) {
            Log::warning('[WA Service] Non-2xx response', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
        }

        return $response->json() ?? ['success' => false, 'error' => 'Empty response'];
    }
}
