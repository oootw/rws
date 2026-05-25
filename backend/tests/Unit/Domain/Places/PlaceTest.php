<?php

declare(strict_types=1);

use App\Domain\Iam\OwnerId;
use App\Domain\Places\Place;
use App\Domain\Places\PlaceId;
use App\Domain\Places\PlatformLink;
use App\Domain\Places\PlatformType;
use App\Domain\Places\Title;

function freshPlace(array $platforms = []): Place
{
    return Place::register(
        id: new PlaceId('11111111-1111-1111-1111-111111111111'),
        ownerId: new OwnerId('22222222-2222-2222-2222-222222222222'),
        title: new Title('Кафе Уют'),
        platforms: $platforms,
        backgroundImageUrl: null,
    );
}

it('по умолчанию активна и без площадок', function (): void {
    $place = freshPlace();

    expect($place->isActive())->toBeTrue()
        ->and($place->hasConfiguredPlatforms())->toBeFalse();
});

it('находит площадку по типу и возвращает null, если её нет', function (): void {
    $place = freshPlace([
        new PlatformLink(PlatformType::TwoGis, 'https://2gis.ru/x', '2GIS'),
    ]);

    expect($place->platform(PlatformType::TwoGis)?->url)->toBe('https://2gis.ru/x')
        ->and($place->platform(PlatformType::Yandex))->toBeNull();
});

it('сравнивает владельца по идентификатору', function (): void {
    $place = freshPlace();

    expect($place->isOwnedBy(new OwnerId('22222222-2222-2222-2222-222222222222')))->toBeTrue()
        ->and($place->isOwnedBy(new OwnerId('99999999-9999-9999-9999-999999999999')))->toBeFalse();
});
