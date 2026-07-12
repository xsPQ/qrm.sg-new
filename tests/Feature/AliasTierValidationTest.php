<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Entitlement\EntitlementGate;
use App\Domain\Entitlement\EntitlementSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Alias tier validation tests (FEAT-05).
 * Backend entitlement gating tests — Creator UI validation pending.
 */
class AliasTierValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_free_plan_alias_min_length_is_8(): void
    {
        $gate = app(EntitlementGate::class);
        $flags = $gate->featureFlags(EntitlementSnapshot::forPlan('free'));

        $this->assertSame(8, $flags['alias_min_length']);
    }

    public function test_pro_plan_alias_min_length_is_4(): void
    {
        $gate = app(EntitlementGate::class);
        $flags = $gate->featureFlags(EntitlementSnapshot::forPlan('pro'));

        $this->assertSame(4, $flags['alias_min_length']);
    }

    public function test_business_plan_alias_min_length_is_2(): void
    {
        $gate = app(EntitlementGate::class);
        $flags = $gate->featureFlags(EntitlementSnapshot::forPlan('business'));

        $this->assertSame(2, $flags['alias_min_length']);
        $this->assertTrue($flags['can_use_premium_alias']);
    }

    public function test_creator_alias_check_rejects_short_alias_for_free(): void
    {
        $user = \App\Models\User::factory()->create();

        \Livewire\Livewire::actingAs($user)
            ->test(\App\Livewire\QrCreator::class)
            ->set('alias', 'short')
            ->assertSet('aliasStatus', 'invalid');
    }

    public function test_creator_alias_check_accepts_long_alias_for_free(): void
    {
        $user = \App\Models\User::factory()->create();

        \Livewire\Livewire::actingAs($user)
            ->test(\App\Livewire\QrCreator::class)
            ->set('alias', 'my-summer-campaign')
            ->assertSet('aliasStatus', 'available');
    }
}
