<?php

namespace App\Livewire\Devices;

use App\Enums\DeviceStatus;
use App\Models\Device;
use App\Services\WhatsAppService;
use Livewire\Component;

class DeviceManager extends Component
{
    public string $newDeviceName = '';
    public bool $showAddModal = false;
    public bool $showQrModal = false;
    public ?string $qrCode = null;
    public ?string $qrDeviceId = null;
    public ?string $qrDeviceName = null;
    public string $qrStatus = 'waiting';
    public int $qrCountdown = 60;

    public function getListeners(): array
    {
        $userId = auth()->id();

        if (! $userId) {
            return [];
        }

        return [
            "echo-private:user.{$userId},qr.generated" => 'handleQrGenerated',
            "echo-private:user.{$userId},device.status" => 'handleDeviceStatusBroadcast',
        ];
    }

    public function mount(): void
    {
        if (request()->boolean('add')) {
            $this->showAddModal = true;
        }
    }

    public function openAddModal(): void
    {
        $this->resetErrorBag();
        $this->newDeviceName = '';
        $this->showAddModal = true;
    }

    public function closeAddModal(): void
    {
        $this->showAddModal = false;
        $this->newDeviceName = '';
        $this->resetErrorBag('newDeviceName');
    }

    public function createDevice(WhatsAppService $wa): void
    {
        $this->validate(['newDeviceName' => 'required|string|min:2|max:60']);

        $user = auth()->user();
        $plan = $user->plan;
        $count = Device::where('user_id', $user->id)->count();

        if ($plan && $count >= $plan->max_devices) {
            $this->addError('plan', "Your plan allows {$plan->max_devices} device(s). Upgrade to add more.");
            $this->showAddModal = false;

            return;
        }

        $device = Device::create([
            'user_id' => $user->id,
            'name' => $this->newDeviceName,
            'status' => 'connecting',
        ]);

        $result = $wa->startSession($device);

        $this->showAddModal = false;
        $this->newDeviceName = '';

        if (! empty($result['qr'])) {
            $this->openQrModal($device->uuid, $device->name, $result['qr']);
        } else {
            $this->openQrModal($device->uuid, $device->name);
        }
    }

    public function openQrModal(string $deviceUuid, string $name, ?string $qr = null): void
    {
        $this->qrDeviceId = $deviceUuid;
        $this->qrDeviceName = $name;
        $this->qrCode = $qr;
        $this->qrStatus = $qr ? 'waiting' : 'loading';
        $this->qrCountdown = 60;
        $this->showQrModal = true;
    }

    public function reconnectDevice(string $deviceUuid, WhatsAppService $wa): void
    {
        $device = Device::where('uuid', $deviceUuid)->where('user_id', auth()->id())->firstOrFail();
        $device->update(['status' => 'connecting']);
        $result = $wa->startSession($device);

        $this->openQrModal($device->uuid, $device->name, $result['qr'] ?? null);
    }

    public function closeQrModal(): void
    {
        $this->showQrModal = false;
        $this->qrCode = null;
        $this->qrDeviceId = null;
        $this->qrStatus = 'waiting';
    }

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

    public function pollQrFromDevice(WhatsAppService $wa): void
    {
        if (! $this->showQrModal || ! $this->qrDeviceId || $this->qrStatus === 'connected') {
            return;
        }

        $device = Device::where('uuid', $this->qrDeviceId)
            ->where('user_id', auth()->id())
            ->first();

        if (! $device) {
            return;
        }

        if ($device->status === DeviceStatus::Connected) {
            $this->qrStatus = 'connected';
            $this->qrCode = null;

            return;
        }

        if ($device->qr_code && ! $device->isQrExpired()) {
            $this->qrCode = $device->qr_code;
            $this->qrStatus = 'waiting';
            $this->qrCountdown = 60;

            return;
        }

        $result = $wa->getQrCode($device);
        $qr = $result['qr'] ?? null;

        if (empty($qr)) {
            return;
        }

        $device->update([
            'qr_code' => $qr,
            'qr_expires_at' => now()->addSeconds(60),
        ]);

        $this->qrCode = $qr;
        $this->qrStatus = 'waiting';
        $this->qrCountdown = 60;
    }

    public function handleQrGenerated(array $data): void
    {
        if (($data['device_id'] ?? null) === $this->qrDeviceId) {
            $this->qrCode = $data['qr'] ?? null;
            $this->qrStatus = 'waiting';
            $this->qrCountdown = 60;
        }
    }

    public function handleDeviceStatusBroadcast(array $data): void
    {
        if (($data['device_id'] ?? null) !== $this->qrDeviceId) {
            return;
        }

        $status = $data['status'] ?? '';

        if ($status === 'connected') {
            $this->qrStatus = 'connected';
        }
    }

    public function render()
    {
        $devices = Device::where('user_id', auth()->id())
            ->orderBy('created_at')
            ->get();

        return view('livewire.devices.device-manager', compact('devices'));
    }
}
