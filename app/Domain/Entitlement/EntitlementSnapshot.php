<?php

declare(strict_types=1);

namespace App\Domain\Entitlement;

use Illuminate\Contracts\Support\Arrayable;
use InvalidArgumentException;

/**
 * Immutable per-QR-code entitlement snapshot (Pflichtenheft §3.5.3, §6.2).
 *
 * Captures the resource-related plan rights of a QR code at the moment of its
 * creation: plan/tier, validity, custom-alias, password-protection, branding,
 * download profile, analytics scope, custom-domain and white-label. Once a
 * snapshot is attached to a QR code it MUST NOT change — a later plan downgrade
 * (Upgrade/Downgrade via the P2-T10 billing webhook) never overwrites the
 * existing snapshot (Bestandsschutz / grandfathering). This is the S-Tier
 * invariant enforced by {@see \App\Models\QrCode}.
 *
 * Snapshot version is 1 (Pflichtenheft §6.2 example). Recurring account-level
 * capabilities (API tokens, team seats, bulk import/export, support tier) are
 * intentionally NOT part of the resource snapshot.
 *
 * @implements Arrayable<array-key, mixed>
 */
final class EntitlementSnapshot implements Arrayable
{
    private const VERSION = 1;

    public const PLAN_FREE = 'free';
    public const PLAN_PRO = 'pro';
    public const PLAN_BUSINESS = 'business';

    public const PLANS = [self::PLAN_FREE, self::PLAN_PRO, self::PLAN_BUSINESS];

    /** @param array<string, mixed>|null $audit */
    private function __construct(
        public readonly string $plan,
        public readonly string $validity,
        public readonly bool $customAlias,
        public readonly bool $passwordProtection,
        public readonly string $branding,
        public readonly string $downloadProfile,
        public readonly string $analytics,
        public readonly bool $customDomain,
        public readonly bool $whiteLabel,
        public readonly ?array $audit = null,
    ) {
        if (! in_array($plan, self::PLANS, true)) {
            throw new InvalidArgumentException("Invalid entitlement plan: {$plan}");
        }
    }

    /**
     * Build the canonical snapshot for a plan at QR-code creation time.
     *
     * @param array<string, mixed>|null $audit Optional audit metadata
     *     (e.g. subscription_id, source) carried alongside the rights.
     */
    public static function forPlan(string $plan, ?array $audit = null): self
    {
        $rights = self::rightsForPlan($plan);

        return new self(
            plan: $plan,
            validity: $rights['validity'],
            customAlias: $rights['custom_alias'],
            passwordProtection: $rights['password_protection'],
            branding: $rights['branding'],
            downloadProfile: $rights['download_profile'],
            analytics: $rights['analytics'],
            customDomain: $rights['custom_domain'],
            whiteLabel: $rights['white_label'],
            audit: $audit,
        );
    }

    /**
     * Reconstruct a snapshot from its persisted JSON shape.
     *
     * Tolerant of both the canonical {@see self::PLAN_*} key and the legacy
     * `tier` alias used by earlier P2 iterations, and backfills any missing
     * resource right from the resolved plan so legacy minimal snapshots
     * (e.g. `['tier' => 'pro']`) reconstruct to full plan rights.
     *
     * @param array<string, mixed>|null $data
     */
    public static function fromArray(?array $data): self
    {
        $data ??= [];

        $plan = self::resolvePlan($data['plan'] ?? $data['tier'] ?? null);
        $rights = self::rightsForPlan($plan);

        $audit = null;
        if (array_key_exists('subscription_id', $data) || array_key_exists('source', $data)) {
            $audit = array_filter(
                [
                    'subscription_id' => $data['subscription_id'] ?? null,
                    'source' => $data['source'] ?? null,
                ],
                fn ($v) => $v !== null,
            );
            $audit = $audit !== [] ? $audit : null;
        }

        return new self(
            plan: $plan,
            validity: is_string($data['validity'] ?? null) ? $data['validity'] : $rights['validity'],
            customAlias: self::boolOrNull($data['custom_alias'] ?? null) ?? $rights['custom_alias'],
            passwordProtection: self::boolOrNull($data['password_protection'] ?? null) ?? $rights['password_protection'],
            branding: is_string($data['branding'] ?? null) ? $data['branding'] : $rights['branding'],
            downloadProfile: is_string($data['download_profile'] ?? null) ? $data['download_profile'] : $rights['download_profile'],
            analytics: is_string($data['analytics'] ?? null) ? $data['analytics'] : $rights['analytics'],
            customDomain: self::boolOrNull($data['custom_domain'] ?? null) ?? $rights['custom_domain'],
            whiteLabel: self::boolOrNull($data['white_label'] ?? null) ?? $rights['white_label'],
            audit: $audit,
        );
    }

    public function plan(): string
    {
        return $this->plan;
    }

    /**
     * Legacy alias for {@see plan()} — earlier P2 consumers read a `tier` key.
     * Kept so existing readers keep working while new code uses {@see plan()}.
     */
    public function tier(): string
    {
        return $this->plan;
    }

    public function isFree(): bool
    {
        return $this->plan === self::PLAN_FREE;
    }

    public function isPaid(): bool
    {
        return ! $this->isFree();
    }

    public function allowsCustomAlias(): bool
    {
        return $this->customAlias;
    }

    public function allowsPasswordProtection(): bool
    {
        return $this->passwordProtection;
    }

    public function allowsCustomDomain(): bool
    {
        return $this->customDomain;
    }

    public function allowsWhiteLabel(): bool
    {
        return $this->whiteLabel;
    }

    public function branding(): string
    {
        return $this->branding;
    }

    public function downloadProfile(): string
    {
        return $this->downloadProfile;
    }

    public function analytics(): string
    {
        return $this->analytics;
    }

    public function validity(): string
    {
        return $this->validity;
    }

    public function version(): int
    {
        return self::VERSION;
    }

    /**
     * Structurally equal — used by the immutability guard to distinguish a
     * genuine change from an identical re-persist.
     */
    public function equals(self $other): bool
    {
        return $this->toArray() === $other->toArray();
    }

    /**
     * Canonical persisted shape (Pflichtenheft §6.2).
     *
     * Emits the canonical `plan` key plus a `tier` alias so existing P2-T06
     * readers (`$snapshot['tier']`) keep working without modification, and the
     * optional audit metadata when present.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = [
            'version' => self::VERSION,
            'plan' => $this->plan,
            // Backward-compat alias for existing consumers (P2-T06).
            'tier' => $this->plan,
            'validity' => $this->validity,
            'custom_alias' => $this->customAlias,
            'password_protection' => $this->passwordProtection,
            'branding' => $this->branding,
            'download_profile' => $this->downloadProfile,
            'analytics' => $this->analytics,
            'custom_domain' => $this->customDomain,
            'white_label' => $this->whiteLabel,
        ];

        if ($this->audit !== null) {
            $payload['subscription_id'] = $this->audit['subscription_id'] ?? null;
            $payload['source'] = $this->audit['source'] ?? null;
        }

        return $payload;
    }

    private static function resolvePlan(mixed $plan): string
    {
        if (is_string($plan) && in_array(strtolower($plan), self::PLANS, true)) {
            return strtolower($plan);
        }

        return self::PLAN_FREE;
    }

    private static function boolOrNull(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private static function rightsForPlan(string $plan): array
    {
        return match ($plan) {
            self::PLAN_FREE => [
                'validity' => '30_days',
                'custom_alias' => false,
                'password_protection' => false,
                'branding' => 'qrm.sg',
                'download_profile' => 'standard',
                'analytics' => 'basic',
                'custom_domain' => false,
                'white_label' => false,
            ],
            self::PLAN_PRO => [
                'validity' => 'unlimited',
                'custom_alias' => true,
                'password_protection' => true,
                'branding' => 'none',
                'download_profile' => 'high_resolution',
                'analytics' => 'full',
                'custom_domain' => false,
                'white_label' => false,
            ],
            self::PLAN_BUSINESS => [
                'validity' => 'unlimited',
                'custom_alias' => true,
                'password_protection' => true,
                'branding' => 'none',
                'download_profile' => 'high_resolution',
                'analytics' => 'full',
                'custom_domain' => true,
                'white_label' => true,
            ],
            default => throw new InvalidArgumentException("Invalid entitlement plan: {$plan}"),
        };
    }
}
