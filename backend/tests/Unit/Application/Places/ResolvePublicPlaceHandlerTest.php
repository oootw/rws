<?php

declare(strict_types=1);

use App\Application\Places\Exceptions\PlaceUnavailable;
use App\Application\Places\ResolvePublicPlace\ResolvePublicPlaceHandler;
use App\Application\Places\ResolvePublicPlace\ResolvePublicPlaceQuery;

it('возвращает активную точку нужного владельца', function (): void {
    $handler = new ResolvePublicPlaceHandler(fakePlacesRepository([activePlace()]));

    $place = $handler->handle(new ResolvePublicPlaceQuery(
        placeId: '11111111-1111-1111-1111-111111111111',
        ownerId: '22222222-2222-2222-2222-222222222222',
    ));

    expect($place->id->value)->toBe('11111111-1111-1111-1111-111111111111');
});

it('бросает «точка недоступна», если точки нет', function (): void {
    (new ResolvePublicPlaceHandler(fakePlacesRepository()))->handle(
        new ResolvePublicPlaceQuery(placeId: 'x', ownerId: 'y'),
    );
})->throws(PlaceUnavailable::class);

it('бросает «точка недоступна» для неактивной точки', function (): void {
    (new ResolvePublicPlaceHandler(fakePlacesRepository([inactivePlace()])))->handle(
        new ResolvePublicPlaceQuery(
            placeId: '11111111-1111-1111-1111-111111111111',
            ownerId: '22222222-2222-2222-2222-222222222222',
        ),
    );
})->throws(PlaceUnavailable::class);

it('бросает «точка недоступна» для чужой точки', function (): void {
    (new ResolvePublicPlaceHandler(fakePlacesRepository([activePlace()])))->handle(
        new ResolvePublicPlaceQuery(
            placeId: '11111111-1111-1111-1111-111111111111',
            ownerId: '99999999-9999-9999-9999-999999999999',
        ),
    );
})->throws(PlaceUnavailable::class);
