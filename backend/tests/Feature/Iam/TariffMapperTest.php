<?php

declare(strict_types=1);

use App\Domain\Iam\Feature;
use App\Infrastructure\Persistence\Eloquent\Iam\TariffMapper;
use App\Models\Tariff as TariffModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function buildTariffModel(mixed $featuresJson): TariffModel
{
    return TariffModel::factory()->create([
        'features' => $featuresJson,
    ]);
}

it('maps a feature list to typed Feature[]', function (): void {
    $model = buildTariffModel([
        Feature::MultiplePlaces->value,
        Feature::WeeklyDigest->value,
    ]);

    $tariff = (new TariffMapper)->toDomain($model);

    expect($tariff->features)->toBe([
        Feature::MultiplePlaces,
        Feature::WeeklyDigest,
    ]);
    expect($tariff->hasFeature(Feature::MultiplePlaces))->toBeTrue();
    expect($tariff->hasFeature(Feature::ApiAccess))->toBeFalse();
});

it('silently drops unknown feature keys (forward-compat)', function (): void {
    $model = buildTariffModel([
        Feature::MultiplePlaces->value,
        'this_feature_does_not_exist_yet',
    ]);

    $tariff = (new TariffMapper)->toDomain($model);

    expect($tariff->features)->toBe([Feature::MultiplePlaces]);
});

it('ignores legacy assoc-array shape (extra_place_price => int)', function (): void {
    // До B1 seeder писал features = ['extra_place_price' => 29000].
    // Mapper должен это спокойно проглотить.
    $model = buildTariffModel(['extra_place_price' => 29000]);

    $tariff = (new TariffMapper)->toDomain($model);

    expect($tariff->features)->toBe([]);
});

it('returns [] for null features', function (): void {
    $model = buildTariffModel(null);

    $tariff = (new TariffMapper)->toDomain($model);

    expect($tariff->features)->toBe([]);
});

it('returns [] for empty features array', function (): void {
    $model = buildTariffModel([]);

    $tariff = (new TariffMapper)->toDomain($model);

    expect($tariff->features)->toBe([]);
});
