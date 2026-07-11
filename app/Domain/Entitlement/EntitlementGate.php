<?php

declare(strict_types=1);

namespace App\Domain\Entitlement;

use App\Exceptions\FeatureNotEntitledException;

/**
 * Central feature-gate for Pro/Business-only QR-code capabilities
 * (Pflichtenheft §2.1–§2.4, §3.5.3; P2-T07).
 *
 * The single source of truth for *whether* a capability is available is the
 * immutable {@see EntitlementSnapshot} attached to each QR code. At creation
 * time callers pass the user's *current* plan snapshot; on edit callers pass
 * the code's own snapshot (grandfathered — a downgrade never removes existing
 * rights, §3.5.3).
 *
 * The gate owns only the *checks* and the *display data*. It contains no
 * persistence, no mutation and no UI logic; wiring into Creator (P2-T04),
 * Edit (P2-T03) and Resolver/Download happens in the service/controller layer.
 *
 * Plan rules (Pflichtenheft §2.1–§2.3):
 *  - Free:  no custom alias, no password protection, qrm.sg branding, standard download.
 *  - Pro:   custom alias, password protection, no branding, high-resolution download.
 *  - Business: everything in Pro + custom domain + white-label.
 */
class EntitlementGate
{
    /**
     * Reject a custom-alias write for a snapshot that does not allow it
     * (Free, §2.1). Pro/Business snapshots pass through.
     *
     * @throws FeatureNotEntitledException
     */
    public function assertCustomAlias(EntitlementSnapshot $snapshot): void
    {
        if (! $snapshot->allowsCustomAlias()) {
            throw FeatureNotEntitledException::forFeature(
                FeatureNotEntitledException::FEATURE_CUSTOM_ALIAS,
                $snapshot,
            );
        }
    }

    /**
     * Reject a password-protection write for a snapshot that does not allow it
     * (Free, §2.1). Pro/Business snapshots pass through.
     *
     * @throws FeatureNotEntitledException
     */
    public function assertPasswordProtection(EntitlementSnapshot $snapshot): void
    {
        if (! $snapshot->allowsPasswordProtection()) {
            throw FeatureNotEntitledException::forFeature(
                FeatureNotEntitledException::FEATURE_PASSWORD_PROTECTION,
                $snapshot,
            );
        }
    }

    /**
     * Reject an A/B testing write for a snapshot that does not allow it
     * (Free, FEAT-06). Pro/Business snapshots pass through.
     *
     * @throws FeatureNotEntitledException
     */
    public function assertAbTesting(EntitlementSnapshot $snapshot): void
    {
        if ($snapshot->isFree()) {
            throw FeatureNotEntitledException::forFeature(
                FeatureNotEntitledException::FEATURE_AB_TESTING,
                $snapshot,
            );
        }
    }

    /**
     * Whether the qrm.sg branding must be rendered for this code's download
     * artifacts. Driven by the snapshot's branding field (§2.1/§2.2): the Free
     * snapshot carries `qrm.sg`; Pro/Business carry `none`.
     */
    public function requiresBranding(EntitlementSnapshot $snapshot): bool
    {
        return $snapshot->branding() !== 'none';
    }

    /**
     * Maximum download pixel size the code's plan permits (§2.1/§2.2).
     *
     * Free (`standard`) downloads are capped at a standard resolution;
     * Pro/Business (`high_resolution`) may use the full high-resolution size.
     */
    public function maxDownloadSize(EntitlementSnapshot $snapshot): int
    {
        return $snapshot->downloadProfile() === 'high_resolution'
            ? (int) config('qr.download.max_size_high_res', 2000)
            : (int) config('qr.download.max_size_standard', 512);
    }

    /**
     * Structured feature flags for the Creator/Edit UIs and the API, so the
     * frontend can deterministically render locked fields and upgrade hints
     * without re-implementing plan rules.
     *
     * @return array<string,mixed>
     */
    public function featureFlags(EntitlementSnapshot $snapshot): array
    {
        $locked = $snapshot->isFree();

        // Alias length tiers (FEAT-05)
        $aliasMinLength = match ($snapshot->plan()) {
            'free' => 8,      // Free: long aliases only (8–32 chars)
            'pro' => 4,       // Pro: short aliases (4–32 chars)
            'business' => 2,  // Business: very short (2–32 chars)
            default => 8,
        };

        return [
            'plan' => $snapshot->plan(),
            'can_use_custom_alias' => $snapshot->allowsCustomAlias(),
            'can_use_password_protection' => $snapshot->allowsPasswordProtection(),
            'branding' => $snapshot->branding(),
            'requires_branding' => $this->requiresBranding($snapshot),
            'download_profile' => $snapshot->downloadProfile(),
            'analytics' => $snapshot->analytics(),
            'is_free' => $locked,
            'alias_min_length' => $aliasMinLength,
            'alias_max_length' => 32,
            'can_use_premium_alias' => $snapshot->plan() === 'business', // ≤4 chars = premium

            // Visual customization (FEAT-04): colors and basic dot styles are
            // available on all plans; gradient, logo and premium error-
            // correction levels (Q, H) require Pro or above.
            'can_set_colors' => true,
            'can_set_dot_style' => true,
            'can_use_gradient' => $snapshot->isPaid(),
            'can_use_logo' => $snapshot->isPaid(),
            'can_use_premium_ec' => $snapshot->isPaid(),

            // A/B Testing (FEAT-06): Pro+ only
            'can_use_ab_testing' => $snapshot->isPaid(),

            'upgrade_hint' => $locked
                ? 'Upgrade to Pro or Business to unlock custom aliases, password protection, branding removal, gradients and logo embedding.'
                : null,
        ];
    }
}
