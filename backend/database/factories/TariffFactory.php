<?php

namespace Database\Factories;

use App\Models\Tariff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tariff>
 */
class TariffFactory extends Factory
{
    protected $model = Tariff::class;

    public function definition(): array
    {
        return [
            'title' => 'MVP',
            'price' => 99000,
            'duration_days' => 30,
            'places_limit' => 1,
            'features' => ['extra_place_price' => 29000],
            'is_active' => true,
        ];
    }
}
