<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Domain\Entitlement\EntitlementGate;
use App\Enums\QrCodeType;
use App\Exceptions\FeatureNotEntitledException;
use App\Exceptions\SlugCollisionException;
use App\Livewire\Concerns\RendersQrTypeFields;
use App\Models\QrCode;
use App\Models\QrCodeVariant;
use App\Services\QrCodeRouteService;
use App\Services\QrCodeService;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * QR-Code edit form (Pflichtenheft §3.6.1 / §5.1, P2-T03).
 *
 * Type-specific editor for an existing QR code, reachable from the dashboard
 * list (P2-T01) and the detail page (P2-T02). It re-uses the shared
 * {@see RendersQrTypeFields} logic so an edited code validates exactly like a
 * freshly created one (Creator, P2-T04): same per-type fields, same per-type
 * domain rules (P1-T08/T09), same alias normalisation and namespace handling
 * (P1-T11). Writes go through the same {@see QrCodeService::update()} pipeline
 * that backs PATCH /api/qr-codes/{id} (P1-T10), so alias collisions surface as
 * a clear, field-level error.
 *
 * The QR type itself is intentionally fixed on edit: changing the type would
 * replace the entire content schema and is out of scope. Delete is gated by a
 * two-step confirmation and performs a soft-delete (QrCode uses SoftDeletes).
 *
 * Feature-gate (P2-T07): custom alias and password protection are checked
 * against the code's own (immutable/grandfathered) entitlement snapshot. A
 * Free-snapshot code can never gain these rights retroactively; a grandfathered
 * Pro/Business code keeps them after a downgrade (§3.5.3).
 */
class QrCodeEditor extends Component
{
    use WithFileUploads;
    use RendersQrTypeFields;

    public QrCode $qrCode;

    /** Required by {@see RendersQrTypeFields}; fixed to the code's type. */
    public string $type = 'url';

    public string $title = '';

    /** @var array<string,mixed> */
    public array $content = [];

    public ?string $alias = null;

    public ?string $password = null;

    public bool $removePassword = false;

    public bool $burn = false;

    public ?int $maxScans = null;

    public bool $showAdvanced = false;

    // Visual customization (FEAT-04).
    /** @var array<string,mixed> */
    public array $style = [];

    public bool $showStylePanel = false;

    // Style form properties (FEAT-04 Design Tab)
    public string $fgColor = '#000000';
    public string $bgColor = '#ffffff';
    public string $dotStyle = 'square';
    public string $errorCorrection = 'm';
    public bool $gradientEnabled = false;
    public string $gradientFrom = '#6366f1';
    public string $gradientTo = '#a855f7';
    public int $gradientAngle = 45;
    public int $qrMargin = 10;
    public $logoUpload = null;

    // Live alias-check state (mirrors the Creator).
    public ?string $aliasStatus = null; // null|available|taken|reserved|invalid
    public ?string $aliasMessage = null;

    // Delete confirmation state.
    public bool $confirmingDelete = false;

    public ?string $successMessage = null;

    // Feature-gate flags for this code's own (grandfathered) snapshot (P2-T07).
    public array $features = [];

    // FEAT-06: A/B testing variant editor state.
    /** @var array<int, array{label:string,url:string,weight:int,device_target:?string}> */
    public array $abVariants = [];

    public string $abStrategy = 'random';

    // Staging fields for adding a new variant.
    public string $newVariantLabel = '';
    public string $newVariantUrl = '';
    public int $newVariantWeight = 1;
    public ?string $newVariantDeviceTarget = null;

    public ?string $abSuccessMessage = null;

    public function mount(QrCode $qrCode): void
    {
        // Owner/admin gate (P1-T15). Non-owners receive 403 even before the
        // form renders, mirroring the detail page controller check.
        $this->authorize('update', $qrCode);

        $this->qrCode = $qrCode;
        $this->type = $qrCode->type;
        $this->title = (string) $qrCode->title;
        $this->content = $this->seedContent($qrCode);
        $this->alias = $qrCode->route?->alias;
        $this->burn = (bool) $qrCode->burn;
        $this->maxScans = $qrCode->max_scans !== null ? (int) $qrCode->max_scans : null;
        $this->style = $this->seedStyle($qrCode);

        // Derive the feature flags from the code's own immutable snapshot so the
        // UI gates alias/password correctly even after a plan downgrade.
        $this->features = app(EntitlementGate::class)->featureFlags($qrCode->entitlementSnapshot());

        // FEAT-06: Seed A/B testing state from persisted variants.
        $this->seedAbVariants($qrCode);
    }

    /**
     * Re-seed content defaults whenever the type would change. The type is
     * fixed on edit, but keeping this hook matches the Creator contract and
     * guards against accidental client-side type mutations.
     */
    public function updatedType(string $value): void
    {
        if (QrCodeType::tryFrom($value) !== null && $value !== $this->qrCode->type) {
            // Type changes are out of scope; restore the canonical type and
            // re-seed from the persisted code.
            $this->type = $this->qrCode->type;
            $this->content = $this->seedContent($this->qrCode);
        }
    }

    public function updatedAlias(?string $value): void
    {
        $this->checkAlias($value);
    }

    /**
     * Live alias availability check, triggered on a debounced alias change.
     * Re-uses the route service (P1-T11) and excludes the code's own route, so
     * keeping the current alias reports as "available".
     */
    public function checkAlias(?string $value = null, QrCodeRouteService $routeService = null): void
    {
        $value ??= $this->alias;
        $value = is_string($value) ? trim($value) : '';

        if ($value === '') {
            $this->resetAliasCheck();

            return;
        }

        $routeService ??= app(QrCodeRouteService::class);
        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';
        $excludeId = $this->qrCode->route?->id;
        $minLength = $this->aliasMinLength();

        // Plan-aware length check (M5-T05): catch too-short aliases before the
        // route service's hardcoded 4-char floor so Free/Pro users see a clear,
        // plan-specific upgrade hint instead of a generic format error.
        if (mb_strlen($value) < $minLength) {
            $this->aliasStatus = 'invalid';

            if ($minLength === 8) {
                $this->aliasMessage = __('Aliases must be at least :min characters on the Free plan. Upgrade to Pro for shorter aliases.', ['min' => $minLength]);
            } elseif ($minLength === 4) {
                $this->aliasMessage = __('Aliases must be at least :min characters. Upgrade to Business for 2–3 character aliases.', ['min' => $minLength]);
            } else {
                $this->aliasMessage = __('Aliases must be at least :min characters.', ['min' => $minLength]);
            }

            return;
        }

        try {
            $routeService->validateAlias($value, $host);
        } catch (\InvalidArgumentException $e) {
            if ($routeService->isReservedPath($value)) {
                $this->aliasStatus = 'reserved';
                $this->aliasMessage = __(':value is a reserved system path and cannot be used as an alias.', ['value' => $value]);
            } else {
                $this->aliasStatus = 'invalid';
                $this->aliasMessage = $e->getMessage();
            }

            return;
        }

        // Business premium-shortcode indicator (M5-T05): aliases ≤4 chars are
        // flagged as premium shortcodes — allowed, but flagged for the UI.
        if ($this->canUsePremiumAlias() && mb_strlen($value) <= 4) {
            $this->aliasStatus = $routeService->isAliasAvailable($value, $host, $excludeId)
                ? 'premium'
                : 'taken';
            $this->aliasMessage = $this->aliasStatus === 'premium'
                ? __(':value is available as a premium shortcode.', ['value' => $value])
                : __(':value is already taken.', ['value' => $value]);

            return;
        }

        if ($routeService->isAliasAvailable($value, $host, $excludeId)) {
            $this->aliasStatus = 'available';
            $this->aliasMessage = __(':value is available.', ['value' => $value]);
        } else {
            $this->aliasStatus = 'taken';
            $this->aliasMessage = __(':value is already taken.', ['value' => $value]);
        }
    }

    /**
     * Validation rules: the common fields plus the per-type domain rules
     * (P1-T08/T09), so the edit form validates exactly like the API request.
     *
     * @return array<string,mixed>
     */
    protected function rules(): array
    {
        $minLength = $this->aliasMinLength();

        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'alias' => ['nullable', 'string', 'min:' . $minLength, 'max:' . $this->aliasMaxLength(), 'regex:/^[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?$/'],
            'password' => ['nullable', 'string', 'min:4'],
            'maxScans' => ['nullable', 'integer', 'min:1'],
        ];

        foreach ($this->typeContentRules() as $key => $rule) {
            $rules["content.{$key}"] = $rule;
        }

        return $rules;
    }

    public function save(QrCodeService $qrCodeService): void
    {
        $this->clearOutcome();

        $validated = $this->validate();

        $payload = [
            'title' => $validated['title'],
            'content' => $this->cleanContent($validated['content']),
            'burn' => $validated['burn'] ?? $this->burn,
            'max_scans' => $validated['maxScans'] ?? null,
            'settings' => ['style' => $this->cleanStyle($this->style)],
            // Always send the alias key so the service can set, change or
            // clear it under the shared namespace (409-equivalent on collision).
            'alias' => ! empty($validated['alias']) ? $validated['alias'] : null,
        ];

        // Password is only touched when the user explicitly changes or removes
        // it; an empty field keeps the existing hash (QrCodeService::update).
        if ($this->removePassword) {
            $payload['password'] = null;
        } elseif (! empty($validated['password'])) {
            $payload['password'] = $validated['password'];
        }

        try {
            $this->qrCode = $qrCodeService->update($this->qrCode, $payload);
        } catch (FeatureNotEntitledException $e) {
            // Editing a Free-snapshot code: this capability is not unlocked.
            $this->addError($e->feature, $e->getMessage());

            return;
        } catch (SlugCollisionException $e) {
            $this->addError('alias', __('This alias is already in use.'));
            $this->aliasStatus = 'taken';
            $this->aliasMessage = __('This alias is already in use.');

            return;
        }

        $this->successMessage = __('QR code updated.');
        $this->alias = $this->qrCode->route?->alias;
        $this->password = null;
        $this->removePassword = false;
        $this->checkAlias($this->alias);
    }

    public function confirmDelete(): void
    {
        $this->authorize('delete', $this->qrCode);
        $this->confirmingDelete = true;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDelete = false;
    }

    public function delete(QrCodeService $qrCodeService): void
    {
        $this->authorize('delete', $this->qrCode);

        $title = $this->qrCode->title;
        $qrCodeService->delete($this->qrCode); // soft-delete (SoftDeletes)

        session()->flash('qr-code-deleted', $title);

        $this->redirect(route('dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.qr-code-editor');
    }

    /**
     * Whether this code's own (grandfathered) snapshot allows custom aliases
     * (P2-T07). The UI uses this to disable the alias field on Free codes.
     */
    public function getCanUseCustomAliasProperty(): bool
    {
        return (bool) ($this->features['can_use_custom_alias'] ?? true);
    }

    /**
     * Whether this code's snapshot allows password protection (P2-T07).
     */
    public function getCanUsePasswordProtectionProperty(): bool
    {
        return (bool) ($this->features['can_use_password_protection'] ?? true);
    }

    /**
     * Plan-aware alias minimum length (M5-T05): Free 8, Pro 4, Business 2.
     * Falls back to 4 (the old hardcoded floor) when feature flags are absent.
     */
    public function aliasMinLength(): int
    {
        return (int) ($this->features['alias_min_length'] ?? 4);
    }

    /**
     * Alias maximum length — constant 32 across all plans (M5-T05).
     */
    public function aliasMaxLength(): int
    {
        return (int) ($this->features['alias_max_length'] ?? 32);
    }

    /**
     * Whether this code's snapshot supports premium shortcodes (≤4 chars) (M5-T05).
     */
    public function canUsePremiumAlias(): bool
    {
        return (bool) ($this->features['can_use_premium_alias'] ?? false);
    }

    /**
     * The plan name from the code's snapshot, for Blade conditionals (M5-T05).
     */
    public function aliasPlan(): string
    {
        return (string) ($this->features['plan'] ?? 'free');
    }

    /**
     * Tier-specific alias hint text shown below the alias field (M5-T05).
     */
    public function aliasTierHint(): string
    {
        $plan = $this->aliasPlan();
        $min = $this->aliasMinLength();
        $max = $this->aliasMaxLength();

        return match ($plan) {
            'free' => __("Custom aliases must be :min–:max characters. Shorter aliases require Pro.", ['min' => $min, 'max' => $max]),
            'pro' => __("Custom aliases can be :min–:max characters.", ['min' => $min, 'max' => $max]),
            'business' => __("Premium aliases (2+ chars) available. Short codes (≤4) are premium.", ['min' => $min, 'max' => $max]),
            default => __("Custom aliases must be :min–:max characters.", ['min' => $min, 'max' => $max]),
        };
    }

    // ---------------------------------------------------------------
    // FEAT-04: Visual Design computed properties (mirrors Creator)
    // ---------------------------------------------------------------

    public function getCanUseGradientProperty(): bool
    {
        return ($this->features['plan'] ?? 'free') !== 'free';
    }

    public function getCanUseLogoProperty(): bool
    {
        return ($this->features['plan'] ?? 'free') !== 'free';
    }

    public function getCanUsePremiumEcProperty(): bool
    {
        return ($this->features['plan'] ?? 'free') !== 'free';
    }

    public function getDotStylesProperty(): array
    {
        return ['square' => 'Square', 'round' => 'Round', 'extra_round' => 'Extra Round'];
    }

    public function getEcLevelsProperty(): array
    {
        return ['l' => 'Low (7%)', 'm' => 'Medium (15%)'];
    }

    public function getPremiumEcLevelsProperty(): array
    {
        return ['q' => 'Quartile (25%)', 'h' => 'High (30%)'];
    }

    // ---------------------------------------------------------------
    // FEAT-06: A/B Testing variant management
    // ---------------------------------------------------------------

    /**
     * Whether this code's snapshot allows A/B testing (FEAT-06, Pro+ only).
     */
    public function getCanUseAbTestingProperty(): bool
    {
        return (bool) ($this->features['can_use_ab_testing'] ?? false);
    }

    /**
     * Whether the A/B testing section should be shown: only for url/redirect
     * types (variant redirect only makes sense for link-type codes).
     */
    public function getShowAbTestingSectionProperty(): bool
    {
        return in_array($this->qrCode->type, ['url', 'redirect'], true);
    }

    /**
     * Load existing variants into the editor state.
     */
    protected function seedAbVariants(QrCode $qrCode): void
    {
        $this->abVariants = $qrCode->variants()
            ->orderBy('sort_order')
            ->get()
            ->map(fn (QrCodeVariant $v) => [
                'label' => $v->label,
                'url' => $v->url,
                'weight' => $v->weight,
                'device_target' => $v->device_target,
            ])
            ->values()
            ->toArray();

        $this->abStrategy = $qrCode->variantStrategy();
    }

    /**
     * Add a new variant to the staging list (not yet persisted).
     */
    public function addVariant(): void
    {
        $this->validate([
            'newVariantLabel' => 'required|string|max:10',
            'newVariantUrl' => 'required|url|max:2048',
            'newVariantWeight' => 'required|integer|min:1|max:100',
            'newVariantDeviceTarget' => 'nullable|in:mobile,desktop,tablet',
        ], [
            'newVariantLabel.required' => __('A label is required.'),
            'newVariantUrl.required' => __('A destination URL is required.'),
            'newVariantUrl.url' => __('The destination must be a valid URL.'),
        ]);

        $this->abVariants[] = [
            'label' => strtoupper(trim($this->newVariantLabel)),
            'url' => trim($this->newVariantUrl),
            'weight' => $this->newVariantWeight,
            'device_target' => $this->newVariantDeviceTarget ?: null,
        ];

        // Auto-detect device strategy if a device target is set.
        if ($this->newVariantDeviceTarget) {
            $this->abStrategy = 'device';
        }

        $this->reset('newVariantLabel', 'newVariantUrl', 'newVariantWeight', 'newVariantDeviceTarget');
        $this->newVariantWeight = 1;
    }

    /**
     * Remove a variant from the staging list by index.
     */
    public function removeVariant(int $index): void
    {
        unset($this->abVariants[$index]);
        $this->abVariants = array_values($this->abVariants);
    }

    /**
     * Persist all staged variants via QrCodeService::syncVariants().
     */
    public function saveVariants(QrCodeService $qrCodeService): void
    {
        $this->abSuccessMessage = null;

        $this->validate([
            'abVariants.*.label' => 'required|string|max:10',
            'abVariants.*.url' => 'required|url|max:2048',
            'abVariants.*.weight' => 'required|integer|min:1|max:100',
            'abVariants.*.device_target' => 'nullable|in:mobile,desktop,tablet',
        ]);

        try {
            $variantsData = array_map(function (array $v): array {
                return [
                    'label' => $v['label'],
                    'url' => $v['url'],
                    'weight' => (int) $v['weight'],
                    'device_target' => $v['device_target'] ?? null,
                ];
            }, $this->abVariants);

            $qrCodeService->syncVariants($this->qrCode, $variantsData);

            // Reload the fresh model + relationship.
            $this->qrCode = $this->qrCode->fresh(['variants', 'route']);
            $this->seedAbVariants($this->qrCode);

            $this->abSuccessMessage = __('A/B test variants saved.');
        } catch (FeatureNotEntitledException $e) {
            $this->addError('ab_testing', $e->getMessage());
        }
    }

    /**
     * Remove all variants (disable A/B testing).
     */
    public function clearAllVariants(QrCodeService $qrCodeService): void
    {
        $qrCodeService->clearVariants($this->qrCode);

        $this->qrCode = $this->qrCode->fresh(['variants', 'route']);
        $this->abVariants = [];
        $this->abStrategy = 'random';
        $this->abSuccessMessage = __('A/B testing disabled.');
    }

    /**
     * Seed form content from the persisted code, overlaying stored values on
     * the type defaults so every field renders with a value and optional
     * fields that were previously cleaned out reappear empty.
     *
     * @return array<string,mixed>
     */
    protected function seedContent(QrCode $qrCode): array
    {
        $stored = is_array($qrCode->content) ? $qrCode->content : [];

        $seeded = $this->defaultContent($qrCode->type);

        foreach ($stored as $key => $value) {
            $seeded[$key] = $value;
        }

        return $seeded;
    }

    protected function resetAliasCheck(): void
    {
        $this->aliasStatus = null;
        $this->aliasMessage = null;
    }

    protected function clearOutcome(): void
    {
        $this->successMessage = null;
    }

    /**
     * Seed visual style from stored settings, falling back to defaults
     * (FEAT-04).
     *
     * @return array<string,mixed>
     */
    protected function seedStyle(QrCode $qrCode): array
    {
        $stored = is_array($qrCode->settings) ? ($qrCode->settings['style'] ?? []) : [];

        return array_merge($this->defaultStyle(), $stored);
    }

    /**
     * @return array<string,mixed>
     */
    protected function defaultStyle(): array
    {
        return [
            'fg_color' => '#000000',
            'bg_color' => '#ffffff',
            'dot_style' => 'square',
            'error_correction' => 'M',
            'margin' => 10,
        ];
    }

    /**
     * @param  array<string,mixed>  $style
     * @return array<string,mixed>
     */
    protected function cleanStyle(array $style): array
    {
        $clean = [];
        foreach ($style as $key => $value) {
            if ($value === '' || $value === null) {
                continue;
            }
            $clean[$key] = $value;
        }

        return $clean;
    }
}
