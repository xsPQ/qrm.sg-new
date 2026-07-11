<?php

namespace App\Domain\QrTypes;

use InvalidArgumentException;

class WifiQr
{
    public function __construct(
        public readonly string $ssid,
        public readonly string $encryption,
        public readonly ?string $password,
        public readonly bool $hidden,
    ) {}

    public static function fromArray(array $data): self
    {
        $errors = [];

        $ssid = $data['ssid'] ?? null;
        if ($ssid === null || $ssid === '') {
            $errors[] = 'ssid is required';
        } elseif (!is_string($ssid) || mb_strlen($ssid) > 32) {
            $errors[] = 'ssid must be a string of max 32 characters';
        }

        $encryption = $data['encryption'] ?? 'WPA';
        if (!in_array($encryption, ['WPA', 'WPA2', 'WEP', 'none'], true)) {
            $errors[] = 'encryption must be one of: WPA, WPA2, WEP, none';
        }

        $password = $data['password'] ?? null;
        if ($password !== null && (!is_string($password) || ($encryption !== 'none' && $password === ''))) {
            $errors[] = 'password must be a non-empty string when encryption is not none';
        }

        $hidden = (bool) ($data['hidden'] ?? false);

        if ($errors !== []) {
            throw new InvalidArgumentException('Invalid WifiQr content: ' . implode('; ', $errors));
        }

        return new self(
            ssid: $ssid,
            encryption: $encryption,
            password: $password,
            hidden: $hidden,
        );
    }

    public function toArray(): array
    {
        return [
            'ssid' => $this->ssid,
            'encryption' => $this->encryption,
            'password' => $this->password,
            'hidden' => $this->hidden,
        ];
    }

    public static function rules(): array
    {
        return [
            'content.ssid' => ['required', 'string', 'max:32'],
            'content.encryption' => ['required', 'string', 'in:WPA,WPA2,WEP,none'],
            'content.password' => ['nullable', 'string'],
            'content.hidden' => ['nullable', 'boolean'],
        ];
    }
}
