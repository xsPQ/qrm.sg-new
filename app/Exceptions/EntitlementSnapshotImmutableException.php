<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when an attempt is made to mutate a QR code's `entitlement_snapshot`
 * after it has been persisted once.
 *
 * This is the S-Tier invariant of the entitlement snapshot (Pflichtenheft
 * §3.5.3, §6.2): the snapshot is set exactly once at QR-code creation and must
 * never change afterwards — not via edits, and not via a billing webhook that
 * reacts to a plan downgrade (Bestandsschutz / grandfathering). Fail loudly so
 * accidental overwrites surface immediately instead of silently corrupting
 * grandfathered rights.
 */
class EntitlementSnapshotImmutableException extends RuntimeException
{
    public function __construct(
        public readonly ?int $qrCodeId = null,
        string $message = '',
    ) {
        parent::__construct(
            $message !== ''
                ? $message
                : 'The entitlement_snapshot is immutable and may not be changed after creation'
                    .($qrCodeId !== null ? " (QR code #{$qrCodeId})." : '.')
        );
    }
}
