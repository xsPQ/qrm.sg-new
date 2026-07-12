<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Domain\QrTypes\ContactQr;
use App\Domain\QrTypes\CryptoQr;
use App\Domain\QrTypes\EventQr;
use App\Domain\QrTypes\MessageQr;
use App\Domain\QrTypes\RedirectQr;
use App\Domain\QrTypes\SocialQr;
use App\Domain\QrTypes\UrlQr;
use App\Domain\QrTypes\WifiQr;
use App\Enums\QrCodeType;
use App\Http\Middleware\AnonymousFairUse;
use App\Models\QrCode;
use App\Models\QrCodeRoute;
use App\Services\QrPreviewService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.creator-guest')]
class AnonymousCreator extends Component
{
    public string $title = '';

    public string $type = 'url';

    /**
     * @var array<string,mixed>
     */
    public array $content = [];

    public ?string $createdCode = null;

    public ?string $createdUrl = null;

    public function mount(?string $type = null): void
    {
        if ($type !== null && QrCodeType::tryFrom($type) !== null) {
            $this->type = $type;
        }

        $this->content = $this->defaultContent($this->type);
    }

    public function updatedType(string $value): void
    {
        if (QrCodeType::tryFrom($value) === null) {
            return;
        }

        $this->type = $value;
        $this->content = $this->defaultContent($value);
        $this->clearOutcome();
    }

    /**
     * @return array<string,mixed>
     */
    protected function rules(): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
        ];

        foreach ($this->typeContentRules() as $key => $rule) {
            $rules["content.{$key}"] = $rule;
        }

        return $rules;
    }

    public function create(): void
    {
        $this->clearOutcome();

        $blocked = app(AnonymousFairUse::class)->check(request());
        if ($blocked !== null) {
            throw new HttpResponseException($blocked);
        }

        $validated = $this->validate();

        DB::transaction(function () use ($validated): void {
            $guestUser = \App\Models\User::create([
                'name' => 'Guest',
                'email' => 'guest_' . Str::random(12) . '@anonymous.qrm.sg',
                'password' => bcrypt(Str::random(32)),
                'email_verified_at' => now(),
            ]);

            $qrCode = QrCode::create([
                'user_id' => $guestUser->id,
                'title' => $validated['title'],
                'type' => $this->type,
                'content' => $this->cleanContent($validated['content']),
                'status' => 'active',
                'expires_at' => now()->addMinutes(15),
            ]);

            $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';
            $code = Str::upper(Str::random(6));

            QrCodeRoute::create([
                'qr_code_id' => $qrCode->id,
                'host' => $host,
                'code' => $code,
            ]);

            $this->createdCode = $code;
            $this->createdUrl = rtrim((string) config('app.url'), '/') . '/' . $code;
        });
    }

    /**
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
        return app(QrPreviewService::class)->payloadFor($this->type, $this->content);
    }

    public function previewDataUri(): ?string
    {
        return app(QrPreviewService::class)->dataUri($this->previewPayload(), 240);
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

        $stripped = [];
        foreach ($rules as $key => $rule) {
            $stripped[str_replace('content.', '', $key)] = $rule;
        }

        return $stripped;
    }

    /**
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

        if (QrCodeType::tryFrom($type) === QrCodeType::Event) {
            $defaults['timezone'] = config('app.timezone', 'UTC');
        }

        return $defaults;
    }

    /**
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

    protected function clearOutcome(): void
    {
        $this->createdCode = null;
        $this->createdUrl = null;
    }

    public function render()
    {
        return view('livewire.anonymous-creator');
    }
}
