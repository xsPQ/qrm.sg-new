<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\FreeTierLimitReached;
use App\Mail\FreeTierUpgradeHintMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

/**
 * Sends the Upgrade-Hinweis-Mail when a Free user reaches the active-QR limit
 * (P2-T12 / Pflichtenheft §2.4: "Nach 10 QR-Codes → Upgrade-Prompt").
 *
 * The enforcement hook fires on every create attempt while the user is at the
 * limit. To avoid spamming, the hint is throttled to at most once per user per
 * week. Listeners own their own idempotency.
 *
 * The Mailable itself is queued (ShouldQueue), so actual delivery runs through
 * the queue (P1-T06).
 */
class SendFreeTierUpgradeHint implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(FreeTierLimitReached $event): void
    {
        $cacheKey = 'mail:free-upgrade-hint:'.$event->user->getKey();

        if (Cache::has($cacheKey)) {
            return;
        }

        Mail::to($event->user)->send(new FreeTierUpgradeHintMail(
            $event->user,
            $event->activeCount,
            $event->limit,
        ));

        // Throttle the hint to once per week.
        Cache::put($cacheKey, true, now()->addDays(7));
    }
}
