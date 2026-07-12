<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\TeamManager;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TeamManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_user_can_create_team(): void
    {
        $user = User::factory()->create(['plan' => 'business']);

        Livewire::actingAs($user)
            ->test(TeamManager::class)
            ->set('teamName', 'Acme Marketing')
            ->call('createTeam')
            ->assertHasNoErrors()
            ->assertSee('Acme Marketing');

        $this->assertDatabaseHas('teams', [
            'owner_id' => $user->id,
            'name' => 'Acme Marketing',
        ]);
    }

    public function test_free_user_is_blocked_from_team_creation(): void
    {
        $user = User::factory()->create(['plan' => 'free']);

        Livewire::actingAs($user)
            ->test(TeamManager::class)
            ->set('teamName', 'Acme')
            ->call('createTeam')
            ->assertHasErrors(['teamName']);

        $this->assertDatabaseMissing('teams', ['name' => 'Acme']);
    }

    public function test_owner_can_invite_member_and_change_role(): void
    {
        $owner = User::factory()->create(['plan' => 'business']);
        $member = User::factory()->create(['email' => 'member@example.com']);

        $team = Team::create([
            'owner_id' => $owner->id,
            'name' => 'Acme',
            'slug' => 'acme-' . str()->random(6),
            'branding_name' => 'Acme',
            'white_label_enabled' => false,
        ]);

        Livewire::actingAs($owner)
            ->test(TeamManager::class)
            ->set('memberEmail', $member->email)
            ->set('memberRole', 'manager')
            ->call('inviteMember')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('team_members', [
            'team_id' => $team->id,
            'user_id' => $member->id,
            'role' => 'manager',
        ]);

        $membership = TeamMember::where('team_id', $team->id)->where('user_id', $member->id)->firstOrFail();

        Livewire::actingAs($owner)
            ->test(TeamManager::class)
            ->call('updateMemberRole', $membership->id, 'member')
            ->assertHasNoErrors();

        $this->assertSame('member', $membership->fresh()->role);
    }

    public function test_owner_can_toggle_white_label_settings(): void
    {
        $owner = User::factory()->create(['plan' => 'business']);
        $team = Team::create([
            'owner_id' => $owner->id,
            'name' => 'Acme',
            'slug' => 'acme-' . str()->random(6),
            'branding_name' => 'Acme',
            'white_label_enabled' => false,
        ]);

        Livewire::actingAs($owner)
            ->test(TeamManager::class)
            ->set('brandingName', 'Acme White Label')
            ->set('brandingLogoPath', 'https://example.com/logo.svg')
            ->set('whiteLabelEnabled', true)
            ->call('saveBranding')
            ->assertHasNoErrors();

        $this->assertSame('Acme White Label', $team->fresh()->branding_name);
        $this->assertTrue($team->fresh()->white_label_enabled);
    }
}
