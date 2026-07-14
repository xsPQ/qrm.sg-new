<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class TeamManager extends Component
{
    public string $teamName = '';
    public string $memberEmail = '';
    public string $memberRole = 'member';
    public string $brandingName = '';
    public string $brandingLogoPath = '';
    public bool $whiteLabelEnabled = false;

    public ?string $message = null;

    protected function currentUser(): User
    {
        return auth()->user() ?? abort(403);
    }

    public function isBusiness(): bool
    {
        $user = $this->currentUser();

        return $user->plan === 'business' || $user->subscribed('business');
    }

    public function currentTeam(): ?Team
    {
        return $this->currentUser()->currentTeam();
    }

    public function mount(): void
    {
        if ($team = $this->currentTeam()) {
            $this->teamName = $team->name;
            $this->brandingName = (string) ($team->branding_name ?? $team->name);
            $this->brandingLogoPath = (string) ($team->branding_logo_path ?? '');
            $this->whiteLabelEnabled = (bool) $team->white_label_enabled;
        }
    }

    public function createTeam(): void
    {
        if (! $this->isBusiness()) {
            throw ValidationException::withMessages([
                'teamName' => __('Team management is available on the Business plan only.'),
            ]);
        }

        $validated = $this->validate([
            'teamName' => ['required', 'string', 'max:120'],
        ]);

        $user = $this->currentUser();

        if ($this->currentTeam()) {
            throw ValidationException::withMessages([
                'teamName' => __('This account already has a team.'),
            ]);
        }

        $team = Team::create([
            'owner_id' => $user->id,
            'name' => $validated['teamName'],
            'slug' => Team::makeSlug($validated['teamName']),
            'branding_name' => $validated['teamName'],
            'white_label_enabled' => false,
        ]);

        $this->message = __('Team created: :name', ['name' => $team->name]);
        $this->teamName = $team->name;
        $this->brandingName = $team->name;
    }

    public function inviteMember(): void
    {
        $team = $this->assertCanManageTeam();

        $validated = $this->validate([
            'memberEmail' => ['required', 'email'],
            'memberRole' => ['required', Rule::in(['manager', 'member'])],
        ]);

        $user = User::where('email', $validated['memberEmail'])->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'memberEmail' => __('No user with that email exists yet.'),
            ]);
        }

        if ($team->roleFor($user)) {
            throw ValidationException::withMessages([
                'memberEmail' => __('That user is already part of the team.'),
            ]);
        }

        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'invited_by_user_id' => $this->currentUser()->id,
            'role' => $validated['memberRole'],
        ]);

        $this->message = __('Member invited: :email', ['email' => $validated['memberEmail']]);
        $this->reset('memberEmail', 'memberRole');
        $this->memberRole = 'member';
    }

    public function updateMemberRole(int $memberId, string $role): void
    {
        $team = $this->assertCanManageTeam();

        if (! in_array($role, ['manager', 'member'], true)) {
            throw ValidationException::withMessages(['memberRole' => __('Invalid role.')]);
        }

        $member = TeamMember::where('team_id', $team->id)->findOrFail($memberId);
        $member->update(['role' => $role]);

        $this->message = __('Role updated.');
    }

    public function removeMember(int $memberId): void
    {
        $team = $this->assertCanManageTeam();
        $member = TeamMember::where('team_id', $team->id)->findOrFail($memberId);
        $member->delete();

        $this->message = __('Member removed.');
    }

    public function saveBranding(): void
    {
        $team = $this->assertCanManageTeam();

        $validated = $this->validate([
            'brandingName' => ['nullable', 'string', 'max:120'],
            'brandingLogoPath' => ['nullable', 'string', 'max:255'],
            'whiteLabelEnabled' => ['boolean'],
        ]);

        $team->update([
            'branding_name' => $validated['brandingName'] ?: $team->name,
            'branding_logo_path' => $validated['brandingLogoPath'] ?: null,
            'white_label_enabled' => (bool) $validated['whiteLabelEnabled'],
        ]);

        $this->message = __('White-label settings saved.');
    }

    protected function assertCanManageTeam(): Team
    {
        $team = $this->currentTeam();

        if (! $team) {
            throw ValidationException::withMessages([
                'teamName' => __('Create a team first.'),
            ]);
        }

        if (! $team->canManage($this->currentUser())) {
            abort(403);
        }

        return $team;
    }

    public function render()
    {
        $team = $this->currentTeam();
        $members = $team?->members()->with('user', 'invitedBy')->latest()->get() ?? collect();

        return view('livewire.team-manager', [
            'team' => $team,
            'members' => $members,
        ]);
    }
}
