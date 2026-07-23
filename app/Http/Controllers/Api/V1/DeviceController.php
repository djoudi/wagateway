<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\DeviceResource;
use App\Models\Device;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DeviceController extends Controller
{
    public function __construct(private readonly WhatsAppService $wa) {}

    // ─── GET /api/v1/devices ──────────────────────────────────────────────────
    public function index(): JsonResponse
    {
        $devices = Device::forUser(auth()->id())->get();

        return response()->json([
            'success' => true,
            'data'    => DeviceResource::collection($devices),
        ]);
    }

    // ─── POST /api/v1/devices ─────────────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $user = auth()->user();
        $plan = $user->plan;

        // Plan device limit enforcement
        $deviceCount = Device::forUser($user->id)->count();
        if ($plan && $deviceCount >= $plan->max_devices) {
            return response()->json([
                'success' => false,
                'error'   => [
                    'code'    => 'DEVICE_LIMIT_REACHED',
                    'message' => "Your plan allows {$plan->max_devices} device(s). Upgrade to add more.",
                ],
            ], 403);
        }

        $data = $request->validate(['name' => ['required', 'string', 'max:60']]);

        $device = Device::create([
            'user_id' => $user->id,
            'name'    => $data['name'],
            'status'  => 'connecting',
        ]);

        // Request QR from Node service
        $qrResult = $this->wa->startSession($device);

        return response()->json([
            'success' => true,
            'data'    => new DeviceResource($device->refresh()),
            'qr'      => $qrResult['qr'] ?? null,
        ], 201);
    }

    // ─── GET /api/v1/devices/{uuid} ───────────────────────────────────────────
    public function show(Device $device): JsonResponse
    {
        $this->authorize('view', $device);

        // Sync live status from Node service
        $status = $this->wa->getSessionStatus($device);
        if (isset($status['status'])) {
            $device->update(['status' => $status['status']]);
        }

        return response()->json(['success' => true, 'data' => new DeviceResource($device->refresh())]);
    }

    // ─── GET /api/v1/devices/{uuid}/qr ───────────────────────────────────────
    public function qr(Device $device): JsonResponse
    {
        $this->authorize('view', $device);

        $result = $this->wa->getQrCode($device);

        if (! ($result['qr'] ?? null)) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'QR_NOT_AVAILABLE', 'message' => 'QR code is not available. The device may already be connected.'],
            ], 404);
        }

        $device->update([
            'qr_code'       => $result['qr'],
            'qr_expires_at' => now()->addSeconds(60),
        ]);

        return response()->json([
            'success' => true,
            'data'    => ['qr' => $result['qr'], 'expires_at' => now()->addSeconds(60)->toISOString()],
        ]);
    }

    // ─── DELETE /api/v1/devices/{uuid} ───────────────────────────────────────
    public function destroy(Device $device): JsonResponse
    {
        $this->authorize('delete', $device);

        $this->wa->terminateSession($device);
        $device->delete();

        return response()->json(['success' => true, 'message' => 'Device removed.']);
    }

    // ─── POST /api/v1/devices/{uuid}/reconnect ────────────────────────────────
    public function reconnect(Device $device): JsonResponse
    {
        $this->authorize('update', $device);

        $device->update(['status' => 'connecting']);
        $result = $this->wa->startSession($device);

        return response()->json([
            'success' => true,
            'qr'      => $result['qr'] ?? null,
        ]);
    }
}
