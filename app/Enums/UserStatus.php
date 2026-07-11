<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Account status of a platform user (Pflichtenheft §3.6.2, Filament-Admin).
 *
 * Admins toggle a user between Active and Blocked via the Filament panel.
 * The `users.status` column defaults to `active` (migration
 * 0001_01_01_000000_create_users_table).
 */
enum UserStatus: string
{
    case Active = 'active';
    case Blocked = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Blocked => 'Blocked',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Filament badge colour per status.
     */
    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Blocked => 'danger',
        };
    }
}
