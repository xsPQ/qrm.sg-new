<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Domain\Entitlement\EntitlementSnapshot;
use RuntimeException;

/**
 * Thrown when a user attempts to use a Pro/Business-only feature on a QR code
 * whose entitlement snapshot does not grant it (Pflichtenheft §2.1–§2.3, P2-T07).
 *
 * The snapshot is the single source of truth: at creation time the gate checks
 * the user's *current* plan; on edit it checks the code's own (immutable,
 * grandfathered) snapshot. Free snapshots never grant custom-alias or
 * password-protection (§2.1).
 */
class FeatureNotEntitledException extends RuntimeException
{
    public const FEATURE_CUSTOM_ALIAS = 'custom_alias';
    public const FEATURE_PASSWORD_PROTECTION = 'password_protection';

    /** Human-readable labels for the structured upgrade hint. */
    private const LABELS = [
        self::FEATURE_CUSTOM_ALIAS => 'Custom aliases',
        self::FEATURE_PASSWORD_PROTECTION => 'Password protection',
    ];

    public function __construct(
        public readonly string $feature,
        public readonly string $plan,
        string $message = '',
    ) {
        $label = self::LABELS[$feature] ?? ucfirst(str_replace('_', ' ', $feature));

        parent::__construct(
            $message !== ''
                ? $message
                : __(':label are not available on the :plan plan. Upgrade to Pro or Business to unlock this feature.', [
                    'label' => __($label),
                    'plan' => ucfirst($plan),
                ])
        );
    }

    public static function forFeature(string $feature, EntitlementSnapshot $snapshot): self
    {
        return new self($feature, $snapshot->plan());
    }
}
