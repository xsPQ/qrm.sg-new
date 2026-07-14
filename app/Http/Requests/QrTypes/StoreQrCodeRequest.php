<?php

namespace App\Http\Requests\QrTypes;

use App\Enums\QrCodeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class StoreQrCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $baseRules = [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:' . implode(',', QrCodeType::values())],
            'content' => ['required', 'array'],
            'alias' => ['nullable', 'string', 'min:3', 'max:32', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'password' => ['nullable', 'string', 'min:4'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'burn' => ['nullable', 'boolean'],
            'max_scans' => ['nullable', 'integer', 'min:1'],
            'settings' => ['nullable', 'array'],
        ];

        $typeRules = $this->typeContentRules();

        return $typeRules !== [] ? array_merge($baseRules, $typeRules) : $baseRules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $type = $this->input('type');
            $content = $this->input('content', []);

            if ($type === null || !is_array($content)) {
                return;
            }

            try {
                match ($type) {
                    QrCodeType::Wifi->value => \App\Domain\QrTypes\WifiQr::fromArray($content),
                    QrCodeType::Crypto->value => \App\Domain\QrTypes\CryptoQr::fromArray($content),
                    QrCodeType::Event->value => \App\Domain\QrTypes\EventQr::fromArray($content),
                    QrCodeType::Vcard->value => \App\Domain\QrTypes\ContactQr::fromArray($content),
                    default => null,
                };
            } catch (\InvalidArgumentException $e) {
                $validator->errors()->add('content', $e->getMessage());
            }
        });
    }

    private function typeContentRules(): array
    {
        $type = $this->input('type');

        return match ($type) {
            QrCodeType::Wifi->value => \App\Domain\QrTypes\WifiQr::rules(),
            QrCodeType::Crypto->value => \App\Domain\QrTypes\CryptoQr::rules(),
            QrCodeType::Event->value => \App\Domain\QrTypes\EventQr::rules(),
            QrCodeType::Vcard->value => \App\Domain\QrTypes\ContactQr::rules(),
            default => [],
        };
    }
}
