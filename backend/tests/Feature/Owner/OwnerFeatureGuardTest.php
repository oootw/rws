<?php

declare(strict_types=1);

use App\Domain\Iam\Feature;
use App\Models\Tariff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function tariffWithFeatures(array $features): Tariff
{
    return Tariff::factory()->create([
        'features' => array_map(static fn (Feature $f) => $f->value, $features),
    ]);
}

it('GET /api/owner/features отдаёт фичи привязанного тарифа', function (): void {
    $tariff = tariffWithFeatures([Feature::MultiplePlaces, Feature::WeeklyDigest]);
    $owner = User::factory()->create([
        'subdomain_slug' => 'cafe',
        'telegram_id' => '1001',
        'tariff_id' => $tariff->id,
    ]);
    loginAsOwner($owner);

    $this->getJson('/api/owner/features', tenantHeaders($owner))
        ->assertOk()
        ->assertJsonPath('data.features', ['multiple_places', 'weekly_digest']);
});

it('GET /api/owner/features отдаёт [] для тарифа без фич', function (): void {
    $tariff = tariffWithFeatures([]);
    $owner = User::factory()->create([
        'subdomain_slug' => 'cafe',
        'telegram_id' => '1001',
        'tariff_id' => $tariff->id,
    ]);
    loginAsOwner($owner);

    $this->getJson('/api/owner/features', tenantHeaders($owner))
        ->assertOk()
        ->assertJsonPath('data.features', []);
});

it('требует auth для GET /api/owner/features', function (): void {
    $owner = User::factory()->create(['subdomain_slug' => 'cafe', 'telegram_id' => '1001']);

    $this->getJson('/api/owner/features', tenantHeaders($owner))
        ->assertUnauthorized();
});

it('POST /api/owner/places: 403 feature_not_available без multiple_places', function (): void {
    $tariff = tariffWithFeatures([]);
    $owner = User::factory()->create([
        'subdomain_slug' => 'cafe',
        'telegram_id' => '1001',
        'tariff_id' => $tariff->id,
    ]);
    loginAsOwner($owner);

    $this->postJson('/api/owner/places', [
        'title' => 'Точка',
        'platforms' => [['type' => '2gis', 'url' => 'https://2gis.test/x', 'label' => '2GIS']],
    ], tenantHeaders($owner))
        ->assertStatus(403)
        ->assertJsonPath('code', 'feature_not_available');
});

it('POST /api/owner/places: 201 с фичей multiple_places', function (): void {
    $tariff = tariffWithFeatures([Feature::MultiplePlaces]);
    $owner = User::factory()->create([
        'subdomain_slug' => 'cafe',
        'telegram_id' => '1001',
        'tariff_id' => $tariff->id,
    ]);
    loginAsOwner($owner);

    $this->postJson('/api/owner/places', [
        'title' => 'Точка',
        'platforms' => [['type' => '2gis', 'url' => 'https://2gis.test/x', 'label' => '2GIS']],
    ], tenantHeaders($owner))
        ->assertCreated();
});

it('subscription guard (402) приоритетнее feature guard (403)', function (): void {
    $tariff = tariffWithFeatures([]);
    $owner = User::factory()->create([
        'subdomain_slug' => 'cafe',
        'telegram_id' => '1001',
        'tariff_id' => $tariff->id,
        'subscription_ends_at' => now()->subDay(),
    ]);
    loginAsOwner($owner);

    $this->postJson('/api/owner/places', [
        'title' => 'Точка',
        'platforms' => [['type' => '2gis', 'url' => 'https://2gis.test/x', 'label' => '2GIS']],
    ], tenantHeaders($owner))
        ->assertStatus(402)
        ->assertJsonPath('code', 'subscription_expired');
});
