<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Mail\ResetPasswordMail;
use App\Mail\VerifyEmailMail;
use App\Models\Team;
use App\Models\TeamMember;
use Illuminate\Support\Facades\Mail;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'email', 'password', 'stripe_id', 'pm_type', 'pm_last_four', 'trial_ends_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, FilamentUser
{
    use Billable;

    use HasApiTokens;
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;
    use HasRoles;

    /**
     * Gate Filament panel access to users holding the `admin` role
     * (DEV-171 / DEV-169 auth foundation). Bound to the default `web` guard
     * via Spatie permission. P2-T11 (DEV-163) will flesh out finer-grained
     * permissions on top of this baseline.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'trial_ends_at' => 'datetime',
        ];
    }

    public function ownedTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'owner_id');
    }

    public function teamMemberships(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_members')
            ->withPivot(['role', 'invited_by_user_id'])
            ->withTimestamps();
    }

    public function currentTeam(): ?Team
    {
        return $this->ownedTeams()->first() ?? $this->teams()->first();
    }

    /**
     * Send the email verification notification (P2-T12 / Pflichtenheft §3.4.1).
     *
     * Routed through the queued, localisable {@see VerifyEmailMail} Mailable
     * instead of the framework's default notification. Delivery runs over the
     * queue (P1-T06).
     */
    public function sendEmailVerificationNotification(): void
    {
        Mail::to($this)->send(new VerifyEmailMail($this));
    }

    /**
     * Send the password reset notification (P2-T12 / Pflichtenheft §3.4.1).
     *
     * Routed through the queued, localisable {@see ResetPasswordMail} Mailable
     * instead of the framework's default notification. The reset token is only
     * embedded in the reset link, never printed in clear text.
     */
    public function sendPasswordResetNotification($token): void
    {
        Mail::to($this)->send(new ResetPasswordMail($this, $token));
    }
}
