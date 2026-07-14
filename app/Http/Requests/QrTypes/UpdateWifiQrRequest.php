<?php

namespace App\Http\Requests\QrTypes;

use App\Domain\QrTypes\WifiQr;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWifiQrRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $contentRules = collect(WifiQr::rules())
            ->map(fn(array $rules) => array_merge(['sometimes'], $rules))
            ->toArray();

        return $this->commonRules() + $contentRules;
    }

    public function validatedContent(): ?WifiQr
    {
        if (!$this->has('content')) {
            return null;
        }

        return WifiQr::fromArray($this->input('content', []));
    }

    private function commonRules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'alias' => ['nullable', 'string', 'min:3', 'max:32', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'password' => ['nullable', 'string', 'min:4'],
            'expires_at' => ['nullable', 'date'],
            'burn' => ['nullable', 'boolean'],
            'max_scans' => ['nullable', 'integer', 'min:1'],
            'settings' => ['nullable', 'array'],
            'status' => ['sometimes', 'string', 'in:active,disabled'],
        ];
    }
}
