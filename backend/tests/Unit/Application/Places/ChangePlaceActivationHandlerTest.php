<?php

declare(strict_types=1);

use App\Application\Places\ChangePlaceActivation\ChangePlaceActivationCommand;
use App\Application\Places\ChangePlaceActivation\ChangePlaceActivationHandler;
use App\Application\Places\Exceptions\PlaceNotFound;

it('выключает активную точку', function (): void {
    $place = activePlace();
    $repo = fakePlacesRepository([$place]);

    (new ChangePlaceActivationHandler($repo))->handle(new ChangePlaceActivationCommand(
        placeId: $place->id->value,
        active: false,
    ));

    expect($repo->places[0]->isActive())->toBeFalse();
});

it('включает выключенную точку', function (): void {
    $place = inactivePlace();
    $repo = fakePlacesRepository([$place]);

    (new ChangePlaceActivationHandler($repo))->handle(new ChangePlaceActivationCommand(
        placeId: $place->id->value,
        active: true,
    ));

    expect($repo->places[0]->isActive())->toBeTrue();
});

it('бросает PlaceNotFound для несуществующей точки', function (): void {
    (new ChangePlaceActivationHandler(fakePlacesRepository()))->handle(
        new ChangePlaceActivationCommand(
            placeId: '00000000-0000-0000-0000-000000000000',
            active: false,
        ),
    );
})->throws(PlaceNotFound::class);
