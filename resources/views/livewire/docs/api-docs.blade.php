<x-layouts.app title="API Docs">

<div class="flex gap-4 -mx-5 -mt-5 h-[calc(100vh-52px)]">

    {{-- ── Sidebar nav ─────────────────────────────────────────────────────── --}}
    <div class="w-48 flex-shrink-0 bg-white border-r border-gray-100 p-4 overflow-y-auto">
        <div class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-3">Reference</div>
        @foreach ($sections as $key => $label)
            <button wire:click="setSection('{{ $key }}')"
                    class="w-full text-left px-3 py-1.5 rounded-lg text-xs font-medium mb-0.5 transition-colors
                        {{ $activeSection === $key
                            ? 'bg-green-50 text-green-700'
                            : 'text-gray-600 hover:bg-gray-50' }}">
                {{ $label }}
            </button>
        @endforeach

        <div class="mt-5 pt-4 border-t border-gray-100">
            <div class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-2">Base URL</div>
            <div class="text-[10px] font-mono bg-gray-50 rounded p-2 text-gray-600 break-all">
                {{ url('/api/v1') }}
            </div>
        </div>
    </div>

    {{-- ── Content ──────────────────────────────────────────────────────────── --}}
    <div class="flex-1 overflow-y-auto p-6">

        {{-- Language switcher --}}
        <div class="flex items-center gap-2 mb-6">
            @foreach ($languages as $lang)
                <button wire:click="setLanguage('{{ $lang }}')"
                        class="px-3 py-1 rounded-lg text-xs font-medium border transition-colors
                            {{ $activeLanguage === $lang
                                ? 'bg-gray-900 text-white border-gray-900'
                                : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300' }}">
                    {{ strtoupper($lang) }}
                </button>
            @endforeach
        </div>

        {{-- ── AUTHENTICATION ─────────────────────────────────────────────── --}}
        @if ($activeSection === 'authentication')
            <h2 class="text-base font-bold text-gray-900 mb-2">Authentication</h2>
            <p class="text-sm text-gray-600 mb-5">
                All API requests must include your API key as a Bearer token in the <code class="bg-gray-100 px-1.5 py-0.5 rounded text-xs">Authorization</code> header.
                Use your <strong>live key</strong> for production and your <strong>test key</strong> for development.
            </p>

            <x-docs-endpoint method="POST" path="/api/v1/messages/send/text" description="Requires Authorization header" />

            <div class="bg-gray-900 rounded-xl p-4 text-xs font-mono text-gray-300 leading-relaxed mb-4">
                @if ($activeLanguage === 'curl')
Authorization: Bearer wg_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
                @elseif ($activeLanguage === 'php')
$client = new \GuzzleHttp\Client();
$response = $client->post('{{ url('/api/v1') }}/messages/send/text', [
    'headers' => [
        'Authorization' => 'Bearer wg_live_xxxxxxxx',
        'Content-Type'  => 'application/json',
    ],
    'json' => [...],
]);
                @elseif ($activeLanguage === 'python')
import requests

headers = {
    "Authorization": "Bearer wg_live_xxxxxxxx",
    "Content-Type": "application/json"
}
                @elseif ($activeLanguage === 'javascript')
const response = await fetch('{{ url('/api/v1') }}/messages/send/text', {
    method: 'POST',
    headers: {
        'Authorization': 'Bearer wg_live_xxxxxxxx',
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({...}),
});
                @endif
            </div>

            <div class="bg-amber-50 border border-amber-100 rounded-xl p-4 text-xs text-amber-800">
                <i class="ti ti-shield-lock mr-1"></i>
                <strong>Security:</strong> Never expose your API key in client-side code or public repositories.
                Use environment variables or secrets management tools.
            </div>
        @endif

        {{-- ── DEVICES ────────────────────────────────────────────────────── --}}
        @if ($activeSection === 'devices')
            <h2 class="text-base font-bold text-gray-900 mb-5">Devices</h2>

            {{-- GET /devices --}}
            <div class="mb-6">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-[10px] font-bold bg-blue-100 text-blue-700 px-2 py-0.5 rounded">GET</span>
                    <code class="text-sm font-mono text-gray-800">/api/v1/devices</code>
                    <span class="text-xs text-gray-400">List all devices</span>
                </div>
                <div class="bg-gray-900 rounded-xl p-4 text-xs font-mono text-gray-300 leading-relaxed">
@if ($activeLanguage === 'curl')
curl -X GET {{ url('/api/v1') }}/devices \
  -H "Authorization: Bearer wg_live_..."
@elseif ($activeLanguage === 'php')
$res = $client->get('{{ url('/api/v1') }}/devices', ['headers' => $headers]);
$data = json_decode($res->getBody(), true);
@elseif ($activeLanguage === 'python')
res = requests.get('{{ url('/api/v1') }}/devices', headers=headers)
data = res.json()
@elseif ($activeLanguage === 'javascript')
const res = await fetch('{{ url('/api/v1') }}/devices', { headers });
const data = await res.json();
@endif
                </div>
                <div class="mt-2 bg-gray-50 border border-gray-100 rounded-xl p-4 text-xs font-mono text-gray-600 leading-relaxed">
<span class="text-green-600">// Response 200</span>
{
  "success": true,
  "data": [
    {
      "id": "uuid-xxxx",
      "name": "Marketing",
      "phone_number": "213700000001",
      "status": "connected",
      "messages_sent_today": 943,
      "messages_sent_total": 41200,
      "connected_at": "2025-06-01T08:00:00Z"
    }
  ]
}
                </div>
            </div>

            {{-- POST /devices --}}
            <div class="mb-6">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-[10px] font-bold bg-green-100 text-green-700 px-2 py-0.5 rounded">POST</span>
                    <code class="text-sm font-mono text-gray-800">/api/v1/devices</code>
                    <span class="text-xs text-gray-400">Add a new device</span>
                </div>
                <div class="bg-gray-900 rounded-xl p-4 text-xs font-mono text-gray-300 leading-relaxed">
@if ($activeLanguage === 'curl')
curl -X POST {{ url('/api/v1') }}/devices \
  -H "Authorization: Bearer wg_live_..." \
  -H "Content-Type: application/json" \
  -d '{"name":"Marketing"}'
@elseif ($activeLanguage === 'php')
$res = $client->post('{{ url('/api/v1') }}/devices', [
    'headers' => $headers,
    'json'    => ['name' => 'Marketing'],
]);
@elseif ($activeLanguage === 'python')
res = requests.post('{{ url('/api/v1') }}/devices',
    headers=headers, json={"name": "Marketing"})
@elseif ($activeLanguage === 'javascript')
const res = await fetch('{{ url('/api/v1') }}/devices', {
    method: 'POST', headers,
    body: JSON.stringify({ name: 'Marketing' }),
});
@endif
                </div>
            </div>
        @endif

        {{-- ── MESSAGES ────────────────────────────────────────────────────── --}}
        @if ($activeSection === 'messages')
            <h2 class="text-base font-bold text-gray-900 mb-5">Messages</h2>

            @php
                $endpoints = [
                    ['method'=>'POST','path'=>'/messages/send/text',     'desc'=>'Send text message'],
                    ['method'=>'POST','path'=>'/messages/send/image',    'desc'=>'Send image'],
                    ['method'=>'POST','path'=>'/messages/send/document', 'desc'=>'Send document'],
                    ['method'=>'POST','path'=>'/messages/send/audio',    'desc'=>'Send audio'],
                    ['method'=>'POST','path'=>'/messages/send/video',    'desc'=>'Send video'],
                    ['method'=>'POST','path'=>'/messages/send/location', 'desc'=>'Send location'],
                    ['method'=>'GET', 'path'=>'/messages',               'desc'=>'List messages'],
                    ['method'=>'GET', 'path'=>'/messages/{id}',          'desc'=>'Get message status'],
                ];
            @endphp

            {{-- Endpoint list --}}
            <div class="space-y-1 mb-6">
                @foreach ($endpoints as $ep)
                    <div class="flex items-center gap-3 p-3 bg-white border border-gray-100 rounded-xl">
                        <span class="text-[10px] font-bold w-10 text-center rounded py-0.5
                            {{ $ep['method'] === 'GET' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700' }}">
                            {{ $ep['method'] }}
                        </span>
                        <code class="text-xs font-mono text-gray-800 flex-1">/api/v1{{ $ep['path'] }}</code>
                        <span class="text-xs text-gray-400">{{ $ep['desc'] }}</span>
                    </div>
                @endforeach
            </div>

            {{-- Send text example --}}
            <h3 class="text-sm font-semibold text-gray-800 mb-3">Send text message</h3>
            <div class="bg-gray-900 rounded-xl p-4 text-xs font-mono text-gray-300 leading-relaxed mb-4">
@if ($activeLanguage === 'curl')
curl -X POST {{ url('/api/v1') }}/messages/send/text \
  -H "Authorization: Bearer wg_live_..." \
  -H "Content-Type: application/json" \
  -d '{
    "device_id": "uuid-xxxx",
    "to": "213700000001",
    "body": "مرحبا! هذه رسالة اختبارية."
  }'
@elseif ($activeLanguage === 'php')
$res = $client->post('{{ url('/api/v1') }}/messages/send/text', [
    'headers' => $headers,
    'json'    => [
        'device_id' => 'uuid-xxxx',
        'to'        => '213700000001',
        'body'      => 'Hello from WaGateway!',
    ],
]);
$msg = json_decode($res->getBody(), true)['data'];
echo $msg['id']; // message UUID
@elseif ($activeLanguage === 'python')
res = requests.post('{{ url('/api/v1') }}/messages/send/text',
    headers=headers, json={
        "device_id": "uuid-xxxx",
        "to": "213700000001",
        "body": "Hello from WaGateway!"
    })
msg = res.json()["data"]
print(msg["id"])
@elseif ($activeLanguage === 'javascript')
const res = await fetch('{{ url('/api/v1') }}/messages/send/text', {
    method: 'POST', headers,
    body: JSON.stringify({
        device_id: 'uuid-xxxx',
        to: '213700000001',
        body: 'Hello from WaGateway!',
    }),
});
const { data } = await res.json();
console.log(data.id); // message UUID
@endif
            </div>
            <div class="bg-gray-50 border border-gray-100 rounded-xl p-4 text-xs font-mono text-gray-600 leading-relaxed">
<span class="text-green-600">// Response 201</span>
{
  "success": true,
  "data": {
    "id": "msg-uuid-xxxx",
    "to": "213700000001",
    "type": "text",
    "status": "sent",
    "wa_id": "3EB0...",
    "sent_at": "2025-06-24T10:42:00Z"
  }
}
            </div>
        @endif

        {{-- ── ERRORS ──────────────────────────────────────────────────────── --}}
        @if ($activeSection === 'errors')
            <h2 class="text-base font-bold text-gray-900 mb-5">Error codes</h2>
            <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="text-left px-4 py-2.5 font-medium text-gray-500">HTTP</th>
                            <th class="text-left px-4 py-2.5 font-medium text-gray-500">Code</th>
                            <th class="text-left px-4 py-2.5 font-medium text-gray-500">Meaning</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach ([
                            [401,'MISSING_API_KEY','No API key provided in Authorization header'],
                            [401,'INVALID_API_KEY','API key not found or revoked'],
                            [402,'SUBSCRIPTION_EXPIRED','Plan has expired — renew to continue'],
                            [403,'ACCOUNT_SUSPENDED','Account is suspended by admin'],
                            [403,'DEVICE_LIMIT_REACHED','Plan device limit reached — upgrade to add more'],
                            [403,'FEATURE_NOT_AVAILABLE','Feature not included in current plan'],
                            [422,'DEVICE_NOT_CONNECTED','Target device is disconnected'],
                            [422,'VALIDATION_ERROR','Request body validation failed'],
                            [429,'RATE_LIMIT','Daily message limit reached'],
                            [500,'WA_SERVICE_ERROR','WhatsApp node service is unreachable'],
                        ] as [$http, $code, $desc])
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2.5 font-mono font-semibold text-gray-700">{{ $http }}</td>
                                <td class="px-4 py-2.5 font-mono text-red-600">{{ $code }}</td>
                                <td class="px-4 py-2.5 text-gray-600">{{ $desc }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 bg-gray-900 rounded-xl p-4 text-xs font-mono text-gray-300 leading-relaxed">
<span class="text-red-400">// Error response format</span>
{
  "success": false,
  "error": {
    "code": "RATE_LIMIT",
    "message": "Daily message limit of 1000 reached."
  }
}
            </div>
        @endif

        {{-- ── WEBHOOKS ────────────────────────────────────────────────────── --}}
        @if ($activeSection === 'webhooks')
            <h2 class="text-base font-bold text-gray-900 mb-2">Webhooks</h2>
            <p class="text-sm text-gray-600 mb-5">
                WaGateway sends a signed <code class="bg-gray-100 px-1 rounded text-xs">POST</code> request to your endpoint
                whenever a subscribed event occurs. Verify the signature using the <code class="bg-gray-100 px-1 rounded text-xs">X-WG-Signature</code> header.
            </p>

            <h3 class="text-sm font-semibold text-gray-800 mb-3">Signature verification</h3>
            <div class="bg-gray-900 rounded-xl p-4 text-xs font-mono text-gray-300 leading-relaxed mb-4">
@if ($activeLanguage === 'php')
$payload   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WG_SIGNATURE'] ?? '';
$secret    = getenv('WG_WEBHOOK_SECRET');

$expected  = 'sha256=' . hash_hmac('sha256', $payload, $secret);

if (!hash_equals($expected, $signature)) {
    http_response_code(401);
    exit('Invalid signature');
}

$event = json_decode($payload, true);
// Process $event['event'] and $event['data']
@elseif ($activeLanguage === 'python')
import hmac, hashlib

def verify(payload: bytes, signature: str, secret: str) -> bool:
    expected = 'sha256=' + hmac.new(
        secret.encode(), payload, hashlib.sha256
    ).hexdigest()
    return hmac.compare_digest(expected, signature)
@elseif ($activeLanguage === 'javascript')
const crypto = require('crypto');

function verify(payload, signature, secret) {
    const expected = 'sha256=' +
        crypto.createHmac('sha256', secret)
              .update(payload).digest('hex');
    return crypto.timingSafeEqual(
        Buffer.from(expected), Buffer.from(signature)
    );
}
@elseif ($activeLanguage === 'curl')
# Webhooks are push-based — no curl example needed.
# Configure your endpoint in the Webhooks section of the dashboard.
@endif
            </div>

            <h3 class="text-sm font-semibold text-gray-800 mb-3">Event payload example</h3>
            <div class="bg-gray-50 border border-gray-100 rounded-xl p-4 text-xs font-mono text-gray-600 leading-relaxed">
{
  "event": "message.received",
  "data": {
    "from": "213770000001",
    "body": "Hello!",
    "type": "text",
    "device_id": "uuid-xxxx",
    "message_id": "msg-uuid-xxxx",
    "timestamp": "2025-06-24T10:42:00Z"
  }
}
            </div>
        @endif

    </div>
</div>

</x-layouts.app>
