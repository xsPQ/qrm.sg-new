<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\QrCode;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/**
 * Delete anonymous (guest) QR codes that have passed their 24h expiry
 * (M5-T03b / Pflichtenheft §2.1).
 *
 * A "guest code" is a QrCode owned by a user whose email matches the
 * `guest_*@anonymous.qrm.sg` pattern created by {@see \App\Livewire\AnonymousCreator}.
 * This job hard-deletes (force-deletes, to bypass SoftDeletes) those QrCodes
 * whose `expires_at` has passed, along with their routes and the now-orphaned
 * guest user accounts. Non-guest codes are never touched — regular Free-Tier
 * expiry is handled separately by {@see QrCode::cleanupExpired()} which only
 * transitions status.
 *
 * Idempotent: only codes past their deadline are removed, so a re-run is safe.
 */
class CleanupGuestQrCodes implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Execute the job.
     *
     * @return int Number of guest QR codes deleted.
     */
    public function handle(): int
    {
        $now = now();

        // Collect guest user IDs first.
        $guestUserIds = User::where('email', 'like', '%@anonymous.qrm.sg')
            ->pluck('id');

        if ($guestUserIds->isEmpty()) {
            return 0;
        }

        // Hard-delete expired guest QR codes (forceDelete bypasses SoftDeletes).
        // We query withTrashed because the regular query excludes already-soft-
        // deleted rows — but since we never soft-delete guest codes elsewhere,
        // there won't typically be any; still, use withTrashed for correctness.
        $expiredGuestQrIds = QrCode::withTrashed()
            ->whereIn('user_id', $guestUserIds)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', $now)
            ->pluck('id');

        if ($expiredGuestQrIds->isNotEmpty()) {
            // Delete routes first (no SoftDeletes on that model).
            DB::table('qr_code_routes')
                ->whereIn('qr_code_id', $expiredGuestQrIds)
                ->delete();

            QrCode::withTrashed()
                ->whereIn('id', $expiredGuestQrIds)
                ->forceDelete();
        }

        // Clean up the now-orphaned guest users that have no QR codes left.
        // Use raw DB to avoid SoftDeletes filtering on qr_codes.
        User::where('email', 'like', '%@anonymous.qrm.sg')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('qr_codes')
                    ->whereColumn('qr_codes.user_id', 'users.id');
            })
            ->delete();

        return $expiredGuestQrIds->count();
    }
}
