<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ApiTokenManager extends Component
{
    public string $name = '';

    /** @var array<int, string> */
    public array $abilities = [];

    public ?string $plainTextToken = null;

    /**
     * @return array<string, string>
     */
    public function availableAbilities(): array
    {
        return [
            'qr:read' => __('Read QR codes'),
            'qr:write' => __('Write QR codes'),
            'stats:read' => __('Read analytics'),
            'billing:read' => __('Read billing data'),
        ];
    }

    public function canManageTokens(): bool
    {
        $user = auth()->user();

        return $user !== null
            && ($user->plan === 'business' || $user->subscribed('business'));
    }

    public function createToken(): void
    {
        if (! $this->canManageTokens()) {
            throw ValidationException::withMessages([
                'name' => __('API token creation is available for Business users only.'),
            ]);
        }

        $validated = $this->validate($this->rules());
        $user = auth()->user();

        if ($user === null) {
            abort(403);
        }

        $token = $user->createToken($validated['name'], $validated['abilities'] ?? []);

        $this->plainTextToken = $token->plainTextToken;
        $this->reset(['name', 'abilities']);
    }

    public function deleteToken(int $tokenId): void
    {
        $user = auth()->user();

        if ($user === null) {
            abort(403);
        }

        $token = $user->tokens()->findOrFail($tokenId);

        $token->delete();
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'abilities' => ['array'],
            'abilities.*' => ['string', Rule::in(array_keys($this->availableAbilities()))],
        ];
    }

    public function render()
    {
        $user = auth()->user();
        $tokens = $user?->tokens()->latest()->get() ?? collect();

        return view('livewire.api-token-manager', [
            'tokens' => $tokens,
            'availableAbilities' => $this->availableAbilities(),
        ]);
    }
}
