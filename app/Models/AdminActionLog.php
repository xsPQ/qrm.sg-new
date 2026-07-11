<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminActionLog extends Model
{
    protected $fillable = [
        'admin_user_id',
        'action',
        'target_type',
        'target_id',
        'before',
        'after',
    ];

    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
        ];
    }

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public static function record(
        User $admin,
        string $action,
        Model $target,
        ?array $before = null,
        ?array $after = null,
    ): self {
        return self::create([
            'admin_user_id' => $admin->id,
            'action' => $action,
            'target_type' => $target::class,
            'target_id' => $target->getKey(),
            'before' => $before,
            'after' => $after,
        ]);
    }
}
