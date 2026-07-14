<?php

declare(strict_types=1);

namespace App\Domain\QrTypes;

use InvalidArgumentException;

class MessageQr
{
    public function __construct(
        public readonly string $message,
    ) {}

    public static function fromArray(array $data): self
    {
        $errors = [];

        $message = $data['message'] ?? null;
        if ($message === null || $message === '') {
            $errors[] = 'message is required';
        } elseif (! is_string($message) || mb_strlen($message) > 2000) {
            $errors[] = 'message must be a string of max 2000 characters';
        }

        if ($errors !== []) {
            throw new InvalidArgumentException('Invalid MessageQr content: ' . implode('; ', $errors));
        }

        return new self(message: $message);
    }

    public function toArray(): array
    {
        return [
            'message' => $this->message,
        ];
    }

    public static function rules(): array
    {
        return [
            'content.message' => ['required', 'string', 'max:2000'],
        ];
    }
}
