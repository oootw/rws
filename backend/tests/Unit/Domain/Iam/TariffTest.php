<?php

declare(strict_types=1);

use App\Domain\Iam\Feature;
use App\Domain\Iam\Tariff;
use App\Domain\Iam\TariffId;

function tariffWith(array $features = []): Tariff
{
    return new Tariff(
        id: new TariffId('cccccccc-cccc-cccc-cccc-cccccccccccc'),
        title: 'MVP',
        features: $features,
    );
}

it('defaults to an empty feature list', function (): void {
    expect(tariffWith()->features)->toBe([]);
});

it('reports hasFeature only for explicitly granted features', function (): void {
    $tariff = tariffWith([Feature::MultiplePlaces, Feature::WeeklyDigest]);

    expect($tariff->hasFeature(Feature::MultiplePlaces))->toBeTrue();
    expect($tariff->hasFeature(Feature::WeeklyDigest))->toBeTrue();
    expect($tariff->hasFeature(Feature::ApiAccess))->toBeFalse();
});

it('returns false on hasFeature for empty tariff', function (): void {
    expect(tariffWith()->hasFeature(Feature::MultiplePlaces))->toBeFalse();
});
