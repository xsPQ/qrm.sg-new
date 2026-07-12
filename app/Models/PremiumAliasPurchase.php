<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Premium alias purchase record.
 *
 * Tracks one-time payments for short aliases (≤4 characters).
 * After payment (status='paid'), the alias is permanently reserved
 * for the purchasing user and can be assigned to any of their QR codes.
 */
class PremiumAliasPurchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'alias',
        'stripe_checkout_session_id',
        'stripe_payment_intent_id',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Whether this purchase is paid and the alias is usable.
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Scope: only paid purchases.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Check if an alias has been purchased and paid for (by anyone).
     */
    public static function isAliasPurchased(string $alias): bool
    {
        return static::where('alias', strtolower($alias))
            ->where('status', 'paid')
            ->exists();
    }

    /**
     * Check if an alias has been purchased and paid for by a specific user.
     */
    public static function isAliasOwnedBy(string $alias, int $userId): bool
    {
        return static::where('alias', strtolower($alias))
            ->where('user_id', $userId)
            ->where('status', 'paid')
            ->exists();
    }
}
