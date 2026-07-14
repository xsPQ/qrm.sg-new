<?php

namespace App\Enums;

enum QrCodeType: string
{
    case Url = 'url';
    case Text = 'text';
    case Email = 'email';
    case Phone = 'phone';
    case Sms = 'sms';
    case Wifi = 'wifi';
    case Crypto = 'crypto';
    case Vcard = 'vcard';
    case Event = 'event';
    case Message = 'message';
    case Redirect = 'redirect';
    case Social = 'social';

    public function label(): string
    {
        return match ($this) {
            self::Url => 'URL',
            self::Text => 'Text',
            self::Email => 'Email',
            self::Phone => 'Phone',
            self::Sms => 'SMS',
            self::Wifi => 'WiFi',
            self::Crypto => 'Crypto',
            self::Vcard => 'vCard',
            self::Event => 'Event',
            self::Message => 'Message',
            self::Redirect => 'Redirect',
            self::Social => 'Social',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
