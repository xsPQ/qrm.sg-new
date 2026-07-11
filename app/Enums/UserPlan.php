<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Subscription plan of a platform user (Pflichtenheft §2.1–§2.4, §3.5.3).
 *
 * Mirrors the plan domain encoded in
 * {@see \App\Domain\Entitlement\EntitlementSnapshot::PLANS}. The `users.plan`
 * column defaults to `free`. Admins may reassign a user's plan via the
 * Filament panel; resource-level grandfathering is enforced separately by the
 * immutable entitlement snapshot.
 */
enum UserPlan: string
{
    case Free = 'free';
    case Pro = 'pro';
    case Business = 'business';

    public function label(): string
    {
        return match ($this) {
            self::Free => 'Free',
            self::Pro => 'Pro',
            self::Business => 'Business',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Filament badge colour per plan.
     */
    public function color(): string
    {
        return match ($this) {
            self::Free => 'gray',
            self::Pro => 'success',
            self::Business => 'warning',
        };
    }
}
