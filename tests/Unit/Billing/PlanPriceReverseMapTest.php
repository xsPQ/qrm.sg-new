<?php

declare(strict_types=1);

namespace Tests\Unit\Billing;

use App\Domain\Billing\Plan;
use App\Enums\UserPlan;
use Tests\TestCase;

class PlanPriceReverseMapTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'billing.plans.pro.price_id' => 'price_pro_test',
            'billing.plans.business.price_id' => 'price_business_test',
        ]);
    }

    public function test_resolves_plan_from_a_known_price_id(): void
    {
        $this->assertSame(UserPlan::Pro, Plan::planForPrice('price_pro_test'));
        $this->assertSame(UserPlan::Business, Plan::planForPrice('price_business_test'));
    }

    public function test_returns_null_for_an_unknown_price_id(): void
    {
        $this->assertNull(Plan::planForPrice('price_unknown'));
        $this->assertNull(Plan::planForPrice(''));
    }

    public function test_returns_null_when_no_prices_are_configured(): void
    {
        config(['billing.plans' => []]);

        $this->assertNull(Plan::planForPrice('price_pro_test'));
    }

    public function test_price_id_for_and_plan_for_price_are_inverse(): void
    {
        $this->assertSame(UserPlan::Business, Plan::planForPrice(Plan::priceIdFor('business')));
    }
}
