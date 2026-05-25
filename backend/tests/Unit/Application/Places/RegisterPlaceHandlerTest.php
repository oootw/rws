<?php

declare(strict_types=1);

use App\Application\Places\RegisterPlace\RegisterPlaceCommand;
use App\Application\Places\RegisterPlace\RegisterPlaceHandler;
use App\Domain\Places\PlatformType;

it('регистрирует точку и возвращает идентификатор', function (): void {
    $repo = fakePlacesRepository();

    $placeId = (new RegisterPlaceHandler($repo, fakePlaceIdGenerator('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa')))
        ->handle(new RegisterPlaceCommand(
            ownerId: '22222222-2222-2222-2222-222222222222',
            title: '  Бар  ',
            platforms: [],
            backgroundImageUrl: '  ',
        ));

    expect($placeId->value)->toBe('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa')
        ->and($repo->places)->toHaveCount(1)
        ->and($repo->places[0]->title()->value)->toBe('  Бар  ')
        ->and($repo->places[0]->backgroundImageUrl())->toBeNull()
        ->and($repo->places[0]->isActive())->toBeTrue();
});

it('сохраняет только платформы с непустым URL', function (): void {
    $repo = fakePlacesRepository();

    (new RegisterPlaceHandler($repo, fakePlaceIdGenerator('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa')))
        ->handle(new RegisterPlaceCommand(
            ownerId: '22222222-2222-2222-2222-222222222222',
            title: 'Кафе',
            platforms: [
                ['type' => '2gis', 'url' => ' https://2gis.ru/x ', 'label' => '2GIS'],
                ['type' => 'yandex', 'url' => '', 'label' => 'Яндекс'],
                ['type' => 'custom', 'url' => '   ', 'label' => 'Сайт'],
            ],
            backgroundImageUrl: 'https://cdn.test/bg.png',
        ));

    $platforms = $repo->places[0]->platforms();

    expect($platforms)->toHaveCount(1)
        ->and($platforms[0]->type)->toBe(PlatformType::TwoGis)
        ->and($platforms[0]->url)->toBe('https://2gis.ru/x')
        ->and($platforms[0]->label)->toBe('2GIS')
        ->and($repo->places[0]->backgroundImageUrl())->toBe('https://cdn.test/bg.png');
});
