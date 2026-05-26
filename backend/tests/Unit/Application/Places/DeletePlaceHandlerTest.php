<?php

declare(strict_types=1);

use App\Application\Places\DeletePlace\DeletePlaceCommand;
use App\Application\Places\DeletePlace\DeletePlaceHandler;
use App\Application\Places\Exceptions\PlaceNotFound;

it('удаляет существующую точку', function (): void {
    $place = activePlace();
    $repo = fakePlacesRepository([$place]);

    (new DeletePlaceHandler($repo))->handle(
        new DeletePlaceCommand(placeId: $place->id->value),
    );

    expect($repo->places)->toBeEmpty();
});

it('бросает PlaceNotFound для неизвестной точки', function (): void {
    (new DeletePlaceHandler(fakePlacesRepository()))->handle(
        new DeletePlaceCommand(placeId: '00000000-0000-0000-0000-000000000000'),
    );
})->throws(PlaceNotFound::class);
