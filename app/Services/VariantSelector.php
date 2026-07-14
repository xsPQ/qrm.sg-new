<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\QrCode;
use App\Models\QrCodeVariant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * FEAT-06: A/B Testing variant selector.
 *
 * Selects a destination variant for a QR code scan based on the configured
 * distribution strategy:
 *
 *  - 'random' (default): weighted random selection. Each variant's `weight`
 *    controls its probability of being picked (weight=2 vs weight=1 → 2:1).
 *
 *  - 'device': matches the scanner's device type to the variant's
 *    `device_target`. If no variant targets the detected device, falls back
 *    to the first variant.
 *
 * After selection the variant's scan_count is atomically incremented and
 * its id is made available for the caller to stamp on the Scan row.
 *
 * Pro/Business only — the entitlement check happens in QrCodeService before
 * variants are created; the selector itself is strategy-pure and assumes
 * the caller has already gated entitlement.
 */
class VariantSelector
{
    public const STRATEGY_RANDOM = 'random';
    public const STRATEGY_DEVICE = 'device';

    public const VALID_STRATEGIES = [
        self::STRATEGY_RANDOM,
        self::STRATEGY_DEVICE,
    ];

    public const VALID_DEVICE_TARGETS = ['mobile', 'desktop', 'tablet'];

    /**
     * Select a variant for this QR code scan, increment its scan_count, and
     * return it. Returns null when the QR code has no variants.
     *
     * @return QrCodeVariant|null The selected variant, or null if none exist.
     */
    public function selectAndIncrement(QrCode $qrCode, Request $request): ?QrCodeVariant
    {
        $variants = $qrCode->relationLoaded('variants')
            ? $qrCode->variants
            : $qrCode->variants()->get();

        if ($variants->isEmpty()) {
            return null;
        }

        $variant = $this->selectByStrategy($variants, $qrCode->variantStrategy(), $request);

        // Atomic increment for concurrency safety (consistent with the
        // resolver's atomic scan consume pattern).
        DB::table('qr_code_variants')
            ->where('id', $variant->id)
            ->increment('scan_count');

        // Keep the in-memory model in sync so callers read the fresh count.
        $variant->scan_count++;

        return $variant;
    }

    /**
     * Pure selection logic: pick a variant by strategy without side effects.
     * Public so it can be unit-tested in isolation.
     *
     * @param  Collection<int, QrCodeVariant>  $variants
     */
    public function selectByStrategy(Collection $variants, string $strategy, Request $request): QrCodeVariant
    {
        if ($strategy === self::STRATEGY_DEVICE) {
            return $this->selectByDevice($variants, $request);
        }

        // Default: weighted random
        return $this->selectWeightedRandom($variants);
    }

    /**
     * Weighted random selection.
     *
     * Builds a cumulative weight distribution and picks the variant at the
     * randomly chosen position. If all weights are zero, falls back to the
     * first variant.
     *
     * @param  Collection<int, QrCodeVariant>  $variants
     */
    public function selectWeightedRandom(Collection $variants): QrCodeVariant
    {
        if ($variants->count() === 1) {
            return $variants->first();
        }

        $totalWeight = $variants->sum(fn (QrCodeVariant $v) => max(0, $v->weight));

        if ($totalWeight <= 0) {
            return $variants->first();
        }

        $pick = mt_rand(1, (int) $totalWeight);
        $cumulative = 0;

        foreach ($variants as $variant) {
            $cumulative += max(0, $variant->weight);

            if ($pick <= $cumulative) {
                return $variant;
            }
        }

        // Fallback (should not be reached due to integer math above).
        return $variants->last();
    }

    /**
     * Device-based selection: find the variant whose device_target matches
     * the scanner's detected device type. Falls back to the first variant
     * (or the first variant without a device_target) if no match.
     *
     * @param  Collection<int, QrCodeVariant>  $variants
     */
    public function selectByDevice(Collection $variants, Request $request): QrCodeVariant
    {
        $deviceType = ScanAttributes::detectDeviceType(
            (string) $request->header('User-Agent', ''),
        );

        if ($deviceType !== null) {
            $match = $variants->first(
                fn (QrCodeVariant $v) => $v->device_target === $deviceType,
            );

            if ($match) {
                return $match;
            }
        }

        // Fallback: first variant with no device_target, then first overall.
        return $variants->first(
            fn (QrCodeVariant $v) => $v->device_target === null || $v->device_target === '',
        ) ?? $variants->first();
    }
}
