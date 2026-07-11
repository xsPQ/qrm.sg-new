<?php

declare(strict_types=1);

namespace App\Enums;

enum QrStatus: string
{
    case Active = 'active';
    case Expired = 'expired';
    case Burned = 'burned';
    case Disabled = 'disabled';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Expired => 'Expired',
            self::Burned => 'Burned',
            self::Disabled => 'Disabled',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Filament badge colour per status (DEV-163 Filament-Admin).
     */
    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Expired => 'gray',
            self::Burned => 'warning',
            self::Disabled => 'danger',
        };
    }
}
