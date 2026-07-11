<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\QrCode;
use App\Models\QrCodeRoute;
use App\Services\QrCodeResolver;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

#[Layout('layouts.guest')]
class AnonymousCreator extends Component
{
    public string $url = '';
    public string $title = '';
    public ?string $createdCode = null;
    public ?string $createdUrl = null;

    protected $rules = [
        'url' => 'required|url|max:2048',
        'title' => 'nullable|string|max:100',
    ];

    public function create(): void
    {
        $this->validate();

        DB::transaction(function () {
            // Create a guest user if not exists
            $guestEmail = 'guest_' . Str::random(12) . '@anonymous.qrm.sg';
            $guestUser = \App\Models\User::create([
                'name' => 'Guest',
                'email' => $guestEmail,
                'password' => bcrypt(Str::random(32)),
                'email_verified_at' => now(),
            ]);

            $qrCode = QrCode::create([
                'user_id' => $guestUser->id,
                'title' => $this->title ?: 'Untitled',
                'type' => 'url',
                'content' => ['url' => $this->url],
                'status' => 'active',
                'expires_at' => now()->addHours(24),
            ]);

            $host = parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost';
            $code = Str::upper(Str::random(6));

            QrCodeRoute::create([
                'qr_code_id' => $qrCode->id,
                'host' => $host,
                'code' => $code,
            ]);

            $this->createdCode = $code;
            $this->createdUrl = rtrim(config('app.url'), '/') . '/' . $code;
        });
    }

    public function render()
    {
        return view('livewire.anonymous-creator');
    }
}