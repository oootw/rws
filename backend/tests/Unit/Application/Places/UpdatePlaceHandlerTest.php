<?php

declare(strict_types=1);

use App\Application\Places\Exceptions\PlaceNotFound;
use App\Application\Places\Support\PlatformsBuilder;
use App\Application\Places\UpdatePlace\UpdatePlaceCommand;
use App\Application\Places\UpdatePlace\UpdatePlaceHandler;
use App\Domain\Places\PlatformType;

it('обновляет профиль точки и сохраняет', function (): void {
    $place = activePlace();
    $repo = fakePlacesRepository([$place]);

    (new UpdatePlaceHandler($repo, new PlatformsBuilder))->handle(new UpdatePlaceCommand(
        placeId: $place->id->value,
        title: 'Новое имя',
        platforms: [
            ['type' => 'yandex', 'url' => ' https://yandex.ru/x ', 'label' => 'Яндекс'],
            ['type' => 'custom', 'url' => '   ', 'label' => 'Сайт'],
        ],
        backgroundImageUrl: '  https://cdn.example/bg.png  ',
    ));

    $updated = $repo->places[0];

    expect($updated->title()->value)->toBe('Новое имя')
        ->and($updated->platforms())->toHaveCount(1)
        ->and($updated->platforms()[0]->type)->toBe(PlatformType::Yandex)
        ->and($updated->platforms()[0]->url)->toBe('https://yandex.ru/x')
        ->and($updated->backgroundImageUrl())->toBe('https://cdn.example/bg.png');
});

it('обнуляет фон, если пришла пустая строка', function (): void {
    $place = activePlace();
    $repo = fakePlacesRepository([$place]);

    (new UpdatePlaceHandler($repo, new PlatformsBuilder))->handle(new UpdatePlaceCommand(
        placeId: $place->id->value,
        title: 'Бар',
        platforms: [],
        backgroundImageUrl: '   ',
    ));

    expect($repo->places[0]->backgroundImageUrl())->toBeNull();
});

it('бросает PlaceNotFound для несуществующей точки', function (): void {
    (new UpdatePlaceHandler(fakePlacesRepository(), new PlatformsBuilder))->handle(
        new UpdatePlaceCommand(
            placeId: '00000000-0000-0000-0000-000000000000',
            title: 'X',
            platforms: [],
            backgroundImageUrl: null,
        ),
    );
})->throws(PlaceNotFound::class);
