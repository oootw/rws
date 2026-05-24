<?php

namespace Database\Factories;

use App\Enums\ReviewStatus;
use App\Models\Place;
use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        return [
            'place_id' => Place::factory(),
            'stars' => fake()->numberBetween(1, 3),
            'contact' => fake()->phoneNumber(),
            'text' => fake()->sentence(),
            'status' => ReviewStatus::New,
        ];
    }
}
