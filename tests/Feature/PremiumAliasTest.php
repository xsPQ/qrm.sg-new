<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PremiumAliasPurchase;
use App\Models\QrCode;
use App\Models\QrCodeRoute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PremiumAliasTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_alias_purchased_returns_false_for_nonexistent(): void
    {
        $this->assertFalse(PremiumAliasPurchase::isAliasPurchased('abc'));
    }

    public function test_is_alias_purchased_returns_true_for_paid(): void
    {
        $user = User::factory()->create();
        PremiumAliasPurchase::create([
            'user_id' => $user->id,
            'alias' => 'abc',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->assertTrue(PremiumAliasPurchase::isAliasPurchased('abc'));
        $this->assertTrue(PremiumAliasPurchase::isAliasPurchased('ABC'));
    }

    public function test_pending_purchase_does_not_count_as_purchased(): void
    {
        $user = User::factory()->create();
        PremiumAliasPurchase::create([
            'user_id' => $user->id,
            'alias' => 'xyz',
            'status' => 'pending',
        ]);

        $this->assertFalse(PremiumAliasPurchase::isAliasPurchased('xyz'));
    }

    public function test_is_alias_owned_by(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        PremiumAliasPurchase::create([
            'user_id' => $user1->id,
            'alias' => 'go',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->assertTrue(PremiumAliasPurchase::isAliasOwnedBy('go', $user1->id));
        $this->assertFalse(PremiumAliasPurchase::isAliasOwnedBy('go', $user2->id));
    }

    public function test_alias_is_case_insensitive(): void
    {
        $user = User::factory()->create();
        PremiumAliasPurchase::create([
            'user_id' => $user->id,
            'alias' => 'abc',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->assertTrue(PremiumAliasPurchase::isAliasPurchased('ABC'));
        $this->assertTrue(PremiumAliasPurchase::isAliasPurchased('Abc'));
        $this->assertTrue(PremiumAliasPurchase::isAliasOwnedBy('ABC', $user->id));
    }

    public function test_checkout_requires_authentication(): void
    {
        $response = $this->post('/billing/premium-alias/checkout', ['alias' => 'abc']);
        $response->assertRedirect(); // redirects to login
    }

    public function test_checkout_rejects_long_alias(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)
            ->post('/billing/premium-alias/checkout', ['alias' => 'toolong']);
        $response->assertSessionHasErrors('alias');
    }

    public function test_checkout_rejects_already_purchased(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        PremiumAliasPurchase::create([
            'user_id' => $user1->id,
            'alias' => 'go',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($user2)
            ->postJson('/billing/premium-alias/checkout', ['alias' => 'go']);
        $response->assertStatus(409);
    }

    public function test_checkout_rejects_already_owned(): void
    {
        $user = User::factory()->create();
        PremiumAliasPurchase::create([
            'user_id' => $user->id,
            'alias' => 'go',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->postJson('/billing/premium-alias/checkout', ['alias' => 'go']);
        $response->assertStatus(200);
        $response->assertJson(['status' => 'already_owned']);
    }

    public function test_premium_alias_config_has_correct_defaults(): void
    {
        $this->assertEquals(1.00, config('billing.premium_alias.cost'));
        $this->assertEquals(4, config('billing.premium_alias.max_length'));
        $this->assertEquals('eur', config('billing.premium_alias.currency'));
    }
}
