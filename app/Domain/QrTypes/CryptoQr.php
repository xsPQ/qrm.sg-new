<?php

namespace App\Domain\QrTypes;

use InvalidArgumentException;

class CryptoQr
{
    private const VALID_CURRENCIES = ['BTC', 'ETH', 'SOL', 'USDT', 'USDC'];

    public function __construct(
        public readonly string $currency,
        public readonly string $address,
        public readonly ?float $amount,
        public readonly ?string $label,
    ) {}

    public static function fromArray(array $data): self
    {
        $errors = [];

        $currency = $data['currency'] ?? null;
        if ($currency === null || $currency === '') {
            $errors[] = 'currency is required';
        } elseif (!in_array($currency, self::VALID_CURRENCIES, true)) {
            $errors[] = 'currency must be one of: ' . implode(', ', self::VALID_CURRENCIES);
        }

        $address = $data['address'] ?? null;
        if ($address === null || $address === '') {
            $errors[] = 'address is required';
        } elseif (!is_string($address)) {
            $errors[] = 'address must be a string';
        }

        $amount = null;
        if (isset($data['amount']) && $data['amount'] !== null) {
            if (!is_numeric($data['amount']) || (float) $data['amount'] <= 0) {
                $errors[] = 'amount must be a positive number';
            } else {
                $amount = (float) $data['amount'];
            }
        }

        $label = $data['label'] ?? null;
        if ($label !== null && !is_string($label)) {
            $errors[] = 'label must be a string';
        }

        if ($errors !== []) {
            throw new InvalidArgumentException('Invalid CryptoQr content: ' . implode('; ', $errors));
        }

        return new self(
            currency: $currency,
            address: $address,
            amount: $amount,
            label: $label,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'currency' => $this->currency,
            'address' => $this->address,
            'amount' => $this->amount,
            'label' => $this->label,
        ], fn($v) => $v !== null);
    }

    public static function rules(): array
    {
        return [
            'content.currency' => ['required', 'string', 'in:' . implode(',', self::VALID_CURRENCIES)],
            'content.address' => ['required', 'string'],
            'content.amount' => ['nullable', 'numeric', 'min:0'],
            'content.label' => ['nullable', 'string', 'max:255'],
        ];
    }
}
