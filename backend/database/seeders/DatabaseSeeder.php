<?php

namespace Database\Seeders;

use App\Models\Tariff;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(TariffSeeder::class);

        $tariff = Tariff::query()->where('title', 'MVP')->firstOrFail();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'subdomain_slug' => 'demo',
            'tariff_id' => $tariff->id,
            'subscription_ends_at' => now()->addDays(30),
        ]);
    }
}
