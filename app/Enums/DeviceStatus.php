<?php

namespace App\Enums;

enum DeviceStatus: string
{
    case Disconnected = 'disconnected';
    case Connecting   = 'connecting';
    case Connected    = 'connected';
    case Banned       = 'banned';
    case Removed      = 'removed';

    public function label(): string
    {
        return match($this) {
            self::Disconnected => 'Disconnected',
            self::Connecting   => 'Connecting…',
            self::Connected    => 'Connected',
            self::Banned       => 'Banned',
            self::Removed      => 'Removed',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Connected    => 'green',
            self::Connecting   => 'yellow',
            self::Disconnected => 'gray',
            self::Banned       => 'red',
            self::Removed      => 'red',
        };
    }

    public function isOperational(): bool
    {
        return $this === self::Connected;
    }
}
