<?php

namespace App\Enums;

enum WebhookEvent: string
{
    case MessageReceived    = 'message.received';
    case MessageSent        = 'message.sent';
    case MessageDelivered   = 'message.delivered';
    case MessageRead        = 'message.read';
    case MessageFailed      = 'message.failed';
    case DeviceConnected    = 'device.connected';
    case DeviceDisconnected = 'device.disconnected';
    case DeviceBanned       = 'device.banned';
    case QrGenerated        = 'qr.generated';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
