<?php

namespace Database\Seeders;

use App\Models\Tariff;
use Illuminate\Database\Seeder;

class TariffSeeder extends Seeder
{
    public function run(): void
    {
        Tariff::query()->updateOrCreate(
            ['title' => 'MVP'],
            [
                'price' => config('guardreviews.subscription.base_price'),
                'duration_days' => config('guardreviews.subscription.duration_days'),
                'places_limit' => 1,
                'features' => [
                    'extra_place_price' => config('guardreviews.subscription.extra_place_price'),
                ],
                'is_active' => true,
                'is_default' => true,
            ],
        );
    }
}
