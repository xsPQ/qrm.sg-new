<?php

declare(strict_types=1);

namespace Tests\Unit\Billing;

use App\Domain\Billing\Plan;
use App\Domain\Billing\PlanNotConfiguredException;
use Tests\TestCase;

class PlanCheckoutTargetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Simulate a fully-configured deployment (price IDs present).
        config([
            'billing.plans.pro.price_id' => 'price_pro_test',
            'billing.plans.business.price_id' => 'price_business_test',
        ]);
    }

    public function test_pro_and_business_are_checkout_targets(): void
    {
        $this->assertTrue(Plan::isCheckoutTarget('pro'));
        $this->assertTrue(Plan::isCheckoutTarget('business'));
        $this->assertTrue(Plan::isCheckoutTarget('Pro')); // case-insensitive
    }

    public function test_free_is_never_a_checkout_target(): void
    {
        $this->assertFalse(Plan::isCheckoutTarget('free'));
    }

    public function test_unknown_plan_is_not_a_checkout_target(): void
    {
        $this->assertFalse(Plan::isCheckoutTarget('enterprise'));
    }

    public function test_price_id_resolves_for_configured_plans(): void
    {
        $this->assertSame('price_pro_test', Plan::priceIdFor('pro'));
        $this->assertSame('price_business_test', Plan::priceIdFor('business'));
    }

    public function test_price_id_rejects_free_as_target(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Plan::priceIdFor('free');
    }

    public function test_price_id_rejects_unknown_plan(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Plan::priceIdFor('enterprise');
    }

    public function test_price_id_throws_when_plan_is_valid_but_not_configured(): void
    {
        config(['billing.plans.business.price_id' => null]);

        $this->expectException(PlanNotConfiguredException::class);
        Plan::priceIdFor('business');
    }

    public function test_price_id_throws_when_price_id_is_empty_string(): void
    {
        config(['billing.plans.pro.price_id' => '']);

        $this->expectException(PlanNotConfiguredException::class);
        Plan::priceIdFor('pro');
    }

    public function test_checkout_targets_match_plan_enum_values(): void
    {
        $this->assertSame(['pro', 'business'], Plan::CHECKOUT_TARGETS);
    }
}
