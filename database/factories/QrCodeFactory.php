<?php

namespace Database\Factories;

use App\Models\QrCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class QrCodeFactory extends Factory
{
    protected $model = QrCode::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => $this->faker->words(3, true),
            'type' => 'url',
            'content' => ['url' => $this->faker->url()],
            'status' => 'active',
        ];
    }
}