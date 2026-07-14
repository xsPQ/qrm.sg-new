<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'branding_name',
        'branding_logo_path',
        'white_label_enabled',
    ];

    protected function casts(): array
    {
        return [
            'white_label_enabled' => 'boolean',
        ];
    }

    public static function createForOwner(User $user, string $name): self
    {
        return static::create([
            'owner_id' => $user->id,
            'name' => $name,
            'slug' => static::makeSlug($name),
            'branding_name' => $user->name . ' Team',
            'white_label_enabled' => false,
        ]);
    }

    public static function makeSlug(string $name): string
    {
        return Str::slug($name) . '-' . Str::lower(Str::random(6));
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function isOwner(User $user): bool
    {
        return (int) $this->owner_id === (int) $user->id;
    }

    public function roleFor(User $user): ?string
    {
        if ($this->isOwner($user)) {
            return 'owner';
        }

        return $this->members()->where('user_id', $user->id)->value('role');
    }

    public function canManage(User $user): bool
    {
        return in_array($this->roleFor($user), ['owner', 'manager'], true);
    }
}
