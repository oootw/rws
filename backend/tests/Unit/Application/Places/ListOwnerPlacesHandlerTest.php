<?php

declare(strict_types=1);

use App\Application\Places\ListOwnerPlaces\ListOwnerPlacesHandler;
use App\Application\Places\ListOwnerPlaces\ListOwnerPlacesQuery;
use App\Domain\Places\Place;
use App\Domain\Places\PlaceId;
use App\Domain\Places\PlatformLink;
use App\Domain\Places\PlatformType;
use App\Domain\Places\Title;
use App\Domain\Iam\OwnerId;

it('возвращает сводку точек владельца', function (): void {
    $ownerId = '22222222-2222-2222-2222-222222222222';
    $active = activePlace($ownerId);
    $inactive = Place::restore(
        id: new PlaceId('33333333-3333-3333-3333-333333333333'),
        ownerId: new OwnerId($ownerId),
        title: new Title('Бар'),
        platforms: [
            new PlatformLink(PlatformType::Yandex, 'https://yandex.ru/x', 'Яндекс'),
        ],
        backgroundImageUrl: null,
        isActive: false,
    );
    $foreign = Place::register(
        id: new PlaceId('44444444-4444-4444-4444-444444444444'),
        ownerId: new OwnerId('99999999-9999-9999-9999-999999999999'),
        title: new Title('Чужое'),
        platforms: [],
        backgroundImageUrl: null,
    );

    $summaries = (new ListOwnerPlacesHandler(fakePlacesRepository([$active, $inactive, $foreign])))
        ->handle(new ListOwnerPlacesQuery(ownerId: $ownerId));

    expect($summaries)->toHaveCount(2)
        ->and($summaries[0]->id)->toBe('11111111-1111-1111-1111-111111111111')
        ->and($summaries[0]->title)->toBe('Кафе Уют')
        ->and($summaries[0]->platformCount)->toBe(0)
        ->and($summaries[0]->isActive)->toBeTrue()
        ->and($summaries[1]->id)->toBe('33333333-3333-3333-3333-333333333333')
        ->and($summaries[1]->platformCount)->toBe(1)
        ->and($summaries[1]->isActive)->toBeFalse();
});

it('возвращает пустой список, если у владельца нет точек', function (): void {
    $summaries = (new ListOwnerPlacesHandler(fakePlacesRepository([activePlace()])))->handle(
        new ListOwnerPlacesQuery(ownerId: '00000000-0000-0000-0000-000000000000'),
    );

    expect($summaries)->toBe([]);
});
