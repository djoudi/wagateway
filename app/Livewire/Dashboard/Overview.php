<?php

namespace App\Livewire\Dashboard;

use App\Models\Device;
use App\Models\Message;
use App\Services\RateLimitService;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class Overview extends Component
{
    public array $stats        = [];
    public array $chartData    = [];
    public array $deviceStatus = [];
    public array $onboarding   = [];

    protected $listeners = [
        'deviceConnected'    => 'loadDeviceStatus',
        'deviceDisconnected' => 'loadDeviceStatus',
    ];

    public function mount(): void
    {
        $this->loadStats();
        $this->loadChart();
        $this->loadDeviceStatus();
        $this->loadOnboarding();
    }

    public function refresh(): void
    {
        Cache::forget("dashboard_stats_{auth()->id()}");
        $this->loadStats();
        $this->loadDeviceStatus();
    }

    private function loadStats(): void
    {
        $user = auth()->user();

        $this->stats = Cache::remember("dashboard_stats_{$user->id}", 60, function () use ($user) {
            $today     = Message::where('user_id', $user->id)->whereDate('created_at', today());
            $yesterday = Message::where('user_id', $user->id)->whereDate('created_at', yesterday());

            $todayCount     = $today->clone()->whereIn('status', ['sent','delivered','read'])->count();
            $yesterdayCount = $yesterday->clone()->whereIn('status', ['sent','delivered','read'])->count();
            $delivered      = $today->clone()->whereIn('status', ['delivered','read'])->count();
            $failed         = $today->clone()->where('status', 'failed')->count();

            $usage = app(RateLimitService::class)->usage($user);
            $limit = $user->plan?->daily_message_limit ?? 0;
            $delta = $yesterdayCount > 0
                ? round((($todayCount - $yesterdayCount) / $yesterdayCount) * 100, 1)
                : ($todayCount > 0 ? 100 : 0);

            return [
                'messages_today'    => $todayCount,
                'delta_percent'     => $delta,
                'delta_positive'    => $delta >= 0,
                'delivery_rate'     => $todayCount > 0 ? round(($delivered / $todayCount) * 100, 1) : 0,
                'failed_today'      => $failed,
                'connected_devices' => Device::where('user_id', $user->id)->where('status','connected')->count(),
                'total_devices'     => Device::where('user_id', $user->id)->count(),
                'usage'             => $usage,
                'limit'             => $limit,
                'usage_percent'     => $limit > 0 ? min(round(($usage / $limit) * 100), 100) : 0,
                'plan_name'         => $user->plan?->name ?? 'Free',
            ];
        });
    }

    private function loadChart(): void
    {
        $user = auth()->user();
        $this->chartData = Cache::remember("dashboard_chart_{$user->id}", 300, function () use ($user) {
            return collect(range(6, 0))->map(function ($i) use ($user) {
                $date  = now()->subDays($i);
                $count = Message::where('user_id', $user->id)
                    ->whereDate('created_at', $date)
                    ->whereIn('status', ['sent','delivered','read'])
                    ->count();
                return ['label' => $date->format('D'), 'value' => $count, 'today' => $i === 0];
            })->toArray();
        });
    }

    public function loadDeviceStatus(): void
    {
        $this->deviceStatus = Device::where('user_id', auth()->id())
            ->select('uuid','name','phone_number','status','messages_sent_today','last_seen_at')
            ->get()->toArray();
    }

    private function loadOnboarding(): void
    {
        $steps = auth()->user()->onboardingSteps();
        if ($steps['completed']) {
            $this->onboarding = []; // hide after completion
            return;
        }
        $this->onboarding = $steps;
    }

    public function render()
    {
        return view('livewire.dashboard.overview');
    }
}
