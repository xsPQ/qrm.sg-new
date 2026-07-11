<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Domain\Entitlement\EntitlementGate;
use App\Domain\Entitlement\EntitlementSnapshot;
use App\Domain\QrTypes\ContactQr;
use App\Domain\QrTypes\CryptoQr;
use App\Domain\QrTypes\EventQr;
use App\Domain\QrTypes\MessageQr;
use App\Domain\QrTypes\RedirectQr;
use App\Domain\QrTypes\SocialQr;
use App\Domain\QrTypes\UrlQr;
use App\Domain\QrTypes\WifiQr;
use App\Enums\QrCodeType;
use App\Exceptions\FeatureNotEntitledException;
use App\Exceptions\FreeTierLimitExceededException;
use App\Exceptions\SlugCollisionException;
use App\Services\QrCodeRouteService;
use App\Services\QrCodeService;
use App\Services\QrPreviewService;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * Creator-UI (P2-T04): type selection, type-specific dynamic forms, live
 * alias/code availability check, live QR preview and creation through the same
 * {@see QrCodeService} pipeline that backs POST /api/qr-codes (P1-T10).
 *
 * Feature-gate (P2-T07): custom alias and password protection are Pro/Business
 * only (§2.1). The service enforces the real gates on submit; here the
 * per-plan flags are exposed so the UI can pre-disable locked fields and the
 * server-side rejection surfaces as a clear upgrade hint.
 */
class QrCreator extends Component
{
    public string $title = '';

    public string $type = 'url';

    /** @var array<string,mixed> */
    public array $content = [];

    public ?string $alias = null;

    public ?string $password = null;

    public bool $burn = false;

    public ?int $maxScans = null;

    public bool $showAdvanced = false;

    // Visual customization (FEAT-04).
    /** @var array<string,mixed> */
    public array $style = [];

    public bool $showStylePanel = false;

    // Live alias-check state.
    public ?string $aliasStatus = null; // null|available|taken|reserved|invalid
    public ?string $aliasMessage = null;

    // Free-tier status (UI-only hint).
    public array $freeTier = [];

    // Feature-gate flags for the current plan (P2-T07).
    public array $features = [];

    // Outcome state.
    public ?array $created = null;

    public ?string $successMessage = null;

    public ?string $upgradeMessage = null;

    public function boot(): void {}

    public function mount(
        QrCodeService $qrCodeService,
        ?string $type = null,
    ): void {
        if ($type !== null && QrCodeType::tryFrom($type) !== null) {
            $this->type = $type;
        }

        $this->content = $this->defaultContent($this->type);
        $this->style = $this->defaultStyle();

        $this->freeTier = $qrCodeService->freeTierStatus(auth()->user());
        $this->features = $qrCodeService->featureStatus(auth()->user());
    }

    /**
     * Re-seed content defaults whenever the type changes so stale fields from
     * a previous type never leak into the new form.
     */
    public function updatedType(string $value): void
    {
        $this->content = $this->defaultContent($value);
        $this->resetAliasCheck();
        $this->clearOutcome();
    }

    /**
     * Live alias availability check, triggered on a debounced alias change.
     * Uses the route service (P1-T11) so availability, collisions, reserved
     * system paths and format rules are all surfaced as clear-text feedback
     * before submit.
     */
    public function updatedAlias(?string $value): void
    {
        $this->checkAlias($value);
    }

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

        if ($routeService->isAliasAvailable($value, $host)) {
            $this->aliasStatus = 'available';
            $this->aliasMessage = __(':value is available.', ['value' => $value]);
        } else {
            $this->aliasStatus = 'taken';
            $this->aliasMessage = __(':value is already taken.', ['value' => $value]);
        }
    }

    /**
     * Rules reuse the per-type domain rules (P1-T08/T09) so the Creator form
     * validates exactly like the API request (P1-T10).
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

    public function submit(QrCodeService $qrCodeService): void
    {
        $this->clearOutcome();

        // Re-check the limit up-front so the UI can render the upgrade prompt
        // deterministically, mirroring the API's 402 path.
        try {
            $qrCodeService->enforceFreeTierLimit(auth()->user());
        } catch (FreeTierLimitExceededException $e) {
            $this->handleFreeTierExceeded($e, $qrCodeService);

            return;
        }

        try {
            $validated = $this->validate();
        } catch (ValidationException $e) {
            // Let Livewire render validation errors on the form.
            throw $e;
        }

        $payload = [
            'title' => $validated['title'],
            'type' => $this->type,
            'content' => $this->cleanContent($validated['content']),
            'burn' => $validated['burn'] ?? false,
            'max_scans' => $validated['maxScans'] ?? null,
            'settings' => ['style' => $this->cleanStyle($this->style)],
        ];

        if (!empty($validated['alias'])) {
            $payload['alias'] = $validated['alias'];
        }

        if (!empty($validated['password'])) {
            $payload['password'] = $validated['password'];
        }

        try {
            $qrCode = $qrCodeService->create(auth()->user(), $payload);
        } catch (FeatureNotEntitledException $e) {
            // Free user used a Pro/Business-only feature (§2.1).
            $this->upgradeMessage = $e->getMessage();
            $this->addError($e->feature, $e->getMessage());

            return;
        } catch (FreeTierLimitExceededException $e) {
            $this->handleFreeTierExceeded($e, $qrCodeService);

            return;
        } catch (SlugCollisionException $e) {
            $this->addError('alias', __('This alias is already in use.'));
            $this->aliasStatus = 'taken';
            $this->aliasMessage = __('This alias is already in use.');

            return;
        }

        $route = $qrCode->route ?? null;
        $slug = $route?->alias ?? $route?->code ?? null;
        $preview = app(QrPreviewService::class);

        $this->created = [
            'id' => $qrCode->id,
            'title' => $qrCode->title,
            'code' => $route?->code,
            'alias' => $route?->alias,
            'url' => $slug ? $preview->resolveUrl($slug) : null,
        ];
        $this->successMessage = 'QR code created.';
        $this->freeTier = $qrCodeService->freeTierStatus(auth()->user());

        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->title = '';
        $this->content = $this->defaultContent($this->type);
        $this->alias = null;
        $this->password = null;
        $this->burn = false;
        $this->maxScans = null;
        $this->showAdvanced = false;
        $this->style = $this->defaultStyle();
        $this->showStylePanel = false;
        $this->resetAliasCheck();
    }

    /**
     * The 8 in-scope creator types (Pflichtenheft §3.1).
     *
     * @return array<int,array{value:string,label:string,description:string,icon:string}>
     */
    public function typeOptions(): array
    {
        return [
            ['value' => 'url', 'label' => 'URL', 'description' => 'Open a link', 'icon' => 'link'],
            ['value' => 'message', 'label' => 'Message', 'description' => 'Show text', 'icon' => 'chat'],
            ['value' => 'redirect', 'label' => 'Redirect', 'description' => 'HTTP redirect', 'icon' => 'arrow'],
            ['value' => 'social', 'label' => 'Social', 'description' => 'Profile link', 'icon' => 'share'],
            ['value' => 'wifi', 'label' => 'WiFi', 'description' => 'Join network', 'icon' => 'wifi'],
            ['value' => 'crypto', 'label' => 'Crypto', 'description' => 'Pay address', 'icon' => 'coin'],
            ['value' => 'event', 'label' => 'Event', 'description' => 'Calendar entry', 'icon' => 'calendar'],
            ['value' => 'vcard', 'label' => 'Contact', 'description' => 'vCard', 'icon' => 'user'],
        ];
    }

    /**
     * Field descriptors driving the dynamic form per type. Each entry maps to
     * a content key and an input type understood by the Blade view.
     *
     * @return array<int,array<string,mixed>>
     */
    public function fields(string $type = null): array
    {
        $type ??= $this->type;

        return match (QrCodeType::tryFrom($type)) {
            QrCodeType::Message => [
                ['key' => 'message', 'label' => 'Message', 'input' => 'textarea', 'required' => true, 'maxlength' => 2000, 'placeholder' => 'What should people see when they scan?'],
            ],
            QrCodeType::Url => [
                ['key' => 'url', 'label' => 'Destination URL', 'input' => 'url', 'required' => true, 'maxlength' => 2048, 'placeholder' => 'https://example.com'],
            ],
            QrCodeType::Redirect => [
                ['key' => 'target_url', 'label' => 'Target URL', 'input' => 'url', 'required' => true, 'maxlength' => 2048, 'placeholder' => 'https://example.com/landing'],
                ['key' => 'redirect_code', 'label' => 'Redirect code', 'input' => 'select', 'required' => true, 'options' => ['301' => '301 — Permanent', '302' => '302 — Found', '307' => '307 — Temporary (method preserved)', '308' => '308 — Permanent (method preserved)']],
            ],
            QrCodeType::Social => [
                ['key' => 'platform', 'label' => 'Platform', 'input' => 'select', 'required' => true, 'options' => ['linkedin' => 'LinkedIn', 'twitter' => 'Twitter / X', 'x' => 'X', 'instagram' => 'Instagram', 'facebook' => 'Facebook', 'github' => 'GitHub', 'youtube' => 'YouTube', 'tiktok' => 'TikTok', 'website' => 'Website']],
                ['key' => 'username', 'label' => 'Username / handle', 'input' => 'text', 'required' => true, 'maxlength' => 255, 'placeholder' => '@handle'],
            ],
            QrCodeType::Wifi => [
                ['key' => 'ssid', 'label' => 'Network name (SSID)', 'input' => 'text', 'required' => true, 'maxlength' => 32],
                ['key' => 'encryption', 'label' => 'Encryption', 'input' => 'select', 'required' => true, 'options' => ['WPA' => 'WPA/WPA2', 'WPA2' => 'WPA2', 'WEP' => 'WEP', 'none' => 'None']],
                ['key' => 'password', 'label' => 'Password', 'input' => 'text', 'required' => false],
                ['key' => 'hidden', 'label' => 'Hidden network', 'input' => 'checkbox', 'required' => false],
            ],
            QrCodeType::Crypto => [
                ['key' => 'currency', 'label' => 'Currency', 'input' => 'select', 'required' => true, 'options' => ['BTC' => 'Bitcoin', 'ETH' => 'Ethereum', 'SOL' => 'Solana', 'USDT' => 'Tether (USDT)', 'USDC' => 'USD Coin']],
                ['key' => 'address', 'label' => 'Wallet address', 'input' => 'text', 'required' => true, 'placeholder' => 'Wallet address'],
                ['key' => 'amount', 'label' => 'Amount (optional)', 'input' => 'number', 'required' => false, 'placeholder' => '0.0'],
                ['key' => 'label', 'label' => 'Label (optional)', 'input' => 'text', 'required' => false, 'maxlength' => 255],
            ],
            QrCodeType::Event => [
                ['key' => 'title', 'label' => 'Event title', 'input' => 'text', 'required' => true, 'maxlength' => 255],
                ['key' => 'start', 'label' => 'Starts', 'input' => 'datetime-local', 'required' => true],
                ['key' => 'end', 'label' => 'Ends (optional)', 'input' => 'datetime-local', 'required' => false],
                ['key' => 'location', 'label' => 'Location (optional)', 'input' => 'text', 'required' => false, 'maxlength' => 255],
                ['key' => 'description', 'label' => 'Description (optional)', 'input' => 'textarea', 'required' => false, 'maxlength' => 2000],
            ],
            QrCodeType::Vcard => [
                ['key' => 'first_name', 'label' => 'First name', 'input' => 'text', 'required' => true, 'maxlength' => 100],
                ['key' => 'last_name', 'label' => 'Last name', 'input' => 'text', 'required' => true, 'maxlength' => 100],
                ['key' => 'organization', 'label' => 'Organization', 'input' => 'text', 'required' => false, 'maxlength' => 255],
                ['key' => 'title', 'label' => 'Job title', 'input' => 'text', 'required' => false, 'maxlength' => 255],
                ['key' => 'email', 'label' => 'Email', 'input' => 'email', 'required' => false, 'maxlength' => 255],
                ['key' => 'phone_mobile', 'label' => 'Mobile', 'input' => 'text', 'required' => false, 'maxlength' => 30],
                ['key' => 'phone_work', 'label' => 'Work phone', 'input' => 'text', 'required' => false, 'maxlength' => 30],
                ['key' => 'website', 'label' => 'Website', 'input' => 'url', 'required' => false, 'maxlength' => 2048],
                ['key' => 'address_street', 'label' => 'Street', 'input' => 'text', 'required' => false, 'maxlength' => 255],
                ['key' => 'address_city', 'label' => 'City', 'input' => 'text', 'required' => false, 'maxlength' => 100],
                ['key' => 'address_zip', 'label' => 'ZIP', 'input' => 'text', 'required' => false, 'maxlength' => 20],
                ['key' => 'address_country', 'label' => 'Country', 'input' => 'text', 'required' => false, 'maxlength' => 100],
            ],
            default => [],
        };
    }

    public function previewPayload(): string
    {
        return app(QrPreviewService::class)->payloadFor($this->type, $this->content, $this->alias);
    }

    public function previewDataUri(): ?string
    {
        $preview = app(QrPreviewService::class);

        $snapshot = EntitlementSnapshot::forPlan(
            $this->features['plan'] ?? 'free',
        );

        return $preview->dataUri(
            $this->previewPayload(),
            240,
            $this->style,
            $snapshot,
        );
    }

    public function getLimitReachedProperty(): bool
    {
        return (bool) ($this->freeTier['limit_reached'] ?? false);
    }

    /**
     * Whether the current plan allows custom aliases (P2-T07). The UI uses this
     * to disable the alias field and show an upgrade hint; the service still
     * enforces the real gate on submit.
     */
    public function getCanUseCustomAliasProperty(): bool
    {
        return (bool) ($this->features['can_use_custom_alias'] ?? true);
    }

    /**
     * Whether the current plan allows password protection (P2-T07).
     */
    public function getCanUsePasswordProtectionProperty(): bool
    {
        return (bool) ($this->features['can_use_password_protection'] ?? true);
    }

    public function render()
    {
        return view('livewire.qr-creator');
    }

    /**
     * @return array<string,array<int,string>>
     */
    protected function typeContentRules(): array
    {
        $rules = match (QrCodeType::tryFrom($this->type)) {
            QrCodeType::Message => MessageQr::rules(),
            QrCodeType::Url => UrlQr::rules(),
            QrCodeType::Redirect => RedirectQr::rules(),
            QrCodeType::Social => SocialQr::rules(),
            QrCodeType::Wifi => WifiQr::rules(),
            QrCodeType::Crypto => CryptoQr::rules(),
            QrCodeType::Event => EventQr::rules(),
            QrCodeType::Vcard => ContactQr::rules(),
            default => [],
        };

        // Domain rules are keyed as "content.<key>"; strip that prefix so they
        // can be re-keyed under the component's content.* properties.
        $stripped = [];
        foreach ($rules as $key => $rule) {
            $stripped[str_replace('content.', '', $key)] = $rule;
        }

        return $stripped;
    }

    /**
     * Seed default content for a type so the form starts clean and re-seeds on
     * type switch.
     *
     * @return array<string,mixed>
     */
    protected function defaultContent(string $type): array
    {
        $defaults = [];
        foreach ($this->fields($type) as $field) {
            $key = $field['key'];
            $defaults[$key] = match ($field['input']) {
                'checkbox' => false,
                'select' => array_key_first($field['options'] ?? []) ?? null,
                'number' => null,
                default => '',
            };
        }

        // Event timezone defaults to the app timezone (UTC fallback).
        if (QrCodeType::tryFrom($type) === QrCodeType::Event) {
            $defaults['timezone'] = config('app.timezone', 'UTC');
        }

        return $defaults;
    }

    /**
     * Drop empty-string optional fields so stored content stays clean.
     *
     * @param  array<string,mixed>  $content
     * @return array<string,mixed>
     */
    protected function cleanContent(array $content): array
    {
        $clean = [];
        foreach ($content as $key => $value) {
            if ($value === '' || $value === null) {
                continue;
            }
            $clean[$key] = $value;
        }

        return $clean;
    }

    protected function resetAliasCheck(): void
    {
        $this->aliasStatus = null;
        $this->aliasMessage = null;
    }

    protected function clearOutcome(): void
    {
        $this->created = null;
        $this->successMessage = null;
        $this->upgradeMessage = null;
    }

    /**
     * Default visual style (FEAT-04): black-on-white, square dots, medium EC.
     *
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
     * Drop empty/null style fields so stored settings stay clean (FEAT-04).
     *
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

    protected function handleFreeTierExceeded(FreeTierLimitExceededException $e, QrCodeService $qrCodeService): void
    {
        $this->freeTier = $qrCodeService->freeTierStatus(auth()->user());
        $this->upgradeMessage = $e->getMessage()
            . " ({$e->activeCount}/{$e->limit} active codes.)";
    }
}
