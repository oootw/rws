<?php

namespace Database\Factories;

use App\Models\Tariff;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'subdomain_slug' => Str::lower(fake()->unique()->lexify('??????')),
            'email_verified_at' => now(),
            'password' => static::$password ??= null,
            'remember_token' => Str::random(10),
            'subscription_ends_at' => now()->addDays(30),
        ];
    }

    public function withTariff(?Tariff $tariff = null): static
    {
        return $this->state(fn (): array => [
            'tariff_id' => $tariff?->id ?? Tariff::factory(),
        ]);
    }

    public function withoutSubscription(): static
    {
        return $this->state(fn (): array => [
            'subscription_ends_at' => now()->subDay(),
        ]);
    }
}
