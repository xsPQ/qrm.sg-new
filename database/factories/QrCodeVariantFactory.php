<?php

namespace Database\Factories;

use App\Models\QrCode;
use App\Models\QrCodeVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

class QrCodeVariantFactory extends Factory
{
    protected $model = QrCodeVariant::class;

    public function definition(): array
    {
        return [
            'qr_code_id' => QrCode::factory(),
            'label' => $this->faker->randomLetter(),
            'url' => $this->faker->url(),
            'weight' => 1,
            'scan_count' => 0,
            'device_target' => null,
            'sort_order' => 0,
        ];
    }
}