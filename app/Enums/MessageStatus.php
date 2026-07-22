<?php

namespace App\Enums;

enum MessageStatus: string
{
    case Queued    = 'queued';
    case Sending   = 'sending';
    case Sent      = 'sent';
    case Delivered = 'delivered';
    case Read      = 'read';
    case Failed    = 'failed';

    public function isFinal(): bool
    {
        return in_array($this, [self::Read, self::Failed]);
    }
}
