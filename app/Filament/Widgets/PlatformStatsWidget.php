<?php
namespace App\Filament\Widgets;
use App\Models\Device;
use App\Models\Message;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected static ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', User::count())->icon('heroicon-o-users')->color('success'),
            Stat::make('Active Devices', Device::where('status','connected')->count())->icon('heroicon-o-device-phone-mobile')->color('info'),
            Stat::make('Messages Today', Message::whereDate('created_at',today())->count())->icon('heroicon-o-chat-bubble-left-right')->color('warning'),
            Stat::make('Failed Today', Message::whereDate('created_at',today())->where('status','failed')->count())->icon('heroicon-o-x-circle')->color('danger'),
        ];
    }
}
