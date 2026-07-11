<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Domain\Entitlement\EntitlementGate;
use App\Enums\QrCodeType;
use App\Exceptions\FeatureNotEntitledException;
use App\Exceptions\SlugCollisionException;
use App\Livewire\Concerns\RendersQrTypeFields;
use App\Models\QrCode;
use App\Services\QrCodeRouteService;
use App\Services\QrCodeService;
use Livewire\Component;

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

    // Live alias-check state (mirrors the Creator).
    public ?string $aliasStatus = null; // null|available|taken|reserved|invalid
    public ?string $aliasMessage = null;

    // Delete confirmation state.
    public bool $confirmingDelete = false;

    public ?string $successMessage = null;

    // Feature-gate flags for this code's own (grandfathered) snapshot (P2-T07).
    public array $features = [];

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

        // Derive the feature flags from the code's own immutable snapshot so the
        // UI gates alias/password correctly even after a plan downgrade.
        $this->features = app(EntitlementGate::class)->featureFlags($qrCode->entitlementSnapshot());
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
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'alias' => ['nullable', 'string', 'min:4', 'max:32', 'regex:/^[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?$/'],
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
}
