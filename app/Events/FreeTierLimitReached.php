<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when a Free user reaches the active-QR limit (Pflichtenheft §2.4:
 * "Nach 10 QR-Codes → Upgrade-Prompt").
 *
 * Fired by {@see \App\Services\QrCodeService::enforceFreeTierLimit()} (the P2-T06
 * Free-Tier-Enforcement hook) right before the limit is rejected. This is the
 * extensible hook the upgrade-hint email (P2-T12) subscribes to. Listeners own
 * their own idempotency.
 */
class FreeTierLimitReached
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly int $activeCount,
        public readonly int $limit,
    ) {}
}
