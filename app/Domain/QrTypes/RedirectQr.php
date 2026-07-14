<?php

declare(strict_types=1);

namespace App\Domain\QrTypes;

use InvalidArgumentException;

class RedirectQr
{
    private const VALID_REDIRECT_CODES = ['301', '302', '307', '308'];

    public function __construct(
        public readonly string $targetUrl,
        public readonly string $redirectCode,
    ) {}

    public static function fromArray(array $data): self
    {
        $errors = [];

        $targetUrl = $data['target_url'] ?? null;
        if ($targetUrl === null || $targetUrl === '') {
            $errors[] = 'target_url is required';
        } elseif (! is_string($targetUrl) || mb_strlen($targetUrl) > 2048) {
            $errors[] = 'target_url must be a string of max 2048 characters';
        } elseif (! filter_var($targetUrl, FILTER_VALIDATE_URL)) {
            $errors[] = 'target_url must be a valid URL';
        }

        $redirectCode = $data['redirect_code'] ?? '302';
        if (! in_array($redirectCode, self::VALID_REDIRECT_CODES, true)) {
            $errors[] = 'redirect_code must be one of: ' . implode(', ', self::VALID_REDIRECT_CODES);
        }

        if ($errors !== []) {
            throw new InvalidArgumentException('Invalid RedirectQr content: ' . implode('; ', $errors));
        }

        return new self(
            targetUrl: $targetUrl,
            redirectCode: $redirectCode,
        );
    }

    public function toArray(): array
    {
        return [
            'target_url' => $this->targetUrl,
            'redirect_code' => $this->redirectCode,
        ];
    }

    public static function rules(): array
    {
        return [
            'content.target_url' => ['required', 'url', 'max:2048'],
            'content.redirect_code' => ['required', 'in:' . implode(',', self::VALID_REDIRECT_CODES)],
        ];
    }
}
