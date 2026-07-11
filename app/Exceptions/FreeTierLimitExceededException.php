<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a Free-Tier user attempts to create a QR-code beyond the active
 * limit (Pflichtenheft §2.4: "Nach 10 QR-Codes → Upgrade-Prompt").
 *
 * Only active, non-expired, non-burned, non-maxed codes with a Free
 * entitlement snapshot count against the limit; grandfathered Pro/Business
 * resources are excluded (Bestandsschutz, §3.5.3).
 */
class FreeTierLimitExceededException extends RuntimeException
{
    public function __construct(
        public readonly int $activeCount,
        public readonly int $limit,
        string $message = '',
    ) {
        parent::__construct(
            $message !== ''
                ? $message
                : "Free-Tier limit reached: {$activeCount}/{$limit} active QR-codes. Upgrade to create more."
        );
    }
}
