<?php

namespace App\Models;

use App\Domain\Entitlement\EntitlementSnapshot;
use App\Enums\QrStatus;
use App\Events\QrCodeExpiringSoon;
use App\Exceptions\EntitlementSnapshotImmutableException;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class QrCode extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'type',
        'content',
        'settings',
        'status',
        'scan_count',
        'max_scans',
        'burn',
        'expires_at',
        'password_hash',
        // 'entitlement_snapshot' is intentionally NOT fillable (DEV-592). It is
        // an immutable per-code snapshot set exactly once at creation
        // (Pflichtenheft §3.5.3, §6.2) and must never be mass-assigned from
        // request input. QrCodeService sets it via direct attribute write, and
        // both the model `saving` guard and a DB trigger keep it immutable.
        'next_cleanup_at',
    ];

    // DEV-613 (F3): the password hash must never leak via model serialization
    // (API resources, JSON responses, logs).
    protected $hidden = [
        'password_hash',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'settings' => 'array',
            'entitlement_snapshot' => 'array',
            'burn' => 'boolean',
            'scan_count' => 'integer',
            'max_scans' => 'integer',
            'expires_at' => 'datetime',
            'next_cleanup_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * S-Tier invariant (Pflichtenheft §3.5.3, §6.2): the entitlement snapshot
     * is set exactly once at QR-code creation and must never be mutated
     * afterwards — not by edits, and not by a billing webhook reacting to a
     * plan downgrade (Bestandsschutz / grandfathering).
     *
     * Once a non-null snapshot is persisted, any attempt to store a different
     * value is rejected. Re-persisting the structurally identical value is
     * allowed, so normal saves that do not touch the snapshot are unaffected.
     */
    protected static function booted(): void
    {
        static::saving(function (QrCode $qrCode) {
            if (! $qrCode->exists || ! $qrCode->isDirty('entitlement_snapshot')) {
                return;
            }

            $original = $qrCode->getOriginal('entitlement_snapshot');

            // First-time assignment on an already-persisted row is allowed.
            if ($original === null || $original === '') {
                return;
            }

            if (self::snapshotEquals($original, $qrCode->entitlement_snapshot)) {
                return;
            }

            throw new EntitlementSnapshotImmutableException($qrCode->id);
        });

        // Resolver-cache invalidation (Pflichtenheft §3.2 #7, P3-T05): every
        // persisted mutation (edit, password change, deactivation/reactivation
        // via setStatus, burn/max_scans set through the service) and every
        // soft-delete flushes any cached resolver entry for this code so no
        // stale response can be served after the change. Expiry-by-date is
        // additionally covered by the cache TTL being bounded by expires_at,
        // and burn/max_scans completion during a live resolve invalidates
        // explicitly in QrCodeResolver (raw UPDATE, no model event).
        $invalidate = static function (QrCode $qrCode): void {
            app(\App\Services\ResolverCache::class)->forgetFor($qrCode);
        };

        static::saved($invalidate);
        static::deleted($invalidate);
    }

    /**
     * Structural equality for the raw snapshot values, tolerant of the array
     * cast vs. the raw JSON string returned by {@see getOriginal}.
     */
    private static function snapshotEquals(mixed $a, mixed $b): bool
    {
        return self::normalizeSnapshot($a) === self::normalizeSnapshot($b);
    }

    private static function normalizeSnapshot(mixed $value): ?string
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : null;
        }

        if (! is_array($value)) {
            return null;
        }

        self::ksortRecursive($value);

        return (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /** @param array<string, mixed> $array */
    private static function ksortRecursive(array &$array): void
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                self::ksortRecursive($value);
            }
        }
        unset($value);

        ksort($array);
    }

    /**
     * Typed read accessor (Expose) for consumers: Creator, Edit, Resolver,
     * Download and the feature gates (P2-T07). Returns the immutable snapshot
     * value object reconstructed from the stored JSON. Pure data access — no
     * gating logic lives here.
     */
    public function entitlementSnapshot(): EntitlementSnapshot
    {
        return EntitlementSnapshot::fromArray($this->entitlement_snapshot);
    }

    public function route(): HasOne
    {
        return $this->hasOne(QrCodeRoute::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(QrCodeVariant::class)->orderBy('sort_order');
    }

    /**
     * Whether this QR code has A/B test variants configured (FEAT-06).
     * Reads from the loaded relationship collection to avoid N+1 queries.
     */
    public function hasVariants(): bool
    {
        $variants = $this->relationLoaded('variants')
            ? $this->variants
            : $this->variants()->get();

        return $variants->isNotEmpty();
    }

    /**
     * The A/B testing strategy for this QR code (FEAT-06).
     * Stored in settings.ab_testing.strategy: 'random' (default) or 'device'.
     */
    public function variantStrategy(): string
    {
        return $this->settings['ab_testing']['strategy'] ?? 'random';
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(QrCodeRevision::class)->orderByDesc('version');
    }

    public function scans(): HasMany
    {
        return $this->hasMany(Scan::class);
    }

    public function statsHourly()
    {
        return $this->hasMany(ScanStatsHourly::class);
    }

    public function statsDaily()
    {
        return $this->hasMany(ScanStatsDaily::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && !$this->isExpired()
            && !$this->isBurned()
            && !$this->hasReachedMaxScans();
    }

    /**
     * Transition the QR code's status and record an audit entry (DEV-163).
     *
     * Used by the Filament admin panel to deactivate / reactivate codes.
     * Deactivating never deletes user data (Bestandsschutz); it only flips the
     * status so the resolver stops serving the code. The acting admin is
     * resolved from the authenticated user, falling back to the code owner so
     * the audit entry is always attributable.
     */
    public function setStatus(QrStatus $status, string $action, ?User $admin = null): void
    {
        $before = ['status' => $this->status];
        $this->status = $status->value;
        $this->save();

        $resolvedAdmin = $admin ?? auth()->user();

        AdminActionLog::record(
            admin: ($resolvedAdmin instanceof User) ? $resolvedAdmin : $this->user,
            action: $action,
            target: $this,
            before: $before,
            after: ['status' => $this->status],
        );
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isBurned(): bool
    {
        return $this->burn && $this->scan_count > 0;
    }

    public function hasReachedMaxScans(): bool
    {
        return $this->max_scans !== null && $this->scan_count >= $this->max_scans;
    }

    /**
     * Whether this code is within the pre-expiry warning window
     * (Pflichtenheft §2.4: 3 Tage vor Ablauf → Ablauf-Prompt/Badge).
     *
     * True only for codes that have a future expires_at falling within the
     * configured warning window. Already-expired codes are not "expiring soon".
     */
    public function isExpiringSoon(?Carbon $now = null): bool
    {
        if ($this->expires_at === null || $this->isExpired()) {
            return false;
        }

        $now ??= Carbon::now();
        $warningDays = (int) config('qr.free.expiry_warning_days', 3);

        return $this->expires_at->lessThanOrEqualTo($now->copy()->addDays($warningDays));
    }

    /**
     * Whole days remaining until expiry (rounded down). Null when the code has
     * no expiry. Zero or negative once expires_at has passed.
     */
    public function expiresInDays(?Carbon $now = null): ?int
    {
        if ($this->expires_at === null) {
            return null;
        }

        $now ??= Carbon::now();

        return (int) floor($now->diffInSeconds($this->expires_at, absolute: false) / 86400);
    }

    /**
     * Scope that selects codes that are "active" for Free-Tier limit counting
     * (Pflichtenheft §2.4): status active, not yet expired by date, not burned
     * and not maxed out. Caller adds user/tier filters as needed.
     *
     * @param  Builder<QrCode>  $query
     */
    public function scopeActiveForLimit(Builder $query, ?Carbon $now = null): void
    {
        $now ??= Carbon::now();

        $query
            ->where('status', 'active')
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', $now);
            })
            ->where(function (Builder $q) {
                // Not burned: burn is false, or a burn code never consumed yet.
                $q->where('burn', false)->orWhere('scan_count', 0);
            })
            ->where(function (Builder $q) {
                // Not maxed out: no limit, or limit not yet reached.
                $q->whereNull('max_scans')->orWhereColumn('scan_count', '<', 'max_scans');
            });
    }

    /**
     * Mark all active QR codes whose expires_at has passed as expired.
     *
     * Intended for scheduled cleanup tasks. Free-tier codes get an immutable
     * 30-day expiry at creation; this transitions those codes (and any other
     * expired active code) to the expired status so the resolver stops serving
     * them and they drop out of the active free-tier limit count.
     *
     * @return int Number of codes transitioned to expired.
     */
    public static function cleanupExpired(): int
    {
        return static::query()
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update([
                'status' => 'expired',
                'updated_at' => now(),
            ]);
    }

    /**
     * Dispatch the {@see QrCodeExpiringSoon} event for every active Free code
     * that has entered the pre-expiry warning window (Pflichtenheft §2.4: "3
     * Tage vor Ablauf → E-Mail mit Pro-Verweis").
     *
     * This is the extensible hook the expiry-warning email (P2-T12) subscribes
     * to via a listener. Intended to run from the scheduler (daily). Only Free
     * entitlement codes are dispatched; grandfathered Pro/Business codes are
     * excluded (Bestandsschutz, §3.5.3). Listeners own their own idempotency.
     *
     * @return int Number of events dispatched.
     */
    public static function dispatchExpiringSoonEvents(?Carbon $now = null): int
    {
        $now ??= Carbon::now();
        $warningDays = (int) config('qr.free.expiry_warning_days', 3);
        $windowEnd = $now->copy()->addDays($warningDays);

        $dispatched = 0;

        static::query()
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [$now, $windowEnd])
            ->chunkById(200, function ($codes) use (&$dispatched) {
                foreach ($codes as $qrCode) {
                    if (($qrCode->entitlement_snapshot['tier'] ?? 'free') !== 'free') {
                        continue;
                    }

                    event(new QrCodeExpiringSoon($qrCode));
                    $dispatched++;
                }
            });

        return $dispatched;
    }
}
