<?php

declare(strict_types=1);

use App\Application\Places\GetPlaceForOwner\GetPlaceForOwnerHandler;
use App\Application\Places\GetPlaceForOwner\GetPlaceForOwnerQuery;
use App\Domain\Iam\OwnerId;
use App\Domain\Places\Place;
use App\Domain\Places\PlaceId;
use App\Domain\Places\Title;

it('возвращает точку владельца', function (): void {
    $place = activePlace();
    $handler = new GetPlaceForOwnerHandler(fakePlacesRepository([$place]));

    $result = $handler->handle(new GetPlaceForOwnerQuery(
        placeId: '11111111-1111-1111-1111-111111111111',
        ownerId: '22222222-2222-2222-2222-222222222222',
    ));

    expect($result)->toBe($place);
});

it('возвращает null, если точки нет', function (): void {
    $result = (new GetPlaceForOwnerHandler(fakePlacesRepository()))->handle(
        new GetPlaceForOwnerQuery(
            placeId: '11111111-1111-1111-1111-111111111111',
            ownerId: '22222222-2222-2222-2222-222222222222',
        ),
    );

    expect($result)->toBeNull();
});

it('возвращает null для чужой точки', function (): void {
    $otherOwnersPlace = Place::register(
        id: new PlaceId('33333333-3333-3333-3333-333333333333'),
        ownerId: new OwnerId('99999999-9999-9999-9999-999999999999'),
        title: new Title('Чужое'),
        platforms: [],
        backgroundImageUrl: null,
    );

    $result = (new GetPlaceForOwnerHandler(fakePlacesRepository([$otherOwnersPlace])))->handle(
        new GetPlaceForOwnerQuery(
            placeId: '33333333-3333-3333-3333-333333333333',
            ownerId: '22222222-2222-2222-2222-222222222222',
        ),
    );

    expect($result)->toBeNull();
});
