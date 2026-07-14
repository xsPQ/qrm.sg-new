<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Entitlement\EntitlementSnapshot;
use App\Livewire\ApiTokenManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ApiTokenManagerTest extends TestCase
{
    use RefreshDatabase;

    private function grantBusinessPlan(User $user): void
    {
        $user->forceFill([
            'plan' => EntitlementSnapshot::forPlan('business')->plan(),
        ])->save();
    }

    public function test_business_user_can_create_token(): void
    {
        $user = User::factory()->create();
        $this->grantBusinessPlan($user);

        $component = Livewire::actingAs($user)
            ->test(ApiTokenManager::class)
            ->set('name', 'Zapier')
            ->set('abilities', ['qr:read', 'stats:read'])
            ->call('createToken')
            ->assertHasNoErrors();

        $this->assertNotEmpty($component->instance()->plainTextToken);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'Zapier',
        ]);
    }

    public function test_free_user_is_denied_token_creation(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ApiTokenManager::class)
            ->set('name', 'Zapier')
            ->set('abilities', ['qr:read'])
            ->call('createToken')
            ->assertHasErrors(['name']);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'Zapier',
        ]);
    }

    public function test_token_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $this->grantBusinessPlan($user);
        $token = $user->createToken('Dashboard', ['qr:read'])->accessToken;

        Livewire::actingAs($user)
            ->test(ApiTokenManager::class)
            ->call('deleteToken', $token->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->id,
        ]);
    }

    public function test_token_is_displayed_in_the_list(): void
    {
        $user = User::factory()->create();
        $this->grantBusinessPlan($user);
        $user->createToken('CLI', ['qr:read']);

        $this->actingAs($user)
            ->get('/account/api-tokens')
            ->assertOk()
            ->assertSee('API Tokens')
            ->assertSee('CLI');
    }
}
