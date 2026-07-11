<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\QrTypes\ContactQr;
use App\Domain\QrTypes\CryptoQr;
use App\Domain\QrTypes\EventQr;
use App\Domain\QrTypes\MessageQr;
use App\Domain\QrTypes\RedirectQr;
use App\Domain\QrTypes\SocialQr;
use App\Domain\QrTypes\UrlQr;
use App\Domain\QrTypes\WifiQr;
use App\Enums\QrCodeType;
use Illuminate\Foundation\Http\FormRequest;

class UpdateQrCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'content' => ['sometimes', 'array'],
            'alias' => ['nullable', 'string', 'min:4', 'max:32', 'regex:/^[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?$/'],
            'password' => ['nullable', 'string', 'min:4'],
            'expires_at' => ['nullable', 'date'],
            'burn' => ['nullable', 'boolean'],
            'max_scans' => ['nullable', 'integer', 'min:1'],
            'settings' => ['nullable', 'array'],
            'status' => ['sometimes', 'string', 'in:active,disabled'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->has('content')) {
                return;
            }

            $qrCode = \App\Models\QrCode::find($this->route('qr_code'));
            $type = $qrCode?->type;

            if ($type === null) {
                return;
            }

            $qrCodeType = QrCodeType::tryFrom($type);
            if ($qrCodeType === null) {
                return;
            }

            $content = $this->input('content');
            $rules = $this->typeSpecificRules($qrCodeType);
            $nestedValidator = validator(['content' => $content], $rules);

            if ($nestedValidator->fails()) {
                foreach ($nestedValidator->errors()->messages() as $key => $messages) {
                    foreach ($messages as $message) {
                        $validator->errors()->add($key, $message);
                    }
                }
            }
        });
    }

    private function typeSpecificRules(QrCodeType $type): array
    {
        return match ($type) {
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
    }

    public function messages(): array
    {
        return [
            'alias.regex' => 'Alias must start and end with an alphanumeric character and may contain hyphens.',
        ];
    }
}
