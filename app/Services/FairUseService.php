<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Entitlement\EntitlementSnapshot;
use App\Models\QrCode;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

/**
 * FEAT-08: Fair-Use enforcement.
 *
 * Checks whether a user's plan allows creating new QR codes or whether
 * rate-limits have been exceeded. Limits are config-driven (qr.php → fair_use)
 * and can be overridden via env.
 */
class FairUseService
{
    /**
     * Get the configured limits for a given plan.
     */
    public function limitsForPlan(string $plan): array
    {
        $key = $plan === 'business' ? 'business' : ($plan === 'pro' ? 'pro' : 'free');

        return config("qr.fair_use.{$key}", []);
    }

    /**
     * Check if a user can create a new QR code. Returns true if allowed,
     * or false with an optional reason.
     */
    public function canCreateQrCode(User $user): array
    {
        $plan = $this->userPlan($user);
        $limits = $this->limitsForPlan($plan);
        $maxActive = $limits['max_active_qr_codes'] ?? config('qr.free.max_active_qr_codes', 10);

        $activeCount = QrCode::where('user_id', $user->id)
            ->activeForLimit()
            ->count();

        if ($activeCount >= $maxActive) {
            return [
                'allowed' => false,
                'reason' => 'max_active_reached',
                'message' => __('You have reached the maximum of :max active QR codes for your plan.', ['max' => $maxActive]),
                'limit' => $maxActive,
                'current' => $activeCount,
            ];
        }

        // Per-hour creation rate-limit.
        $maxPerHour = $limits['max_qr_per_hour'] ?? 5;
        $key = "qr-create:{$user->id}";
        $created = (int) cache()->get($key, 0);

        if ($created >= $maxPerHour) {
            return [
                'allowed' => false,
                'reason' => 'rate_limited',
                'message' => __('You are creating QR codes too fast. Please wait a moment and try again.'),
                'limit' => $maxPerHour,
                'current' => $created,
            ];
        }

        return ['allowed' => true];
    }

    /**
     * Record a QR code creation for rate-limiting purposes.
     */
    public function recordQrCreation(User $user): void
    {
        $key = "qr-create:{$user->id}";
        $current = (int) cache()->get($key, 0);
        cache()->put($key, $current + 1, now()->addHour());
    }

    /**
     * Check if a QR code has exceeded its daily scan fair-use limit.
     */
    public function checkScanLimit(QrCode $qrCode): bool
    {
        $plan = $qrCode->entitlementSnapshot()->plan();

        if ($plan === 'free') {
            return true; // Free has no scan limit
        }

        $limits = $this->limitsForPlan($plan);
        $maxPerDay = $limits['max_scans_per_day'] ?? null;

        if ($maxPerDay === null) {
            return true;
        }

        $todayScans = Scan::where('qr_code_id', $qrCode->id)
            ->whereDate('scanned_at', today())
            ->count();

        return $todayScans < $maxPerDay;
    }

    /**
     * Get max A/B variants for a plan.
     */
    public function maxAbVariants(string $plan): int
    {
        $limits = $this->limitsForPlan($plan);

        return $limits['max_ab_variants'] ?? 5;
    }

    /**
     * Determine the user's current plan from their Stripe subscription
     * or fallback to the 'plan' column.
     */
    private function userPlan(User $user): string
    {
        if ($user->subscribed('pro')) {
            return 'pro';
        }

        if ($user->subscribed('business')) {
            return 'business';
        }

        return $user->plan ?? 'free';
    }
}
