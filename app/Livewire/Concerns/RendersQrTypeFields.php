<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Domain\QrTypes\ContactQr;
use App\Domain\QrTypes\CryptoQr;
use App\Domain\QrTypes\EventQr;
use App\Domain\QrTypes\MessageQr;
use App\Domain\QrTypes\RedirectQr;
use App\Domain\QrTypes\SocialQr;
use App\Domain\QrTypes\UrlQr;
use App\Domain\QrTypes\WifiQr;
use App\Enums\QrCodeType;

/**
 * Shared, stateless QR-type form logic for the Creator (P2-T04) and the Edit
 * form (P2-T03). Centralising the type-specific field descriptors, the per-type
 * validation rules (re-using the domain rules from P1-T08/T09), the default
 * content seeding and the empty-field cleaning keeps the two forms in lock-step
 * so an edited code validates exactly like a freshly created one (Pflichtenheft
 * §3.1 / §3.6.1).
 *
 * The consuming component must expose a public string `$type` property.
 */
trait RendersQrTypeFields
{
    /**
     * Field descriptors driving the dynamic form per type. Each entry maps to
     * a content key and an input type understood by the Blade view.
     *
     * @return array<int,array<string,mixed>>
     */
    public function fields(?string $type = null): array
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

    /**
     * Per-type validation rules, re-using the domain rule classes (P1-T08/T09)
     * so the form validates exactly like the API request (P1-T10).
     *
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
}
