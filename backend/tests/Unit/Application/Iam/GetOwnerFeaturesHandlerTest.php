<?php

declare(strict_types=1);

use App\Application\Iam\GetOwnerFeatures\GetOwnerFeaturesHandler;
use App\Application\Iam\GetOwnerFeatures\GetOwnerFeaturesQuery;
use App\Domain\Iam\Feature;
use App\Domain\Iam\Tariff;
use App\Domain\Iam\TariffId;

it('возвращает фичи привязанного к owner-у тарифа', function (): void {
    $owner = restoredOwner();
    $tariff = new Tariff(
        id: new TariffId('cccccccc-cccc-cccc-cccc-cccccccccccc'),
        title: 'MVP',
        features: [Feature::MultiplePlaces, Feature::WeeklyDigest],
    );

    $features = (new GetOwnerFeaturesHandler(
        fakeOwnerRepository([$owner]),
        fakeTariffRepository(tariffs: [$tariff]),
    ))->handle(new GetOwnerFeaturesQuery(ownerId: $owner->id->value));

    expect($features)->toBe([Feature::MultiplePlaces, Feature::WeeklyDigest]);
});

it('падает в default-тариф, если привязанный не найден', function (): void {
    $owner = restoredOwner();
    $default = new Tariff(
        id: new TariffId('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa'),
        title: 'Free',
        features: [Feature::WeeklyDigest],
    );

    $features = (new GetOwnerFeaturesHandler(
        fakeOwnerRepository([$owner]),
        fakeTariffRepository(default: $default),
    ))->handle(new GetOwnerFeaturesQuery(ownerId: $owner->id->value));

    expect($features)->toBe([Feature::WeeklyDigest]);
});

it('возвращает [] если ни привязанного ни default-тарифа нет', function (): void {
    $owner = restoredOwner();

    $features = (new GetOwnerFeaturesHandler(
        fakeOwnerRepository([$owner]),
        fakeTariffRepository(),
    ))->handle(new GetOwnerFeaturesQuery(ownerId: $owner->id->value));

    expect($features)->toBe([]);
});

it('возвращает [] для неизвестного owner-а', function (): void {
    $features = (new GetOwnerFeaturesHandler(
        fakeOwnerRepository(),
        fakeTariffRepository(),
    ))->handle(new GetOwnerFeaturesQuery(ownerId: '00000000-0000-0000-0000-000000000000'));

    expect($features)->toBe([]);
});
