<?php

namespace App\Domain\QrTypes;

use InvalidArgumentException;

class ContactQr
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly ?string $organization,
        public readonly ?string $title,
        public readonly ?string $email,
        public readonly ?string $phoneMobile,
        public readonly ?string $phoneWork,
        public readonly ?string $website,
        public readonly ?string $addressStreet,
        public readonly ?string $addressCity,
        public readonly ?string $addressZip,
        public readonly ?string $addressCountry,
    ) {}

    public static function fromArray(array $data): self
    {
        $errors = [];

        $firstName = $data['first_name'] ?? null;
        if ($firstName === null || $firstName === '') {
            $errors[] = 'first_name is required';
        } elseif (!is_string($firstName) || mb_strlen($firstName) > 100) {
            $errors[] = 'first_name must be a string of max 100 characters';
        }

        $lastName = $data['last_name'] ?? null;
        if ($lastName === null || $lastName === '') {
            $errors[] = 'last_name is required';
        } elseif (!is_string($lastName) || mb_strlen($lastName) > 100) {
            $errors[] = 'last_name must be a string of max 100 characters';
        }

        $validators = [
            'organization' => fn($v) => $v !== null && !is_string($v),
            'title' => fn($v) => $v !== null && !is_string($v),
            'email' => fn($v) => $v !== null && (!is_string($v) || ($v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL))),
            'phone_mobile' => fn($v) => $v !== null && !is_string($v),
            'phone_work' => fn($v) => $v !== null && !is_string($v),
            'website' => fn($v) => $v !== null && (!is_string($v) || ($v !== '' && !filter_var($v, FILTER_VALIDATE_URL))),
            'address_street' => fn($v) => $v !== null && !is_string($v),
            'address_city' => fn($v) => $v !== null && !is_string($v),
            'address_zip' => fn($v) => $v !== null && !is_string($v),
            'address_country' => fn($v) => $v !== null && !is_string($v),
        ];

        foreach ($validators as $field => $isInvalid) {
            $value = $data[$field] ?? null;
            if ($isInvalid($value)) {
                $errors[] = "$field is invalid";
            }
        }

        if ($errors !== []) {
            throw new InvalidArgumentException('Invalid ContactQr content: ' . implode('; ', $errors));
        }

        return new self(
            firstName: $firstName,
            lastName: $lastName,
            organization: $data['organization'] ?? null,
            title: $data['title'] ?? null,
            email: $data['email'] ?? null,
            phoneMobile: $data['phone_mobile'] ?? null,
            phoneWork: $data['phone_work'] ?? null,
            website: $data['website'] ?? null,
            addressStreet: $data['address_street'] ?? null,
            addressCity: $data['address_city'] ?? null,
            addressZip: $data['address_zip'] ?? null,
            addressCountry: $data['address_country'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'organization' => $this->organization,
            'title' => $this->title,
            'email' => $this->email,
            'phone_mobile' => $this->phoneMobile,
            'phone_work' => $this->phoneWork,
            'website' => $this->website,
            'address_street' => $this->addressStreet,
            'address_city' => $this->addressCity,
            'address_zip' => $this->addressZip,
            'address_country' => $this->addressCountry,
        ], fn($v) => $v !== null);
    }

    public static function rules(): array
    {
        return [
            'content.first_name' => ['required', 'string', 'max:100'],
            'content.last_name' => ['required', 'string', 'max:100'],
            'content.organization' => ['nullable', 'string', 'max:255'],
            'content.title' => ['nullable', 'string', 'max:255'],
            'content.email' => ['nullable', 'email', 'max:255'],
            'content.phone_mobile' => ['nullable', 'string', 'max:30'],
            'content.phone_work' => ['nullable', 'string', 'max:30'],
            'content.website' => ['nullable', 'url', 'max:2048'],
            'content.address_street' => ['nullable', 'string', 'max:255'],
            'content.address_city' => ['nullable', 'string', 'max:100'],
            'content.address_zip' => ['nullable', 'string', 'max:20'],
            'content.address_country' => ['nullable', 'string', 'max:100'],
        ];
    }
}
