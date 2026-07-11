<?php

declare(strict_types=1);

namespace App\Domain\QrTypes;

use InvalidArgumentException;

class UrlQr
{
    public function __construct(
        public readonly string $url,
    ) {}

    public static function fromArray(array $data): self
    {
        $errors = [];

        $url = $data['url'] ?? null;
        if ($url === null || $url === '') {
            $errors[] = 'url is required';
        } elseif (! is_string($url) || mb_strlen($url) > 2048) {
            $errors[] = 'url must be a string of max 2048 characters';
        } elseif (! filter_var($url, FILTER_VALIDATE_URL)) {
            $errors[] = 'url must be a valid URL';
        }

        if ($errors !== []) {
            throw new InvalidArgumentException('Invalid UrlQr content: ' . implode('; ', $errors));
        }

        return new self(url: $url);
    }

    public function toArray(): array
    {
        return [
            'url' => $this->url,
        ];
    }

    public static function rules(): array
    {
        return [
            'content.url' => ['required', 'url', 'max:2048'],
        ];
    }
}
