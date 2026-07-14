<?php

namespace App\Http\Requests\QrTypes;

use App\Enums\QrCodeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

class UpdateQrCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $baseRules = [
            'title' => ['sometimes', 'string', 'max:255'],
            'content' => ['sometimes', 'array'],
            'alias' => ['nullable', 'string', 'min:3', 'max:32', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'password' => ['nullable', 'string', 'min:4'],
            'expires_at' => ['nullable', 'date'],
            'burn' => ['nullable', 'boolean'],
            'max_scans' => ['nullable', 'integer', 'min:1'],
            'settings' => ['nullable', 'array'],
            'status' => ['sometimes', 'string', 'in:active,disabled'],
        ];

        if (!$this->has('content')) {
            return $baseRules;
        }

        $type = $this->route('qr_code')?->type ?? $this->input('type');

        $contentRules = $this->typeContentRules($type);

        return array_merge($baseRules, $contentRules);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (!$this->has('content')) {
                return;
            }

            $content = $this->input('content', []);
            if (!is_array($content)) {
                return;
            }

            $type = $this->route('qr_code')?->type ?? $this->input('type');

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

    private function typeContentRules(array|string|null $type): array
    {
        if ($type === null) {
            return [];
        }

        $contentRules = match ($type) {
            QrCodeType::Wifi->value => \App\Domain\QrTypes\WifiQr::rules(),
            QrCodeType::Crypto->value => \App\Domain\QrTypes\CryptoQr::rules(),
            QrCodeType::Event->value => \App\Domain\QrTypes\EventQr::rules(),
            QrCodeType::Vcard->value => \App\Domain\QrTypes\ContactQr::rules(),
            default => [],
        };

        foreach ($contentRules as &$rules) {
            if (!in_array('sometimes', $rules, true)) {
                array_unshift($rules, 'sometimes');
            }
        }

        return $contentRules;
    }
}
