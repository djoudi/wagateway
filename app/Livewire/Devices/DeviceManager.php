<?php

namespace App\Livewire\Devices;

use App\Models\Device;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class DeviceManager extends Component
{
    public string  $newDeviceName = '';
    public bool    $showAddModal  = false;
    public bool    $showQrModal   = false;
    public ?string $qrCode        = null;
    public ?string $qrDeviceId    = null;
    public ?string $qrDeviceName  = null;
    public string  $qrStatus      = 'waiting'; // waiting | connected | expired
    public int     $qrCountdown   = 60;

    protected $listeners = [
        'qrGenerated'       => 'handleQrGenerated',
        'deviceConnected'   => 'handleDeviceConnected',
        'deviceDisconnected'=> 'handleDeviceDisconnected',
        'echo:private.user.' => 'handleBroadcast',
    ];

    public function mount(): void {}

    // ─── Add Device ──────────────────────────────────────────────────────────

    public function openAddModal(): void
    {
        $user  = auth()->user();
        $plan  = $user->plan;
        $count = Device::where('user_id', $user->id)->count();

        if ($plan && $count >= $plan->max_devices) {
            $this->addError('plan', "Your plan allows {$plan->max_devices} device(s). Upgrade to add more.");
            return;
        }

        $this->showAddModal = true;
        $this->newDeviceName = '';
    }

    public function createDevice(WhatsAppService $wa): void
    {
        $this->validate(['newDeviceName' => 'required|string|min:2|max:60']);

        $user   = auth()->user();
        $device = Device::create([
            'user_id' => $user->id,
            'name'    => $this->newDeviceName,
            'status'  => 'connecting',
        ]);

        $result = $wa->startSession($device);

        $this->showAddModal = false;
        $this->newDeviceName = '';

        // Show QR modal if QR returned immediately
        if (!empty($result['qr'])) {
            $this->openQrModal($device->uuid, $device->name, $result['qr']);
        } else {
            // QR will arrive via event push (Reverb broadcast)
            $this->qrDeviceId   = $device->uuid;
            $this->qrDeviceName = $device->name;
            $this->showQrModal  = true;
            $this->qrStatus     = 'waiting';
        }
    }

    // ─── QR Modal ────────────────────────────────────────────────────────────

    public function openQrModal(string $deviceUuid, string $name, ?string $qr = null): void
    {
        $this->qrDeviceId   = $deviceUuid;
        $this->qrDeviceName = $name;
        $this->qrCode       = $qr;
        $this->qrStatus     = $qr ? 'waiting' : 'loading';
        $this->qrCountdown  = 60;
        $this->showQrModal  = true;
    }

    public function reconnectDevice(string $deviceUuid, WhatsAppService $wa): void
    {
        $device = Device::where('uuid', $deviceUuid)->where('user_id', auth()->id())->firstOrFail();
        $device->update(['status' => 'connecting']);
        $result = $wa->startSession($device);

        if (!empty($result['qr'])) {
            $this->openQrModal($device->uuid, $device->name, $result['qr']);
        } else {
            $this->openQrModal($device->uuid, $device->name);
        }
    }

    public function closeQrModal(): void
    {
        $this->showQrModal  = false;
        $this->qrCode       = null;
        $this->qrDeviceId   = null;
        $this->qrStatus     = 'waiting';
    }

    // ─── Disconnect / Remove ─────────────────────────────────────────────────

    public function disconnectDevice(string $deviceUuid, WhatsAppService $wa): void
    {
        $device = Device::where('uuid', $deviceUuid)->where('user_id', auth()->id())->firstOrFail();
        $wa->terminateSession($device);
        $device->update(['status' => 'disconnected']);
    }

    public function removeDevice(string $deviceUuid, WhatsAppService $wa): void
    {
        $device = Device::where('uuid', $deviceUuid)->where('user_id', auth()->id())->firstOrFail();
        $wa->terminateSession($device);
        $device->delete();
    }

    // ─── Real-time event handlers (via Laravel Echo / Reverb) ────────────────

    public function handleQrGenerated(array $data): void
    {
        if (($data['device_id'] ?? null) === $this->qrDeviceId) {
            $this->qrCode      = $data['qr'];
            $this->qrStatus    = 'waiting';
            $this->qrCountdown = 60;
        }
    }

    public function handleDeviceConnected(array $data): void
    {
        if (($data['device_id'] ?? null) === $this->qrDeviceId) {
            $this->qrStatus = 'connected';
        }
    }

    public function handleDeviceDisconnected(array $data): void
    {
        // Refresh device list
    }

    public function render()
    {
        $devices = Device::where('user_id', auth()->id())
            ->orderBy('created_at')
            ->get();

        return view('livewire.devices.device-manager', compact('devices'));
    }
}
