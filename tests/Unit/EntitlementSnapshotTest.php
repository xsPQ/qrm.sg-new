<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Entitlement\EntitlementSnapshot;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * EntitlementSnapshot value object (Pflichtenheft §3.5.3, §6.2): the canonical
 * per-plan resource rights captured once at QR-code creation, plus serialization
 * round-trip and legacy-shape reconstruction.
 */
class EntitlementSnapshotTest extends TestCase
{
    public function test_free_plan_snapshot_matches_spec(): void
    {
        $snapshot = EntitlementSnapshot::forPlan(EntitlementSnapshot::PLAN_FREE);

        $this->assertSame('free', $snapshot->plan());
        $this->assertSame('free', $snapshot->tier()); // legacy alias
        $this->assertTrue($snapshot->isFree());
        $this->assertFalse($snapshot->isPaid());
        $this->assertSame('30_days', $snapshot->validity());
        $this->assertFalse($snapshot->allowsCustomAlias());
        $this->assertFalse($snapshot->allowsPasswordProtection());
        $this->assertSame('qrm.sg', $snapshot->branding());
        $this->assertSame('standard', $snapshot->downloadProfile());
        $this->assertSame('basic', $snapshot->analytics());
        $this->assertFalse($snapshot->allowsCustomDomain());
        $this->assertFalse($snapshot->allowsWhiteLabel());
        $this->assertSame(1, $snapshot->version());
    }

    public function test_pro_plan_snapshot_matches_spec(): void
    {
        $snapshot = EntitlementSnapshot::forPlan(EntitlementSnapshot::PLAN_PRO);

        $this->assertSame('pro', $snapshot->plan());
        $this->assertTrue($snapshot->isPaid());
        $this->assertSame('unlimited', $snapshot->validity());
        $this->assertTrue($snapshot->allowsCustomAlias());
        $this->assertTrue($snapshot->allowsPasswordProtection());
        $this->assertSame('none', $snapshot->branding());
        $this->assertSame('high_resolution', $snapshot->downloadProfile());
        $this->assertSame('full', $snapshot->analytics());
        $this->assertFalse($snapshot->allowsCustomDomain());
        $this->assertFalse($snapshot->allowsWhiteLabel());
    }

    public function test_business_plan_enables_custom_domain_and_white_label(): void
    {
        $snapshot = EntitlementSnapshot::forPlan(EntitlementSnapshot::PLAN_BUSINESS);

        $this->assertSame('business', $snapshot->plan());
        $this->assertSame('unlimited', $snapshot->validity());
        $this->assertTrue($snapshot->allowsCustomAlias());
        $this->assertTrue($snapshot->allowsPasswordProtection());
        $this->assertSame('high_resolution', $snapshot->downloadProfile());
        // Business-only rights (§6.2).
        $this->assertTrue($snapshot->allowsCustomDomain());
        $this->assertTrue($snapshot->allowsWhiteLabel());
    }

    public function test_invalid_plan_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        EntitlementSnapshot::forPlan('enterprise');
    }

    public function test_to_array_emits_canonical_shape_with_tier_alias(): void
    {
        $payload = EntitlementSnapshot::forPlan(
            EntitlementSnapshot::PLAN_PRO,
            audit: ['subscription_id' => 7, 'source' => 'stripe'],
        )->toArray();

        $this->assertSame(1, $payload['version']);
        $this->assertSame('pro', $payload['plan']);
        // Backward-compat alias for existing P2-T06 consumers.
        $this->assertSame('pro', $payload['tier']);
        $this->assertSame('unlimited', $payload['validity']);
        $this->assertTrue($payload['custom_alias']);
        $this->assertTrue($payload['password_protection']);
        $this->assertSame('none', $payload['branding']);
        $this->assertSame('high_resolution', $payload['download_profile']);
        $this->assertSame('full', $payload['analytics']);
        $this->assertFalse($payload['custom_domain']);
        $this->assertFalse($payload['white_label']);
        $this->assertSame(7, $payload['subscription_id']);
        $this->assertSame('stripe', $payload['source']);
    }

    public function test_round_trip_to_array_then_from_array_is_equal(): void
    {
        $original = EntitlementSnapshot::forPlan(EntitlementSnapshot::PLAN_BUSINESS);

        $reconstructed = EntitlementSnapshot::fromArray($original->toArray());

        $this->assertTrue($original->equals($reconstructed));
        $this->assertSame('business', $reconstructed->plan());
        $this->assertTrue($reconstructed->allowsCustomDomain());
    }

    public function test_from_array_reconstructs_legacy_minimal_tier_snapshot(): void
    {
        // Legacy stored shape from earlier P2 iterations: only `tier`.
        $snapshot = EntitlementSnapshot::fromArray(['tier' => 'pro', 'source' => 'stripe']);

        $this->assertSame('pro', $snapshot->plan());
        $this->assertTrue($snapshot->allowsCustomAlias());
        $this->assertSame('high_resolution', $snapshot->downloadProfile());
        $this->assertSame('stripe', $snapshot->audit['source'] ?? null);
    }

    public function test_from_array_defaults_to_free_for_unknown_shape(): void
    {
        $snapshot = EntitlementSnapshot::fromArray(null);

        $this->assertSame('free', $snapshot->plan());
        $this->assertTrue($snapshot->isFree());
        $this->assertSame('qrm.sg', $snapshot->branding());
    }

    public function test_value_object_is_immutable_at_rest(): void
    {
        $snapshot = EntitlementSnapshot::forPlan(EntitlementSnapshot::PLAN_PRO);

        // All properties are readonly; the snapshot exposes no mutators.
        $this->assertSame('pro', $snapshot->plan());

        // A fresh snapshot for a different plan is a distinct instance, not a
        // mutation of the existing one.
        $downgraded = EntitlementSnapshot::forPlan(EntitlementSnapshot::PLAN_FREE);

        $this->assertSame('pro', $snapshot->plan());
        $this->assertSame('free', $downgraded->plan());
        $this->assertFalse($snapshot->equals($downgraded));
    }
}
