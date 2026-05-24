<?php

namespace Database\Factories;

use App\Models\Place;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Place>
 */
class PlaceFactory extends Factory
{
    protected $model = Place::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->company(),
            'platforms' => [
                [
                    'type' => '2gis',
                    'url' => 'https://2gis.ru/firm/example',
                    'label' => '2GIS',
                ],
            ],
            'background_image_url' => null,
            'is_active' => true,
        ];
    }

    public function withoutPlatforms(): static
    {
        return $this->state(fn (): array => [
            'platforms' => [],
        ]);
    }
}
