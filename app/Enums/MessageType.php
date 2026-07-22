<?php

namespace App\Enums;

enum MessageType: string
{
    case Text     = 'text';
    case Image    = 'image';
    case Document = 'document';
    case Audio    = 'audio';
    case Video    = 'video';
    case Location = 'location';
    case Contact  = 'contact';
    case Sticker  = 'sticker';

    public function requiresMedia(): bool
    {
        return in_array($this, [self::Image, self::Document, self::Audio, self::Video, self::Sticker]);
    }
}
