<?php

namespace App\Http\Requests\QrTypes;

use App\Domain\QrTypes\ContactQr;
use Illuminate\Foundation\Http\FormRequest;

class StoreContactQrRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->commonRules() + ContactQr::rules();
    }

    public function validatedContent(): ContactQr
    {
        return ContactQr::fromArray($this->input('content', []));
    }

    private function commonRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'alias' => ['nullable', 'string', 'min:3', 'max:32', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'password' => ['nullable', 'string', 'min:4'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'burn' => ['nullable', 'boolean'],
            'max_scans' => ['nullable', 'integer', 'min:1'],
            'settings' => ['nullable', 'array'],
        ];
    }
}
