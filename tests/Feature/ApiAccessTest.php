<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_free_user_with_token_gets_403_on_qr_codes_index(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/qr-codes')
            ->assertStatus(403)
            ->assertJson([
                'message' => 'API access requires Pro with API add-on or Business plan.',
                'code' => 'API_ACCESS_DENIED',
                'upgrade_required' => true,
            ]);
    }

    public function test_business_user_with_token_gets_200_on_qr_codes_index(): void
    {
        $user = User::factory()->create(['plan' => 'business']);
        Sanctum::actingAs($user);

        $this->getJson('/api/qr-codes')
            ->assertOk();
    }

    public function test_pro_user_without_addon_gets_403(): void
    {
        $user = User::factory()->create(['plan' => 'pro']);
        Sanctum::actingAs($user);

        $this->getJson('/api/qr-codes')
            ->assertStatus(403)
            ->assertJson([
                'code' => 'API_ACCESS_DENIED',
                'upgrade_required' => true,
            ]);
    }

    public function test_pro_user_with_addon_flag_gets_200(): void
    {
        $user = User::factory()->create([
            'plan' => 'pro',
            'api_addon_enabled' => true,
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/qr-codes')
            ->assertOk();
    }
}
